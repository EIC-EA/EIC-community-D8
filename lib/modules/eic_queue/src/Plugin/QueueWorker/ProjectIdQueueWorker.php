<?php

namespace Drupal\eic_queue\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * @QueueWorker(
 *   id = "extraction_request_project_id",
 *   title = @Translation("Project Id for Extraction queue worker"),
 *   cron = {"time" = 60}
 * )
 */
class ProjectIdQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

}