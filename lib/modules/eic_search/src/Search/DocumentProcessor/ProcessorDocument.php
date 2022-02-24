<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\eic_media_statistics\EntityFileDownloadCount;
use Drupal\node\Entity\Node;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorDocument
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorDocument extends DocumentProcessor {

  /**
   * @var \Drupal\eic_media_statistics\EntityFileDownloadCount $entityDownloadHelper
   */
  private $entityDownloadHelper;

  /**
   * @param \Drupal\eic_media_statistics\EntityFileDownloadCount $entityDownloadHelper
   */
  public function __construct(EntityFileDownloadCount $entityDownloadHelper) {
    $this->entityDownloadHelper = $entityDownloadHelper;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $node = Node::load($fields['its_content_nid']);
    $document->addField('its_document_download_total', $this->entityDownloadHelper->getFileDownloads($node));
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    return array_key_exists('ss_content_type', $fields) && 'document' === $fields['ss_content_type'];
  }

}
