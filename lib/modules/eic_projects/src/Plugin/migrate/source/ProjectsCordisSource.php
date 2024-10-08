<?php

namespace Drupal\eic_projects\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

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

    foreach ($requests as $request) {
      /** @var \Drupal\file\FileInterface $zip_file */
      $zip_file = $request->get('extraction_file')->entity;
      $filepath = \Drupal::service('file_system')->realpath($zip_file->getFileUri());
      $filename = pathinfo($filepath, PATHINFO_FILENAME);

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
          'euroscivocCode' => $this->getEuroSciVocCode($xpath),
          'fundingProgramme' => $this->getFundingProgramme($xpath),
          'stakeholder_coordinators' => $this->getOrganisation($xpath, 'coordinator'),
          'stakeholder_participants' => $this->getOrganisation($xpath, 'participant'),
        ];
      }
//      $request->set('extraction_status', 'migrating')->save();
    }

    return new \ArrayIterator($records);
  }
  private function getOrganisation(\DOMXPath $xpath, string $type) {
    $query = $xpath->query("/project/relations/associations/organization[@type='$type']");
    $count = $query->count();
    $organisations = [];
    for ($i = 0; $i < $count; $i++) {
      $value = $query->item($i);
      if (!is_null($value)) {
        $name = $value->getElementsByTagName('legalName')->item(0)->nodeValue;
        $country_code = $xpath->query("relations/regions/region/euCode", $value)->item(0)->nodeValue;
        $country_name = $xpath->query("relations/regions/region[@type='relatedRegion']/name", $value)->item(0)->nodeValue;
        $pic = $value->getElementsByTagName('id')->item(0)->nodeValue;
        $organisations[] = [
          'name' => $name,
          'country_code' => $country_code,
          'country_name' => $country_name,
          'pic' => $pic,
        ];
      }
    }

    return $organisations;
  }

  private function getFundingProgramme(\DOMXPath $xpath) {
    $query = $xpath->query("/project/relations/associations/programme[@type='relatedLegalBasis']");
    $count = $query->count();
    $programmes = [];
    for ($i = 0; $i < $count; $i++) {
      $value = $query->item($i);
      if (!is_null($value)) {
        $id = $value->getElementsByTagName('frameworkProgramme')->item(0)->nodeValue;
        $programmes[] = $id;
      }
    }

    return $programmes;
  }

  private function getEuroSciVocCode(\DOMXPath $xpath) {
    $query = $xpath->query("/project/relations/categories/category[@classification='euroSciVoc' and @type='isInFieldOfScience']/code");
    $nodeValue = '';
    $count = $query->count();
    for ($i = 0; $i < $count; $i++) {
      $value = $query->item($i);
      if (!is_null($value)) {
        $code = explode('/', $value->nodeValue)[1];
        if (!str_contains($nodeValue, ",$code,")) {
          $nodeValue .= "$code,";
        }
      }
    }

    if (!empty($nodeValue)) {
      $nodeValue = rtrim($nodeValue, ',');
    }

    return $nodeValue;
  }

  private function getXmlValue(\DOMXPath $xpath, string $query): ?string {
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
      'euroscivocCode' => $this->t('Project field of science'),
      'fundingProgramme' => $this->t('Project funding programme'),
      'stakeholder_coordinators' => $this->t('Project Organisation coordinators'),
      'stakeholder_participants' => $this->t('Project Organisation participants'),
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

}
