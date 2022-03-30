<?php

namespace Drupal\eic_vod\StreamWrapper;

/**
 * This trait make is intended for stream wrappers which are integrating an external services where the files are store.
 * It 'disable' some methods which are intended for file systems.
 *
 * @see \Drupal\Core\StreamWrapper\PublicStream
 */
trait RemoteStreamWrapperTrait {

  /**
   * {@inheritdoc}
   */
  public function dir_closedir() {
    $this->throw_warning();
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function dir_opendir($path, $options) {
    $this->throw_warning();
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function dir_readdir() {
    $this->throw_warning();
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function dir_rewinddir() {
    $this->throw_warning();
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($path_from, $path_to) {
    $this->throw_warning();
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rmdir($path, $options) {
    $this->throw_warning();
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_cast($cast_as) {
    $this->throw_warning();
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_flush() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_metadata($path, $option, $value) {
    $this->throw_warning();
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_truncate($new_size) {
    $this->throw_warning();
    return FALSE;
  }

  private function throw_warning() {
    trigger_error(sprintf('%s not supported for remote stream wrappers', __FUNCTION__), E_USER_WARNING);
  }

}
