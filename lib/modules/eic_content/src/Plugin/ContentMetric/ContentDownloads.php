<?php

namespace Drupal\eic_content\Plugin\ContentMetric;

use Drupal\eic_content\ContentMetricPluginBase;
use Drupal\node\NodeInterface;

/**
 * Group metric plugin implementation for group downloads.
 *
 * @ContentMetric(
 *   id = "eic_content_downloads",
 *   label = @Translation("Content downloads"),
 *   description = @Translation("Provides a counter for content downloads.")
 * )
 */
class ContentDownloads extends ContentMetricPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue(NodeInterface $node, array $configuration = []): int {
    return $this->entityFileDownloadCount->getFileDownloads($node);
  }

}
