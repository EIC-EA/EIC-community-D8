<?php

namespace Drupal\eic_media_statistics\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\media\MediaInterface;
use Drupal\media_entity_download\Controller\DownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a controller to download media file.
 */
class MediaFileDownloadController extends DownloadController {

  /**
   * Eic Media statistics config settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $eicMediaStatisticsSettings;

  /**
   * Eic Media file statistics database storage.
   *
   * @var \Drupal\eic_media_statistics\FileStatisticsDatabaseStorage
   */
  protected $fileStatisticsStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = parent::create($container);
    $controller->eicMediaStatisticsSettings = $container->get('config.factory')->get('eic_media_statistics.settings');
    $controller->fileStatisticsStorage = $container->get('eic_media_statistics.storage.file');
    return $controller;
  }

  /**
   * {@inheritdoc}
   */
  public function download(MediaInterface $media) {
    try {
      $response = parent::download($media);
    }
    catch (\Exception $e) {
      throw $e;
    }

    $source = $media->getSource();
    $config = $source->getConfiguration();
    $field = $config['source_field'];
    $request_query = $this->requestStack->getCurrentRequest()->query;

    // If a delta was provided, use that.
    $delta = $request_query->get('delta');

    // Get the ID of the requested file by its field delta.
    if (is_numeric($delta)) {
      $values = $media->{$field}->getValue();

      if (isset($values[$delta])) {
        $fid = $values[$delta]['target_id'];
      }
      else {
        throw new NotFoundHttpException("The requested file could not be found.");
      }
    }
    else {
      $fid = $media->{$field}->target_id;
    }

    if (!$this->eicMediaStatisticsSettings->get('count_file_downloads')) {
      return $response;
    }

    $success = $this->fileStatisticsStorage->recordView($fid);

    if ($success) {
      Cache::invalidateTags(self::getMediaFileDownloadCacheTags($fid));
    }

    return $response;
  }

  /**
   * Get file download cache tags.
   *
   * @param int $fid
   *   The entity file ID.
   *
   * @return array
   *   Array of cache tags.
   */
  public static function getMediaFileDownloadCacheTags($fid) {
    return [
      "media_file_download: $fid",
    ];
  }

}
