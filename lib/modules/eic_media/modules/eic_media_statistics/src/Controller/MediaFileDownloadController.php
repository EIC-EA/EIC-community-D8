<?php

namespace Drupal\eic_media_statistics\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\eic_user\UserHelper;
use Drupal\media\MediaInterface;
use Drupal\media_entity_download\Controller\DownloadController;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The current route match.
   *
   * @var \Drupal\eic_search\Service\SolrDocumentProcessor
   */
  protected $solrDocumentProcessor;

  /**
   * The EIC groups helper.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $groupsHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = parent::create($container);
    $controller->eicMediaStatisticsSettings = $container->get('config.factory')
      ->get('eic_media_statistics.settings');
    $controller->fileStatisticsStorage = $container->get('eic_media_statistics.storage.file');
    $controller->cacheBackend = $container->get('cache.default');
    $controller->solrDocumentProcessor = $container->get('eic_search.solr_document_processor');
    $controller->groupsHelper = $container->get('eic_groups.helper');
    return $controller;
  }

  /**
   * {@inheritdoc}
   */
  public function download(MediaInterface $media) {
    $response = parent::download($media);

    $source = $media->getSource();
    $config = $source->getConfiguration();
    $field = $config['source_field'];
    $request_query = $this->requestStack->getCurrentRequest()->query;
    $request_query->get('node_id');

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
    $node_id = $request_query->get('node_id');
    $node = Node::load($node_id);
    $group = $this->groupsHelper->getGroupFromContent($node);
    $user = User::load($this->currentUser()->id());

    // Do not allow downloading file if group sensitive and user no role sensitive.
    if ($this->groupsHelper->isGroupSensitive($group) && !$user->hasRole(UserHelper::ROLE_SENSITIVE)) {
      throw new AccessDeniedHttpException(t('You are not allowed to download a sensitive content.'));
    }

    if ($success && $node instanceof NodeInterface) {
      $node = Node::load($node_id);

      $messages = $this->entityTypeManager->getStorage('message')->loadByProperties([
        'field_referenced_node' => $node_id,
      ]);

      $data_to_index = array_values($messages);
      $data_to_index[] = $node;
      $this->solrDocumentProcessor->lateReIndexEntities($data_to_index);
    }

    if ($success) {
      // Invalidate custom media file download cache tags.
      Cache::invalidateTags(self::getMediaFileDownloadCacheTags($fid));
      // Invalidate file_download_stats cache.
      $this->cacheBackend->invalidate('file_download_stats:' . $media->getEntityTypeId() . ':' . $media->id());
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
      "media_file_download:$fid",
    ];
  }

}
