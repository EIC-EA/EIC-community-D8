<?php

namespace Drupal\eic_menus;

use Drupal\menu_trail_by_path\MenuTrailByPathActiveTrail;
use Drupal\system\Entity\Menu;

/**
 * Decorates menu active trail by path to fix menu active trails.
 */
class EICMenuTrailByPathActiveTrail extends MenuTrailByPathActiveTrail {

  /**
   * {@inheritdoc}
   */
  protected function doGetActiveTrailIds($menu_name) {
    // Parent ids; used both as key and value to ensure uniqueness.
    // We always want all the top-level links with parent == ''.
    $active_trail = ['' => ''];

    $entity = Menu::load($menu_name);
    if (!$entity) {
      // If a link in the given menu indeed matches the route, then use it to
      // complete the active trail.
      if ($active_link = $this->getActiveLink($menu_name)) {
        if ($parents = $this->menuLinkManager->getParentIds($active_link->getPluginId())) {
          $active_trail = $parents + $active_trail;
        }
      }

      return $active_trail;
    }

    return parent::doGetActiveTrailIds($menu_name);
  }

}
