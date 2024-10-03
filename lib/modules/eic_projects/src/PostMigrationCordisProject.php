<?php

namespace Drupal\eic_projects;

use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate\Event\MigrateImportEvent;

class PostMigrationCordisProject {

  protected FileSystemInterface $fileSystem;

  public function __construct(FileSystemInterface $fileSystem) {
    $this->fileSystem = $fileSystem;
  }

  public function handlePostMigration() {
    $private_dir_path = $this->fileSystem->realpath("private://");

    /** @var \Drupal\eic_projects\Entity\ExtractionRequest[] $requests */
    $requests = \Drupal::entityTypeManager()
      ->getStorage('extraction_request')
      ->loadByProperties(['extraction_status' => 'migrating']);

    foreach ($requests as $request) {
      /** @var \Drupal\file\FileInterface $zip_file */
      $zip_file = $request->get('extraction_file')->entity;
      $filepath = $this->fileSystem->realpath($zip_file->getFileUri());
      $filename = pathinfo($filepath, PATHINFO_FILENAME);

      $request->set('extraction_status', 'completed')->save();
      $this->fileSystem->deleteRecursive("$private_dir_path/cordis-xml/export/$filename");

    }

  }
}
