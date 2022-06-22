<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\eic_flags\FlagType;
use Drupal\eic_group_statistics\GroupStatisticsHelper;
use Drupal\eic_search\Search\Sources\GlobalSourceType;
use Drupal\eic_search\Service\SolrSearchManager;
use Drupal\flag\FlagCountManagerInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Provides a processor for group entity.
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorGroup extends DocumentProcessor {

  /**
   * The EIC Solr search manager.
   *
   * @var \Drupal\eic_search\Service\SolrSearchManager|null
   */
  private ?SolrSearchManager $solrSearchManager;

  /**
   * The EIC Group statistics helper service.
   *
   * @var \Drupal\eic_group_statistics\GroupStatisticsHelper|null
   */
  private ?GroupStatisticsHelper $groupStatisticsHelper;

  /**
   * The Flag count manager.
   *
   * @var \Drupal\flag\FlagCountManagerInterface|null
   */
  private ?FlagCountManagerInterface $flagCountManager;

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  private $urlGenerator;

  /**
   * Constructs a new ProcessorGroup object.
   *
   * @param \Drupal\eic_search\Service\SolrSearchManager $solr_search_manager
   *   The EIC Solr search manager.
   * @param \Drupal\flag\FlagCountManagerInterface $flag_count_manager
   *   The Flag count manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $url_generator
   *   The URL generator service.
   */
  public function __construct(
    SolrSearchManager $solr_search_manager,
    FlagCountManagerInterface $flag_count_manager,
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $url_generator
  ) {
    $this->solrSearchManager = $solr_search_manager;
    $this->flagCountManager = $flag_count_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->urlGenerator = $url_generator;
  }

  /**
   * Sets the EIC Group statistics helper service.
   *
   * @param \Drupal\eic_group_statistics\GroupStatisticsHelper $groupStatisticsHelper
   *   The EIC Group statistics herlper service.
   */
  public function setGroupStatistics(GroupStatisticsHelper $groupStatisticsHelper) {
    $this->groupStatisticsHelper = $groupStatisticsHelper;
  }

  /**
   * {@inheritdoc}
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

    $fid = array_key_exists('its_group_teaser_fid', $fields) ?
      $fields['its_group_teaser_fid'] :
      NULL;

    $teaser_relative = '';

    // Generates image style for the group teaser.
    if ($fid) {
      /** @var \Drupal\image\Entity\ImageStyle $image_style */
      $image_style = $this->entityTypeManager->getStorage('image_style')
        ->load('oe_theme_ratio_3_2_medium');
      /** @var \Drupal\file\Entity\File $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      $image_uri = $file->getFileUri();
      $teaser_relative = $this->urlGenerator->transformRelative($image_style->buildUrl($image_uri));
    }

    $this->addOrUpdateDocumentField(
      $document,
      'ss_group_teaser_formatted_image',
      $fields,
      $teaser_relative
    );
  }

  /**
   * {@inheritdoc}
   */
  public function supports(array $fields): bool {
    $datasource = $fields['ss_search_api_datasource'];

    return $datasource === 'entity:group';
  }

}
