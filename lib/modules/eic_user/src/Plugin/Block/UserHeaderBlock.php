<?php

namespace Drupal\eic_user\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;

/**
 * @Block(
 *   id = "eic_user_header",
 *   admin_label = @Translation("EIC User Header"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class UserHeaderBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
    $form = parent::blockForm($form, $form_state);

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#min' => 0,
      '#step' => 3,
      '#default_value' => $this->configuration['title'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    parent::blockSubmit($form, $form_state);
    $this->configuration['title'] = $form_state->getValue('title');
  }

  /**
   * The profile header block.
   *
   * @inheritDoc
   */
  public function build()
  {
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

    $menu_items = array_map(function(array $item) use ($current_route) {
      return [
        'link' => [
          'label' => $item['label'],
          'path' => Url::fromRoute($item['route'])->toString(),
        ],
        'is_active' => $current_route === $item['route'],
      ];
    }, $menu_data);

    return [
      '#theme' => 'user_header_block',
      '#title' => $this->configuration['title'],
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
