<?php

namespace Drupal\eic_groups;

use Drupal\group\Entity\GroupInterface;

/**
 * GroupsModerationHelper helper class.
 */
class GroupsModerationHelper {

  /**
   * The refused state key for Group group type.
   *
   * @var string
   */
  const GROUP_REFUSED_STATE = 'refused';

  /**
   * The pending state key for Group group type.
   *
   * @var string
   */
  const GROUP_PENDING_STATE = 'pending';

  /**
   * The draft state key for Group group type.
   *
   * @var string
   */
  const GROUP_DRAFT_STATE = 'draft';

  /**
   * The published state key for Group group type.
   *
   * @var string
   */
  const GROUP_PUBLISHED_STATE = 'published';

  /**
   * The blocked state key for Group group type.
   *
   * @var string
   */
  const GROUP_BLOCKED_STATE = 'blocked';

  /**
   * The archived state key for Group group type.
   *
   * @var string
   */
  const GROUP_ARCHIVED_STATE = 'archived';

  /**
   * Checks if a group is archived.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return bool
   *   TRUE if group is archived, FALSE otherwise.
   */
  public static function isArchived(GroupInterface $group) {
    if ($group->get('moderation_state')->value === GroupsModerationHelper::GROUP_ARCHIVED_STATE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if a group is blocked.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return bool
   *   TRUE if group is blocked, FALSE otherwise.
   */
  public static function isBlocked(GroupInterface $group) {
    if ($group->get('moderation_state')->value === GroupsModerationHelper::GROUP_BLOCKED_STATE) {
      return TRUE;
    }

    return FALSE;
  }

}
