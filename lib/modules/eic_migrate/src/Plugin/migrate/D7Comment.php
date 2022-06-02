<?php

namespace Drupal\eic_migrate\Plugin\migrate;

use Drupal\migrate_drupal\Plugin\migrate\FieldMigration;

/**
 * Migration plugin for Drupal 7 comments with fields.
 */
class D7Comment extends FieldMigration {

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $this->fieldDiscovery->addEntityFieldProcesses($this, 'comment');
    }

    // We don't want comment_body to be processed automatically as we want to
    // override it in our custom migration.
    if (isset($this->process['comment_body'])) {
      unset($this->process['comment_body']);
    }

    return parent::getProcess();
  }

}
