<?php

namespace Drupal\eic_vod\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_vod\Service\VODClient;
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

  protected VODClient $httpClient;

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
    $this->stream->close();
  }

  /**
   * {@inheritdoc}
   */
  public function stream_cast($cast_as) {
    return $this->stream ?? FALSE;
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
    // Lock isn't supported.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_tell() {
    $this->stream->tell();
  }

  /**
   * {@inheritdoc}
   */
  public function stream_write($data) {
    $this->stream->write($data);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_open($path, $mode, $options, &$opened_path) {
    if (!in_array($mode, ['r', 'rb', 'rt'])) {
      if ($options & STREAM_REPORT_ERRORS) {
        trigger_error('stream_open() write modes not supported for HTTP stream wrappers', E_USER_WARNING);
      }
      return FALSE;
    }

    try {
      $this->setUri($path);
      $url = $this->getExternalUrl();
      $response = \Drupal::httpClient()->request('GET', $url, ['stream' => TRUE]);
      $this->stream = $response->getBody();
    } catch (\Exception $e) {
      if ($options & STREAM_REPORT_ERRORS) {
        watchdog_exception('eic_vod', $e);
      }
      return FALSE;
    }

    if ($options & STREAM_USE_PATH) {
      $opened_path = $path;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_read($count) {
    return $this->stream->read($count);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    $this->stream->seek($offset, $whence);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_stat() {
    // @see https://github.com/guzzle/psr7/blob/master/src/StreamWrapper.php
    $stat = [
      'dev' => 0,
      'ino' => 0,
      'mode' => 0100000 | 0444,
      'nlink' => 0,
      'uid' => 0,
      'gid' => 0,
      'rdev' => 0,
      'size' => 0,
      'atime' => 0,
      'mtime' => 0,
      'ctime' => 0,
      'blksize' => 0,
      'blocks' => 0,
    ];

    if (!$this->uri) {
      return FALSE;
    }

    $presigned_download_url = $this->getHttpClient()->getPresignedUrl('download', $this->uri);
    if (!$presigned_download_url) {
      return FALSE;
    }

    try {
      $response = \Drupal::httpClient()->request('GET', $presigned_download_url);

      if ($response->hasHeader('Content-Length')) {
        $stat['size'] = (int) $response->getHeaderLine('Content-Length');
      }
      elseif ($size = $response->getBody()->getSize()) {
        $stat['size'] = $size;
      }
      if ($response->hasHeader('Last-Modified')) {
        if ($mtime = strtotime($response->getHeaderLine('Last-Modified'))) {
          $stat['mtime'] = $mtime;
        }
      }
    } catch (\Exception $exception) {
      trigger_error(sprintf('Could not retrieve the file %s', $this->uri));
    }

    return $stat;
  }

  /**
   * {@inheritdoc}
   */
  public function unlink($path) {
    // TODO: No delete endpoint provided for VODs for the moment.
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

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    return $this->getHttpClient()->getPresignedUrl('download', self::getTarget($this->uri));
  }

  /**
   * {@inheritdoc}
   */
  public function realpath() {
    return FALSE;
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
   * @return string|null
   */
  public static function getTarget(string $path) {
    $uri = StreamWrapperManager::getTarget($path);
    if (preg_match('/^(\/)?([^\/\.]*\/?)+?(.+\..+)$/', $uri)) {
      return $uri;
    }

    return NULL;
  }

  /**
   * @return \Drupal\eic_vod\Service\VODClient
   */
  private function getHttpClient(): VODClient {
    if (!isset($this->httpClient)) {
      $this->httpClient = \Drupal::service('eic_vod.client');
    }

    return $this->httpClient;
  }

}
