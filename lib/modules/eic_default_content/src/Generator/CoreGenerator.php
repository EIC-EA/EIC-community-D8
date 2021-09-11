<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\data_fixtures\AbstractGenerator;
use Drupal\data_fixtures\Interfaces\Generator;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;

/**
 * Class CoreGenerator
 *
 * @package Drupal\eic_content\Generator
 */
abstract class CoreGenerator extends AbstractGenerator implements Generator {

  /**
   * @param $definition
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createParagraph($definition) {
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = Paragraph::create($definition);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Create a taxonomy term for given vid
   */
  protected function createTerm($vid, $fields) {
    $term = Term::create(
      [
        'vid' => $vid,
      ] + $fields
    );

    $term->save();

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function load();

  /**
   * {@inheritdoc}
   */
  abstract public function unLoad();

}
