<?php

namespace Drupal\eic_vod\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Defines a Drupal vod (vod://) stream wrapper class.
 *
 * Provides support for storing files on the VOD bucket and retrieve provide the converted url.
 */
class VODStream implements StreamWrapperInterface {

  use StringTranslationTrait;
  use RemoteStreamWrapperTrait;
  
  /**
   * Instance uri referenced as "<scheme>://key".
   */
  protected ?string $uri = NULL;

  protected StreamInterface $stream;

  protected ClientInterface $httpClient;

  /**
   * @param \GuzzleHttp\ClientInterface $http_client
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
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
    // TODO: Implement stream_open() method.
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
    // TODO: Implement stream_stat() method.
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

  public function url_stat($path, $flags) {
    // TODO: Implement url_stat() method.
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
    // TODO: Implement getExternalUrl() method.
  }

  public function realpath() {
    // TODO: Implement realpath() method.
  }

  public function dirname($uri = NULL) {
    // TODO: Implement dirname() method.
  }

}
