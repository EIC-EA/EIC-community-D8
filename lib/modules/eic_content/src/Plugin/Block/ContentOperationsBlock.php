<?php

namespace Drupal\eic_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an ContentOperations block.
 *
 * @Block(
 *   id = "eic_content_operations",
 *   admin_label = @Translation("EIC Content operations"),
 *   category = @Translation("European Innovation Council")
 * )
 */
class ContentOperationsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * ContentOperationsBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $account
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $supported_entities = [
      'node' => [
        'add_route' => function ($entity, $bundle) {
          return Url::fromRoute('node.add', ['node_type' => $bundle]);
        },
        'bundles' => [
          'story',
          'news',
          'page',
        ],
      ],
      'group' => [
        'add_route' => 'entity.group.add_page',
        'bundles' => [
          'group',
        ],
      ],
    ];

    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->setCacheContexts([
      'user.permissions',
    ]);

    $items = [];
    foreach ($supported_entities as $entity_id => $config) {
      $access_handler = $this->entityTypeManager->getAccessControlHandler($entity_id);

      if (isset($config['bundles'])) {
        foreach ($config['bundles'] as $bundle) {
          if ($access_handler->createAccess($bundle)) {
            $url = is_callable($config['add_route'])
              ? call_user_func($config['add_route'], $entity_id, $bundle)
              : Url::fromRoute($config['add_route']);

            $items[] = [
              'title' => $this->t("Add $bundle"),
              'url' => $url->toString(),
            ];
          }
        }

        continue;
      }

      if ($access_handler->createAccess()) {
        $items[] = [
          'title' => $this->t("Add $entity_id"),
          'url' => Url::fromRoute($config['add_route'])->toString(),
        ];
      }
    }

    if (empty($items)) {
      return $build;
    }

    $build = [
      '#theme' => 'eic_content_actions',
      '#actions' => [
        [
          'label' => $this->t('Add content'),
          'links' => $items,
        ],
      ],
    ];

    $cacheable_metadata->applyTo($build);

    return $build;
  }

}
