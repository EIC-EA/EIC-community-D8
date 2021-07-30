<?php

namespace Drupal\eic_search\Collector;

use Drupal\eic_search\Search\Sources\SourceTypeInterface;

/**
 * Class SourcesCollector
 *
 * @package Drupal\eic_search\Collector
 */
class SourcesCollector {

  private $sources = [];

  /**
   * @param \Drupal\eic_search\Search\Sources\SourceTypeInterface $sourceType
   */
  public function addSource(SourceTypeInterface $sourceType) {
    $this->sources[get_class($sourceType)] = $sourceType;
  }

  /**
   * @return array
   */
  public function getSources(): array {
    return $this->sources;
  }

}
