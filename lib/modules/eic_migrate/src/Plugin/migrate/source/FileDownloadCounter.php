<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Drupal 7 file download counter source from database.
 *
 * @MigrateSource(
 *   id = "eic_d7_file_download_counter",
 *   source_module = "eic_migrate"
 * )
 */
class FileDownloadCounter extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Source data is queried from 'file_download_count' table.
    $query = $this->select('file_download_count', 'f');
    $query->fields('f', [
      'fid',
    ]);
    // Counts the number of downloads per file.
    $query->addExpression('COUNT(fid)', 'download_count');
    // Gets the timestamp of the latest download.
    $query->addExpression('MAX(timestamp)', 'download_timestamp');
    $query->groupBy('f.fid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'fid' => $this->t('File ID'),
      'download_count' => $this->t('File download count'),
      'download_timestamp' => $this->t('Timestamp of the latest download'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'fid' => [
        'type' => 'integer',
        'alias' => 'f',
      ],
    ];
  }

}
