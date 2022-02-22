<?php

namespace Drupal\eic_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * @Block(
 *   id = "eic_user_activity_subnavigation_block",
 *   admin_label = @Translation("EIC User Activity Subnavigation"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class UserActivitySubnavigationBlock extends BlockBase {

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
        'route' => 'eic_user.my_profile_activity',
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
      return [
        'link' => [
          'label' => $item['label'],
          'path' => Url::fromRoute($item['route'])->toString(),
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
