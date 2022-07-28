<?php

namespace Drupal\eic_content_book\Plugin\Block;

use Drupal\book\BookManagerInterface;
use Drupal\book\Plugin\Block\BookNavigationBlock;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\eic_content\EICContentHelperInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Book navigation' block.
 *
 * @Block(
 *   id = "eic_content_book_navigation",
 *   admin_label = @Translation("EIC Content Book navigation"),
 *   category = @Translation("European Innovation Council")
 * )
 */
class EICBookNavigationBlock extends BookNavigationBlock {

  /**
   * The EIC content helper.
   *
   * @var \Drupal\eic_content\EICContentHelperInterface
   */
  protected $eicContentHelper;

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
   * @param \Drupal\eic_content\EICContentHelperInterface $eic_content_helper
   *   The EIC content helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, BookManagerInterface $book_manager, EntityStorageInterface $node_storage, EICContentHelperInterface $eic_content_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_match, $book_manager, $node_storage);
    $this->eicContentHelper = $eic_content_helper;
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
      $container->get('eic_content.helper')
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
    // If there wasn't any node found in the route context, we do nothing.
    if (!$node = $this->routeMatch->getParameter('node')) {
      return [];
    }

    if (!($node instanceof NodeInterface)) {
      return [];
    }

    if ($node->bundle() !== 'book') {
      return [];
    }

    // Ignore book page that belongs to a group.
    if ($this->eicContentHelper->getGroupContentByEntity($node)) {
      return [];
    }

    // Ignore book pages that don't have a bid.
    if (empty($node->book['bid'])) {
      return [];
    }

    $data = $this->bookManager->bookTreeAllData($node->book['bid']);
    $book_data = reset($data);

    if (empty($book_data['below'])) {
      return [];
    }

    // Ignore top level book page from the menu data.
    $book_data_below = $book_data['below'];
    // Get book menu renderable array.
    $book_menu = [$this->bookManager->bookTreeOutput($book_data_below)];

    return [
      '#theme' => 'book_all_books_block',
    ] + $book_menu;
  }

}
