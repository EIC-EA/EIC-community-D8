<?php

namespace Drupal\oec_group_comments;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Group permission checker to retrieve permission for given group contents.
 */
class GroupPermissionChecker {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The entity type manager service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * Checks if given account was granted permission in group.
   *
   * @param string $perm
   *   The permission to check for, e.g. 'view comments'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   * @param \Drupal\group\Entity\GroupContentInterface[] $group_contents
   *   The array of group contents.
   * @param array|null $output
   *   The output to add cacheable dependency to.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns allowed or forbidden access result.
   */
  public function getPermissionInGroups($perm, AccountInterface $account, array $group_contents, &$output = NULL) {
    foreach ($group_contents as $group_content) {
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $group_content->getGroup();

      // Add cacheable dependency.
      if ($output) {
        $membership = $group->getMember($account);
        $this->renderer->addCacheableDependency($output, $membership);
      }

      if ($group->hasPermission($perm, $account)) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    // Fallback.
    return AccessResult::forbidden()->cachePerUser();
  }

}
