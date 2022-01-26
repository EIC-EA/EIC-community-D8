<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\flag\FlagServiceInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

/**
 * Access check for like group content action.
 */
class LikeGroupContentAccessCheck implements AccessInterface {

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  private $flagService;

  /**
   * The constructor.
   *
   * @param \Drupal\flag\FlagServiceInterface $flagService
   *   The flag service.
   */
  public function __construct(FlagServiceInterface $flagService) {
    $this->flagService = $flagService;
  }

  /**
   * Access method.
   *
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The AccountProxy.
   * @param \Drupal\group\Entity\GroupInterface|null $group
   *   The group entity.
   * @param \Drupal\node\NodeInterface|null $node
   *   The node entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Return the access result.
   */
  public function access(
    AccountProxy $account,
    GroupInterface $group = NULL,
    NodeInterface $node = NULL
  ) {
    if (!$group || !$node) {
      return AccessResult::forbidden();
    }

    $flag_entity = $this->flagService->getFlagById('like_content');

    return $flag_entity->actionAccess('flag', $account, $node);
  }

}
