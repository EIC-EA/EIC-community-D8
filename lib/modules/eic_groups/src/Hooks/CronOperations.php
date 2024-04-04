<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_groups\Plugin\GroupContentEnabler\GroupInvitation as GroupContentEnablerGroupInvitation;
use Drupal\eic_messages\Service\MessageBus;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\message\Entity\Message;
use Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CronOperations.
 *
 * Implementations for hook_cron().
 */
class CronOperations implements ContainerInjectionInterface {

  /**
   * Group alias update queue name.
   */
  const GROUP_URL_ALIAS_UPDATE_QUEUE = 'eic_groups_cron_group_url_alias_update';

  /**
   * Group alias update state cache base machine name.
   */
  const GROUP_URL_ALIAS_UPDATE_STATE_CACHE = 'eic_groups_cron_group_url_alias_update:gid:';

  /**
   * Group content alias update queue name.
   */
  const GROUP_CONTENT_URL_ALIAS_UPDATE_QUEUE = 'eic_groups_group_content_url_alias_update';

  /**
   * Reindex content search api queue name.
   */
  const REINDEX_CONTENT_SEARCH_API_QUEUE = 'eic_groups_reindex_content';

  /**
   * Reindex content search api queue name.
   */
  const LAST_TIME_REINDEX_STATE_ID = 'eic_groups_last_time_reindex';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The pathauto generator.
   *
   * @var \Drupal\pathauto\PathautoGeneratorInterface
   */
  private $pathautoGenerator;

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  private $queueFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * The queue worker manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  private $queueManager;

  /**
   * The message bus service.
   *
   * @var MessageBus
   */
  private $messageBus;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * The EIC Groups settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $eicGroupSettings;

  /**
   * The EIC groups helper.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  private $groupsHelper;

  /**
   * Constructs a CronOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator
   *   The pathauto generator.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_worker_manager
   *   The queue worker manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param MessageBus $bus
   *   The message bus service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\eic_groups\EICGroupsHelper $groups_helper
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    PathautoGeneratorInterface $pathauto_generator,
    QueueFactory $queue_factory,
    QueueWorkerManagerInterface $queue_worker_manager,
    StateInterface $state,
    MessageBus $bus,
    Connection $database,
    ConfigFactoryInterface $config_factory,
    EICGroupsHelper $groups_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pathautoGenerator = $pathauto_generator;
    $this->queueFactory = $queue_factory;
    $this->queueManager = $queue_worker_manager;
    $this->state = $state;
    $this->messageBus = $bus;
    $this->database = $database;
    $this->eicGroupSettings = $config_factory->get('eic_groups.settings');
    $this->groupsHelper = $groups_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('pathauto.generator'),
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker'),
      $container->get('state'),
      $container->get('eic_messages.message_bus'),
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('eic_groups.helper')
    );
  }

  /**
   * Implements hook_cron().
   */
  public function cron() {
    $this->processGroupUrlAliasUpdateQueue();
    $this->processGroupContentUrlAliasUpdateQueue();
    $this->processGroupWaitingApprovalReminder();
    $this->processGroupInvitationsReminder();
    $this->processContentSolrReindex();
  }

  /**
   * Process Group alias update queue.
   *
   * @todo In the future this method should be called by ultimate cron module.
   */
  private function processGroupUrlAliasUpdateQueue() {
    $group_alias_queue = $this->queueFactory->get(self::GROUP_URL_ALIAS_UPDATE_QUEUE);

    while ($item = $group_alias_queue->claimItem()) {
      try {
        if (!empty($item->data['gid'])) {
          /** @var \Drupal\group\Entity\GroupInterface $group */
          $group = $this->entityTypeManager->getStorage('group')->load($item->data['gid']);
          if (!$group instanceof GroupInterface) {
            $group_alias_queue->deleteItem($item);
            $this->state->delete(self::GROUP_URL_ALIAS_UPDATE_STATE_CACHE . $item->data['gid']);
            continue;
          }

          $installedContentPluginIds = $group->getGroupType()->getInstalledContentPlugins()->getInstanceIds();
          foreach ($installedContentPluginIds as $key => $pluginId) {
            $installedContentPluginIds[$key] = 'group-' . str_replace(':', '-', $pluginId);
          }

          $query = $this->entityTypeManager->getStorage('group_content')->getQuery();
          $query->condition('type', $installedContentPluginIds, 'IN');
          $query->condition('gid', $group->id());
          $results = $query->execute();

          if (!empty($results)) {
            $group_content_url_alias_update_queue = $this->queueFactory->get(
              self::GROUP_CONTENT_URL_ALIAS_UPDATE_QUEUE
            );

            foreach ($results as $group_content_id) {
              $group_content_url_alias_update_queue->createItem($group_content_id);
            }
          }
        }

        $group_alias_queue->deleteItem($item);
        $this->state->delete(self::GROUP_URL_ALIAS_UPDATE_STATE_CACHE . $group->id());
      } catch (SuspendQueueException $e) {
        $group_alias_queue->releaseItem($item);
        break;
      }
    }
  }

  /**
   * Process Group content url alias update queue.
   *
   * @todo This method should be removed after installing and configure
   * ultimate cron module.
   */
  private function processGroupContentUrlAliasUpdateQueue() {
    $queue = $this->queueFactory->get(self::GROUP_CONTENT_URL_ALIAS_UPDATE_QUEUE);
    $queue_worker = $this->queueManager->createInstance(self::GROUP_CONTENT_URL_ALIAS_UPDATE_QUEUE);

    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      } catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      }
    }
  }

  /**
   * Notify all SA, SCM groups that are waiting for approval.
   */
  private function processGroupWaitingApprovalReminder() {
    // Value returned is timestamp.
    $last_reminder_time = $this->state->get('last_cron_pending_group_approval_time', 0);
    $now = time();

    if (0 < ($last_reminder_time + Settings::get('cron_interval_pending_approval_time', 86400)) - $now) {
      return;
    }

    $query = $this->database->select('groups', 'g');
    $query->condition('g.type', 'group');
    $query->join('content_moderation_state_field_data', 'cm', 'cm.content_entity_id = g.id');
    $query->fields('g', ['id']);
    $query->condition('cm.moderation_state', GroupsModerationHelper::GROUP_PENDING_STATE);
    $query->orderBy('g.id');
    $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $results = array_map(function (array $item) {
      return $item['id'];
    }, $results);

    $groups = Group::loadMultiple($results);
    $query = \Drupal::entityQuery('user')
      ->condition('status', 1);

    $or_condition = $query->orConditionGroup()
      ->condition('roles', 'content_administrator')
      ->condition('roles', 'site_admin');

    $uids = $query->condition($or_condition)->execute();

    foreach ($groups as $group) {
      $template = [
        'template' => 'notify_group_wait_approval',
        'field_group_ref' => ['target_id' => $group->id()],
        'field_event_executing_user' => ['target_id' => $group->getOwnerId()],
      ];

      $is_group_sensitive = $this->groupsHelper->isGroupSensitive($group);

      // Remove all uids that has no access to sensitive group.
      $group_uids = array_filter($uids, function(int $uid) use ($is_group_sensitive) {
        $user = User::load($uid);

        return
          $user instanceof UserInterface &&
          (!$is_group_sensitive || $user->hasRole('sensitive'));
      });

      foreach ($group_uids as $uid) {
        $this->messageBus->dispatch($template + ['uid' => $uid]);
      }
    }

    $this->state->set('last_cron_pending_group_approval_time', $now);
  }

  /**
   * This notification is sent to SU to remind him of his pending invitation to a group.
   */
  private function processGroupInvitationsReminder() {
    // Value returned is timestamp.
    $last_reminder_time = $this->state->get('last_cron_group_invite_time', 0);
    $now = time();

    $cron_interval_group_invite = $this->eicGroupSettings->get('eic_groups_cron_settings.cron_interval_group_invite_time') ?
      $this->eicGroupSettings->get('eic_groups_cron_settings.cron_interval_group_invite_time') :
      86400;

    if (0 < ($last_reminder_time + $cron_interval_group_invite) - $now) {
      return;
    }

    // Date from which we want to skip invitation reminders to avoind sending
    // reminders for old invitations.
    $skip_invitation_reminder_default_days = $this->eicGroupSettings->get('eic_groups_cron_settings.cron_interval_group_skip_invite_reminder_days') ?
      $this->eicGroupSettings->get('eic_groups_cron_settings.cron_interval_group_skip_invite_reminder_days') :
      30;
    $skip_invitation_reminder_time = new DrupalDateTime('today -' . $skip_invitation_reminder_default_days . ' days');

    // Frequency date to send reminders. By default every 3 days a reminder
    // will be sent for the same invitation.
    $reminder_frequency_default_days = $this->eicGroupSettings->get('eic_groups_cron_settings.cron_interval_group_invite_reminder_frequency_days') ?
      $this->eicGroupSettings->get('eic_groups_cron_settings.cron_interval_group_invite_reminder_frequency_days') :
      3;
    $reminder_frequency_time = new DrupalDateTime('today -' . $reminder_frequency_default_days . ' days');

    $query = $this->database->select('group_content_field_data', 'gc_fd');
    $query->condition('gc_fd.type', '%-group_invitation', 'LIKE');
    $query->join('group_content__invitation_status', 'gc_is', 'gc_is.entity_id = gc_fd.id');
    $query->fields('gc_fd', ['id', 'gid', 'entity_id']);

    $query->leftJoin('group_content__field_invitation_reminder_count', 'gc_irc', 'gc_irc.entity_id = gc_fd.id');
    // Reminders are sent 3 times and therefore, we skip invitations that
    // reached that limit.
    $orInvitationReminderCounter = $query->orConditionGroup()
      ->isNull('gc_irc.field_invitation_reminder_count_value')
      ->condition('gc_irc.field_invitation_reminder_count_value', GroupContentEnablerGroupInvitation::INVITATION_REMINDER_MAX_COUNT, '<');
    $query->condition($orInvitationReminderCounter);

    $query->leftJoin('group_content__field_invitation_reminder_date', 'gc_ird', 'gc_ird.entity_id = gc_fd.id');
    // Reminders are sent every 3 days and therefore we need to grab
    // invitations where the last reminder date was registered 3 days ago.
    $orInvitationReminderDate = $query->orConditionGroup()
      ->isNull('gc_ird.field_invitation_reminder_date_value')
      ->condition('gc_ird.field_invitation_reminder_date_value', $reminder_frequency_time->getTimestamp(), '<=');
    $query->condition($orInvitationReminderDate);

    $query->condition('gc_is.invitation_status_value', GroupInvitation::INVITATION_PENDING);
    $query->condition('gc_fd.created', $skip_invitation_reminder_time->getTimestamp(), '>=');
    $query->orderBy('gc_fd.id');
    $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($results as $result) {
      $uid = $result['entity_id'];

      /** @TODO handle the anonymous invite */
      if (0 === (int) $uid) {
        continue;
      }

      $gid = $result['gid'];
      $group_content_id = $result['id'];

      $group = Group::load($gid);
      $invitee = User::load($uid);
      $group_content = GroupContent::load($group_content_id);

      if (
        !$group instanceof GroupInterface ||
        !$invitee instanceof UserInterface ||
        !$group_content instanceof GroupContent ||
        $group->get('moderation_state')->value === DefaultContentModerationStates::ARCHIVED_STATE
      ) {
        continue;
      }

      $owner = $group_content->getOwner();
      $reminder_counter = $group_content->get('field_invitation_reminder_count')->isEmpty() ?
        1 :
        (int) $group_content->get('field_invitation_reminder_count')->value + 1;

      $message = Message::create([
        'template' => 'notify_group_invitation_reminder',
        'field_group_ref' => ['target_id' => $group->id()],
        'field_event_executing_user' => ['target_id' => $group->getOwnerId()],
        'field_invitee' => $invitee,
        'field_group_invitation' => $group_content,
        'field_inviter' => $owner,
      ]);

      $message->setOwnerId($uid);

      $this->messageBus->dispatch($message);

      $group_content->set('field_invitation_reminder_count', $reminder_counter);
      $group_content->set('field_invitation_reminder_date', $now);
      $group_content->save();
    }

    $this->state->set('last_cron_group_invite_time', $now);
  }

  /**
   * Re-index entities in queue to solr index.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function processContentSolrReindex() {
    $last_request_time = \Drupal::state()->get(self::LAST_TIME_REINDEX_STATE_ID);
    $now = time();
    $interval_time = Settings::get('cron_interval_late_reindex_entities', 3600);

    // Re-sync each day.
    if (0 >= ($last_request_time + $interval_time) - $now) {
      $queue = $this->queueFactory->get(self::REINDEX_CONTENT_SEARCH_API_QUEUE);
      $queue_worker = $this->queueManager->createInstance(self::REINDEX_CONTENT_SEARCH_API_QUEUE);

      while ($item = $queue->claimItem()) {
        try {
          $queue_worker->processItem($item->data);
          $queue->deleteItem($item);
        } catch (SuspendQueueException $e) {
          $queue->releaseItem($item);
          break;
        }
      }

      \Drupal::state()->set(self::LAST_TIME_REINDEX_STATE_ID, $now);
    }
  }

}
