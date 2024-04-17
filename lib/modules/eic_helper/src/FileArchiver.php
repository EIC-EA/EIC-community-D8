<?php

namespace Drupal\eic_helper;

use Drupal\Core\File\FileSystemInterface;
use ZipArchive;

/**
 * Archiver service that provides helper functions for files archiving.
 */
class FileArchiver {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $filesystem;

  /**
   * Constructs an Archiver object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $filesystem
   *   The file system.
   */
  public function __construct(FileSystemInterface $filesystem) {
    $this->filesystem = $filesystem;
  }

  /**
   * Archives a list of files into a zip.
   *
   * @param \Drupal\file\FileInterface[] $entities
   *   The files to archive.
   */
  public function archive(array $entities) {
    if (!is_array($entities)) {
      $entities = [$entities];
    }

    $file = $this->filesystem->tempnam($this->filesystem->getTempDirectory(), 'eic_helper-archiver');

    // We use ZipArchive instead of ArchiveManager because the output of the
    // later one is not as expected.
    $zip = new \ZipArchive();
    $zip->open($file, ZipArchive::CREATE);

    foreach ($entities as $entity) {
      $file_path = $this->filesystem->realpath($entity->getFileUri());
      $zip->addFile($file_path, $entity->getFilename());
    }

    $zip->close();
    return $file;
  }

}
