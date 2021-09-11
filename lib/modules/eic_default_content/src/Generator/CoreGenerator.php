<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\Core\File\FileSystemInterface;
use Drupal\data_fixtures\AbstractGenerator;
use Drupal\data_fixtures\Interfaces\Generator;
use Drupal\media\Entity\Media;
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
   * @param array $fields
   * @param string $bundle
   *
   * @return \Drupal\media\MediaInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createMedia(array $fields, string $bundle) {
    $media = Media::create([
        'bundle' => $bundle,
      ] + $fields);

    $media->save();

    return $media;
  }

  /**
   * Create a file with a random image.
   *
   * @return array|NULL
   */
  protected function getRandomImage(string $wrapper = 'private://') {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $data = file_get_contents('https://picsum.photos/1280/964.jpg');
    if (!$data) {
      return NULL;
    }

    $destination = "$wrapper://fixtures/";
    $basename = pathinfo($file_system->tempnam($destination, 'eic_'), PATHINFO_BASENAME) . '.jpg';
    $file_system->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);

    $file = file_save_data($data, $destination . $basename, FileSystemInterface::EXISTS_REPLACE);

    return [
      'target_id' => $file->id(),
      'target_revision_id' => $file->getRevisionId(),
    ];
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
