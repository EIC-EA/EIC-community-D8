<?php

/**
 * @file
 * Helper install functions for the EIC Messages module.
 */

use Drupal\Core\Cache\Cache;
use Drupal\group_permissions\Entity\GroupPermission;

/**
 * Operation batch to delete messages with references to non-existing content.
 */
function _eic_messages_batch_delete_messages_with_empty_references(array $message_ids, int $progress, int $max, int $total, &$context) {
  $message_storage = \Drupal::entityTypeManager()->getStorage('message');
  $messages = $message_storage->loadMultiple($message_ids);
  $message_storage->delete($messages);

  if ($max > $total) {
    $max = $total;
  }

  $context['message'] = t('Removed @progress message of @total', [
    '@progress' => $max,
    '@total' => $total,
  ]);
}

/**
 * Generic function to use in batch processes.
 */
function _eic_messages_batch_finished($success, array $results, array $operations) {
  if ($success) {
    \Drupal::messenger()
      ->addMessage(t('@count results processed.', [
        '@count' => count($results),
      ]));
  }
  else {
    // An error occurred.
    // $operations contains the operations that remained unprocessed.
    $error_operation = reset($operations);
    \Drupal::messenger()
      ->addError(t('An error occurred while processing @operation with arguments : @args', [
        '@operation' => $error_operation[0],
        '@args' => print_r($error_operation[0], TRUE),
      ]));
  }
}

