<?php

/**
 * @file
 * Prepares variables for node video templates.
 */

/**
 * Implements hook_preprocess_node__bundle() for vide node.
 */
function eic_community_preprocess_node__video(array &$variables) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];

  switch ($variables['view_mode']) {
    case 'full':
      _eic_community_display_flags($variables);
      _eic_community_display_topics($variables);
      _eic_community_display_document_contributors($variables);

      // Provide the Social Share block.
      if ($share_block = _eic_community_get_social_share_block()) {
        $variables['social_share'] = $share_block;
      }
      break;

    case 'teaser':
      // Get the topics.
      $tags = [];
      if (!$node->get('field_vocab_topics')->isEmpty()) {
        foreach ($node->get('field_vocab_topics')->referencedEntities() as $term) {
          $tags[] = _eic_community_preprocess_ecl_tag($term)['tag'];
        }
      }

      $item = [
        'type' => [
          'label' => $node->type->entity->label(),
          'icon' => [
            'name' => $node->bundle(),
            'type' => 'custom',
          ],
        ],
        'tags' => $tags,
        'language' => $node->get('field_language')->entity->label(),
        'timestamp' => [
          'label' => eic_community_get_teaser_time_display($node->get('published_at')->value),
        ],
        'author' => eic_community_get_teaser_user_display($node->getOwner()),
      ];

      $variables['video_item'] = $item;
      break;
  }
}
