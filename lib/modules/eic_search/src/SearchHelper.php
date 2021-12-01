<?php

namespace Drupal\eic_search;

/**
 * SearchHelper providing helper functions for search.
 */
class SearchHelper {

  /**
   * Beautify the key build in the query params.
   */
  const BEAUTIFIED_QUERY_PARAMS = [
    'itm_aggregated_field_vocab_topics' => 'topics_id',
    'sm_content_field_vocab_topics_string' => 'topics',
    'sm_user_profile_topic_expertise_string' => 'topics_expertise',
    'ss_global_content_type' => 'content_type',
  ];

  /**
   * The name of the main filter parameter in the query url.
   */
  const FILTER_QUERY_ID = 'filter';

  /**
   * @param $param_id
   *
   * @return string
   */
  public static function getBeautifiedQueryParam($param_id): string {
    return self::BEAUTIFIED_QUERY_PARAMS[$param_id] ?? $param_id;
  }

  /**
   * @param $beautify_param_id
   *
   * @return string
   */
  public static function getOriginalQueryParam($beautify_param_id): string {
    $flip_beautified_map = array_flip(self::BEAUTIFIED_QUERY_PARAMS);

    return $flip_beautified_map[$beautify_param_id] ?? $beautify_param_id;
  }

  /**
   * Returns an array for Solr query params.
   *
   * @param array $params
   *   An array of key/value to use as Solr query params.
   *
   * @return array
   *   The query params.
   */
  public static function buildSolrQueryParams(array $params): array {
    $beautified_params = [];

    foreach ($params as $key => $param) {
      $beautified_params[self::getBeautifiedQueryParam($key)] =
        is_array($param) ? $param : [$param];
    }

    return [
      self::FILTER_QUERY_ID => $beautified_params,
    ];
  }

  /**
   * @param array $filters
   *
   * @return array|null
   */
  public static function decodeSolrQueryParams(array $filters): array {
    $decoded_params = [];

    foreach ($filters as $key => $filter) {
      $decoded_params[self::getOriginalQueryParam($key)] = $filter;
    }

    return $decoded_params;
  }

}
