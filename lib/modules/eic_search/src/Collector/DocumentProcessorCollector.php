<?php

namespace Drupal\eic_search\Collector;

use Drupal\eic_search\Search\DocumentProcessor\DocumentProcessorInterface;

/**
 * Class DocumentProcessorCollector
 *
 * @package Drupal\eic_search\Collector
 */
class DocumentProcessorCollector {

  private $processors = [];

  public function addProcessor(DocumentProcessorInterface $documentProcessor) {
    $this->processors[get_class($documentProcessor)] = $documentProcessor;
  }

  /**
   * @return array
   */
  public function getProcessors(): array {
    return $this->processors;
  }

}
