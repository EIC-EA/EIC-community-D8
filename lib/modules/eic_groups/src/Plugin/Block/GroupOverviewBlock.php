<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

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
      '#url' => Url::fromRoute('eic_groups.solr_search')->toString(),
      '#isAnonymous' => \Drupal::currentUser()->isAnonymous(),
      '#translations' => [
        'public' => $this->t('Public', [], ['context' => 'eic_group']),
        'private' => $this->t('Private', [], ['context' => 'eic_group']),
        'filter' => $this->t('Filter', [], ['context' => 'eic_group']),
        'topics' => $this->t('Topics', [], ['context' => 'eic_group']),
        'search_text' => $this->t('Search for a group', [], ['context' => 'eic_group']),
        'no_results' => $this->t('No results', [], ['context' => 'eic_group']),
        'members' => $this->t('Members', [], ['context' => 'eic_group']),
        'reactions' => $this->t('Reactions', [], ['context' => 'eic_group']),
        'documents' => $this->t('Documents', [], ['context' => 'eic_group']),
      ],
    ];
  }

}
