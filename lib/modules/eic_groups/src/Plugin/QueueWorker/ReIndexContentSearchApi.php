<?php

namespace Drupal\eic_groups\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'eic_groups_reindex_content' queue worker.
 *
 * @QueueWorker(
 *   id = "eic_groups_reindex_content",
 *   title = @Translation("Task worker: Update content in search_api"),
 * )
 */
class ReIndexContentSearchApi extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The SOLR Document Processor service.
   *
   * @var SolrDocumentProcessor $solrDocumentProcessor
   */
  private $solrDocumentProcessor;

  /**
   * Constructs a new ReIndexContentSearchApi instance.
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
   * @param SolrDocumentProcessor $solr_document_processor
   *   The SOLR Document Processor service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SolrDocumentProcessor $solr_document_processor) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('eic_search.solr_document_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->solrDocumentProcessor->reIndexEntities($data);
  }

}
