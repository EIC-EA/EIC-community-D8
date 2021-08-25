<?php

/**
 * @file
 * Prepares variables for node News and Story templates.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_private_content\PrivateContentConst;
use Drupal\file\Entity\File;

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

  $item = [
    'type' => [
      'label' => $node->type->entity->label(),
      'icon' => [
        'name' => $node->bundle(),
        'type' => 'custom',
      ],
    ],
    'timestamp' => [
      'label' => eic_community_get_teaser_time_display($node->get('published_at')->value),
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
    $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
    $item['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $image_item->getAlt(),
    ];
  }

  $variables['story_item'] = $item;

  if ($variables['view_mode'] === 'full') {
    // Main elements.
    _eic_community_story_image_field($variables);
    _eic_community_story_document_field($variables);
    // Sidebar elements.
    _eic_community_display_flags($variables);

    if ($node->hasField(PrivateContentConst::FIELD_NAME)
      && !$node->get(PrivateContentConst::FIELD_NAME)->value
    ) {
      $share_block = \Drupal::service('plugin.manager.block')
        ->createInstance('social_share', []);
      if ($share_block instanceof BlockPluginInterface) {
        $build = $share_block->build();
        $build['#title'] = t('Share');
        $variables['social_share'] = $build;
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
    $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
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
  if (isset($variables['content']['field_document_media'][0])) {
    $media = $variables['content']['field_document_media'][0]['#media'];
    $file = File::load($media->field_media_file->target_id);
    $file_description = isset($media->field_body) ? Markup::create(Xss::filter($media->get('field_body')->value)) : $media->getName();

    $variables['download'] = [
      'title' => $file_description,
      'language' => $media->language()->getName(),
      'timestamp' => eic_community_get_teaser_time_display($media->get('changed')->getString()),
      'filesize' => format_size($file->get('filesize')->getString()),
      'highlight' => FALSE,
      'path' => file_create_url($file->getFileUri()),
      'icon_file_path' => $variables['eic_icon_path'],
      'icon' => [
        'type' => 'custom',
        'name' => substr(strrchr($file->get('filemime')->getString(), '/'), 1),
      ],
    ];
  }
}

/**
 * Preprocess flags.
 */
function _eic_community_display_flags(&$variables) {
  $flags = array_filter($variables['elements'], function ($key) {
    return strpos($key, 'flag_') !== FALSE;
  }, ARRAY_FILTER_USE_KEY);

  foreach ($flags as $flag) {
    $variables['editorial_actions']['items'][] = [
      'content' => $flag,
    ];
  }
}

/**
 * Preproccess contributors.
 */
function _eic_community_display_contributors(&$variables) {
  if ($variables['node']->bundle() === 'story') {
    $contributors = [];

    foreach ($variables['elements']['field_story_paragraphs'] as $key => $value) {
      if (is_int($key)) {
        $contributors[] = [
          'content' => $value,
        ];
      }
    }

    if (count($contributors) > 0) {
      $variables['contributors'] = [
        'title' => $variables['elements']['field_story_paragraphs']['#title'],
        'items' => $contributors,
      ];
    }
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
