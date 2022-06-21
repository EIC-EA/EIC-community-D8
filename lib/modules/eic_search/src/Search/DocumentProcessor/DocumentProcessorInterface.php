<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Solarium\QueryType\Update\Query\Document;

/**
 * Interface DocumentProcessorInterface
 *
 * @package Drupal\eic_groups\Search\DocumentProcessor
 */
interface DocumentProcessorInterface {

  public const SOLR_MOST_ACTIVE_ID = "its_activity_score";

  public const SOLR_MOST_ACTIVE_ID_GROUP = "its_activity_score_group";

  public const SOLR_GROUP_ROLES = "sm_roles_group";

  public const SOLR_FIELD_NEED_GROUP_INJECT = [
    self::SOLR_GROUP_ROLES,
    self::SOLR_MOST_ACTIVE_ID_GROUP,
  ];

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param array $fields
   *
   * @return void
   */
  public function process(Document &$document, array $fields, array $items = []): void;

  /**
   * Return true if processor's condition match.
   *
   * @param array $fields
   *
   * @return bool
   */
  public function supports(array $fields): bool;

}
