<?php

namespace Drupal\eic_private_message\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Annotation\EntityReferenceSelection;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\user\Entity\User;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:group_membership",
 *   label = @Translation("Node by field selection"),
 *   entity_types = {"node"},
 *   group = "default",
 *   weight = 3
 * )
 */
class MembershipSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   *
   * Need to use complex query to join group content to user fields data.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $filter_settings = $this->configuration['filter'] ?? [];

    $connection = Database::getConnection();

    $query = $connection->select('group_content_field_data', 'gc');
    $query->join('users_field_data', 'user_fd', 'gc.entity_id = user_fd.uid');
    $query->join('user__field_first_name', 'user_fn', 'gc.entity_id = user_fn.entity_id');
    $query->join('user__field_last_name', 'user_ln', 'gc.entity_id = user_ln.entity_id');
    $query->condition('gc.gid', $filter_settings['gid']);
    $query->condition('gc.type', 'group-group_membership');
    $query->fields('gc', ['entity_id']);
    $query->distinct(TRUE);

    $like_match = '%' . $query->escapeLike($match) . '%';

    $orCondition = $query->orConditionGroup()
    ->condition('user_fd.mail',  $like_match, 'LIKE')
    ->condition('user_fn.field_first_name_value', $like_match, 'LIKE')
    ->condition('user_ln.field_last_name_value', $like_match, 'LIKE');

    $query->condition($orCondition);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $query = $this->buildEntityQuery($match, $match_operator);
    $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $results = array_map(function ($item) {
      return $item['entity_id'];
    }, $results);

    if (empty($results)) {
      return [];
    }

    $options = [];
    $entities = User::loadMultiple($results);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = realname_load($entity) . ' [' . $entity->getEmail() . ']';
    }

    return $options;
  }

}
