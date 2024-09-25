<?php

namespace Drupal\eic_projects\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\eic_projects\CordisExtractionService;
use Drupal\file\FileInterface;
use Psr\Log\LoggerInterface;
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

  private CordisExtractionService $cordisExtractionService;

  private EntityTypeManagerInterface $entityTypeManager;

  private FileSystemInterface $fileSystem;

  private LoggerInterface $logger;

  /**
   * Main constructor.
   *
   * @param array $configuration
   *   Configuration array.
   * @param mixed $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $cordisExtractionService, $entityTypeManager, $fileSystem, $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cordisExtractionService = $cordisExtractionService;
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->logger = $logger;
  }


  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_projects.data_extraction'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('logger.factory')->get('eic_projects'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $running_entity_id = $data;
    $extraction_entity_ids = $this->entityTypeManager
      ->getStorage('extraction_request')->getQuery()
      ->condition('extraction_status', 'pending_extraction')
      ->execute();
    // todo also get 'extracted' requests to retry the file download.
    if (count($extraction_entity_ids) > 0) {
      foreach ($extraction_entity_ids as $extraction_entity_id) {
        if ($extraction_entity_id === $running_entity_id) {
          $status = $this->cordisExtractionService->getStatus($running_entity_id);
          if ($status) {
            switch ($status['progress']) {
              case 'Ongoing':
                // still waiting for the extraction to be completed.
                throw new DelayedRequeueException();
              case 'Finished':
                $url = 'https://cordis.europa.eu' . $status['destinationFileUri'];
                $extr_file = system_retrieve_file($url, destination: 'private://cordis-xml/', managed: TRUE);
                $extraction_entity = $this->entityTypeManager->getStorage('extraction_request')->load($extraction_entity_id);
                if ($extr_file instanceof FileInterface) {
                  // Download successful.
                  $extraction_entity
                    ->set('extraction_file', $extr_file->id())
                    ->set('extraction_status', 'pending_migration')
                    ->save();
                  $filepath = $this->fileSystem->realpath($extr_file->getFileUri());
                  $filename = pathinfo($filepath, PATHINFO_FILENAME);
                  $private_dir_path = $this->fileSystem->realpath("private://");

                  $zip = new \Drupal\Core\Archiver\Zip($filepath);
                  $zip->extract("$private_dir_path/cordis-xml/export/$filename");
                  $export_zip = new \Drupal\Core\Archiver\Zip("$private_dir_path/cordis-xml/export/$filename/xml.zip");
                  $export_zip->extract("$private_dir_path/cordis-xml/export/$filename");

                  // Delete extraction from API.
                  $this->cordisExtractionService->deleteExtraction($extraction_entity_id);
                }
                elseif (!$extr_file) {
                  // For some reason we couldn't download the file, so we have to
                  // retry the download
                  $extraction_entity
                    ->set('extraction_status', 'extracted')
                    ->save();
                }
                break;
              case 'Failed':
                $this->logger->error("Extraction with ID $extraction_entity_id could not take place due to an error.");
            }
          }
          else {
            $this->logger->error("Extraction with ID $extraction_entity_id could not take place due to an error.");
          }

        }
        else {
          // todo if status extracted, re-download the file
          // This means there is already an extraction going on, so we cannot
          //  request another one due to CORDIS Data Extraction requirements.
          throw new DelayedRequeueException();
        }
      }
    }
    else {
      // No extraction is pending, proceed with a new one.
      $this->cordisExtractionService->requestExtraction($data);
      throw new DelayedRequeueException();
    }

  }


}
