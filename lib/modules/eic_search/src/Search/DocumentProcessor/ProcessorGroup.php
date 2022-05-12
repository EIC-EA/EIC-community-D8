<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\eic_flags\FlagType;
use Drupal\eic_group_statistics\GroupStatisticsHelper;
use Drupal\eic_search\Search\Sources\GlobalSourceType;
use Drupal\eic_search\Service\SolrSearchManager;
use Drupal\flag\FlagCountManagerInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorGroup
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorGroup extends DocumentProcessor {

  /**
   * @var \Drupal\eic_search\Service\SolrSearchManager|NULL
   */
  private ?SolrSearchManager $solrSearchManager;

  /**
   * @var \Drupal\eic_group_statistics\GroupStatisticsHelper|NULL
   */
  private ?GroupStatisticsHelper $groupStatisticsHelper;

  /**
   * @var \Drupal\flag\FlagCountManagerInterface|NULL
   */
  private ?FlagCountManagerInterface $flagCountManager;

  /**
   * @param \Drupal\eic_search\Service\SolrSearchManager $solr_search_manager
   * @param \Drupal\eic_group_statistics\GroupStatisticsHelper $group_statistics_helper
   * @param \Drupal\flag\FlagCountManagerInterface $flag_count_manager
   */
  public function __construct(
    SolrSearchManager $solr_search_manager,
    GroupStatisticsHelper $group_statistics_helper,
    FlagCountManagerInterface $flag_count_manager
  ) {
    $this->solrSearchManager = $solr_search_manager;
    $this->groupStatisticsHelper = $group_statistics_helper;
    $this->flagCountManager = $flag_count_manager;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $group_id = $fields['its_group_id_integer'] ?? NULL;

    if (!$group_id) {
      return;
    }

    $group = Group::load($group_id);

    if (!$group instanceof GroupInterface) {
      return;
    }

    // Count comments and members.
    $total_comments = (int) $this->groupStatisticsHelper->loadGroupStatistics($group)->getCommentsCount();
    $total_members = (int) $this->groupStatisticsHelper->loadGroupStatistics($group)->getMembersCount();

    // Count total contents of group.
    $this->solrSearchManager->init(GlobalSourceType::class, []);
    $this->solrSearchManager->buildGroupQuery($group_id);
    $results = $this->solrSearchManager->search();
    $results = json_decode($results, TRUE);
    $total_contents = !empty($results) ? (int) $results['response']['numFound'] : 0;

    // Count likes and follows.
    $flag_counts = $this->flagCountManager->getEntityFlagCounts($group);
    $total_likes = array_key_exists(FlagType::RECOMMEND_GROUP, $flag_counts) ?
      (int) $flag_counts[FlagType::RECOMMEND_GROUP] :
      0;
    $total_follows = array_key_exists(FlagType::FOLLOW_GROUP, $flag_counts) ?
      (int) $flag_counts[FlagType::FOLLOW_GROUP] :
      0;

    $most_active_total = $total_contents * 3 + $total_comments * 3 + $total_likes * 2 + $total_follows * 2 + $total_members;

    $this->addOrUpdateDocumentField(
      $document,
      DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID,
      $fields,
      $most_active_total
    );
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    $datasource = $fields['ss_search_api_datasource'];

    return $datasource === 'entity:group';
  }

}
