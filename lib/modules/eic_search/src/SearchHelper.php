<?php

namespace Drupal\eic_search;

/**
 * SearchHelper providing helper functions for search.
 */
class SearchHelper {

  /**
   * Returns an array for Solr query params.
   *
   * @param array $params
   *   An array of key/value to use as Solr query params.
   *
   * @return array
   *   The query params.
   */
  public static function buildSolrQueryParams(array $params) {
    $i = 0;
    $query_params = [];

    foreach ($params as $key => $value) {
      $query_params["f[$i]"] = "$key:$value";
      $i++;
    }

    return $query_params;
  }

}
