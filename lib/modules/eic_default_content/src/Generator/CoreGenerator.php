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
   * {@inheritdoc}
   */
  public function getLink($uri = NULL, $title = NULL, $type = NULL) {
    $link = parent::getLink($uri, $title);
    if ($type) {
      $link['link_type'] = $type;
    }

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function load();

  /**
   * {@inheritdoc}
   */
  abstract public function unLoad();

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
   * Create a file with a random image.
   *
   * @param string $wrapper
   *
   * @return \Drupal\file\FileInterface
   */
  protected function getRandomImage(string $wrapper = 'private://') {
    static $images;
    // To avoid downloading a lot of images, we only allow 3 random images
    // Passed this, we reuse those saved previously.
    if (is_array($images) && count($images) === 3) {
      return $this->faker->randomElement($images);
    }

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $data = file_get_contents('https://picsum.photos/1200/900.jpg');
    if (!$data) {
      return NULL;
    }

    $destination = $wrapper . 'fixtures/';
    $basename = pathinfo($file_system->tempnam($destination, 'eic_'), PATHINFO_BASENAME) . '.jpg';
    $file_system->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);

    $file = file_save_data($data, $destination . $basename, FileSystemInterface::EXISTS_REPLACE);
    $images[] = $file;

    return $file;
  }

}
