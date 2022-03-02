<?php

namespace Drupal\eic_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;

/**
 * Provides a ActivityStreamBlock block.
 *
 * @Block(
 *   id = "eic_user_my_profile_header",
 *   admin_label = @Translation("EIC my profile header"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class MyProfileHeaderBlock extends BlockBase {

  /**
   * The profile header block.
   *
   * @inheritDoc
   */
  public function build() {
    $menu_items_url = [
      [
        'link' => [
          'path' => Url::fromRoute('entity.group.add_form', ['group_type' => 'event']),
          'label' => $this->t('New event', [], ['context' => 'eic_user']),
        ],
      ],
      [
        'link' => [
          'path' => Url::fromRoute('entity.group.add_form', ['group_type' => 'group']),
          'label' => $this->t('New group', [], ['context' => 'eic_user']),
        ],
      ],
      [
        'link' => [
          'path' => Url::fromRoute('node.add', ['node_type' => 'story']),
          'label' => $this->t('New story', [], ['context' => 'eic_user']),
        ],
      ],
      [
        'link' => [
          'path' => Url::fromRoute('node.add', ['node_type' => 'news']),
          'label' => $this->t('New news article', [], ['context' => 'eic_user']),
        ],
      ],
    ];

    $menu_items_url = array_filter($menu_items_url, function (array $item) {
      return $item['link']['path']->access();
    });

    $current_user = \Drupal::currentUser();
    $user = User::load($current_user->id());

    // Loads user member profile.
    $member_profile = \Drupal::service('eic_user.helper')->getUserMemberProfile($user);
    $current_route = \Drupal::routeMatch()->getRouteName();

    /** @TODO Wait for other pages/overviews and replace default eic_search.global_search */
    $menu_data = [
      [
        'label' => $this->t('Interesting for you', [], ['context' => 'eic_user']),
        'route' => 'eic_user.user.activity',
        'route_parameters' => ['user' => $current_user->id()]
      ],
      [
        'label' => $this->t('Following', [], ['context' => 'eic_user']),
        'route' => 'eic_user.user.following',
        'route_parameters' => ['user' => $current_user->id()]
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

    $menu_items = array_map(function(array $item) use ($current_route) {
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
      '#theme' => 'my_profile_header_block',
      '#cache' => ['contexts' => ['url.path', 'url.query_args']],
      '#title' => $this->t('My activity feed', [], ['context' => 'eic_user']),
      '#menu_items' => ['items' => $menu_items],
      '#actions' => [
        [
          'link' => [
            'label' => $this->t('Manage profile', [], ['context' => 'eic_user']),
            'path' => $member_profile instanceof ProfileInterface ?
              Url::fromRoute('entity.profile.edit_form', ['profile' => $member_profile->id()]) :
              Url::fromRoute('profile.user_page.single', ['user' => $user->id(), 'profile_type' => 'member'])
          ],
          'icon' => [
            'name' => 'gear',
            'type' => 'custom',
          ],
        ],
        [
          'label' => $this->t('Post content', [], ['context' => 'eic_user']),
          'items' => $menu_items_url,
        ],
      ],
    ];
  }

}
