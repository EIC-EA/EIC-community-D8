<?php

namespace Drupal\eic_content_book\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class BookTokens.
 *
 * Implementations of token hooks.
 */
class BookTokens implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new GroupTokens object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Implements hook_token_info().
   */
  public function tokenInfo() {
    $info = [];
    $info['tokens']['node'] = [
      'node_book_parent_url' => [
        'name' => $this->t('Node book parent url'),
        'description' => $this->t('The url of the parent book this node belongs to'),
      ],
    ];
    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];
    $base_url = $this->requestStack->getCurrentRequest()->getBaseUrl();

    if ($type === 'node' && !empty($data['node'])) {
      return $replacements;
    }

    // Replace node tokens.
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'node_book_parent_url':
          // Grabs the node object from the data.
          if (!empty($data['entity'])) {
            $node = $data['entity'];
          }
          elseif (!empty($data['node'])) {
            $node = $data['node'];
          }

          if (!$node instanceof NodeInterface) {
            break;
          }

          if (!$node->book['nid'] || $node->book['pid'] <= 0) {
            break;
          }

          /**
           * Loads the parent book node.
           *
           * @var \Drupal\node\NodeInterface $parent_book_node
           */
          $parent_book_node = $this->entityTypeManager->getStorage('node')
            ->load($node->book['pid']);

          if (!$parent_book_node) {
            break;
          }

          $parent_book_node_url = $parent_book_node->toUrl()->toString();

          // If base path is presented in the URL, we need to remove it
          // in order to avoid duplicated base paths.
          $has_base_path = substr($parent_book_node_url, 0, strlen($base_url)) === $base_url;
          $replacements[$original] = $has_base_path
            ? substr_replace($parent_book_node_url, '', 0, strlen($base_url))
            : $parent_book_node_url;
          break;

      }
    }

    return $replacements;
  }

}
