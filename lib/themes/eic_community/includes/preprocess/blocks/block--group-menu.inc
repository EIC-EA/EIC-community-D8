<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for group menu.
 */

use Drupal\Core\Url;
use Drupal\oec_group_features\GroupFeatureInterface;

/**
 * Implements hook_preprocess_block() for group menu.
 */
function eic_community_preprocess_block__group_menu(
  &$variables,
  &$cache_tags = []
) {
  $items = [];
  $current_url_to_compare = Url::fromRouteMatch(\Drupal::routeMatch());
  // Reset options if we have to only compare url without parameters.
  $current_url_to_compare->setOptions([]);
  $overview_parameter = GroupFeatureInterface::QUERY_PARAMETER_OVERVIEW_URL;

  foreach ($variables['content']['#items'] as $link) {
    /** @var \Drupal\Core\Url $link_url */
    $link_url = $link['url'];
    $overview_url_link = NULL;

    // If we have an overview-url in parameter, do a special check for the active.
    if (
      array_key_exists($overview_parameter, $link_url->getOption('query')) &&
      $overview_url_link = $link_url->getOption('query')[$overview_parameter]
    ) {
      $options = $link_url->getOptions();
      // We do not generate a query parameter in the front-end path
      // to avoid page refresh and ugly url.
      unset($options['query'][$overview_parameter]);

      $link_url->setOptions($options);
    }

    $items[] = [
      'is_active' =>
        $link['in_active_trail'] ||
        $current_url_to_compare->toString() === $overview_url_link,
      'link' => [
        'label' => $link['title'],
        'path' => $link_url->toString(),
      ],
    ];
  }

  $variables['group_menu'] = ['items' => $items];
}
