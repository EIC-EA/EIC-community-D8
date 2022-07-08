<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\fragments\Entity\Fragment;
use Drupal\fragments\Entity\FragmentInterface;

/**
 * Class to generate fragments using fixtures.
 *
 * @package Drupal\eic_default_content\Generator
 */
class FragmentGenerator extends CoreGenerator {

  use StringTranslationTrait;

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
    $this->createNewsDislcaimerFragment();
  }

  /**
   * Creates a new disclaimer fragment for the News and Stories.
   *
   * @return \Drupal\fragments\Entity\FragmentInterface
   *   The fragment entity.
   */
  private function createNewsDislcaimerFragment(): FragmentInterface {
    $fragments = $this->entityTypeManager->getStorage('fragment')
      ->loadByProperties([
        'type' => 'disclaimer',
      ]
    );

    if (!empty($fragments)) {
      return reset($fragments);
    }

    $fragment = Fragment::create([
      'type' => 'disclaimer',
      'title' => 'Disclaimer',
      'field_body' => $this->getFormattedText(
        'full_html',
        'DISCLAIMER: This information is provided in the interest of knowledge sharing and should not be interpreted as the official view of the European Commission, or any other organisation.'
      ),
    ]);
    $fragment->save();
    return $fragment;
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('fragment', ['type' => 'disclaimer']);
  }

}
