<?php

namespace Drupal\eic_dashboards\Plugin\views\field;


use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_profile_completion_status")
 */
class UserProfileCompletionStatus extends FieldPluginBase {

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
    $uid = $values->uid;

    $query = $this->connection->select('profile', 'p')
      ->condition('p.uid', $uid)
      ->fields('p', ['uid', 'profile_id']);
    $query->join('profile__field_vocab_topic_expertise', 'pvte', 'p.profile_id = pvte.entity_id');
    $query->join('profile__field_vocab_topic_interest', 'pvti', 'p.profile_id = pvti.entity_id');
    $query->join('profile__field_location_address', 'pla', 'p.profile_id = pla.entity_id');

    $result = $query->execute()->fetchAll();
    if (empty($result)) {
      return $this->t("Incomplete");
    }
    else {
      return $this->t("Completed");
    }

  }

}
