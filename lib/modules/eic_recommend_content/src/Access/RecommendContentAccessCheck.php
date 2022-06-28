<?php

namespace Drupal\eic_recommend_content\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_recommend_content\Services\RecommendContentManager;
use Drupal\eic_user\UserHelper;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 *
 * To make use of this access checker add
 * '_eic_recommend_content_access_check: Some value' entry to route definition
 * under requirements section.
 */
class RecommendContentAccessCheck implements AccessInterface {

  /**
   * The OEC Group flex helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Entity type manager.
   *
   * @var \Drupal\eic_recommend_content\Services\RecommendContentManager
   */
  protected $recommendContentManager;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $eicGroupsHelper;

  /**
   * Constructs a new RecommendGroupAccessCheck object.
   *
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $oec_group_flex_helper
   *   The OEC Group flex helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager.
   * @param \Drupal\eic_recommend_content\Services\RecommendContentManager $recommend_content_manager
   *   The EIC Recommend content manager.
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The EIC Groups helper service.
   */
  public function __construct(
    OECGroupFlexHelper $oec_group_flex_helper,
    EntityTypeManagerInterface $entity_type_manager,
    RecommendContentManager $recommend_content_manager,
    EICGroupsHelper $eic_groups_helper
  ) {
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->recommendContentManager = $recommend_content_manager;
    $this->eicGroupsHelper = $eic_groups_helper;
  }

  /**
   * Checks routing access for the recommend content route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $entity_type
   *   The entity type machine name.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, string $entity_type = NULL, int $entity_id = NULL) {
    if (!$entity_type || !$entity_id || $account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    $support_entity_types = RecommendContentManager::getSupportedEntityTypes();
    if (!array_key_exists($entity_type, $support_entity_types)) {
      return AccessResult::forbidden();
    }

    $entity = $this->entityTypeManager->getStorage($entity_type)
      ->load($entity_id);

    if (!$entity) {
      return AccessResult::forbidden();
    }

    // Default access.
    $access = AccessResult::forbidden()
      ->setCacheMaxAge(0);

    if (!$this->recommendContentManager->isRecommendationEnabled($entity)) {
      return $access;
    }

    // If entity is a group, we need to check visibility before allowing
    // access.
    if ($entity_type === 'group') {
      $visibility_settings = $this->oecGroupFlexHelper->getGroupVisibilitySettings($entity);
      $allowed_group_visibilities = [
        'public',
        'private',
        'custom_restricted',
        'restricted_community_members',
      ];

      if (!in_array($visibility_settings['plugin_id'], $allowed_group_visibilities)) {
        return $access;
      }
    }

    $moderation_state = $entity->get('moderation_state')->value;

    switch ($moderation_state) {
      case DefaultContentModerationStates::PUBLISHED_STATE:
        // If the entity belongs to a group and the group is not published, we
        // deny access to recommend it.
        if ($group = $this->eicGroupsHelper->getOwnerGroupByEntity($entity)) {
          if ($group->get('moderation_state')->value !== $moderation_state) {
            $access->setCacheMaxAge(0);
            break;
          }
        }

        // Power users can always recommend published content.
        if (UserHelper::isPowerUser($account)) {
          $access = AccessResult::allowed()
            ->setCacheMaxAge(0);
          break;
        }

        // At this point, it means the user is not a power user. If the current
        // user does not have permission to view the content, we deny access to
        // recommend it.
        if (!$entity->access('view', $account)) {
          break;
        }

        $access = AccessResult::allowed()
          ->setCacheMaxAge(0);
        break;

    }

    return $access;
  }

}
