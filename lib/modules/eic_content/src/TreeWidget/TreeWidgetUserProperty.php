<?php

namespace Drupal\eic_content\TreeWidget;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\user\Entity\User;

class TreeWidgetUserProperty implements TreeWidgetProperties {

  /**
   * {@inheritdoc}
   */
  public function getSortField(): string {
    return 'field_first_name';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelFromEntity(EntityInterface $entity): string {
    return $entity->get('field_first_name')->value . ' ' . $entity->get(
        'field_last_name'
      )->value . ' ' . '(' . $entity->getEmail() . ')';
  }

  /**
   * {@inheritdoc}
   */
  public function generateExtraCondition(QueryInterface &$query, $options): void {
    $query->condition('uid', 0, '<>');
    $ignore_current_user = $options[TreeWidgetProperties::OPTION_IGNORE_CURRENT_USER];

    if ($ignore_current_user) {
      $andCondition = $query->andConditionGroup()
        ->condition('uid', 0, '<>')
        ->condition('uid', \Drupal::currentUser()->id(), '<>');

      $query->condition($andCondition);
    }
    else {
      $query->condition('uid', 0, '<>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntities(array $ids): array {
    return User::loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function generateSearchQueryResults(string $search_text): array {
    $connection = Database::getConnection();

    $query = $connection->select('users_field_data', 'fd');
    $query->join('realname', 'rn', 'fd.uid = rn.uid');
    $query->fields('rn', ['realname']);
    $query->fields('fd', ['uid', 'mail']);

    if (!empty($ignored_tids)) {
      $query->condition('fd.uid', $ignored_tids, 'NOT IN');
    }

    $query->distinct(TRUE);

    $like_match = '%' . $query->escapeLike($search_text) . '%';

    $orCondition = $query->orConditionGroup()
      ->condition('fd.mail', $like_match, 'LIKE')
      ->condition('rn.realname', $like_match, 'LIKE');

    $query->condition($orCondition);

    $entities = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    return array_map(function ($user) {
      return [
        'name' => $user['realname'] . ' ' . '(' . $user['mail'] . ')',
        'tid' => $user['uid'],
        'parent' => 0,
      ];
    }, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function formatPreselection(array $entities): string {
    return json_encode(
      array_map(function (EntityInterface $entity) {
        return [
          'name' =>  realname_load($entity) . ' ' . '(' . $entity->getEmail() . ')',
          'tid' => $entity->id(),
          'parent' => 0,
        ];
      }, $entities)
    );
  }

}
