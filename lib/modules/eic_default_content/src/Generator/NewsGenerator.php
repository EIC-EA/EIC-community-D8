<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_private_content\PrivateContentConst;
use Drupal\node\Entity\Node;

/**
 * Class to generate news using fixtures.
 *
 * @package Drupal\eic_default_content\Generator
 */
class NewsGenerator extends CoreGenerator {

  /**
   * {@inheritdoc}
   */
  public function load() {
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'title' => "News #$i",
        'type' => 'news',
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
        'moderation_state' => DefaultContentModerationStates::PUBLISHED_STATE,
        PrivateContentConst::FIELD_NAME => FALSE,
      ];

      $node = Node::create($values);
      $node->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('node', ['type' => 'news']);
  }

}
