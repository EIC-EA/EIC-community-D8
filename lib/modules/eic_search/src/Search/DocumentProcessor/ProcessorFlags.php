<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\Database\Connection;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\flag\FlagCountManager;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorFlags
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorFlags extends DocumentProcessor {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * The flag count manager.
   *
   * @var \Drupal\flag\FlagCountManager
   */
  private $flagCountManager;

  /**
   * @param \Drupal\Core\Database\Connection $connection
   *   The current active database's master connection.
   * @param \Drupal\flag\FlagCountManager $flag_count_manager
   *   The flag count manager.
   */
  public function __construct(
    Connection $connection,
    FlagCountManager $flag_count_manager
  ) {
    $this->connection = $connection;
    $this->flagCountManager = $flag_count_manager;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $entity_id = NULL;
    $entity_type = NULL;
    $last_flagging_flag_types = [];

    switch ($fields['ss_search_api_datasource']) {
      case 'entity:node':
        $entity_id = $fields['its_content_nid'];
        $entity_type = 'node';
        $last_flagging_flag_types = [
          FlagType::BOOKMARK_CONTENT,
          FlagType::HIGHLIGHT_CONTENT,
          FlagType::LIKE_CONTENT,
        ];

        $node = Node::load($entity_id);
        $flags_count = $this->flagCountManager->getEntityFlagCounts($node);

        $this->addOrUpdateDocumentField(
          $document,
          'its_flag_like_content',
          $fields,
          isset($flags_count['like_content']) ? $flags_count['like_content'] : 0
        );
        break;
      case 'entity:group':
        $entity_id = $fields['its_group_id_integer'];
        $entity_type = 'group';

        $node = Group::load($entity_id);
        $flags_count = $this->flagCountManager->getEntityFlagCounts($node);

        $this->addOrUpdateDocumentField(
          $document,
          'its_flag_recommend_group',
          $fields,
          isset($flags_count['recommend_group']) ? $flags_count['recommend_group'] : 0
        );
        break;
    }

    // If we don't have a proper entity ID and type, skip this document.
    if (empty($entity_id) || empty($entity_type)) {
      return;
    }

    // Get the last flagging timestamp for each of the targeted flag types.
    foreach ($last_flagging_flag_types as $flag_type) {
      // Unfortunately flaggings don't have timestamps, so we grab the
      // last_updated from the flag_counts table.
      $result = $this->connection->select('flag_counts', 'fc')
        ->fields('fc', ['count', 'last_updated'])
        ->condition('flag_id', $flag_type)
        ->condition('entity_type', $entity_type)
        ->condition('entity_id', $entity_id)
        ->execute()->fetchAssoc();
      if (!empty($result['last_updated'])) {
        $document->addField('its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . $flag_type, $result['last_updated']);
      }
    }
  }

}
