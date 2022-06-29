<?php

/**
 * @file
 * Contains implementation for hook_preprocess_page().
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_page().
 */
function eic_community_preprocess_page(array &$variables): void {
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
    'account_block' => _eic_get_account_header_block(),
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

    if ($key === 0) {
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

  _preprocess_node_page($variables);

  if ($variables['is_front']) {
    _preprocess_front_page($variables);
  }

  _preprocess_group_page($variables);

  _preprocess_page_not_found($variables);
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
        if (!$node->get('field_header_visual')->isEmpty()) {
          /** @var \Drupal\media\Entity\Media $media */
          $media = \Drupal::service('entity.repository')
            ->getTranslationFromContext($node->get('field_header_visual')->entity,
              $node->language()->getId());
        }

        $image_item = isset($media) ?
          ImageValueObject::fromStyledImageItem(
            $media->get('oe_media_image')->first(),
            'banner_3840x392'
          ) :
          NULL;

        $author = eic_community_get_teaser_user_display($node->getOwner());
        $author_theme = [
          '#theme' => 'eic_community_author',
          '#name' => $author['name'],
          '#path' => $author['path'] ?? '',
          '#image' => $author['image'] ?? NULL,
        ];

        if (isset($author['image'])) {
          $author_theme['image'] = $author['image'];
        }
        /* @todo Missing follow author flag when user is logged in. */

        $publish_date_format = $variables['node']->getType() === 'news' ? 'd F' : 'd F Y';
        $is_restricted = $node->private->value === '1';
        $tag = [
          'type' => 'display',
          'label' => $is_restricted ? t('Restricted') : t('Public'),
        ];
        $variables['editorial_header'] = [
          'tags' => [
            [
              'extra_classes' => 'ecl-tag--is-' . ($is_restricted ? 'restricted' : 'public'),
              'tag' => $tag,
            ],
          ],
          'meta' => [
            ['label' => $node->type->entity->label()],
            [
              'label' => \Drupal::service('date.formatter')
                ->format($node->get('published_at')->value, 'custom', $publish_date_format),
            ],
          ],
          'title' => $node->label(),
        ];

        $words_per_minute = \Drupal::config('node_read_time.settings')
          ->get('reading_time')['words_per_minute'] ?: 225;
        $reading_time_service = \Drupal::service('reading_time');
        $reading_time = $reading_time_service
          ->setWordsPerMinute($words_per_minute)
          ->collectWords($node)
          ->calculateReadingTime()
          ->getReadingTime();

        $variables['hero'] = [
          'image' => [
            'src' => $image_item ? $image_item->getSource() : NULL,
            'alt' => $image_item ? $image_item->getAlt() : NULL,
          ],
          'items' => [
            ['content' => $author_theme],
            [
              'content' => [
                '#theme' => 'eic_community_timestamp',
                '#label' => $reading_time,
                '#icon_file_path' => $variables['eic_icon_path'],
              ],
            ],
          ],
        ];

        break;
    }
  }
}

/**
 * Preprocess front page.
 */
function _preprocess_front_page(&$variables) {
  $front = [
    'top' => [],
    'middle_left' => [],
    'middle_right' => [],
    'bottom' => [],
  ];

  $content_sections = array_filter($variables['page']['content'], function ($key) {
    return strpos($key, '#') === FALSE;
  }, ARRAY_FILTER_USE_KEY);

  foreach ($content_sections as $section) {
    if (isset($section['#id'])) {
      switch ($section['#id']) {
        case 'homepage_stay_up_to_date':
          $front['middle_left'][] = $section;
          break;

        case 'homepage_follow_us':
        case 'eic_social_feed':
          $front['middle_right'][] = $section;
          break;

        case 'homepage_facts_figures':
          $front['bottom'][] = $section;
          break;

        default:
          $front['top'][] = $section;
      }
    }
    else {
      $front['top'][] = $section;
    }
  }

  $variables['front'] = $front;
}

/**
 * Preprocess group page.
 */
function _preprocess_group_page(&$variables) {
  // We need to add missing cache tags related to the group otherwise
  // theme suggestions will not be updated when the group changes status or
  // after removing a group membership.
  if ($group = \Drupal::service('eic_groups.helper')->isGroupPage()) {
    if (!isset($variables['#cache']['tags'])) {
      $variables['#cache']['tags'] = $group->getCacheTags();
    }
    else {
      $variables['#cache']['tags'] = Cache::mergeTags($variables['#cache'], $group->getCacheTags());
    }
    // Adds url.path as cache context otherwise anonymous users will always see
    // the same message depending on which group the user visited first.
    $variables['#cache']['contexts'][] = 'url.path';
  }
}

/**
 * Preprocess page not found.
 */
function _preprocess_page_not_found(&$variables) {
  if (\Drupal::routeMatch()->getRouteName() === 'system.404') {
    // Adds URL to the frontpage.
    $variables['front_url'] = Url::fromRoute('<front>')->toString();
  }
}

/**
 * Get account header block.
 *
 * @return array
 *   The block renderable array.
 */
function _eic_get_account_header_block(): array {
  $config = [];
  $block_manager = \Drupal::service('plugin.manager.block');
  $plugin_block = $block_manager->createInstance('eic_account_header_block', $config);

  $access_result = $plugin_block->access(\Drupal::currentUser());
  if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
    return [];
  }

  return $plugin_block->build();
}
