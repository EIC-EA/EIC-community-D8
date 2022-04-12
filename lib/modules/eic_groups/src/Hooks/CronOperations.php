<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_messages\Service\MessageBus;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\pathauto\PathautoGeneratorInterface;
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
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    PathautoGeneratorInterface $pathauto_generator,
    QueueFactory $queue_factory,
    QueueWorkerManagerInterface $queue_worker_manager,
    StateInterface $state,
    MessageBus $bus,
    Connection $database
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pathautoGenerator = $pathauto_generator;
    $this->queueFactory = $queue_factory;
    $this->queueManager = $queue_worker_manager;
    $this->state = $state;
    $this->messageBus = $bus;
    $this->database = $database;
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
      $container->get('database')
    );
  }

  /**
   * Implements hook_cron().
   */
  public function cron() {
    $this->processGroupUrlAliasUpdateQueue();
    $this->processGroupContentUrlAliasUpdateQueue();
    $this->processGroupWaitingApprovalReminder();
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

    // Re-sync each 2 hours.
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
        'field_event_executing_user' => ['target_id' => $group->getOwnerId()]
      ];

      foreach ($uids as $uid) {
        $this->messageBus->dispatch($template + ['uid' => $uid]);
      }
    }

    $this->state->set('last_cron_pending_group_approval_time', $now);
  }

}
