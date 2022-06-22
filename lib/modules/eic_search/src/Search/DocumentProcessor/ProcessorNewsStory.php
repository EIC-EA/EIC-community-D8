<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorNewsStory
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorNewsStory extends DocumentProcessor {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var FileUrlGeneratorInterface $urlGenerator
   */
  private $urlGenerator;

  /**
   * Constructs a new ProcessorNewsStory object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $url_generator
   *   The URL generator service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $urlGenerator
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $teaser_image_fid = array_key_exists('its_content_teaser_image_fid', $fields) ?
      $fields['its_content_teaser_image_fid'] :
      NULL;
    $node = Node::load($fields['its_content_nid']);
    $is_restricted = $node->private->value === '1';
    $teaser_relative = '';

    if ($teaser_image_fid) {
      $image_style = ImageStyle::load('ratio_5_4_small');
      $file = File::load($teaser_image_fid);
      $image_uri = $file->getFileUri();

      $teaser_relative = $this->urlGenerator->transformRelative($image_style->buildUrl($image_uri));
    }

    $user_picture_uri = array_key_exists('ss_content_author_image_uri', $fields) ?
      $fields['ss_content_author_image_uri'] :
      NULL;

    $user_picture_relative = '';

    // Generates image style for the user picture.
    if ($user_picture_uri) {
      /** @var \Drupal\image\Entity\ImageStyle $image_style */
      $image_style = $this->entityTypeManager->getStorage('image_style')
        ->load('crop_36x36');
      $user_picture_relative = $this->urlGenerator->transformRelative($image_style->buildUrl($user_picture_uri));
    }

    $this->addOrUpdateDocumentField($document, 'ss_content_author_formatted_image', $fields, $user_picture_relative);
    $this->addOrUpdateDocumentField($document, 'ss_content_teaser_image_url', $fields, $teaser_relative);
    $this->addOrUpdateDocumentField($document, 'bs_is_restricted', $fields, $is_restricted);
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    return array_key_exists('ss_content_type', $fields) && in_array($fields['ss_content_type'], ['news', 'story']);
  }
}
