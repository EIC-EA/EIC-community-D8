<?php

namespace Drupal\eic_moderation\Constants;

/**
 * Constants for the eic_content_moderation workflow.
 */
final class EICContentModeration {

  /**
   * The worflow machine name.
   *
   * @var string
   */
  const MACHINE_NAME = 'eic_moderated_workflow';

  /**
   * The 'Draft' state machine name.
   *
   * @var string
   */
  const STATE_DRAFT = 'draft';

  /**
   * The 'Waiting for approval' state machine name.
   *
   * @var string
   */
  const STATE_WAITING_APPROVAL = 'waiting_for_approval';

  /**
   * The 'Needs review' state machine name.
   *
   * @var string
   */
  const STATE_NEEDS_REVIEW = 'needs_review';

  /**
   * The 'Published' state machine name.
   *
   * @var string
   */
  const STATE_PUBLISHED = 'published';

  /**
   * The 'Unpublished' state machine name.
   *
   * @var string
   */
  const STATE_UNPUBLISHED = 'unpublished';

  /**
   * The 'Content ready for review' message template machine name.
   *
   * @var string
   */
  const MESSAGE_WAITING_APPROVAL = 'notify_content_ready_for_review';

  /**
   * The 'Content needs review' message template machine name.
   *
   * @var string
   */
  const MESSAGE_NEEDS_REVIEW = 'notify_content_needs_review';

  /**
   * The 'Content approved' message template machine name.
   *
   * @var string
   */
  const MESSAGE_PUBLISHED = 'notify_content_approved';

}
