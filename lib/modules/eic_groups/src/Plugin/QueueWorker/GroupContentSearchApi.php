<?php

namespace Drupal\eic_groups\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'eic_groups_group_content_url_alias_update' queue worker.
 *
 * @QueueWorker(
 *   id = "eic_groups_group_content_search_api",
 *   title = @Translation("Task worker: Update group content into search_api"),
 * )
 */
class GroupContentSearchApi extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The Entity Type Manager service.
   *
   * @var EntityTypeManagerInterface $entityTypeManager
   */
  private $entityTypeManager;

  /**
   * The SOLR Document Processor service.
   *
   * @var SolrDocumentProcessor $solrDocumentProcessor
   */
  private $solrDocumentProcessor;

  /**
   * Constructs a new GroupContentSearchApi instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The pathauto generator.
   * @param SolrDocumentProcessor $solr_document_processor
   *   The SOLR Document Processor service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, SolrDocumentProcessor $solr_document_processor) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->solrDocumentProcessor = $solr_document_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('eic_search.solr_document_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->solrDocumentProcessor->reIndexEntities([$data->getEntity()]);
  }

}
