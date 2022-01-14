<?php

/**
 * @file
 * Hooks provided by the eic_flags module.
 */

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eic_flags\RequestTypes;
use Drupal\flag\FlaggingInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Respond to a new request.
 *
 * @param \Drupal\flag\FlaggingInterface $flagging
 *   The flag that has been created and applied to the content.
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The content entity the flag has been applied to.
 * @param string $type
 *   The type of the flag (delete, archive, etc.).
 *
 * @see \Drupal\eic_flags\RequestTypes
 * @see \Drupal\eic_flags\RequestStatus
 */
function hook_request_open(
  FlaggingInterface $flagging,
  ContentEntityInterface $entity,
  string $type
) {
  // Log when a delete new request is made.
  if (RequestTypes::DELETE === $type) {
    \Drupal::logger('eic_flags')->notice('A new delete request has been made.');
  }
}

/**
 * Respond to a request being handled.
 *
 * @param \Drupal\flag\FlaggingInterface $flagging
 *   The flag that has been created and applied to the content.
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The content entity the flag has been applied to.
 * @param string $type
 *   The type of the flag (delete, archive, etc.).
 *
 * @see \Drupal\eic_flags\RequestTypes
 * @see \Drupal\eic_flags\RequestStatus
 */
function hook_request_close(
  FlaggingInterface $flagging,
  ContentEntityInterface $entity,
  string $type
) {
  // Create a log entry with some info.
  \Drupal::logger('eic_flags')
    ->notice(
      'Request @flag_id with type @type has been handled. Response @response given',
      [
        '@flag_id' => $flagging->id(),
        '@type' => $type,
        '@response' => $flagging->get('field_request_response')->value,
      ]
    );
}

/**
 * Respond to a request being expired.
 *
 * @param \Drupal\flag\FlaggingInterface $flagging
 *   The flag that has been created and applied to the content.
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The content entity the flag has been applied to.
 * @param string $type
 *   The type of the flag (delete, archive, etc.).
 */
function hook_request_timeout(
  FlaggingInterface $flagging,
  ContentEntityInterface $entity,
  string $type
) {
  // Create a log entry with some info.
  \Drupal::logger('eic_flags')
    ->notice(
      'Request @flag_id with type @type has been handled. Response @response given',
      [
        '@flag_id' => $flagging->id(),
        '@type' => $type,
        '@response' => 'request timeout',
      ]
    );
}

/**
 * @} End of "addtogroup hooks".
 */
