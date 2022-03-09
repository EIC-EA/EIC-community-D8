<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Drupal 7 photoalbum photo relationship source from database.
 *
 * Usage:
 *
 * @code
 * source:
 *   plugin: eic_d7_photoalbum_photo_relationship
 * @endcode
 *
 * @MigrateSource(
 *   id = "eic_d7_photoalbum_photo_relationship",
 *   source_module = "eic_migrate",
 * )
 */
class PhotoalbumPhotoRelationship extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node_gallery_relationship', 'ngr')
      ->fields('ngr');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('id'),
      'relationship_type' => $this->t('relationship_type'),
      'nid' => $this->t('nid'),
      'ngid' => $this->t('ngid'),
      'weight' => $this->t('weight'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [
      'ngid' => [
        'type' => 'integer',
      ],
      'nid' => [
        'type' => 'integer',
      ],
    ];
    return $ids;
  }

}
