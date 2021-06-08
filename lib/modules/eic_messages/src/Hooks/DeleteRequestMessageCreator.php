<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlaggingInterface;

/**
 * Class DeleteRequestMessageCreator.
 */
class DeleteRequestMessageCreator extends MessageCreatorBase {

  use StringTranslationTrait;

  /**
   * Implements hook_delete_request_insert().
   */
  public function deleteRequestInsert(FlaggingInterface $flag, ContentEntityInterface $entity) {
    $messages = [];
    // Prepare messages to SA/CA.
    foreach ($this->eicUserHelper->getSitePowerUsers() as $uid) {
      $message = $this->entityTypeManager->getStorage('message')->create([
        'template' => 'notify_new_deletion_request',
        'field_message_subject' => $this->t('New deletion request'),
        'field_referenced_flag' => $flag,
        'field_referenced_entity' => $entity,
        'uid' => $uid,
      ]);
      $message->save();

      $messages[] = $message;
    }

    $this->processMessages($messages);
  }

}
