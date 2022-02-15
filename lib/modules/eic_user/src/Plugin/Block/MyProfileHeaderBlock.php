<?php

namespace Drupal\eic_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
    return [
      '#theme' => 'my_profile_header_block',
      '#title' => $this->t('My activity feed', [], ['context' => 'eic_user']),
      '#actions' => [
        [
          'link' => [
            'label' => $this->t('Manage profile', [], ['context' => 'eic_user']),
            'path' => '/test',
          ],
          'icon' => [
            'name' => 'gear',
            'type' => 'custom',
          ],
        ],
        [
          'label' => $this->t('Post content', [], ['context' => 'eic_user']),
          'items' => [
            [
              'link' => [
                'label' => $this->t('New story', [], ['context' => 'eic_user']),
                'path' => '/test',
              ],
            ],
            [
              'link' => [
                'label' => $this->t('New wiki', [], ['context' => 'eic_user']),
                'path' => '/test',
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
