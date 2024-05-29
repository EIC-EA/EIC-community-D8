<?php

/**
 * @file
 * Contains implementation for hook_preprocess_page().
 */

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
/**
 * Implements hook_preprocess_page().
 */
function eic_community_preprocess_maintenance_page(array &$variables): void {
  $variables['main_attributes'] = new Attribute([
    'id' => ['main-content'],
  ]);

  foreach ($variables['page']['content'] as $key => $content_section) {
    if (is_array($content_section) && isset($content_section['#theme']) && $content_section['#theme'] === 'block') {
      $variables['page']['content'][$key]['#attributes']['class'][] = 'ecl-section-wrapper';
    }
  }

  $variables['site_header'] = [
    'icon_file_path' => $variables['eic_icon_path'],
    'site_name' => \Drupal::config('system.site')->get('name'),
    'logo' => [
      'title' => t('European Commission'),
      'alt' => t('European Commission logo'),
      'href' => \Drupal::urlGenerator()->generateFromRoute('<front>'),
      'src_desktop' => $variables['eic_logo_path'] . '/logo--' . $variables['language']->getId() . '.svg',
      'src_mobile' => $variables['eic_logo_path'] . '/logo--mute.svg',
    ],
  ];

  $footer_sections = [];

  $first_section = array_filter($variables['page']['footer'], function ($content, $key) {
    return strpos($key, 'content') !== FALSE;
  }, ARRAY_FILTER_USE_BOTH);

  $first_section = array_shift($first_section);

  if (!empty($first_section['#derivative_plugin_id'])) {
    $block_content = \Drupal::service('entity.repository')
      ->loadEntityByUuid('block_content', $first_section['#derivative_plugin_id']);

    $footer_sections[] = [
      'title' => $block_content->field_title->value,
      'description' => Markup::create($block_content->body->value),
    ];
  }

  $menu_sections = array_filter($variables['page']['footer'], function ($content, $key) {
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
    $links = array_map(function ($item) {
      return [
        'link' => [
          'label' => $item->link->getTitle(),
          'path' => $item->link->getUrlObject()->toString(),
        ],
      ];
    }, $tree);

    $section = [
      'title' => $menu['#configuration']['label'],
      'links' => $links,
    ];

    if ($key < count($menu_sections) - 1) {
      $section['list_class_name'] = 'ecl-footer-core__list--columns';
      $section['section_class_name'] = 'ecl-footer-core__section--separator';
    }

    // If there are more than 3 links, we need to split the menu in 2 columns.
    if (count($links) > 3) {
      $section['list_class_name'] = 'ecl-footer-core__list--columns';
    }

    $footer_sections[] = $section;
  }

  $variables['site_footer'] = ['sections' => $footer_sections];
}
