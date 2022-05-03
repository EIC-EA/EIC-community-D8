<?php

namespace Drupal\eic_media_statistics\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_user\UserHelper;
use Drupal\entity_usage\EntityUsageInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 *
 * @DCG
 * To make use of this access checker add '_foo: Some value' entry to route
 * definition under requirements section.
 */
class MediaFileDownloadAccessCheck implements AccessInterface {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Entity usage service.
   *
   * @var \Drupal\entity_usage\EntityUsageInterface
   */
  protected $entityUsage;

  /**
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_usage\EntityUsageInterface $entity_usage
   *   The Entity usage service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityUsageInterface $entity_usage
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityUsage = $entity_usage;
  }

  /**
   * Access callback.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to test access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, MediaInterface $media, AccountInterface $account) {
    // Loads up the entity usage for the media.
    $media_usage = $this->entityUsage->listSources($media);

    // We loop through all source entities and we try to find one the user has
    // access to.
    foreach (array_keys($media_usage) as $entity_type) {
      foreach (array_keys($media_usage[$entity_type]) as $entity_id) {
        $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
        $access = $entity->access('view', $account, TRUE);

        if ($access->isAllowed()) {
          // We found an entity the user has access to. The user can download
          // the media.
          return $access;
        }
      }
    }

    // Allow access if the user is a power user.
    return AccessResult::allowedIf(UserHelper::isPowerUser($account));
  }

}
