<?php

namespace Drupal\eic_dashboards\Plugin\views\field;


use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_organisation_group_memberships")
 */
class UserOrganisationGroupMembership extends FieldPluginBase {

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
    $string = '';

    $query = $this->connection->select('group_content_field_data', 'gcfd')
      ->condition('gcfd.entity_id', $uid)
      ->condition('gcfd.type', 'organisation-group_membership')
      ->range(0, 10);
    $query->addField('gcfd', 'gid');
    $query->join('groups_field_data', 'gfd', 'gcfd.gid = gfd.id');
    $query->addField('gfd', 'label');
    $results = $query->execute()->fetchAllAssoc('gid');
    if (!empty($results)) {
      foreach ($results as $result) {
        $url = Url::fromRoute('entity.group.canonical', ['group' => $result->gid])->toString();
        $string .= "<a href='$url'>$result->label</a><br>";
      }
      $string = substr($string, 0, -4);
    }
    else {
      $string = "This user does not belong to any organisation.";
    }

    return $this->t($string);

  }

}
