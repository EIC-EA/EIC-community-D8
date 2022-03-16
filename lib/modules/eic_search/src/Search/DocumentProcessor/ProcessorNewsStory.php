<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorNewsStory
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorNewsStory extends DocumentProcessor {

  /**
   * @var FileUrlGeneratorInterface $urlGenerator
   */
  private $urlGenerator;

  /**
   * @param FileUrlGeneratorInterface $urlGenerator
   */
  public function __construct(FileUrlGeneratorInterface $urlGenerator) {
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $teaser_image_fid = array_key_exists('its_content_teaser_image_fid', $fields) ?
      $fields['its_content_teaser_image_fid'] :
      NULL;

    $teaser_relative = '';

    if ($teaser_image_fid) {
      $image_style = ImageStyle::load('media_entity_browser_thumbnail');
      $file = File::load($teaser_image_fid);
      $image_uri = $file->getFileUri();

      $teaser_relative = $this->urlGenerator->transformRelative($image_style->buildUrl($image_uri));
    }

    $this->addOrUpdateDocumentField($document, 'ss_content_teaser_image_url', $fields, $teaser_relative);
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    return array_key_exists('ss_content_type', $fields) && in_array($fields['ss_content_type'], ['news', 'story']);
  }
}
