<?php

namespace Drupal\eic_content_book\Plugin\Block;

use Drupal\book\BookManagerInterface;
use Drupal\book\Plugin\Block\BookNavigationBlock;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_content\EICContentHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   * @param \Drupal\eic_content\EICContentHelperInterface $eic_content_helper
   *   The EIC content helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, BookManagerInterface $book_manager, EntityStorageInterface $node_storage, EICContentHelperInterface $eic_content_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $request_stack, $book_manager, $node_storage);
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
      $container->get('request_stack'),
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
    if (!$node = $this->requestStack->getCurrentRequest()->get('node')) {
      return [];
    }

    if ($node->bundle() !== 'book') {
      return [];
    }

    // Ignore book page that belongs to a group.
    if ($this->eicContentHelper->getGroupContentByEntity($node)) {
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
