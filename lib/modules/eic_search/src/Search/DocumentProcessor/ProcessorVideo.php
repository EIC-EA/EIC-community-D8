<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Solarium\QueryType\Update\Query\Document;

/**
 * Provides a processor for Content type Video.
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorVideo extends DocumentProcessor {

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  private $urlGenerator;

  /**
   * Constructs a new ProcessorVideo object.
   *
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $url_generator
   *   The URL generator service.
   */
  public function __construct(FileUrlGeneratorInterface $url_generator) {
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    // Try to get the teaser from the node.
    $fid = array_key_exists('its_content_teaser_image_fid', $fields) ?
      $fields['its_content_teaser_image_fid'] :
      NULL;

    // If not available, fallback to the video thumbnail (auto-generated).
    if (empty($fid)) {
      $fid = array_key_exists('its_content_field_video_media_thumbnail_fid', $fields) ?
      $fields['its_content_field_video_media_thumbnail_fid'] :
      NULL;
    }

    $teaser_relative = '';

    if ($fid) {
      $image_style = ImageStyle::load('gallery_teaser_crop_160x160');
      $file = File::load($fid);
      $image_uri = $file->getFileUri();

      $teaser_relative = $this->urlGenerator->transformRelative($image_style->buildUrl($image_uri));
    }

    $this->addOrUpdateDocumentField(
      $document,
      'ss_video_formatted_image',
      $fields,
      $teaser_relative
    );
  }

  /**
   * {@inheritdoc}
   */
  public function supports(array $fields): bool {
    return 'entity:node' === $fields['ss_search_api_datasource'] && 'video' === $fields['ss_content_type'];
  }

}
