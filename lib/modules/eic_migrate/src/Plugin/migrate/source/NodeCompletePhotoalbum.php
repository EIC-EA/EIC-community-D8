<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Gets Photoalbums with all relationships to Photo.
 *
 * @MigrateSource(
 *   id = "eic_d7_node_complete_photoalbum",
 *   source_module = "node"
 * )
 */
class NodeCompletePhotoalbum extends NodeCompleteWithSMEDIds {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nodeGalleryRelationships = $this->getNodeGalleryRelationships($row->getSourceProperty('nid'));
    $row->setSourceProperty('node_gallery_relationship', $nodeGalleryRelationships);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['node_gallery_relationship'] = $this->t('Array of Photo node gallery ids and node ids from the Node Gallery Relationship.');
    return $fields;
  }

  /**
   * Get node gallery relationships.
   *
   * @param int $ngid
   *   Node gallery ID.
   *
   * @return array
   *   Array of ngid and nid.
   */
  protected function getNodeGalleryRelationships(int $ngid) {
    $nodeGalleryRelationshipsQuery = $this->select('node_gallery_relationship', 'ngr')
      ->fields('ngr', ['ngid', 'nid'])
      ->where('ngr.ngid = :ngid', [':ngid' => $ngid])
      ->orderBy('ngr.weight')
      ->orderBy('ngr.nid');
    $nodeGalleryRelationships = $nodeGalleryRelationshipsQuery->execute()->fetchAll();

    return $nodeGalleryRelationships;
  }

}
