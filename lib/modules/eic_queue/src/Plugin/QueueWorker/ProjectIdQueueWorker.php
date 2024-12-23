<?php

namespace Drupal\eic_queue\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = "extraction_request_project_id",
 *   title = @Translation("Project Id for Extraction queue worker"),
 * )
 */
class ProjectIdQueueWorker extends QueueWorkerBase {

  public function processItem($data) {
    // TODO: Implement processItem() method.
  }

}
