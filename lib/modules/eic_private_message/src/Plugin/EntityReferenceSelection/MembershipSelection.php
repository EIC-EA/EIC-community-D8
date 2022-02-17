<?php

namespace Drupal\eic_private_message\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\user\Entity\User;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:group_membership",
 *   label = @Translation("Node by field selection"),
 *   entity_types = {"user"},
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

    // If no gid in filter settings, it's the default user selection.
    if (!array_key_exists('gid', $filter_settings)) {
      $query = parent::buildEntityQuery($match, $match_operator);
      $configuration = $this->getConfiguration();

      // Filter out the Anonymous user if the selection handler is configured to
      // exclude it.
      if (!$configuration['include_anonymous']) {
        $query->condition('uid', 0, '<>');
      }

      // The user entity doesn't have a label column.
      if (isset($match)) {
        $query->condition('name', $match, $match_operator);
      }

      // Filter by role.
      if (!empty($configuration['filter']['role'])) {
        $query->condition('roles', $configuration['filter']['role'], 'IN');
      }

      // Adding the permission check is sadly insufficient for users: core
      // requires us to also know about the concept of 'blocked' and 'active'.
      if (!$this->currentUser->hasPermission('administer users')) {
        $query->condition('status', 1);
      }

      return $query;
    }

    $connection = Database::getConnection();

    $query = $connection->select('group_content_field_data', 'gc');
    $query->join('users_field_data', 'user_fd', 'gc.entity_id = user_fd.uid');
    $query->join('realname', 'rn', 'gc.entity_id = rn.uid');
    $query->condition('gc.gid', $filter_settings['gid']);
    $query->condition('gc.type', 'group-group_membership');
    $query->fields('gc', ['entity_id']);
    $query->distinct(TRUE);

    $like_match = '%' . $query->escapeLike($match) . '%';

    $orCondition = $query->orConditionGroup()
    ->condition('user_fd.mail',  $like_match, 'LIKE')
    ->condition('rn.realname', $like_match, 'LIKE');

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
