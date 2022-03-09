<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Solarium\QueryType\Update\Query\Document;

/**
 * Interface DocumentProcessorInterface
 *
 * @package Drupal\eic_groups\Search\DocumentProcessor
 */
interface DocumentProcessorInterface {

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param array $fields
   *
   * @return void
   */
  public function process(Document &$document, array $fields, array $items = []): void;

  /**
   * Return true if processor's condition match.
   *
   * @param array $fields
   *
   * @return bool
   */
  public function supports(array $fields): bool;
}
