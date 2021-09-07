<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_search\Search\Sources\ActivityStreamSourceType;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a LastGroupActivitiesBlock block.
 *
 * @Block(
 *   id = "eic_groups_last_group_activities",
 *   admin_label = @Translation("EIC last group activities"),
 *   category = @Translation("European Innovation Council"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE)
 *   }
 * )
 */
class LastGroupActivitiesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The EIC groups helper
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  private $groupsHelper;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  private $entityTypeManager;

  /**
   * @var ActivityStreamSourceType $activityStreamSourceType
   */
  private $activityStreamSourceType;

  /**
   * @var \Drupal\Core\Datetime\DateFormatter $dateFormatter
   */
  private $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_groups.helper'),
      $container->get('entity_type.manager'),
      $container->get('eic_search.activity_stream_library'),
      $container->get('date.formatter')
    );
  }

  /**
   * LastGroupMembersBlock constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param EICGroupsHelper $groups_helper
   *   The Form builder service.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The Form builder service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EICGroupsHelper $groups_helper,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityStreamSourceType $activityStreamSourceType,
    DateFormatter $dateFormatter
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupsHelper = $groups_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->activityStreamSourceType = $activityStreamSourceType;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->groupsHelper->getGroupFromRoute();

    if (!$group instanceof GroupInterface) {
      return [];
    }

    $members_id = \Drupal::entityQuery('group_content')
      ->condition('type', 'group-group_membership')
      ->condition('gid', $group->id())
      ->sort('created', 'DESC')
      ->range(0, 5)
      ->execute();

    $members = GroupContent::loadMultiple($members_id);

    $members_data = array_map(function(GroupContent $groupContent) {
      $profiles = $this->entityTypeManager->getStorage('profile')->loadByProperties([
        'uid' => $groupContent->get('uid')->getString(),
        'type' => 'member',
      ]);

      /** @var \Drupal\profile\Entity\ProfileInterface $profile */
      $profile = reset($profiles);
      if (!$profile) {
        return [];
      }

      $user = $profile->getOwner();

      if (!$user) {
        return [];
      }

      /** @var \Drupal\media\MediaInterface|null $media_picture */
      $media_picture = $user->get('field_media')->referencedEntities();
      /** @var File|NULL $file */
      $file = $media_picture ? File::load($media_picture[0]->get('oe_media_image')->target_id) : NULL;
      $file_url = $file ? file_url_transform_relative(file_create_url($file->get('uri')->value)) : NULL;

      return [
        'joined_date' => $this->dateFormatter->format($groupContent->getCreatedTime(), 'eu_short_date'),
        'full_name' => $user->get('field_first_name')->value . ' ' . $user->get('field_last_name')->value,
        'email' => $user->getEmail(),
        'picture' => $file_url,
        'url' => $profile->toUrl()->toString(),
      ];
    }, $members);

    $current_group_route = $this->groupsHelper->getGroupFromRoute();    $user_group_roles = [];

    if ($current_group_route) {
      $account = \Drupal::currentUser();
      $membership = $current_group_route->getMember($account);
      $user_group_roles = $membership instanceof GroupMembership ? $membership->getRoles() : [];
    }

    $build['#attached']['drupalSettings']['overview'] = [
      'is_group_owner' => array_key_exists(EICGroupsHelper::GROUP_OWNER_ROLE, $user_group_roles),
    ];

    return $build += [
      '#theme' => 'eic_group_last_activities_members',
      '#cache' => ['contexts' => ['url.path', 'url.query_args']],
      '#members' => $members_data,
      '#url' => Url::fromRoute('eic_groups.solr_search')->toString(),
      '#translations' => [
        'no_results' => $this->t('No results', [], ['context' => 'eic_group']),
        'load_more' => $this->t('Load more', [], ['context' => 'eic_group']),
        'block_title' => $this->t('Latest member activity', [], ['context' => 'eic_group']),
        'commented_on' => $this->t('commented on', [], ['context' => 'eic_group']),
        'delete_modal_title' => $this->t('Delete activity from activity stream', [], ['context' => 'eic_group']),
        'delete_modal_desc' => $this->t('Are you sure you want to delete this activity from the activity stream? Important: this action cannot be undone.', [], ['context' => 'eic_group']),
        'delete_modal_confirm' => $this->t('Yes, delete activity', [], ['context' => 'eic_group']),
        'delete_modal_cancel' => $this->t('Cancel', [], ['context' => 'eic_group']),
        'delete_modal_close' => $this->t('Close', [], ['context' => 'eic_group']),
        ],
      '#datasource' => $this->activityStreamSourceType->getSourcesId(),
      '#source_class' => ActivityStreamSourceType::class,
      '#group_id' => $group->id(),
    ];
  }

}
