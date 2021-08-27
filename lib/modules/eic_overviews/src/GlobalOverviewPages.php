<?php

namespace Drupal\eic_overviews;

use Drupal\Core\Url;

/**
 * Provides functionality for global overview pages.
 *
 * @package Drupal\eic_overviews
 */
class GlobalOverviewPages {

  /**
   * ID of the Groups overview page.
   */
  const GROUPS = 1;

  /**
   * ID of the Members overview page.
   */
  const MEMBERS = 2;

  /**
   * ID of the Global search overview page.
   */
  const GLOBAL_SEARCH = 3;

  /**
   * Returns the URL object for the given global overview page.
   *
   * @param string $page
   *   The page identifier for which we return the URL.
   *
   * @return \Drupal\Core\Url|null
   *   The URL object or NULL if does not apply.
   */
  public static function getGlobalOverviewPageUrl(string $page) {
    $page_id = NULL;
    switch ($page) {
      case 'groups':
        $page_id = self::GROUPS;
        break;

      case 'members':
        $page_id = self::MEMBERS;
        break;

      case 'global_search':
        $page_id = self::GLOBAL_SEARCH;
        break;
    }

    if (!empty($page_id)) {
      return Url::fromRoute('entity.overview_page.canonical', ['overview_page' => $page_id]);
    }

    return NULL;
  }

}
