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

  $footer_sections = [
    [
      'title' => 'European Innovation Council Community',
      'links' => [
        'menu_link_content:73ca9966-ac73-48c2-b821-fabe29fe75fc' => [
          'link' => [
            'label' => 'Help and Guidance - F.A.Q.',
            'path' => '/help_and_guidance',
          ],
        ],
        'menu_link_content:8f11c803-bb82-4ccb-a6ee-e6b34bc4e587' => [
          'link' => [
            'label' => 'Contact Us',
            'path' => '/contact',
          ],
        ],
        'menu_link_content:57bdebe9-b8b5-4e16-a981-72edec1d96be' => [
          'link' => [
            'label' => 'Terms & Conditions',
            'path' => '/sites/default/files/terms_and_conditions.pdf',
          ],
        ],
        'menu_link_content:66970ce3-9245-442d-a7b6-a4b41ce13b36' => [
          'link' => [
            'label' => 'Privacy statement',
            'path' => '/sites/default/files/privacy_statement.pdf',
          ],
        ],
      ],
      'list_class_name' => 'ecl-footer-core__list--columns',
      'section_class_name' => 'ecl-footer-core__section--separator',
    ],
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
