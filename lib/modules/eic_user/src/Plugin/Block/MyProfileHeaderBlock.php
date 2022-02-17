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

    return [
      '#theme' => 'my_profile_header_block',
      '#title' => $this->t('My activity feed', [], ['context' => 'eic_user']),
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
