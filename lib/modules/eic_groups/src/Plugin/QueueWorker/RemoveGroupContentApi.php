<?php

namespace Drupal\eic_groups\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Defines 'eic_group_remove_content' queue worker.
 *
 * @QueueWorker(
 *   id = "eic_group_remove_content",
 *   title = @Translation("Task worker: Remove group content that has no more group parent"),
 *   cron = {"time" = 60}
 * )
 */
class RemoveGroupContentApi extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $node = Node::load($data);

    if (!$node instanceof NodeInterface) {
      return;
    }

    if ($node->bundle() === 'document') {
      $media = $node->get('field_document_media')->entity;

      if ($media instanceof MediaInterface) {
        // Loads up the entity usage for the media.
        $media_usage = \Drupal::service('entity_usage.usage')->listSources($media);

        $count = 0;

        foreach ($media_usage as $usage) {
          $count += count($usage);
        }

        if ($count <= 1) {
          $media->delete();
        }
      }
    }

    $node->delete();
  }

}
