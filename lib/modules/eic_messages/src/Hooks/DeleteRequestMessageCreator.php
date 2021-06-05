<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\flag\FlaggingInterface;

/**
 * Class DeleteRequestMessageCreator.
 */
class DeleteRequestMessageCreator extends MessageCreatorBase {

  /**
   * Implements hook_delete_request_insert().
   */
  public function deleteRequestInsert(FlaggingInterface $flag, ContentEntityInterface $entity) {
    $messages = [];
    // Prepare messages to SA/CA.
    foreach ($this->eicUserHelper->getSitePowerUsers() as $uid) {
      $messages[] = $this->entityTypeManager->getStorage('message')->create([
        'template' => 'notify_new_deletion_request',
        'uid' => $uid,
      ]);
    }

    $this->processMessages($messages);
  }

}
