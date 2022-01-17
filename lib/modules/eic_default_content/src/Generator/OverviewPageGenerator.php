<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\eic_events\Constants\Event;
use Drupal\eic_overviews\Entity\OverviewPage;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\eic_search\Search\Sources\GlobalSourceType;
use Drupal\eic_search\Search\Sources\GlobalEventSourceType;
use Drupal\eic_search\Search\Sources\GroupSourceType;
use Drupal\eic_search\Search\Sources\NewsStorySourceType;
use Drupal\eic_search\Search\Sources\OrganisationSourceType;
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
    ], 'Global search', '/search',
      GlobalOverviewPages::GLOBAL_SEARCH
    );

    $this->createOverview([
      'enable_search' => TRUE,
      'facets' => [
        'ss_group_topic_name',
      ],
      'source_type' => GroupSourceType::class,
    ], 'Groups', '/groups',
      GlobalOverviewPages::GROUPS
    );

    $this->createOverview([
      'enable_search' => TRUE,
      'source_type' => UserGallerySourceType::class,
    ], 'Members', '/people',
      GlobalOverviewPages::MEMBERS
    );

    $this->createOverview([
      'enable_search' => TRUE,
      'facets' => [
        'ss_content_type',
        'sm_content_field_vocab_topics_string',
        'sm_content_field_vocab_geo_string',
      ],
      'source_type' => NewsStorySourceType::class,
    ], 'News & Stories', '/articles',
      GlobalOverviewPages::NEWS_STORIES
    );

    $this->createOverview([
      'enable_search' => TRUE,
      'facets' => [
        'ss_group_topic_name',
        'sm_group_field_location_type',
        Event::SOLR_FIELD_ID_WEIGHT_STATE_LABEL,
        'ss_group_event_country',
      ],
      'source_type' => GlobalEventSourceType::class,
    ], 'Events', '/events',
      GlobalOverviewPages::EVENTS
    );

    $this->createOverview([
      'enable_search' => TRUE,
      'facets' => [
        'sm_group_organisation_type_string',
        'ss_group_topic_name',
        'sm_group_field_locations_string',
      ],
      'source_type' => OrganisationSourceType::class,
    ], 'Organisations', '/organisations',
      GlobalOverviewPages::ORGANISATIONS
    );
  }

  /**
   * @param array $block_settings
   * @param string $title
   * @param string $path_alias
   * @param int $page_id
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createOverview(
    array $block_settings,
    string $title,
    string $path_alias,
    int $page_id
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
      'field_overview_id' => $page_id,
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
