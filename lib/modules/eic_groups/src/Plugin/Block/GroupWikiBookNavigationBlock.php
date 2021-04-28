<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\book\BookManagerInterface;
use Drupal\book\Plugin\Block\BookNavigationBlock;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group_purl\Context\GroupPurlContext;
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
   * The group purl context.
   *
   * @var \Drupal\group_purl\Context\GroupPurlContext
   */
  protected $groupPurlContext;

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
   * @param \Drupal\group_purl\Context\GroupPurlContext $group_purl_context
   *   The group purl context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, BookManagerInterface $book_manager, EntityStorageInterface $node_storage, Connection $database, GroupPurlContext $group_purl_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $request_stack, $book_manager, $node_storage);
    $this->database = $database;
    $this->groupPurlContext = $group_purl_context;
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
      $container->get('group_purl.context_provider')
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
    if (!$this->groupPurlContext->getGroupFromRoute()) {
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

}
