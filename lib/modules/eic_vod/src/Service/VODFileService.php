<?php

namespace Drupal\eic_vod\Service;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\eic_vod\StreamWrapper\VODStream;
use Psr\Log\LoggerInterface;

/**
 * Class VODFileService
 *
 * @package Drupal\eic_vod\Service
 */
class VODFileService implements FileSystemInterface {

  protected FileSystemInterface $decorated;

  protected LoggerInterface $logger;

  protected StreamWrapperManagerInterface $streamWrapperManager;

  protected VODClient $vodClient;

  /**
   * VODFileService constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $decorated
   *   FileSystem Service being decorated.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   StreamWrapper manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logging service.
   * @param \Drupal\eic_vod\Service\VODClient $vod_client
   *   The client service.
   */
  public function __construct(
    FileSystemInterface $decorated,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    LoggerInterface $logger,
    VODClient $vod_client
  ) {
    $this->decorated = $decorated;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->logger = $logger;
    $this->vodClient = $vod_client;
  }

  /**
   * {@inheritdoc}
   */
  public function chmod($uri, $mode = NULL) {
    return $this->decorated->chmod($uri, $mode);
  }

  /**
   * {@inheritdoc}
   */
  public function unlink($uri, $context = NULL) {
    return $this->decorated->unlink($uri, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function realpath($uri) {
    return $this->decorated->realpath($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri) {
    return $this->decorated->dirname($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function basename($uri, $suffix = NULL) {
    return $this->decorated->basename($uri, $suffix);
  }

  /**
   * {@inheritdoc}
   */
  public function mkdir($uri, $mode = NULL, $recursive = FALSE, $context = NULL) {
    return $this->decorated->mkdir($uri, $mode, $recursive, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function rmdir($uri, $context = NULL) {
    return $this->decorated->rmdir($uri, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function tempnam($directory, $prefix) {
    return $this->decorated->tempnam($directory, $prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($path) {
    return $this->decorated->delete($path);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRecursive($path, callable $callback = NULL) {
    return $this->decorated->deleteRecursive($path, $callback);
  }

  /**
   * {@inheritdoc}
   */
  public function move($source, $destination, $replace = self::EXISTS_RENAME) {
    $wrapper = $this->streamWrapperManager->getViaUri($destination);
    if (is_a($wrapper, VODStream::class)) {
      $destination = $destination . DIRECTORY_SEPARATOR . basename($source);
      $this->vodClient->putVideo($source, $destination);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveData($data, $destination, $replace = self::EXISTS_RENAME) {
    $this->decorated->saveData($data, $destination, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareDirectory(&$directory, $options = self::MODIFY_PERMISSIONS) {
    return $this->decorated->prepareDirectory($directory, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationFilename($destination, $replace) {
    return $this->decorated->getDestinationFilename($destination, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function createFilename($basename, $directory) {
    return $this->decorated->createFilename($basename, $directory);
  }

  /**
   * {@inheritdoc}
   */
  public function getTempDirectory() {
    return $this->decorated->getTempDirectory();
  }

  /**
   * {@inheritdoc}
   */
  public function scanDirectory($dir, $mask, array $options = []) {
    return $this->decorated->scanDirectory($dir, $mask, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function moveUploadedFile($filename, $uri) {
    $wrapper = $this->streamWrapperManager->getViaUri($uri);
    if (is_a($wrapper, VODStream::class)) {
    }
    else {
      return $this->decorated->moveUploadedFile($filename, $uri);
    }
  }

  public function copy($source, $destination, $replace = self::EXISTS_RENAME) {
    $wrapper = $this->streamWrapperManager->getViaUri($destination);
    if (is_a($wrapper, VODStream::class)) {
      // send file
    }
    else {
      return $this->decorated->copy($source, $destination, $replace);
    }
  }

}
