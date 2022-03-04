<?php

namespace Drupal\eic_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\eic_user\UserHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id = "eic_user_activity_subnavigation_block",
 *   admin_label = @Translation("EIC User Activity Subnavigation"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class UserActivitySubnavigationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account_proxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $account_proxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * The profile header block.
   *
   * @inheritDoc
   */
  public function build() {
    $current_route = \Drupal::routeMatch()->getRouteName();

    /** @TODO Wait for other pages/overviews and replace default eic_search.global_search */
    $menu_data = [
      [
        'label' => $this->t('Interesting for you', [], ['context' => 'eic_user']),
        'route' => 'eic_user.user.activity',
        'route_parameters' => ['user' => $this->currentUser->id()],
      ],
      [
        'label' => $this->t('Following', [], ['context' => 'eic_user']),
        'route' => 'eic_search.global_search',
      ],
      [
        'label' => $this->t('Bookmarked', [], ['context' => 'eic_user']),
        'route' => 'eic_search.global_search',
      ],
      [
        'label' => $this->t('Drafts', [], ['context' => 'eic_user']),
        'route' => 'eic_search.global_search',
      ],
    ];

    $menu_items = array_map(function (array $item) use ($current_route) {
      $route_parameters = array_key_exists('route_parameters', $item) ?
        $item['route_parameters'] :
        [];

      return [
        'link' => [
          'label' => $item['label'],
          'path' => Url::fromRoute($item['route'], $route_parameters)->toString(),
        ],
        'is_active' => $current_route === $item['route'],
      ];
    }, $menu_data);

    return [
      '#theme' => 'user_activity_subnavigation_block',
      '#menu_items' => ['items' => $menu_items],
    ];
  }

}
