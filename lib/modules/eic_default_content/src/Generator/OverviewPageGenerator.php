<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\eic_overviews\Entity\OverviewPage;
use Drupal\eic_search\Search\Sources\GlobalSourceType;
use Drupal\eic_search\Search\Sources\GroupSourceType;
use Drupal\eic_search\Search\Sources\UserGallerySourceType;

/**
 * Class OverviewPageGenerator
 *
 * @package Drupal\eic_default_content\Generator
 */
class OverviewPageGenerator extends CoreGenerator {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createOverview([
      'enable_search' => TRUE,
      'facets' => [
        'ss_global_content_type_label',
        'sm_content_field_vocab_topics_string',
        'sm_content_field_vocab_geo_string',
      ],
      'source_type' => GlobalSourceType::class,
    ], 'Global search', '/search');

    $this->createOverview([
      'enable_search' => TRUE,
      'facets' => [
        'ss_group_topic_name',
      ],
      'source_type' => GroupSourceType::class,
    ], 'Groups overview', '/groups');

    $this->createOverview([
      'enable_search' => TRUE,
      'source_type' => UserGallerySourceType::class,
    ], 'Members overview', '/people');
  }

  /**
   * @param array $block_settings
   * @param string $title
   * @param string $path_alias
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createOverview(
    array $block_settings,
    string $title,
    string $path_alias
  ) {
    $block_field = [
      'plugin_id' => 'eic_search_overview',
      'settings' => $block_settings + $this->getDefaultSettings(),
    ];

    $overview = OverviewPage::create([
      'title' => $title,
      'path' => $path_alias,
      'field_overview_block' => $block_field,
      'banner_image' => $this->getRandomImage(),
    ]);

    $overview->save();
  }

  /**
   * @return array
   */
  private function getDefaultSettings(): array {
    static $settings;
    if (empty($settings)) {
      $settings = [
        'id' => 'eic_search_overview',
        'label' => NULL,
        'label_display' => FALSE,
        'provider' => NULL,
        'facets' => [],
        'page_options' => 'normal',
        'prefilter_group' => FALSE,
        'add_facet_interests' => TRUE,
        'add_facet_my_groups' => TRUE,
        'sort_options' => [],
      ];
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('overview_page');
  }

}
