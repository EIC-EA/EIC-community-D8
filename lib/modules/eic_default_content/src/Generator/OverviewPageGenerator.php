<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\eic_events\Constants\Event;
use Drupal\eic_flags\FlagType;
use Drupal\eic_overviews\Entity\OverviewPage;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\eic_search\Search\DocumentProcessor\DocumentProcessorInterface;
use Drupal\eic_search\Search\Sources\GlobalSourceType;
use Drupal\eic_search\Search\Sources\GlobalEventSourceType;
use Drupal\eic_search\Search\Sources\GroupSourceType;
use Drupal\eic_search\Search\Sources\NewsStorySourceType;
use Drupal\eic_search\Search\Sources\OrganisationSourceType;
use Drupal\eic_search\Search\Sources\UserGallerySourceType;
use Drupal\eic_search\Service\SolrDocumentProcessor;

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
        'ss_global_content_type_label' => 'ss_global_content_type_label',
        'sm_content_field_vocab_topics_string' => 'sm_content_field_vocab_topics_string',
        'sm_content_field_vocab_geo_string' => 'sm_content_field_vocab_geo_string',
      ],
      'sort_options' => [
        'ss_global_created_date' => 'ss_global_created_date',
        'timestamp' => 'timestamp',
        'ss_group_user_fullname' => 'ss_group_user_fullname',
        'its_document_download_total' => 'its_document_download_total',
        'its_statistics_view' => 'its_statistics_view',
        'its_content_comment_count' => 'its_content_comment_count',
        'its_flag_like_content' => 'its_flag_like_content',
        'dm_aggregated_changed' => 'dm_aggregated_changed',
        'its_last_comment_timestamp' => 'its_last_comment_timestamp',
        'its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . FlagType::LIKE_CONTENT => (
          'its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . FlagType::LIKE_CONTENT
        ),
        'score' => 'score',
      ],
      'source_type' => GlobalSourceType::class,
    ], 'Global search', '/search',
      GlobalOverviewPages::GLOBAL_SEARCH
    );

    $this->createOverview([
      'enable_search' => TRUE,
      'facets' => [
        'sm_group_topic_name' => 'sm_group_topic_name',
      ],
      'sort_options' => [
        DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID => DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID,
        'ss_global_created_date' => 'ss_global_created_date',
        'timestamp' => 'timestamp',
        'its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . FlagType::LIKE_CONTENT => (
          'its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . FlagType::LIKE_CONTENT
        ),
        'score' => 'score',
      ],
      'source_type' => GroupSourceType::class,
    ], 'Groups', '/groups',
      GlobalOverviewPages::GROUPS
    );

    $this->createOverview([
      'enable_search' => TRUE,
      'facets' => [
        'sm_user_profile_topic_expertise_string' => 'sm_user_profile_topic_expertise_string',
        'sm_user_profile_job_string' => 'sm_user_profile_job_string',
        'sm_user_profile_field_vocab_topic_interest_array' => 'sm_user_profile_field_vocab_topic_interest_array',
        'ss_user_profile_field_location_address_country_code' => 'ss_user_profile_field_location_address_country_code',
        'sm_user_profile_field_vocab_language_array' => 'sm_user_profile_field_vocab_language_array'
      ],
      'sort_options' => [
        DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID => DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID,
        'ss_global_created_date' => 'ss_global_created_date',
        'timestamp' => 'timestamp',
        'ds_user_access' => 'ds_user_access',
        'score' => 'score',
      ],
      'source_type' => UserGallerySourceType::class,
    ], 'Members', '/people',
      GlobalOverviewPages::MEMBERS
    );

    $this->createOverview([
      'enable_search' => TRUE,
      'facets' => [
        'ss_content_type' => 'ss_content_type',
        'sm_content_field_vocab_topics_string' => 'sm_content_field_vocab_topics_string',
        'sm_content_field_vocab_geo_string' => 'sm_content_field_vocab_geo_string',
      ],
      'sort_options' => [
        'ss_drupal_changed_timestamp' => 'ss_drupal_changed_timestamp',
        'ss_drupal_timestamp' => 'ss_drupal_timestamp',
        'its_statistics_view' => 'its_statistics_view',
        'its_flag_like_content' => 'its_flag_like_content',
        'its_content_comment_count' => 'its_content_comment_count',
      ],
      'source_type' => NewsStorySourceType::class,
    ], 'News & Stories', '/articles',
      GlobalOverviewPages::NEWS_STORIES
    );

    $this->createOverview([
      'enable_search' => TRUE,
      'facets' => [
        'sm_group_topic_name' => 'sm_group_topic_name',
        'sm_group_field_location_type' => 'sm_group_field_location_type',
        Event::SOLR_FIELD_ID_WEIGHT_STATE_LABEL => Event::SOLR_FIELD_ID_WEIGHT_STATE_LABEL,
        'ss_group_event_country' => 'ss_group_event_country',
      ],
      'sort_options' => [
        Event::SOLR_FIELD_ID_WEIGHT_STATE => Event::SOLR_FIELD_ID_WEIGHT_STATE,
        'ss_global_created_date' => 'ss_global_created_date',
        'timestamp' => 'timestamp',
        'ss_group_label_string' => 'ss_group_label_string',
        'its_content_field_date_range_start_value' => 'its_content_field_date_range_start_value',
        'score' => 'score',
      ],
      'source_type' => GlobalEventSourceType::class,
    ], 'Events', '/events',
      GlobalOverviewPages::EVENTS
    );

    $this->createOverview([
      'enable_search' => TRUE,
      'facets' => [
        'sm_group_organisation_type_string' => 'sm_group_organisation_type_string',
        'sm_group_topic_name' => 'sm_group_topic_name',
        'sm_group_field_locations_string' => 'sm_group_field_locations_string',
      ],
      'sort_options' => [
        DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID => DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID,
        'ss_global_created_date' => 'ss_global_created_date',
        'timestamp' => 'timestamp',
        'score' => 'score',
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

    $random_image = $this->getRandomImage();

    $overview = OverviewPage::create([
      'title' => $title,
      'path' => $path_alias,
      'field_overview_block' => $block_field,
      'banner_image' => [
        'target_id' => $random_image ? $random_image->id() : NULL,
        'alt' => $title,
      ],
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
        'provider' => 'eic_search',
        'facets' => [],
        'page_options' => 'normal',
        'prefilter_group' => FALSE,
        'add_facet_interests' => TRUE,
        'add_facet_my_groups' => TRUE,
        'sort_options' => [],
        'enable_date_filter' => FALSE,
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
