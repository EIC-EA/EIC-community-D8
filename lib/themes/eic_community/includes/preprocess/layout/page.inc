<?php

/**
 * @file
 * Contains implementation for hook_preprocess_page().
 */

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Template\Attribute;

/**
 * Implements hook_preprocess_page().
 */
function eic_community_preprocess_page(array &$variables): void {
  $variables['main_attributes'] = new Attribute([
    'class' => ['ecl-u-pb-xl'],
    'id' => ['main-content'],
  ]);

  foreach ($variables['page']['content'] as $key => $content_section) {
    if (isset($content_section['#theme']) && $content_section['#theme'] === 'block') {
      $variables['page']['content'][$key]['#attributes']['class'][] = 'ecl-section-wrapper';
    }
  }

  $sections = [];

  $first_section = array_filter($variables['page']['footer'], function($content, $key){
    return strpos($key, 'content') !== FALSE;
  }, ARRAY_FILTER_USE_BOTH);
  $first_section = array_shift($first_section);
  $block_content = \Drupal::service('entity.repository')->loadEntityByUuid('block_content', $first_section['#derivative_plugin_id']);

  $sections[] =[
    'title' => [
      'link' => [
        'label' => $block_content->field_title->value,
        'path' => '',
      ],
    ],
    'description' =>  $block_content->body->value,
  ];

  $menu_sections = array_filter($variables['page']['footer'], function($content, $key){
    return strpos($key, 'menu') !== FALSE;
  }, ARRAY_FILTER_USE_BOTH);
  $menu_sections = array_values($menu_sections);

  foreach ($menu_sections as $key => $menu) {
    $menu_name = $menu['#derivative_plugin_id'];
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $menu_active_trail = \Drupal::service('menu.active_trail')->getActiveTrailIds($menu_name);
    $parameters->setActiveTrail($menu_active_trail);
    $tree = \Drupal::menuTree()->load($menu_name, $parameters);
    $title_branch = array_shift($tree);
    $links = [];

    foreach ($tree as $branch) {
      $links[]['link'] = [
          'label' => $branch->link->getTitle(),
          'path' => $branch->link->getUrlObject()->toString(),
        ];
    }

    $section = [
      'title' => [
        'link' => [
          'label' => $title_branch->link->getTitle(),
          'path' => $title_branch->link->getUrlObject()->toString(),
        ]
      ],
      'links' => $links,
    ];

    if ($key === 0) {
      $section['list_class_name'] = 'ecl-footer-core__list--columns';
      $section['section_class_name'] = 'ecl-footer-core__section--separator';
    }

    $sections[] = $section;
  }


  $variables['site_footer'] = ['sections' => $sections];
}
