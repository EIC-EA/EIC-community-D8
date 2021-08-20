<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\profile\Entity\Profile;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a LastGroupMembersBlock block.
 *
 * @Block(
 *   id = "eic_groups_last_members",
 *   admin_label = @Translation("EIC Last Group Members"),
 *   category = @Translation("European Innovation Council"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE)
 *   }
 * )
 */
class LastGroupMembersBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_groups.helper'),
      $container->get('entity_type.manager')
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
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupsHelper = $groups_helper;
    $this->entityTypeManager = $entity_type_manager;
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
        'joined_timestamp' => $groupContent->getCreatedTime(),
        'full_name' => $user->get('field_first_name')->value . ' ' . $user->get('field_last_name')->value,
        'email' => $user->getEmail(),
        'picture' => $file_url,
        'url' => $profile->toUrl()->toString(),
      ];
    }, $members);

    return [
      '#theme' => 'eic_group_last_members',
      '#members' => $members_data,
    ];
  }

}
