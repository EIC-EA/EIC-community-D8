<?php

/**
 * @file
 * Contains implementation for hook_preprocess_page().
 */

use Drupal\Core\Template\Attribute;

/**
 * Implements hook_preprocess_page().
 */
function eic_community_preprocess_maintenance_page(array &$variables): void {
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

  $footer_sections = [
    [
      'title' => 'European Commission',
      'links' => [
        'menu_link_content:168b6965-a75b-435f-8ec3-6ad646147249' => [
          'link' => [
            'label' => 'Commission and its priorities',
            'path' => 'https://ec.europa.eu/commission/index_en',
          ],
        ],
        'menu_link_content:f0392196-98f8-4a93-a745-0bb3b98d96fe' => [
          'link' => [
            'label' => 'Policies information and services',
            'path' => 'https://ec.europa.eu/info/index_en',
          ],
        ],
      ],
      'list_class_name' => 'ecl-footer-core__list--columns',
      'section_class_name' => 'ecl-footer-core__section--separator',
    ],
    [
      'title' => 'European Union',
      'links' => [
        'menu_link_content:1d5428a8-c49e-4b73-8281-f32126a730a4' => [
          'link' => [
            'label' => 'EU institutions',
            'path' => 'https://europa.eu/european-union/about-eu_en',
          ],
        ],
        'menu_link_content:7c37bb57-a7b0-4c97-b559-fe20e2c9be67' => [
          'link' => [
            'label' => 'European Union',
            'path' => 'https://europa.eu/',
          ],
        ],
      ],
    ],
  ];

  $variables['site_footer'] = ['sections' => $footer_sections];
}
