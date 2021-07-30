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

  $variables['site_header'] = [
    'icon_file_path' => $variables['eic_icon_path'],
    'logo' => [
      'title' => t('European Commission'),
      'alt' => t('European Commission logo'),
      'href' => \Drupal::urlGenerator()->generateFromRoute('<front>'),
      'src_desktop' => $variables['eic_logo_path'] . '/logo--' . $variables['language']->getId() . '.svg',
    ],
  ];

  if ($variables['user']->isAnonymous()) {
    $variables['site_header']['login']['link'] = [
      'label' => t('Log in'),
      'path' => Url::fromRoute('user.login'),
    ];
  }
  else {
    $account = User::load($variables['user']->id());
    $user = eic_community_get_teaser_user_display($account);
    unset($user['path']);

    $user['actions'] = [
      [
        'link' => [
          'label' => t('My profile'),
          'path' => $account->toUrl()->toString(),
        ],
      ],
      [
        'link' => [
          'label' => t('Log out'),
          'path' => Url::fromRoute('user.logout'),
        ],
      ],
    ];

    $variables['site_header']['user'] = $user;
  }

  $footer_sections = [];

  $first_section = array_filter($variables['page']['footer'], function($content, $key){
    return strpos($key, 'content') !== FALSE;
  }, ARRAY_FILTER_USE_BOTH);
  $first_section = array_shift($first_section);
  $block_content = \Drupal::service('entity.repository')->loadEntityByUuid('block_content', $first_section['#derivative_plugin_id']);

  $footer_sections[] = [
    'title' => $block_content->field_title->value,
    'description' => strip_tags($block_content->body->value),
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
    $links = [];

    foreach ($tree as $branch) {
      $links[]['link'] = [
          'label' => $branch->link->getTitle(),
          'path' => $branch->link->getUrlObject()->toString(),
        ];
    }

    $section = [
      'title' => $menu['#configuration']['label'],
      'links' => $links,
    ];

    if ($key === 0) {
      $section['list_class_name'] = 'ecl-footer-core__list--columns';
      $section['section_class_name'] = 'ecl-footer-core__section--separator';
    }

    $footer_sections[] = $section;
  }

  $variables['site_footer'] = ['sections' => $footer_sections];

  _preprocess_node_page($variables);
}

/**
 * Preprocess node pages.
 */
function _preprocess_node_page(&$variables) {
  if (isset($variables['node'])) {
    /** @var \Drupal\Core\Entity\EntityInterface $node */
    $node = $variables['node'];
    switch ($variables['node']->getType()) {
      case 'story':
      case 'news':
        /** @var \Drupal\media\Entity\Media $media */
        $media = \Drupal::service('entity.repository')->getTranslationFromContext($node->get('field_header_visual')->entity, $node->language()->getId());
        $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
        $author = eic_community_get_teaser_user_display($node->getOwner());
        $author_theme = [
          '#theme' => 'eic_community_author',
          '#name' => $author['name'],
          '#path' => $author['path'],
        ];

        if (isset($author['image'])) {
          $author_theme['image'] = $author['image'];
        }
        /* @todo Missing follow author flag when user is logged in. */

        $publish_date_format = $variables['node']->getType() === 'news' ? 'd F' : 'd F Y';

        $variables['editorial_header'] = [
          // @todo Not the correct display, has to be created in storybook.
          'type' => [
            'label' => $node->private->value === '0' ? t('Public') : t('Community members only'),
          ],
          'meta' => [
            ['label' => $node->type->entity->label()],
            ['label' => \Drupal::service('date.formatter')->format($node->get('published_at')->value, 'custom', $publish_date_format)],
          ],
          'title' => $node->label(),
        ];

        $variables['hero'] = [
          'image' => [
            'src' => $image_item->getSource(),
            'alt' => $image_item->getAlt(),
          ],
          'items' => [
            ['content' => $author_theme],
            [
              'content' => [
                '#theme' => 'eic_community_timestamp',
                '#label' => \Drupal::service('reading_time')->getReadingTime(),
                '#icon_file_path' => $variables['eic_icon_path'],
              ],
            ],
          ],
        ];

        break;
    }
  }
}
