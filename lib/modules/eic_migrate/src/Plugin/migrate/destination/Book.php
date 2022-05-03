<?php

namespace Drupal\eic_migrate\Plugin\migrate\destination;

use Drupal\book\Plugin\migrate\destination\Book as BookBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\TranslatableInterface;

/**
 * @MigrateDestination(
 *   id = "eic_book"
 * )
 */
class Book extends BookBase {

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    if ($this->isTranslationDestination()) {
      // Attempt to update book from translation.
      $entity = $this->storage->load(reset($destination_identifier));
      if ($entity && $entity instanceof TranslatableInterface) {
        if ($key = $this->getKey('langcode')) {
          if (isset($destination_identifier[$key])) {
            $langcode = $destination_identifier[$key];
            if ($entity->hasTranslation($langcode)) {
              $translation = $entity->getTranslation($langcode);
              $this->deleteBookFromNode($translation->id());
              $translation->book = NULL;
              $translation->save();
            }
          }
        }
      }
    }
    else {
      $entity = $this->storage->load(reset($destination_identifier));
      if ($entity) {
        $this->deleteBookFromNode($entity->id());
        $entity->book = NULL;
        $entity->save();
      }
    }
  }

  /**
   * Remove book reference from a node.
   *
   * @param string|int $nid
   *   The node ID.
   */
  private function deleteBookFromNode($nid) {
    Database::getConnection('default', 'default')->delete('book')
      ->condition('nid', $nid)
      ->execute();
  }

}
