<?php

namespace Drupal\eic_search;

/**
 * SearchHelper providing helper functions for search.
 */
class SearchHelper {

  /**
   * Builds a string for Solr query params.
   *
   * @param array $params
   *   An array of key/value to use as Solr query params.
   * @param bool $include_question_mark
   *   Whether to include a leading question mark. Default to FALSE.
   *
   * @return string
   *   The query param string.
   */
  public static function buildSolrQueryParams(array $params, $include_question_mark = FALSE) {
    $i = 0;
    $query_string = '';

    foreach ($params as $key => $value) {
      if ($i > 0) {
        $query_string .= '&';
      }

      $query_string .= "f[$i]=$key:$value";
      $i++;
    }

    if ($include_question_mark && strlen($query_string) > 0) {
      $query_string = '?' . $query_string;
    }

    return $query_string;
  }

}
