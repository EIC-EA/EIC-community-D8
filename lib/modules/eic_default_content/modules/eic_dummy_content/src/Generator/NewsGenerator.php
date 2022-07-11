<?php

namespace Drupal\eic_dummy_content\Generator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_default_content\Generator\CoreGenerator;
use Drupal\eic_moderation\Constants\EICContentModeration;
use Drupal\eic_private_content\PrivateContentConst;
use Drupal\fragments\Entity\Fragment;
use Drupal\fragments\Entity\FragmentInterface;
use Drupal\node\Entity\Node;

/**
 * Class to generate news using fixtures.
 *
 * @package Drupal\eic_dummy_content\Generator
 */
class NewsGenerator extends CoreGenerator {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct();

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $disclaimer = $this->getFragmentDisclaimer();

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
        'field_disclaimer' => $disclaimer,
        'moderation_state' => EICContentModeration::STATE_PUBLISHED,
        PrivateContentConst::FIELD_NAME => FALSE,
      ];

      $node = Node::create($values);
      $node->save();
    }
  }

  /**
   * Gets the default disclaimer fragment.
   *
   * @return \Drupal\fragments\Entity\FragmentInterface|null
   *   The fragment entity.
   */
  public function getFragmentDisclaimer(): ?FragmentInterface {
    $fragments = $this->entityTypeManager->getStorage('fragment')
      ->loadByProperties([
        'type' => 'disclaimer',
      ]
    );

    if (empty($fragments)) {
      $fragment = Fragment::create([
        'type' => 'disclaimer',
        'title' => 'Default disclaimer',
        'field_body' => $this->getFormattedText(
          'full_html',
          'DISCLAIMER: This information is provided in the interest of knowledge sharing and should not be interpreted as the official view of the European Commission, or any other organisation.'
        ),
      ]);
      $fragment->save();
      return $fragment;
    }
    return reset($fragments);
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('node', ['type' => 'news']);
  }

}
