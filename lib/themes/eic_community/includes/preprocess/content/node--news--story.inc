<?php

/**
 * @file
 * Prepares variables for node News and Story templates.
 */

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_media\MediaHelper;
use Drupal\eic_private_content\PrivateContentConst;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;

/**
 * Implements hook_preprocess_node__story() for story node.
 */
function eic_community_preprocess_node__story(array &$variables) {
  _eic_community_preprocess_node_news_story($variables);
}

/**
 * Implements hook_preprocess_node__news() for news node.
 */
function eic_community_preprocess_node__news(array &$variables) {
  _eic_community_preprocess_node_news_story($variables);
}

/**
 * Custom function to preprocess News and Story node.
 */
function _eic_community_preprocess_node_news_story(array &$variables) {
  /** @var \Drupal\Core\Entity\EntityInterface $node */
  $node = $variables['node'];
  $view_mode = $variables['view_mode'];
  if ($view_mode === 'teaser') {
    // We display view stats on teasers, they need additional cache tags.
    $variables['#cache']['tags'][] = 'eic_statistics:node:' . $node->id();
  }

  $item = [
    'type' => [
      'label' => $node->type->entity->label(),
      'icon' => [
        'name' => $node->bundle(),
        'type' => 'custom',
      ],
    ],
    'timestamp' => [
      'label' => $node->get('published_at')->isEmpty() ?
        eic_community_get_teaser_time_display($node->get('created')->value) :
        eic_community_get_teaser_time_display($node->get('published_at')->published_at_or_created),
    ],
    'author' => eic_community_get_teaser_user_display($node->getOwner()),
  ];

  if (!$node->get('field_header_visual')->isEmpty()) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = \Drupal::service('entity.repository')
      ->getTranslationFromContext(
        $node->get('field_header_visual')->entity,
        $node->language()->getId()
      );
    $image_item = ImageValueObject::fromStyledImageItem(
      $media->get('oe_media_image')->first(),
      'banner_3840x392'
    );
    $item['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $image_item->getAlt(),
    ];
  }

  if ('mail_teaser' === $view_mode) {
    $item = _eic_community_prepare_node_teaser_array($node);
    /** @var \Drupal\media\MediaInterface $media */
    $media = \Drupal::service('entity.repository')
      ->getTranslationFromContext(
        $node->get('field_image')->entity,
        $node->language()->getId()
      );
    $image_item = ImageValueObject::fromStyledImageItem(
      $media->get('oe_media_image')->first(),
      'oe_theme_ratio_3_2_medium'
    );
    $item['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $image_item->getAlt(),
    ];
  }

  if ('teaser' === $view_mode) {
    if (!$node->get('field_image')->isEmpty()) {
      /** @var \Drupal\media\Entity\Media $media */
      $media = \Drupal::service('entity.repository')
        ->getTranslationFromContext(
          $node->get('field_image')->entity,
          $node->language()->getId()
        );
      $image_item = ImageValueObject::fromStyledImageItem(
        $media->get('oe_media_image')->first(),
        'ratio_5_4_small'
      );
      $item['image'] = [
        'src' => $image_item->getSource(),
        'alt' => $image_item->getAlt(),
      ];
    }

    $stats = _eic_community_get_entity_stats($node);
    // We do not need downloads for news/story stats.
    if (array_key_exists('downloads', $stats)) {
      unset($stats['downloads']);
    }

    $variables['stats'] = $stats;
  }

  $variables['story_item'] = $item;

  if ($variables['view_mode'] === 'full') {
    // Main elements.
    _eic_community_story_image_field($variables);
    _eic_community_story_video_field($variables);
    _eic_community_story_document_field($variables);
    // Sidebar elements.
    _eic_community_display_flags($variables);

    $group = \Drupal::service('eic_groups.helper')->getGroupFromRoute();
    if (!$group && $node->hasField(PrivateContentConst::FIELD_NAME)
      && !$node->get(PrivateContentConst::FIELD_NAME)->value) {
      $variables['editorial_actions']['items'][]['content'] = _eic_community_get_social_share_block();
    }
    elseif ($group instanceof GroupInterface) {
      $visibility = \Drupal::service('oec_group_flex.group_visibility.storage')->load($group->id());
      if (in_array($group->bundle(), ['event', 'group'])
        && !GroupsModerationHelper::isBlocked($group)
        && $visibility instanceof GroupVisibilityRecordInterface
        && $visibility->getType() === GroupVisibilityType::GROUP_VISIBILITY_PUBLIC
      ) {
        $variables['editorial_actions']['items'][]['content'] = _eic_community_get_social_share_block();
      }
    }

    _eic_community_display_contributors($variables);
    _eic_community_display_topics($variables);
    _eic_community_story_related_stories($variables);
  }
}

/**
 * Preproccess image field for full news/story.
 */
function _eic_community_story_image_field(&$variables) {
  if (isset($variables['content']['field_image'][0])) {
    $media = $variables['content']['field_image'][0]['#media'];
    $image_item = ImageValueObject::fromStyledImageItem(
      $media->get('oe_media_image')->first(),
      'scale_739px_width_2x'
    );
    $image_wrapper = [
      'image' => $image_item->getSource(),
    ];

    if (isset($variables['content']['field_image_caption'][0])) {
      $image_wrapper['description'] = $variables['content']['field_image_caption'][0]['#context']['value'];
    }

    $variables['image_wrapper'] = $image_wrapper;
  }
}

/**
 * Preproccess document field for full news/story.
 */
function _eic_community_story_document_field(&$variables) {
  $node = $variables['node'];
  /** @var \Drupal\media\MediaInterface[] $medias */
  $medias = $node->get('field_document_media')->referencedEntities();

  foreach ($medias as $media) {
    switch ($media->bundle()) {
      case 'video':
        $file = File::load($media->field_media_video_file->target_id);
        break;

      default:
        $file = File::load($media->field_media_file->target_id);
        break;
    }

    $language = $media->language()->getName();
    if ($media_language = _eic_community_get_entity_field_language($media)) {
      $language = $media_language;
    }
    elseif ($node_language = _eic_community_get_entity_field_language($node)) {
      $language = $node_language;
    }

    $author_url = $media->getOwner()->toUrl();
    $download_url = MediaHelper::formatMediaDownloadLink($media)->toString();
    $file_type = strstr($file->get('filemime')->getString(), '/', TRUE);

    $variables['downloads'][] = [
      'title' => $media->getName(),
      'author' => [
        'name' => $media->getOwner()->getDisplayName(),
        'path' => $author_url->access() ? $media->getOwner()->toUrl()->toString() : NULL,
      ],
      'language' => $language,
      'timestamp' => eic_community_get_teaser_time_display($media->get('changed')->getString()),
      'filesize' => format_size($file->get('filesize')->getString()),
      'highlight' => FALSE,
      'path' => $download_url,
      'icon_file_path' => $variables['eic_icon_path'],
      'icon' => [
        'type' => $file_type === 'video' ? 'general' : 'custom',
        'name' => $file_type === 'video' ? $file_type : substr(strrchr($file->get('filemime')->getString(), '/'), 1),
      ],
    ];
  }
}

/**
 * Preprocess flags.
 */
function _eic_community_display_flags(&$variables) {
  if (!isset($variables['elements']['#entity_type'])) {
    return;
  }

  $entity_type = $variables['elements']['#entity_type'];
  $entity = $variables[$entity_type];

  $flags = array_filter($variables['elements'], function ($key) use ($entity) {
    // If element is not a flag we skip it.
    if (strpos($key, 'flag') === FALSE) {
      return FALSE;
    }

    return _eic_community_get_flag_access(str_replace('flag_', '', $key), $entity);
  }, ARRAY_FILTER_USE_KEY);

  foreach ($flags as $flag) {
    $variables['editorial_actions']['items'][] = [
      'content' => $flag,
    ];
  }

  // Show recommend content flag in the 2nd place.
  if (isset($variables['elements']['recommend_content'])) {
    array_splice($variables['editorial_actions']['items'], 2, 0, [['content' => $variables['elements']['recommend_content']['content']]]);
  }
}

/**
 * Preproccess contributors.
 */
function _eic_community_display_contributors(&$variables) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];
  if ($node->bundle() !== 'story') {
    return [];
  }

  $contributors = [];

  $contributors_fields = [
    'field_story_paragraphs',
    'field_related_contributors',
  ];

  $field_title = FALSE;

  foreach ($contributors_fields as $field_name) {
    if ($node->hasField($field_name)) {
      $field_title = isset($variables['elements'][$field_name]['#title']) ? $variables['elements'][$field_name]['#title'] : '';

      foreach ($variables['elements'][$field_name] as $key => $value) {
        $has_contributor = FALSE;

        if (is_int($key)) {
          /** @var \Drupal\paragraphs\ParagraphInterface $contributor */
          $contributor = $value['#paragraph'];
          $variant = $contributor->get('paragraph_view_mode')->value;

          switch ($variant) {
            case 'external_person':
              if (!$contributor->get('field_name')->isEmpty()) {
                $has_contributor = TRUE;
              }
              break;

            case 'platform_member':
              if (!$contributor->get('field_user_ref')->isEmpty()) {
                $user = $contributor->get('field_user_ref')->entity;

                // User entity has probably been deleted but the reference
                // still exists. In this case, we do nothing.
                if (!$user) {
                  break;
                }

                $has_contributor = TRUE;
              }
              break;

          }

          if (!$has_contributor) {
            continue;
          }

          $contributors[] = [
            'content' => $value,
          ];
        }
      }
    }
  }

  if (count($contributors) > 0) {
    $variables['contributors'] = [
      'title' => $field_title,
      'items' => $contributors,
    ];
  }
}

/**
 * Preproccess topics for full news/story.
 */
function _eic_community_display_topics(&$variables) {
  $topics = [];

  foreach ($variables['elements']['field_vocab_topics'] as $key => $value) {
    if (is_int($key)) {
      if (isset($value['#plain_text'])) {
        $topics[] = [
          'label' => $value['#plain_text'],
        ];
      }
      else {
        $topics[] = [
          'path' => $value['#url'],
          'label' => $value['#title'],
        ];
      }
    }
  }

  $variables['topics'] = [
    'title' => $variables['elements']['field_vocab_topics']['#title'],
    'items' => $topics,
  ];
}

/**
 * Preprocess related stories full news/story.
 */
function _eic_community_story_related_stories(&$variables) {
  $related_stories = [];

  foreach ($variables['elements']['field_related_stories'] as $key => $value) {
    if (is_int($key)) {
      $related_stories[] = [
        'path' => $value['#url'],
        'title' => $value['#title'],
      ];
    }
  }

  if (count($related_stories) > 0) {
    $variables['stories'] = [
      'title' => $variables['elements']['field_related_stories']['#title'],
      'items' => $related_stories,
    ];
  }
}

/**
 * Preprocess video field full news/story.
 */
function _eic_community_story_video_field(&$variables) {
  if (isset($variables['content']['field_video'][0])) {
    $media = $variables['content']['field_video'][0]['#media'];

    switch ($media->bundle()) {
      case 'video':
        $file = File::load($media->field_media_video_file->target_id);
        $download_url = MediaHelper::formatMediaDownloadLink($media)->toString();
        $stream_target = StreamWrapperManager::getTarget($file->getFileUri());
        $variables['video_download'] = [
          'title' => $media->getName(),
          'language' => $media->language()->getName(),
          'timestamp' => eic_community_get_teaser_time_display($media->get('changed')->getString()),
          'filesize' => format_size($file->get('filesize')->getString()),
          'highlight' => FALSE,
          'path' => $download_url,
          'icon_file_path' => $variables['eic_icon_path'],
          'icon' => [
            'type' => 'general',
            'name' => 'video',
          ],
          'stream_target' => $stream_target,
        ];
        break;

    }
  }
}
