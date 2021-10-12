<?php

namespace Drupal\eic_content\Services;

use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Class EntityTreeManager
 *
 * @package Drupal\eic_content\Services
 */
class EntityTreeManager {

  /**
   * @param $target_entity
   * @param $target_bundle
   * @param false $load_all
   * @param int $offset
   * @param int $length
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function generateTree($target_entity, $target_bundle, $load_all = FALSE, $offset = 0, $length = 50) {
    $terms = \Drupal::entityTypeManager()->getStorage($target_entity)
      ->loadTree($target_bundle, 0, 1);

    if (!$load_all) {
      $terms = array_slice($terms, $offset, $length);
    }

    $tree = [];

    foreach ($terms as $tree_object) {
      $this->buildTree($tree, $tree_object, $target_bundle, 1, 0, $target_entity);
    }

    return $tree;
  }

  /**
   * @param $target_entity
   * @param $target_bundle
   * @param $parent
   * @param $level
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadChildrenLevel($target_entity, $target_bundle, $parent, $level) {
    $children = \Drupal::entityTypeManager()->getStorage($target_entity)
      ->loadTree($target_bundle, $parent, 1);

    foreach ($children as &$child) {
      $child->children = \Drupal::entityTypeManager()
        ->getStorage($target_entity)
        ->loadTree($target_bundle, $child->tid, 1);

      $child->level = $level + 1;
    }

    return $children;
  }

  /**
   * @param $target_entity
   * @param $target_bundle
   * @param $text
   * @param $ignored_values
   * @param bool $disable_top_selection
   *
   * @return array|array[]
   */
  public function search($target_entity, $target_bundle, $text, $ignored_values, bool $disable_top_selection = FALSE) {
    //We need to ignore values in suggestions that are already selected by the user
    $ignored_tids = array_map(function($selected_value) {
      $selected_value = json_decode($selected_value, TRUE);

      return $selected_value['tid'];
    }, $ignored_values);

    if ($target_entity === 'user') {
      $query = \Drupal::entityQuery($target_entity)
        ->condition('uid', 0, '<>')
        ->range(0, 20);
      $orCondition = $query->orConditionGroup()
        ->condition('field_first_name', $text, 'CONTAINS')
        ->condition('field_last_name', $text, 'CONTAINS')
        ->condition('mail', $text, 'CONTAINS');
      $query->condition($orCondition);

      if (!empty($ignored_tids)) {
        $query->condition('uid', $ignored_tids, 'NOT IN');
      }

      $query->sort('field_first_name', 'ASC');

      $entities = $query->execute();
      $entities = User::loadMultiple($entities);

      return array_map(function (UserInterface $user) {
        return [
          'name' => $user->get('field_first_name')->value . ' ' . $user->get('field_last_name')->value . ' ' . '('. $user->getEmail() .')',
          'tid' => $user->id(),
          'parent' => 0,
        ];
      }, $entities);
    } else {
      $query = \Drupal::entityQuery($target_entity)
        ->condition('vid', $target_bundle)
        ->condition('name', $text, 'CONTAINS')
        ->range(0, 20);

      if ($disable_top_selection) {
        $query->condition('parent', 0, '<>');
      }

      if (!empty($ignored_tids)) {
        $query->condition('tid', $ignored_tids, 'NOT IN');
      }

      $entities = $query->execute();
      $entities = Term::loadMultiple($entities);

      return array_map(function (Term $term) {
        $parent = $term->get('parent')->getValue();

        return [
          'name' => $term->getName(),
          'tid' => $term->id(),
          'parent' => (int) reset($parent)['target_id'],
        ];
      }, $entities);
    }
  }

  /**
   * @param $tree
   * @param $object
   * @param $vocabulary
   * @param $depth
   * @param $level
   * @param $target_entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function buildTree(&$tree, $object, $vocabulary, $depth, $level, $target_entity) {
    if ($object->depth !== 0) {
      return;
    }
    $tree[$object->tid] = $object;
    $tree[$object->tid]->children = [];
    $tree[$object->tid]->level = $level;
    $object_children = &$tree[$object->tid]->children;

    $children = \Drupal::entityTypeManager()->getStorage($target_entity)
      ->loadChildren($object->tid);

    if (!$children) {
      return;
    }

    $level += 1;

    $child_tree_objects = \Drupal::entityTypeManager()
      ->getStorage($target_entity)
      ->loadTree($vocabulary, $object->tid);

    foreach ($children as $child) {
      foreach ($child_tree_objects as $child_tree_object) {
        if ($child_tree_object->tid == $child->id()) {
          $this->buildTree($object_children, $child_tree_object, $vocabulary, $depth, $level, $target_entity);
        }
      }
    }
  }
}
