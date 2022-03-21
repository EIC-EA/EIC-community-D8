<?php

namespace Drupal\eic_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\eic_content\TreeWidget\TreeWidgetProperties;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

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
    return new static(
      $container->get('eic_content.entity_tree_manager')
    );
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

    $options = [
      TreeWidgetProperties::OPTION_IGNORE_CURRENT_USER => $ignore_current_user,
    ];

    if ('taxonomy_term' !== $target_entity) {
      $tree_property = $this->treeManager->getTreeWidgetProperty($target_entity);

      $query = \Drupal::entityQuery($target_entity)
        ->condition('status', 1);

      if (!$load_all) {
        $query->range($offset, $length);
      }

      $query->sort($tree_property->getSortField(), 'ASC');
      $tree_property->generateExtraCondition($query, $options);
      $entities_id = $query->execute();
      $entities = $tree_property->loadEntities($entities_id);

      return new JsonResponse([
        'terms' => array_map(function (EntityInterface $entity) use ($tree_property) {
          return [
            'tid' => $entity->id(),
            'level' => 0,
            'parents' => [0],
            'depth' => 0,
            'name' => $tree_property->getLabelFromEntity($entity),
            'weight' => 0,
          ];
        }, $entities),
        'total' => count($entities),
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

  /**
   * Create a taxonomy term with given bundle, name.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createTaxonomyTerm(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    $target_bundle = $content['target_bundle'];
    $name = $content['name'];

    if (empty($target_bundle) || empty ($name)) {
      return new JsonResponse(
        [
          'message' => $this->t(
            'Missing required parameters: target_bundle, name',
            [],
            ['context' => 'eic_content']
          ),
          'error' => 1,
        ],
        Response::HTTP_BAD_REQUEST
      );
    }

    if (!$this->currentUser()->hasPermission("create terms in $target_bundle")) {
      return new JsonResponse(
        [
          'message' => $this->t(
            'You do not have the permission to create term in @vocabulary',
            ['@vocabulary' => $target_bundle],
            ['context' => 'eic_content']
          ),
          'error' => 1,
        ],
        Response::HTTP_BAD_REQUEST
      );
    }

    $results = $this->entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'vid' => $target_bundle,
      'name' => $name,
    ]);

    // We already have an entity with this label existing.
    if (!empty($results)) {
      return new JsonResponse(
        [
          'message' => $this->t(
            'Entity with name @name already exists',
            ['@name' => $name],
            ['context' => 'eic_content']
          ),
          'error' => 1,
        ],
        Response::HTTP_BAD_REQUEST
      );
    }

    try {
      $term = $this->entityTypeManager()->getStorage('taxonomy_term')->create([
        'name' => $name,
        'vid' => $target_bundle,
      ]);

      $term->save();
    } catch (EntityStorageException $e) {
      return new JsonResponse(
        [
          'message' => $this->t(
            '@name cannot be created due to this error: @error',
            ['@name' => $name, '@error' => $e->getMessage()],
            ['context' => 'eic_content']
          ),
          'error' => 1,
        ],
        Response::HTTP_BAD_REQUEST
      );
    }

    return new JsonResponse(
      [
        'message' => $this->t(
          '@name has been correctly created.',
          ['@name' => $name],
          ['context' => 'eic_content']
        ),
        'error' => 0,
        'result' => [
          'tid' => $term->id(),
          'level' => 0,
          'parents' => [0],
          'depth' => 0,
          'name' => $name,
          'weight' => 0,
        ],
      ],
      Response::HTTP_CREATED
    );
  }

}
