<?php

namespace Drupal\eic_dashboards\Plugin\views\field;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FlagPluginBase extends FieldPluginBase {

  public function __construct($configuration, $plugin_id, $plugin_definition, private readonly Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * @inheritDoc
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  public function getFlagResults($entity_id, $entity_type) {
    $flag_id = str_replace('flag_','', $this->getPluginId());

    $query = $this->connection->select('flag_counts', 'fc');
    $query->addField('fc', 'count');
    $query->condition('fc.entity_type', $entity_type)
      ->condition('fc.entity_id', $entity_id)
      ->condition('fc.flag_id', $flag_id);

    $results = $query->execute()->fetchAssoc();
    if (!empty($results)) {
      return $results['count'];
    }
    return 'N/A';

  }
}
