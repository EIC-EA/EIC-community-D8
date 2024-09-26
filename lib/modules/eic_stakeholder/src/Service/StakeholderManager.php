<?php

namespace Drupal\eic_stakeholder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoader;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;

/**
 * Class that manages functions for the stakeholder feature.
 *
 * @package Drupal\eic_stakeholder\Service
 */
class StakeholderManager {

  use StringTranslationTrait;

  /**
   * The Group Content plugin ID for stakeholder content.
   *
   * @var string
   */
  const GROUP_CONTENT_STAKEHOLDER_BASE_PLUGIN_ID = 'group_stakeholder';

  /**
   * The EIC Groups helper.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  private $groupsHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The group content enabler manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  private $groupContentPluginManager;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoader
   */
  private $groupMembershipLoader;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $groups_helper
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   * @param \Drupal\group\GroupMembershipLoader $group_membership_loader
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  public function __construct(
    EICGroupsHelperInterface $groups_helper,
    EntityTypeManagerInterface $entity_type_manager,
    GroupContentEnablerManagerInterface $plugin_manager,
    GroupMembershipLoader $group_membership_loader,
    AccountProxyInterface $account
  ) {
    $this->groupsHelper = $groups_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupContentPluginManager = $plugin_manager;
    $this->groupMembershipLoader = $group_membership_loader;
    $this->currentUser = $account;
  }

  /**
   * Returns the list of stakeholder group_content entities.
   *
   * @param int $stakeholder_id
   *   The stakeholder id for which we're looking.
   * @param \Drupal\group\Entity\GroupInterface|null $target_group
   *   The group to filter on. If null, stakeholder entities will be returned for all
   *   groups.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   The list of found group_content entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStakeholderGroupContentEntities(int $stakeholder_id, GroupInterface $target_group = NULL): array {
    $query = $this->entityTypeManager->getStorage('group_content')->getQuery();
    $query->condition('entity_id', $stakeholder_id);
    if ($target_group) {
      $query->condition('type', $this->defineGroupContentType($target_group->bundle()), 'LIKE');
      $query->condition('gid', $target_group->id());
    }
    else {
      // Filter by all group types.
      $group_type_conditions = $this->defineGroupContentType();
      $query->condition('type', $group_type_conditions, 'IN');
    }
    return $this->entityTypeManager->getStorage('group_content')->loadMultiple($query->execute());
  }

  /**
   * Returns the projects an organisation has participated in.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *    The group entity.
   * @param string[] $stakeholder_types
   *    An array of stakeholder type machine names. If empty, the function will return
   *    results for all types.
   * @param bool $published_only
   *    Whether to return published groups only.
   *
   * @return GroupInterface|array
   *    The project groups or empty array.
   */
  public function getOrganisationParticipatedProjects(GroupInterface $group, array $stakeholder_types = [], bool $published_only = TRUE): array|GroupInterface {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->entityTypeManager->getStorage('stakeholder')->getQuery();

    if (!empty($stakeholder_types)) {
      $query->condition('type', $stakeholder_types, 'IN');
    }

    if ($published_only) {
      $query->condition('status', 1);
    }

    $query->condition('field_stakeholder_organisation', [$group->id()], 'IN');
    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    $stakeholder_ids = array_keys($ids);
    $stakeholder_id = reset($stakeholder_ids);

    $projects = [];
    foreach ($this->getStakeholderGroupContentEntities($stakeholder_id) as $group_content) {
      $group = $group_content->getGroup();
      $projects[$group->id()] = $group;
    }

    return $projects;
  }

  /**
   * Returns the types for a stakeholder content from given group type.
   *
   * @param string $target_group_type
   *   The target group type.
   *
   * @return array
   *   The types to be used for the group_content entity.
   */
  public static function defineGroupContentType(string $target_group_type = ''): array {
    $plugin_manager = \Drupal::service('plugin.manager.group_content_enabler');
    $entity_type_manager = \Drupal::entityTypeManager();
    $bundles = [];
    $group_type = NULL;

    if (!empty($target_group_type)) {
      $group_type = \Drupal::entityTypeManager()
        ->getStorage('group_type')
        ->load($target_group_type);
    }

    // Retrieve all group_content_menu plugins for the group's type.
    $plugin_ids = $plugin_manager->getInstalledIds($group_type);
    foreach ($plugin_ids as $key => $plugin_id) {
      $stakeholder_group_content_plugin_id = self::GROUP_CONTENT_STAKEHOLDER_BASE_PLUGIN_ID . ':';
      if (strpos($plugin_id, $stakeholder_group_content_plugin_id) !== 0) {
        unset($plugin_ids[$key]);
      }
    }

    // Retrieve all of the responsible group content types, keyed by plugin ID.
    $storage = $entity_type_manager->getStorage('group_content_type');
    $properties = ['content_plugin' => $plugin_ids];
    foreach ($storage->loadByProperties($properties) as $group_content_type) {
      /** @var \Drupal\group\Entity\GroupContentTypeInterface $group_content_type */
      $bundles[] = $group_content_type->getOriginalId();
    }

    return $bundles;
  }

}
