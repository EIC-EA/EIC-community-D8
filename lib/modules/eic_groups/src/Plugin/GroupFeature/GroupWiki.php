<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;

/**
 * Group feature plugin implementation for Wiki.
 *
 * @GroupFeature(
 *   id = "eic_groups_wiki",
 *   label = @Translation("Wiki"),
 *   description = @Translation("Group wiki features.")
 * )
 */
class GroupWiki extends EicGroupsGroupFeaturePluginBase {

  /**
   * Route of the top-level wiki page (book).
   *
   * @var string
   */
  const PRIMARY_OVERVIEW_ROUTE = 'entity.node.canonical';

  /**
   * {@inheritdoc}
   */
  protected function getMenuItem(Url $url, string $menu_name) {
    $menu_item = parent::getMenuItem($url, $menu_name);
    // Set a specific weight for the menu item.
    $menu_item->set('weight', 6);
    return $menu_item;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateFeatureUrl(GroupInterface $group) {
    if ($book_page_nid = $this->eicGroupHelper->getGroupBookPage($group)) {
      $url_params = ['node' => $book_page_nid];
      return Url::fromRoute(static::PRIMARY_OVERVIEW_ROUTE, $url_params);
    }
    return FALSE;
  }

}
