<?php

namespace Drupal\eic_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * @Block(
 *   id = "eic_user_header",
 *   admin_label = @Translation("EIC User Header"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class UserHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
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
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['title'] = $form_state->getValue('title');
  }

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
      '#theme' => 'user_header_block',
      '#cache' => ['contexts' => ['url.path', 'url.query_args']],
      '#title' => $this->configuration['title'],
      '#actions' => [
        [
          'link' => [
            'label' => $this->t('My profile', [], ['context' => 'eic_user']),
            'path' => $user->toUrl(),
          ],
          'icon' => [
            'name' => 'user',
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
