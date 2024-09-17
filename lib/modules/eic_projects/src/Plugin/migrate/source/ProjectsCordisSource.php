<?php

namespace Drupal\eic_projects\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;

/**
 * The 'projects_cordis' source plugin.
 *
 * @MigrateSource(
 *   id = "projects_cordis",
 *   source_module = "eic_projects"
 * )
 */
class ProjectsCordisSource extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return (string) $this->t('Importing CORDIS Data');
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {

    /** @var \Drupal\eic_projects\Entity\ExtractionRequest[] $requests */
    $requests = \Drupal::entityTypeManager()
      ->getStorage('extraction_request')
      ->loadByProperties(['extraction_status' => 'pending_migration']);

    $private_dir_path = \Drupal::service('file_system')->realpath("private://");
    $records = [];

//    foreach ($requests as $request) {
      /** @var \Drupal\file\FileInterface $zip_file */
//      $zip_file = $request->get('extraction_file')->entity;
//      $filepath = \Drupal::service('file_system')->realpath($zip_file->getFileUri());
//      $filename = pathinfo($filepath, PATHINFO_FILENAME);
      $filename = 'EXTRACTION_699495820_20240715123650901';

      // todo load XMLs from this path "$private_dir_path/cordis-xml/export/$filename/xml"
      $directory_iterator = new \RecursiveDirectoryIterator("$private_dir_path/cordis-xml/export/$filename/xml", \FilesystemIterator::KEY_AS_PATHNAME);
      $files = new \RecursiveIteratorIterator($directory_iterator);
      // -1 max_depth is for no-limit
      $files->setMaxDepth(-1);

      $file_mask = '/(.*\.(xml)$)/i';

      $files = new \RegexIterator($files, $file_mask, \RegexIterator::MATCH, \RegexIterator::USE_KEY);

      foreach ($files as $file) {
        $doc = new \DOMDocument();
        $doc->load($file->getPathname());
        $xpath = new \DOMXPath($doc);
        $records[] = [
          'id' => $this->getXmlValue($xpath, '/project/id') ?? 0,
          'title' => $this->getXmlValue($xpath, '/project/title'),
          'acronym' => $this->getXmlValue($xpath, '/project/acronym'),
          'startDate' => $this->getXmlValue($xpath, '/project/startDate') . 'T00:00:00',
          'endDate' => $this->getXmlValue($xpath, '/project/endDate') . 'T00:00:00',
          'totalCost' => $this->getXmlValue($xpath, '/project/totalCost'),
          'ecMaxContribution' => $this->getXmlValue($xpath, '/project/ecMaxContribution'),
          'objective' => $this->getXmlValue($xpath, '/project/objective'),
          'status' => $this->getXmlValue($xpath, '/project/status') ?? 'CLOSED',
          'teaser' => $this->getXmlValue($xpath, '/project/teaser'),
          'duration' => $this->getXmlValue($xpath, 'project/duration'),
        ];
      }
//    }

    return new \ArrayIterator($records);
  }

  private function getXmlValue(\DOMXPath $xpath, $query): ?string {
    $q = $xpath->query($query)->item(0);
    if (is_null($q)) {
      return FALSE;
    }
    else {
      return $q->nodeValue;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('Project Identifier'),
      'title' => $this->t('Project title'),
      'acronym' => $this->t('Project acronym'),
      'startDate' => $this->t('Start date of the project'),
      'endDate' => $this->t('End date of the project'),
      'totalCost' => $this->t('Total cost of the project'),
      'ecMaxContribution' => $this->t('Maximum contribution of the European Commission regarding the total cost'),
      'objective' => $this->t('Objective of the project'),
      'status' => $this->t('Project status'),
      'teaser' => $this->t('Project teaser'),
      'duration' => $this->t('Project duration'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id'] = [
      'type' => 'integer',
      'unsigned' => TRUE,
      'size' => 'big',
    ];
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // @DCG
    // Extend/modify the row here if needed.
    //
    // Example:
    // @code
    // $name = $row->getSourceProperty('name');
    // $row->setSourceProperty('name', Html::escape('$name');
    // @endcode
    return parent::prepareRow($row);
  }

}
