<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an GroupOverviewBlock block.
 *
 * @Block(
 *   id = "eic_group_overview",
 *   admin_label = @Translation("EIC Group Overview"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class GroupOverviewBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'group_overview_block',
      '#datasource' => 'entity:group',
      '#facets' => [
        [
          'label' => $this->t('Topic', [], ['context' => 'eic_groups']),
          'field' => 'ss_group_topic_name',
        ]
      ]
    ];
  }

}
