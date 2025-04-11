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

    $query = $this->connection->select('profile__field_body', 'pfb')
      ->condition('pfb.entity_id', $uid);
    $query->addField('pfb', 'field_body_value');
    $query->join('profile__field_vocab_topic_expertise', 'pvte', 'pfb.entity_id = pvte.entity_id');
    $query->join('profile__field_vocab_topic_interest', 'pvti', 'pfb.entity_id = pvti.entity_id');
    $query->join('profile__field_location_address', 'pla', 'pfb.entity_id = pla.entity_id');
    $query->fields('pvte', ['field_vocab_topic_expertise_target_id']);
    $query->fields('pvti', ['field_vocab_topic_interest_target_id']);
    $query->fields('pla', ['field_location_address_address_line1']);

    $result = $query->execute()->fetchAll();
    $is_completed = FALSE;
    foreach ($result as $field) {
      if (!empty($field)) {
        $is_completed = TRUE;
      }
      else {
        $is_completed = FALSE;
      }
    }
    if ($is_completed) {
      return $this->t("Completed");
    }
    return $this->t("Incomplete");
  }

}
