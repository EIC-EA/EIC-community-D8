<?php

/**
 * @file
 * Contains implementation for hook_preprocess_pager().
 */

use Drupal\Core\Url;

/**
 * Implements hook_preprocess_pager().
 *
 * @see template_preprocess_pager()
 *
 * Generates pagination items for ECL pagination component.
 */
function eic_community_preprocess_pager(array &$variables): void {
  $element = $variables['pager']['#element'];
  $parameters = $variables['pager']['#parameters'];
  $route_name = $variables['pager']['#route_name'];
  $route_parameters = $variables['pager']['#route_parameters'] ?? [];
  global $pager_page_array, $pager_total;
  // Maximum page number for this pager.
  $pager_max = $pager_total[$element];
  // Nothing to do if there is only one page.
  if ($pager_max <= 1) {
    return;
  }
  // Max number of pages to display next to the current page (left/right).
  // The first and last page links are excluded from this count.
  $pager_offset = 2;
  // The page we are currently paged to.
  $pager_current = $pager_page_array[$element] + 1;
  // First page of the pagination calculated by offset from current.
  $pager_first = $pager_offset < $pager_current ? $pager_current - $pager_offset : 1;
  // Last page of the pagination calculated by offset from current.
  $pager_last = $pager_current + $pager_offset;

  // Remove extra pages if last page is larger than maximum.
  if ($pager_last > $pager_max) {
    $pager_last = $pager_max;
  }

  // Array to collect the pagination items.
  $ecl_items = [];

  // Prepare navigation link item for previous/next links with defaults.
  $nav_link = [
    'link' => [
      'icon' => [
        'path' => $variables['eic_icon_path'],
        'type' => 'ui',
        'name' => 'corner-arrow',
        'size' => 'xs',
        'transform' => 'rotate-270',
      ],
    ],
  ];
  // Prepare ellipsis item.
  $ellipsis = [
    'type' => 'ellipsis',
    'label' => '...',
  ];

  // Add 'Previous' navigation link.
  if ($pager_current > 1) {
    $options = [
      'query' => pager_query_add_page($parameters, $element,
        $pager_page_array[$element] - 1),
    ];
    $nav_link['link']['link'] = [
      'path' => Url::fromRoute($route_name, $route_parameters, $options),
      'label' => t('Previous'),
      'icon_position' => 'before',
    ];
    $nav_link['type'] = 'previous';
    $nav_link['aria_label'] = t('Go to previous page');
    $ecl_items[] = $nav_link;
  }

  // Add first page link.
  if ($pager_first != $pager_current) {
    $options = [
      'query' => pager_query_add_page($parameters, $element, 0),
    ];
    $ecl_items[] = [
      'type' => 'first',
      'link' => [
        'link' => [
          'path' => Url::fromRoute($route_name, $route_parameters, $options),
          'label' => '1',
        ],
      ],
      'aria_label' => t('Go to page @number', ['@number' => 1]),
    ];
  }

  // Add first ellipsis.
  if ($pager_current - $pager_offset > 2) {
    $ecl_items[] = $ellipsis;
  }

  // Generate page link items.
  for ($i = $pager_first; $i <= $pager_current + $pager_offset && $i <= $pager_last; $i++) {
    $label = (string) $i;
    if ($i == $pager_current) {
      $ecl_items[] = [
        'type' => 'current',
        'label' => $label,
        'aria_label' => t('Page @number', ['@number' => $label]),
      ];
    }
    else {
      // Skip first or last page.
      if ($i == 1 || $i == $pager_max) {
        continue;
      }
      $options = [
        'query' => pager_query_add_page($parameters, $element, $i - 1),
      ];
      $ecl_items[] = [
        'link' => [
          'link' => [
            'path' => Url::fromRoute($route_name, $route_parameters, $options),
            'label' => $label,
          ],
        ],
        'aria_label' => t('Go to page @number', ['@number' => $i]),
      ];
    }
  }

  // Add second ellipsis.
  if ($pager_last < $pager_max - 1) {
    $ecl_items[] = $ellipsis;
  }

  // Add last page link.
  if ($pager_max != $pager_current) {
    $options = [
      'query' => pager_query_add_page($parameters, $element, $pager_max - 1),
    ];
    $ecl_items[] = [
      'type' => 'last',
      'link' => [
        'link' => [
          'path' => Url::fromRoute($route_name, $route_parameters, $options),
          'label' => (string) $pager_max,
        ],
      ],
      'aria_label' => t('Go to page @number', ['@number' => $pager_max]),
    ];
  }

  // Add 'Next' navigation link.
  if ($pager_current < $pager_max) {
    $options = [
      'query' => pager_query_add_page($parameters, $element,
        $pager_page_array[$element] + 1),
    ];
    $nav_link['link']['link'] = [
      'path' => Url::fromRoute($route_name, $route_parameters, $options),
      'label' => t('Next'),
      'icon_position' => 'after',
    ];
    $nav_link['type'] = 'next';
    $nav_link['aria_label'] = t('Go to next page');
    $nav_link['link']['icon']['transform'] = 'rotate-90';
    $ecl_items[] = $nav_link;
  }
  $variables['label'] = t('Pagination');
  $variables['ecl_items'] = $ecl_items;
}
