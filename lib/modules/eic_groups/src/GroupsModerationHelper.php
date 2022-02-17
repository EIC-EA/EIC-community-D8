<?php

namespace Drupal\eic_groups;

/**
 * GroupsModerationHelper helper class.
 */
class GroupsModerationHelper {

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

}
