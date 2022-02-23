<?php

namespace Drupal\eic_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityTreeController
 *
 * @package Drupal\eic_content\Controller
 */
class EntityTreeController extends ControllerBase {

  /** @var \Drupal\eic_content\Services\EntityTreeManager */
  private $treeManager;

  /**
   * EntityTreeController constructor.
   *
   * @param \Drupal\eic_content\Services\EntityTreeManager $tree_manager
   */
  public function __construct($tree_manager) {
    $this->treeManager = $tree_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\eic_content\Controller\EntityTreeController|static
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('eic_content.entity_tree_manager'));
  }

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
    $target_entity = $request->query->get('targetEntity');
    $target_bundle = $request->query->get('targetBundle');
    // This will check if we need to split result items
    $load_all = (int) $request->query->get('loadAll', 0);
    $ignore_current_user = (int) $request->query->get('ignoreCurrentUser', 0);

    if ('user' === $target_entity) {
      $query = \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->condition('uid', 0, '<>');

      if ($ignore_current_user) {
        $andCondition = $query->andConditionGroup()
          ->condition('uid', 0, '<>')
          ->condition('uid', $this->currentUser()->id(), '<>');

        $query->condition($andCondition);
      } else {
        $query->condition('uid', 0, '<>');
      }

      if (!$load_all) {
        $query->range($offset, $length);
      }

      $query->sort('field_first_name', 'ASC');

      $user_ids = $query->execute();

      $users = User::loadMultiple($user_ids);
      return new JsonResponse([
        'terms' => array_map(function(UserInterface $user) {
          return [
            'tid' => $user->id(),
            'level' => 0,
            'parents' => [0],
            'depth' => 0,
            'name' => $user->get('field_first_name')->value . ' ' . $user->get('field_last_name')->value . ' ' . '('. $user->getEmail() .')',
            'weight' => 0,
          ];
        }, $users),
        'total' => count($user_ids),
      ]);
    }

    return new JsonResponse([
      'terms' => $this->treeManager->generateTree($target_entity, $target_bundle, $load_all, $offset, $length),
      'total' => \Drupal::entityTypeManager()
        ->getStorage($target_entity)
        ->getQuery()
        ->condition('vid', $target_bundle)
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
    $target_entity = $request->query->get('targetEntity');
    $target_bundle = $request->query->get('targetBundle');

    return new JsonResponse([
      'terms' => $this->treeManager->loadChildrenLevel($target_entity, $target_bundle, $parent, $level),
    ]);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function search(Request $request) {
    $text = $request->query->get('search_text', '');
    $selected_values = $request->query->get('values', []);
    $target_entity = $request->query->get('targetEntity');
    $target_bundle = $request->query->get('targetBundle');
    $disable_top = (bool) $request->query->get('disableTop', FALSE);

    return new JsonResponse(
      $this->treeManager->search($target_entity, $target_bundle, $text, $selected_values, $disable_top)
    );
  }

}
