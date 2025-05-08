<?php

namespace Drupal\eic_dashboards\Plugin\views\field;


use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show number of content flag likes.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("content_flag_likes")
 */
class ContentFlagLike extends FieldPluginBase {

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

  /**
   * @inheritdoc
   */
  public function render(ResultRow $values) {
    $string = 'N/A';
    $nid = $values->node_field_data_group_content_field_data_nid;

    if ($nid) {
      $query = $this->connection->select('flag_counts', 'fc');
      $query->addField('fc', 'count');
      $query->condition('fc.entity_type', 'node')
        ->condition('fc.entity_id', $nid)
        ->condition('fc.flag_id', 'like_content');

      $results = $query->execute()->fetchAssoc();
      if (!empty($results)) {
        $string = $results['count'];
      }
    }

    return $string;

  }

}
