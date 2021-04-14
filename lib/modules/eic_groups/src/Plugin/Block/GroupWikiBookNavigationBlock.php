<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\book\BookManagerInterface;
use Drupal\book\Plugin\Block\BookNavigationBlock;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Book navigation' block.
 *
 * @Block(
 *   id = "eic_groups_wiki_book_navigation",
 *   admin_label = @Translation("EIC Group Wiki Book navigation"),
 *   category = @Translation("European Innovation Council")
 * )
 */
class GroupWikiBookNavigationBlock extends BookNavigationBlock {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new BookNavigationBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, BookManagerInterface $book_manager, EntityStorageInterface $node_storage, Connection $database, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $request_stack, $book_manager, $node_storage);
    $this->database = $database;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('book.manager'),
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('database'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!$this->getGroupFromRoute()) {
      return [];
    }

    if (!$node = $this->requestStack->getCurrentRequest()->get('node')) {
      return [];
    }

    if ($node->bundle() !== 'wiki_page') {
      return [];
    }

    $data = $this->bookManager->bookTreeAllData($node->book['bid'], $node->book);
    $book_data = reset($data);

    if (empty($book_data['below'])) {
      return [];
    }

    // Ignore top level book page from the menu data.
    $wiki_pages_data = $book_data['below'];
    // Get book menu renderable array.
    $book_menu = [$this->bookManager->bookTreeOutput($wiki_pages_data)];

    return [
      '#theme' => 'book_all_books_block',
    ] + $book_menu;
  }

  /**
   * Get the group from the current route match.
   *
   * @return bool|\Drupal\group\Entity\GroupInterface
   *   The Group entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getGroupFromRoute() {
    $entity = FALSE;
    $parameters = $this->routeMatch->getParameters()->all();
    if (!empty($parameters['group']) && is_numeric($parameters['group'])) {
      $group = Group::load($parameters['group']);
      return $group;
    }
    if (!empty($parameters)) {
      foreach ($parameters as $parameter) {
        if ($parameter instanceof EntityInterface) {
          $entity = $parameter;
          break;
        }
      }
    }
    if ($entity) {
      return $this->getGroupByEntity($entity);
    }
    return FALSE;
  }

  /**
   * Get Group of a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity.
   *
   * @return bool|\Drupal\group\Entity\GroupInterface
   *   The Group entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getGroupByEntity(EntityInterface $entity) {
    $group = FALSE;
    if ($entity instanceof GroupInterface) {
      return $entity;
    }
    elseif ($entity instanceof NodeInterface) {
      // Load all the group content for this entity.
      $group_content = GroupContent::loadByEntity($entity);
      // Assuming that the content can be related only to 1 group.
      $group_content = reset($group_content);
      if (!empty($group_content)) {
        $group = $group_content->getGroup();
      }
    }
    return $group;
  }

}
