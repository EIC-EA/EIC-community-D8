<?php

namespace Drupal\eic_projects\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CronOperations implements ContainerInjectionInterface {

  /**
   * The queue factory
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  public function __construct(QueueFactory $queueFactory, EntityTypeManager $entityTypeManager) {
    $this->queueFactory = $queueFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('entity_type.manager'),
    );
  }

  public function cron() {
    $queue = $this->queueFactory->get('extraction_request_project_id');

    $total_items = $queue->numberOfItems();
    $query = '';
    for ($i = 0; $i < $total_items; $i++) {
      $item = $queue->claimItem();
      if ($item) {
        $project_id = $item->data->project_id;
        if (!empty($project_id)) {
          $query .= "'$project_id',";

          if (($i != 0) && ($i % 10 === 0)) {
            $query = rtrim($query, ',');
            $values = [
              'label' => $query,
              'extraction_status' => 'requested',
              'query' => "/project/id==" . $query,
              'uid' => 1
            ];
            $extraction_request = $this->entityTypeManager
              ->getStorage('extraction_request')->create($values);
            $extraction_request->save();

            // Reset query string to start over.
            $query = '';
          }
        }
      }

      $queue->deleteItem($item);
    }
  }

}
