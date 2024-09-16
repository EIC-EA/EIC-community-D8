<?php

namespace Drupal\eic_projects\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'eic_projects_cordis_extraction_worker' queue worker.
 *
 * @QueueWorker(
 *   id = "eic_projects_cordis_extraction_worker",
 *   title = @Translation("Cordis Extraction Worker"),
 *   cron = {"time" = 60}
 * )
 */
class CordisExtractionWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {


  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // TODO: Implement create() method.
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $running_entity_id = $data;
    $cordis_service = \Drupal::service('eic_projects.data_extraction');
    $extraction_entity_ids = \Drupal::entityQuery('extraction_request')
      ->condition('extraction_status', 'pending_extraction')
      ->execute();
    // todo also get 'extracted' requests to retry the file download.
    if (count($extraction_entity_ids) > 0) {
      foreach ($extraction_entity_ids as $extraction_entity_id) {
        if ($extraction_entity_id === $running_entity_id) {
          //todo check status. if status completed, extract zip, if not, keep it in queue
          // @todo Process data here.
          $status = $cordis_service->getStatus($running_entity_id);
          switch ($status['progress']) {
            case 'Progress':
              // still waiting for the extraction to be completed.
              throw new DelayedRequeueException();
            case 'Finished':
              $url = 'https://cordis.europa.eu' . $status['destinationFileUri'][0];
              $extr_file = system_retrieve_file($url, destination: 'private://cordis-xml/', managed: TRUE);
              $extraction_entity = \Drupal::entityTypeManager()->getStorage('extraction_request')->load($extraction_entity_id);
              if ($extr_file instanceof FileInterface) {
                // download successful
                $extraction_entity
                  ->set('extraction_file', $extr_file->id())
                  ->set('extraction_status', 'pending_migration')
                  ->save();
                /** @var \Drupal\file\FileInterface $zip_file */
                $zip_file = $extraction_entity->get('extraction_file')->entity;
                $filepath = \Drupal::service('file_system')->realpath($zip_file->getFileUri());
                $filename = pathinfo($filepath, PATHINFO_FILENAME);
                $private_dir_path = \Drupal::service('file_system')->realpath("private://");

                $zip = new \Drupal\Core\Archiver\Zip($filepath);
                $zip->extract("$private_dir_path/cordis-xml/export/$filename");
                $export_zip = new \Drupal\Core\Archiver\Zip("$private_dir_path/cordis-xml/export/$filename/xml.zip");
                $export_zip->extract("$private_dir_path/cordis-xml/export/$filename");
                // todo send request to delete extraction after the file has been downloaded
              }
              elseif (!$extr_file) {
                // For some reason we couldn't download the file, so we have to
                // retry the download
                $extraction_entity
                  ->set('extraction_status', 'extracted')
                  ->save();
              }
          }
        }
        else {
          // todo if status extracted, re-download the file
          throw new DelayedRequeueException();
        }
      }

      // This means there is already an extraction going on, so we cannot
      //  request another one due to CORDIS Data Extraction requirements.
      throw new DelayedRequeueException();
    }
    else {
      // No extraction is pending, proceed with a new one.
      $cordis_service->requestExtraction($data);
    }

  }


}
