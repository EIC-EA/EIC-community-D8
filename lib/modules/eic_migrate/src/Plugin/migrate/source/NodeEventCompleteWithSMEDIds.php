<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Drupal 7 all event node revisions source, including translation revisions.
 *
 * Also includes SMED IDs for taxonomy term references.
 *
 * Usage:
 *
 * @code
 * source:
 *   plugin: eic_d7_node_complete_with_smed_ids
 *   smed_taxonomy_fields:
 *     - taxonomy_field_x
 *     - taxonomy_field_y
 * @endcode
 *
 *
 * For all available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "eic_d7_node_event_complete_with_smed_ids",
 *   source_module = "node"
 * )
 */
class NodeEventCompleteWithSMEDIds extends NodeCompleteWithSMEDIds {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $result = parent::prepareRow($row);

    $location = $row->getSourceProperty('c4m_location_address');
    if (!empty($location)) {
      $row->setSourceProperty('location_type', 'on_site');
    }
    else {
      $row->setSourceProperty('location_type', 'remote');
    }

    $valid_social_links = TRUE;

    $facebook_link = $row->getSourceProperty('c4m_facebook');
    if (
      !empty($facebook_link) &&
      strlen($facebook_link[0]['url']) > 128
    ) {
      $row->setSourceProperty('c4m_facebook', NULL);
      $valid_social_links = FALSE;
    }

    $twitter_link = $row->getSourceProperty('c4m_twitter');
    if (
      !empty($twitter_link) &&
      strlen($twitter_link[0]['url']) > 128
    ) {
      $row->setSourceProperty('c4m_twitter', NULL);
      $valid_social_links = FALSE;
    }

    $linkedin_link = $row->getSourceProperty('c4m_linkedin');
    if (
      !empty($linkedin_link) &&
      strlen($linkedin_link[0]['url']) > 128
    ) {
      $row->setSourceProperty('c4m_linkedin', NULL);
      $valid_social_links = FALSE;
    }

    if (!$valid_social_links) {
      $this->idMap->saveMessage($row->getSourceIdValues(), $this->t('Some social links could not be migrated.'), MigrationInterface::MESSAGE_INFORMATIONAL);
    }

    return $result;
  }

}
