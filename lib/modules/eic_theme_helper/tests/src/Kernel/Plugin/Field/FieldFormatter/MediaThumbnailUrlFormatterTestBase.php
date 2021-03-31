<?php

declare(strict_types = 1);

namespace Drupal\Tests\eic_theme_helper\Kernel\Plugin\Field\FieldFormatter;

use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\oe_theme\Kernel\AbstractKernelTestBase;

/**
 * Base class for formatters rendering media thumbnail URLs.
 */
class MediaThumbnailUrlFormatterTestBase extends AbstractKernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'entity_test',
    'media',
    'image',
    'file',
    'entity_reference',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');

    $this->installConfig([
      'file',
      'field',
      'entity_reference',
      'media',
    ]);
  }

  /**
   * Create a media of type "image".
   *
   * @param string $filepath
   *   Full path to image file.
   *
   * @return \Drupal\media\Entity\Media
   *   Media object.
   */
  protected function createMediaImage(string $filepath): Media {
    $media_type = $this->createMediaType('image');

    $file = file_save_data(file_get_contents($filepath), 'public://' . $this->container->get('file_system')->basename($filepath));
    $file->setPermanent();
    $file->save();

    /** @var \Drupal\media\Entity\Media $media */
    $media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'test image',
      'field_media_file' => [
        'target_id' => $file->id(),
      ],
    ]);
    $media->save();

    return $media;
  }

}
