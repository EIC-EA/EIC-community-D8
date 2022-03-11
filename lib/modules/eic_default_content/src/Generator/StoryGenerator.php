<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\eic_moderation\Constants\EICContentModeration;
use Drupal\node\Entity\Node;

/**
 * Class StoryGenerator
 *
 * @package Drupal\eic_default_content\Generator
 */
class StoryGenerator extends CoreGenerator {

  /**
   * {@inheritdoc}
   */
  public function load() {
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'title' => "Story #$i",
        'type' => 'story',
        'field_body' => $this->getFormattedText('full_html'),
        'field_introduction' => $this->getFormattedText('full_html'),
        'field_header_visual' => $this->createMedia([
          'oe_media_image' => $this->getRandomImage(),
        ], 'image'),
        'field_image' => $this->createMedia([
          'oe_media_image' => $this->getRandomImage(),
        ], 'image'),
        'field_image_caption' => $this->faker->sentence(10),
        'field_vocab_topics' => $this->getRandomEntities('taxonomy_term', ['vid' => 'topics'], 1),
        'field_vocab_geo' => $this->getRandomEntities('taxonomy_term', ['vid' => 'geo'], 1),
        'moderation_state' => EICContentModeration::STATE_PUBLISHED,
      ];

      $node = Node::create($values);
      $node->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('node', ['type' => 'story']);
  }

}
