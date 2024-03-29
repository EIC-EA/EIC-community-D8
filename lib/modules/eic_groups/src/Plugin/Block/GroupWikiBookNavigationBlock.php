<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\book\BookManagerInterface;
use Drupal\book\Plugin\Block\BookNavigationBlock;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The EIC groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  protected $eicGroupsHelper;

  /**
   * Constructs a new BookNavigationBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $eic_groups_helper
   *   The EIC groups helper service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, BookManagerInterface $book_manager, EntityStorageInterface $node_storage, Connection $database, EICGroupsHelperInterface $eic_groups_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_match, $book_manager, $node_storage);
    $this->database = $database;
    $this->eicGroupsHelper = $eic_groups_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('book.manager'),
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('database'),
      $container->get('eic_groups.helper')
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
    // If group was not found in the route context, we do nothing.
    if (!$this->eicGroupsHelper->getGroupFromRoute()) {
      return [];
    }

    // If there wasn't any node found in the route context, we do nothing.
    if (!$node = $this->routeMatch->getParameter('node')) {
      return [];
    }

    if (!($node instanceof NodeInterface)) {
      return [];
    }

    if ($node->bundle() !== 'wiki_page') {
      return [];
    }

    $data = $this->bookManager->bookTreeAllData($node->book['bid']);
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

}
