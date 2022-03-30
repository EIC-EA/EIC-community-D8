<?php

namespace Drupal\eic_vod\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;

/**
 * Defines a Drupal vod (vod://) stream wrapper class.
 *
 * Provides support for storing files on the VOD bucket and retrieve provide the converted url.
 */
class VODStream implements StreamWrapperInterface {

  use StringTranslationTrait;
  use RemoteStreamWrapperTrait;

  const FALLBACK_SCHEME = 'private';

  /**
   * Instance uri referenced as "<scheme>://key".
   */
  protected ?string $uri = NULL;

  protected StreamInterface $stream;

  protected ClientInterface $httpClient;

  protected StreamWrapperManagerInterface $streamWrapperManager;

  public function __construct() {
    $this->streamWrapperManager = \Drupal::service('stream_wrapper_manager');
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::NORMAL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('VOD file system');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Stores the original file in a source bucket and retrieves the converted one.');
  }

  /**
   * {@inheritdoc}
   */
  public function stream_close() {
    // Nothing to do when closing an HTTP stream.
  }

  /**
   * {@inheritdoc}
   */
  public function stream_eof() {
    return $this->stream->eof();
  }

  /**
   * {@inheritdoc}
   */
  public function stream_lock($operation) {
    return TRUE;
  }

  public function stream_open($path, $mode, $options, &$opened_path) {
    $this->uri = $path;
    try {
      $url = $this->getExternalUrl();
    } catch (\Exception $exception) {
      return FALSE;
    }

    return $url ?? FALSE;
  }

  public function stream_read($count) {
    // TODO: Implement stream_read() method.
  }

  public function stream_seek($offset, $whence = SEEK_SET) {
    // TODO: Implement stream_seek() method.
  }

  public function stream_set_option($option, $arg1, $arg2) {
    // TODO: Implement stream_set_option() method.
  }

  public function stream_stat() {
    // @see https://github.com/guzzle/psr7/blob/master/src/StreamWrapper.php
    $stat = [
      'dev' => 0,               // device number
      'ino' => 0,               // inode number
      'mode' => 0100000 | 0444, // inode protection (regular file + read only)
      'nlink' => 0,             // number of links
      'uid' => 0,               // userid of owner
      'gid' => 0,               // groupid of owner
      'rdev' => 0,              // device type, if inode device *
      'size' => 0,              // size in bytes
      'atime' => 0,             // time of last access (Unix timestamp)
      'mtime' => 0,             // time of last modification (Unix timestamp)
      'ctime' => 0,             // time of last inode change (Unix timestamp)
      'blksize' => 0,           // blocksize of filesystem IO
      'blocks' => 0,            // number of blocks allocated
    ];

    if (!$this->uri) {
      return $stat;
    }

    $presigned_download_url = $this->getHttpClient()->getPresignedUrl('download', self::getTarget($this->uri));
    if (!$presigned_download_url) {
      return $stat;
    }

    try {
      $response = $this->getHttpClient()->request('GET', $presigned_download_url);
    } catch (GuzzleException $exception) {

    }

    return $stat;
  }

  public function stream_tell() {
    // TODO: Implement stream_tell() method.
  }

  public function stream_write($data) {
    // TODO: Implement stream_write() method.
  }

  public function unlink($path) {
    // TODO: Implement unlink() method.
  }

  /**
   * {@inheritdoc}
   */
  public function url_stat($path, $flags) {
    if ($uri = self::getTarget($path)) {
      $this->uri = $uri;
    }

    if ($flags & STREAM_URL_STAT_QUIET) {
      return @$this->stream_stat();
    }
    else {
      return $this->stream_stat();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  public function getExternalUrl() {
    return $this->getHttpClient()->getPresignedUrl('download', self::getTarget($this->uri));
  }

  public function realpath() {
    // TODO: Implement realpath() method.
  }

  /**
   * {@inheritdoc}
   */
  public function mkdir($path, $mode, $options) {
    $fallback_wrapper = $this->streamWrapperManager->getViaScheme(self::FALLBACK_SCHEME);
    $fallback_wrapper->mkdir($path, $mode, $options);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    $scheme = StreamWrapperManager::getScheme($uri);
    $dirname = dirname($this->streamWrapperManager::getTarget($uri));
    if ($dirname == '.') {
      $dirname = '';
    }

    return "$scheme://$dirname";
  }

  /**
   * @param string|null $path
   *
   * @return mixed|string|null
   */
  public static function getTarget(string $path) {
    [, $uri] = explode('://', $path);
    if (preg_match('/^(\/)?([^\/\.]*\/)+?(.+\..+)$/', $uri)) {
      return $uri;
    }

    return NULL;
  }

  /**
   * @return \Drupal\eic_vod\Service\VODClient
   */
  private function getHttpClient() {
    if (!isset($this->httpClient)) {
      $this->httpClient = \Drupal::service('eic_vod.client');
    }

    return $this->httpClient;
  }

}
