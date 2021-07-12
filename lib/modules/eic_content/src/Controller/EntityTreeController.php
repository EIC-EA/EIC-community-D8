<?php

namespace Drupal\eic_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EntityTreeController
 *
 * @package Drupal\eic_content\Controller
 */
class EntityTreeController extends ControllerBase {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function tree(Request $request) {
    $offset = $request->query->get('offset', 0);
    $length = $request->query->get('length', 25);
    // This will check if we need to split result items
    $loadAll = (int) $request->query->get('loadAll', 0);

    $vocabulary = 'topics';

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadTree('topics', 0, 1);

    if (!$loadAll) {
      $terms = array_slice($terms, $offset, $length);
    }
    $tree = [];

    foreach ($terms as $tree_object) {
      $this->buildTree($tree, $tree_object, $vocabulary, 1, 0);
    }

    return new JsonResponse([
      'terms' => $terms,
      'total' => \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->getQuery()
        ->count()
        ->execute(),
    ]);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadChildren(Request $request) {
    $parent = $request->query->get('parent_term', NULL);
    $level = $request->query->get('level', 0);

    $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadTree('topics', $parent, 1);

    foreach ($children as &$child) {
      $child->children = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadTree('topics', $child->tid, 1);

      $child->level = $level + 1;
    }

    return new JsonResponse([
      'terms' => $children,
    ]);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function search(Request $request) {
    $text = $request->query->get('search_text', '');

    $entities = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', $text, 'CONTAINS')
      ->range(0, 10)
      ->execute();

    $entities = Term::loadMultiple($entities);

    return new JsonResponse(
      array_map(function (Term $term) {
        $parent = $term->get('parent')->getValue();

        return [
          'name' => $term->getName(),
          'tid' => $term->id(),
          'parent' => (int) reset($parent)['target_id'],
        ];
      }, $entities)
    );
  }

  /**
   * @param $tree
   * @param $object
   * @param $vocabulary
   * @param $depth
   * @param $level
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildTree(&$tree, $object, $vocabulary, $depth, $level) {
    if ($object->depth !== 0) {
      return;
    }
    $tree[$object->tid] = $object;
    $tree[$object->tid]->children = [];
    $tree[$object->tid]->level = $level;
    $object_children = &$tree[$object->tid]->children;

    $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadChildren($object->tid);
    if (!$children) {
      return;
    }

    $level += 1;

    $child_tree_objects = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vocabulary, $object->tid);

    foreach ($children as $child) {
      foreach ($child_tree_objects as $child_tree_object) {
        if ($child_tree_object->tid == $child->id()) {
          $this->buildTree($object_children, $child_tree_object, $vocabulary, $depth, $level);
        }
      }
    }
  }

}
