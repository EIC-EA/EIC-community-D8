<?php

/**
 * @file
 * Hooks provided by the eic_blocks module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows to modify the list of contextual action links for the given route.
 */
function hook_eic_page_contextual_actions(array &$links, RouteMatchInterface $route, AccountInterface $account) {
  switch ($route->getRouteName()) {
    case 'view.my_view.page_1':
      $url = Url::fromRoute('<front>');
      $links[] = Link::fromTextAndUrl(t('Back to home'), $url);
      break;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
