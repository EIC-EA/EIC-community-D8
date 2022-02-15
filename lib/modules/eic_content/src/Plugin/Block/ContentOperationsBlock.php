<?php

namespace Drupal\eic_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * ContentOperationsBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
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
  public function defaultConfiguration() {
    return [
      'title' => '',
      'description' => [
        'value' => NULL,
        'format' => 'basic_text',
      ],
      'show_user_activity_feed_link' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title display'),
      '#default_value' => $this->configuration['title'],
      '#description' => $this->t('Text to be displayed as title when viewing the block.'),
    ];
    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#format' => $this->configuration['description']['format'],
      '#default_value' => $this->configuration['description']['value'],
    ];
    $form['show_user_activity_feed_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show user activity feed link'),
      '#default_value' => $this->configuration['show_user_activity_feed_link'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['description'] = $form_state->getValue('description');
    $this->configuration['show_user_activity_feed_link'] = $form_state->getValue('show_user_activity_feed_link');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $supported_entities = [
      'group' => [
        'add_route' => function ($entity, $bundle) {
          return Url::fromRoute('entity.group.add_form', ['group_type' => $bundle]);
        },
        'bundles' => [
          'group',
          'event',
        ],
      ],
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
    ];

    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->setCacheContexts([
      'user.permissions',
    ]);

    $items = [];
    foreach ($supported_entities as $entity_id => $config) {
      $access_handler = $this->entityTypeManager->getAccessControlHandler($entity_id);

      switch ($entity_id) {
        case 'group':
          $storage_id = 'group_type';
          $label_field = 'label';
          break;

        case 'node':
        default:
          $storage_id = 'node_type';
          $label_field = 'name';
          break;

      }
      $entity_storage = $this->entityTypeManager->getStorage($storage_id);

      if (isset($config['bundles'])) {
        foreach ($config['bundles'] as $bundle) {
          if ($access_handler->createAccess($bundle)) {
            $url = is_callable($config['add_route'])
              ? call_user_func($config['add_route'], $entity_id, $bundle)
              : Url::fromRoute($config['add_route']);

            $bundle_label = $entity_storage->load($bundle)->get($label_field);
            $items[] = [
              'title' => $this->t("Add @bundle", ['@bundle' => $bundle_label]),
              'url' => $url->toString(),
            ];
          }
        }

        continue;
      }

      if ($access_handler->createAccess()) {
        $items[] = [
          'title' => $this->t("Add @entity_id", ['@entity_id' => $entity_id]),
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

    // Add title field to the renderable array.
    if (!empty($this->configuration['title'])) {
      $build['#title'] = $this->configuration['title'];
    }

    // Add description field to the renderable array.
    if (!empty($this->configuration['description']['value'])) {
      $build['#description'] = $this->configuration['description']['value'];
    }

    // Add user's activity feed link to the renderable array.
    if ($this->configuration['show_user_activity_feed_link']) {
      $user_feed_link = Url::fromRoute(
        'entity.user.canonical',
        [
          'user' => $this->currentUser->id(),
        ]
      );
      $build['#user_activity_feed_link']['link'] = [
        'label' => $this->t('My activity feed'),
        'path' => $user_feed_link->toString(),
      ];
    }

    $cacheable_metadata->applyTo($build);

    return $build;
  }

}
