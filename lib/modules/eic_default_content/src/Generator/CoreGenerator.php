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
   * {@inheritdoc}
   *
   * Override to add accessCheck(FALSE) for Drupal 10 compatibility.
   */
  protected function getRandomEntities($entity_type_id, array $conditions = [], $limit = 5) {
    static $entities;

    // Generate a hash for static storage of query results per condition set.
    asort($conditions);
    $hash = md5($entity_type_id . ':' . json_encode($conditions));

    if (!isset($entities[$hash])) {
      $query = \Drupal::entityQuery($entity_type_id)
        ->accessCheck(FALSE);

      if ($conditions) {
        foreach ($conditions as $key => $value) {
          $query->condition($key, $value);
        }
      }

      $ids = $query->execute();
      if ($ids) {
        $entities[$hash] = \Drupal::entityTypeManager()
          ->getStorage($entity_type_id)
          ->loadMultiple($ids);
      }
      else {
        $entities[$hash] = [];
      }
    }

    if (empty($entities[$hash])) {
      return [];
    }

    $shuffled = $entities[$hash];
    shuffle($shuffled);
    return array_slice($shuffled, 0, $limit);
  }

  /**
   * Create a file with a random image.
   *
   * @param string $wrapper
   *   The stream wrapper to use.
   *
   * @return \Drupal\file\FileInterface|null
   *   The created file entity or NULL on failure.
   */
  protected function getRandomImage(string $wrapper = 'public://') {
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

    $file = \Drupal::service('file.repository')->writeData($data, $destination . $basename, FileSystemInterface::EXISTS_REPLACE);
    $images[] = $file;

    return $file;
  }

}
