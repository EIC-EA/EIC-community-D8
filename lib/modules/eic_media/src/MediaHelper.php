<?php

namespace Drupal\eic_media;

use Drupal\Core\Url;
use Drupal\media\MediaInterface;

/**
 * MediaHelper service that provides helper functions for media.
 */
class MediaHelper {

  /**
   * Returns a download URL object for the given media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media object.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public static function formatMediaDownloadLink(MediaInterface $media) {
    return Url::fromRoute('media_entity_download.download', ['media' => $media->id()]);
  }

}
