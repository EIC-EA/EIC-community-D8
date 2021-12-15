<?php

namespace Drupal\eic_overviews;

use Drupal\eic_overviews\Entity\OverviewPage;

/**
 * Provides functionality for global overview pages.
 *
 * @package Drupal\eic_overviews
 */
class GlobalOverviewPages {

  /**
   * ID of the Global search overview page.
   */
  const GLOBAL_SEARCH = 1;

  /**
   * ID of the Groups overview page.
   */
  const GROUPS = 2;

  /**
   * ID of the Members overview page.
   */
  const MEMBERS = 3;

  /**
   * ID of the News & Stories overview page.
   */
  const NEWS_STORIES = 4;

  /**
   * ID of the Events overview page.
   */
  const EVENTS = 5;

  /**
   * Returns the URL object for the given global overview page.
   *
   * @param int $page
   *   The page identifier for which we return the URL.
   *
   * @return \Drupal\Core\Url|null
   *   The URL object or NULL if does not apply.
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function getGlobalOverviewPageUrl(int $page) {
    $overview_entities = \Drupal::entityQuery('overview_page')
      ->condition('field_overview_id', $page)
      ->execute();

    if (empty($overview_entities)) {
      return NULL;
    }

    /** @var OverviewPage $overview_entity */
    $overview_entity = OverviewPage::load(reset($overview_entities));

    if (!$overview_entity instanceof OverviewPage) {
      return NULL;
    }

    return $overview_entity->toUrl();
  }

}
