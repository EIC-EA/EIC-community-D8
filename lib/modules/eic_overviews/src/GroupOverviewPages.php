<?php

namespace Drupal\eic_overviews;

use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides functionality for global overview pages.
 *
 * @package Drupal\eic_overviews
 */
class GroupOverviewPages {

  /**
   * Route to the group discussions overview.
   */
  const DISCUSSIONS = 'eic_overviews.groups.overview_page.discussions';

  /**
   * Route to the group events overview.
   */
  const EVENTS = 'eic_overviews.groups.overview_page.events';

  /**
   * Route to the group files overview.
   */
  const FILES = 'eic_overviews.groups.overview_page.files';

  /**
   * Route to the group latest activity overview.
   */
  const LATEST_ACTIVITY = 'eic_overviews.groups.overview_page.latest_activity_stream';

  /**
   * Route to the group members overview.
   */
  const MEMBERS = 'eic_overviews.groups.overview_page.members';

  /**
   * Route to the group team from organisation overview.
   */
  const ORGANISATIONS_TEAM = 'eic_overviews.groups.overview_page.team';

  /**
   * Route to the group news overview.
   */
  const NEWS = 'eic_overviews.groups.overview_page.news';

  /**
   * Route to the group search overview.
   */
  const SEARCH = 'eic_overviews.groups.overview_page.search';

  /**
   * Returns the URL object for the given group overview page.
   *
   * @param string $page
   *   The page identifier for which we return the URL.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which we return the URL.
   *
   * @return \Drupal\Core\Url|null
   *   The URL object or NULL if does not apply.
   */
  public static function getGroupOverviewPageUrl(string $page, GroupInterface $group) {
    $page_id = NULL;
    switch ($page) {
      case 'discussions':
        $page_id = self::DISCUSSIONS;
        break;

      case 'files':
        $page_id = self::FILES;
        break;

      case 'events':
        $page_id = self::EVENTS;
        break;

      case 'members':
        $page_id = self::MEMBERS;
        break;

      case 'group_search':
        $page_id = self::SEARCH;
        break;
    }

    if (!empty($page_id) && !$group->isNew()) {
      return Url::fromRoute($page_id, ['group' => $group->id()]);
    }

    return NULL;
  }

}
