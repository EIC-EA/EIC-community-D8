<?php

namespace Drupal\eic_projects;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;

class MigrationCordisProject {

  protected FileSystemInterface $fileSystem;

  protected StateInterface $state;

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(FileSystemInterface $fileSystem, StateInterface $state, EntityTypeManagerInterface $entityTypeManager) {
    $this->fileSystem = $fileSystem;
    $this->state = $state;
    $this->entityTypeManager = $entityTypeManager;
  }

  public function handlePostMigration() {
    $private_dir_path = $this->fileSystem->realpath("private://");

    /** @var \Drupal\eic_projects\Entity\ExtractionRequest[] $requests */
    $requests = $this->entityTypeManager->getStorage('extraction_request')
      ->loadByProperties(['extraction_status' => 'pending_migration']);

    foreach ($requests as $request) {
      /** @var \Drupal\file\FileInterface $zip_file */
      $zip_file = $request->get('extraction_file')->entity;
      $filepath = $this->fileSystem->realpath($zip_file->getFileUri());
      $filename = pathinfo($filepath, PATHINFO_FILENAME);

//      $request->set('extraction_status', 'completed')->save();
//      $this->fileSystem->deleteRecursive("$private_dir_path/cordis-xml/export/$filename");

    }

  }

  public function handlePreMigration() {
    $running_extractions = $this->state->get('eic_projects.cordis_running_extractions');
    if (!empty($running_extractions)) {
      $running_extractions = $this->entityTypeManager->getStorage('extraction_request')->loadMultiple($running_extractions);
      foreach ($running_extractions as $request) {
        $request->set('extraction_status', 'migrating')->save();
      }
      $this->state->delete('eic_projects.cordis_running_extractions');
    }
  }
}
