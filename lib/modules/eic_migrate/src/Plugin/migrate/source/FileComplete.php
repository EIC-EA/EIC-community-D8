<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\file\Plugin\migrate\source\d7\File;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Drupal 7 file complete source from database.
 *
 * This includes the field_file_image_alt_text and field_file_image_title_text
 * fields data on top of the default d7_file data.
 *
 * Usage:
 *
 * @code
 * source:
 *   plugin: eic_d7_file_complete
 *   file_type: image
 *   exclude_photos: true
 * @endcode
 *
 * @MigrateSource(
 *   id = "eic_d7_file_complete",
 *   source_module = "file"
 * )
 */
class FileComplete extends File {

  /**
   * The file type to select.
   *
   * @var string
   */
  protected $fileType;

  /**
   * Exclude files which are linked to Photo nodes.
   *
   * @var bool
   */
  protected $excludePhotos;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);

    // Set up some defaults based on the source configuration.
    foreach (['fileType' => 'file_type', 'excludePhotos' => 'exclude_photos'] as $property => $config_key) {
      if (isset($configuration[$config_key])) {
        $this->$property = (bool) $configuration[$config_key];
      }
    }
    $this->fileType = !empty($configuration['file_type']) ? $configuration['file_type'] : NULL;
    $this->excludePhotos = !empty($configuration['exclude_photos']) ? $configuration['exclude_photos'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    // Only fetch files from the configured file type.
    if (!empty($this->fileType)) {
      $query->where('f.type = :file_type', [':file_type' => $this->fileType]);
    }

    // Exclude files which are attached to Photo nodes.
    if ($this->excludePhotos) {
      $query->leftJoin('field_data_c4m_media', 'm', 'f.fid = m.c4m_media_fid AND m.bundle = :photo_bundle', [':photo_bundle' => 'photo']);
      $query->where('m.entity_id IS NULL');
    }

    // Include image alt and title text.
    $query->leftJoin('field_data_field_file_image_alt_text', 'a', 'f.fid = a.entity_id');
    $query->leftJoin('field_data_field_file_image_title_text', 't', 'f.fid = t.entity_id');
    $query->fields('a', ['field_file_image_alt_text_value']);
    $query->fields('t', ['field_file_image_title_text_value']);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();

    $fields['field_file_image_alt_text'] = $this->t('Image alt text.');
    $fields['field_file_image_title_text'] = $this->t('Image title text.');

    return $fields;
  }

}
