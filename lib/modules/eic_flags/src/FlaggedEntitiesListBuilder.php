<?php

namespace Drupal\eic_flags;

use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\flag\FlagService;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a class to build a listing of flags with type 'delete_request_*'.
 */
class FlaggedEntitiesListBuilder extends EntityListBuilder {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * @var string
   */
  protected $requestType;

  /**
   * @var \Drupal\eic_flags\Service\HandlerInterface|null
   */
  protected $requestHandler;

  /**
   * FlaggedEntitiesListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\flag\FlagService $flag_service
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    EntityTypeManagerInterface $entityTypeManager,
    Connection $database,
    DateFormatterInterface $date_formatter,
    Request $request,
    FlagService $flag_service,
    RequestHandlerCollector $collector
  ) {
    parent::__construct($entity_type, $storage);

    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
    $this->flagService = $flag_service;
    $this->requestType = $request->get('request_type');
    $this->requestHandler = $collector->getHandlerByType($this->requestType);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('flag'),
      $container->get('eic_flags.handler_collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Enable language column and filter if multiple languages are added.
    $header = [
      'title' => $this->t('Title'),
      'type' => [
        'data' => $this->t('Content type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'author' => [
        'data' => $this->t('Author'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'request_number' => [
        'data' => $this->t('Number of request'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'changed' => [
        'data' => $this->t('Changed'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'created' => [
        'data' => $this->t('Created'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    // Will be implemented with another PR
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return $this->t(
      'Content requested for @request-type',
      ['@request-type' => $this->requestType]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t(
        'There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];

    foreach ($this->load() as $result) {
      $entity = $result['entity'];
      if ($row = $this->buildRow($result)) {
        $build['table']['#rows'][$entity->getEntityTypeId() . '_' . $entity->id()] = $row;
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }
    return $build;
  }

  /**
   * @param array $result
   *
   * @return array
   */
  public function buildRow($result) {
    static $flags;
    /** @var \Drupal\Core\Entity\ContentEntityInterface $flagged_entity */
    $flagged_entity = $result['entity'];
    $supported_entity_types = $this->requestHandler->getSupportedEntityTypes();
    $entity_type_id = $flagged_entity->getEntityTypeId();

    if (!isset($flags[$flagged_entity->getEntityTypeId()])) {
      $flags[$entity_type_id] = $this->flagService
        ->getFlagById($supported_entity_types[$entity_type_id]);
    }

    $request_count = count(
      $this->requestHandler->getOpenRequests($flagged_entity)
    );

    $type = '';
    switch ($flagged_entity->getEntityTypeId()) {
      case 'node':
        $type = $flagged_entity->bundle();
        break;
      case 'group':
      case 'comment':
        $type = $flagged_entity->getEntityTypeId();
        break;
    }

    $row['title'] = $flagged_entity->label();
    $row['type'] = $type;
    $row['author']['data'] = [
      '#theme' => 'username',
      '#account' => $flagged_entity->getOwner(),
    ];
    $row['request_number']['data'] = [
      '#type' => 'link',
      '#title' => $request_count,
      '#url' => Url::fromRoute(
        'eic_flags.flagged_entity.detail', [
        'request_type' => $this->requestHandler->getType(),
        'entity_type' => $entity_type_id,
        'entity_id' => $flagged_entity->id(),
      ]),
    ];

    $row['changed'] = $this->dateFormatter->format($flagged_entity->getChangedTime(), 'short');
    $row['created'] = $this->dateFormatter->format($flagged_entity->get('created')->value, 'short');
    $row['operations']['data'] = $this->buildOperations($flagged_entity);

    return $row;
  }

  /**
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function load() {
    $results = $this->getEntityIds();
    if (empty($results)) {
      return [];
    }

    $entities = [];
    $supported_entity_types = $this->requestHandler->getSupportedEntityTypes();
    foreach ($results as $result) {
      if (!isset($supported_entity_types[$result['entity_type']])) {
        continue;
      }

      $entity = $this->entityTypeManager
        ->getStorage($result['entity_type'])
        ->load($result['entity_id']);
      $flag = $this->flagService->getFlagById($supported_entity_types[$result['entity_type']]);

      $entities[] = [
        'entity' => $entity,
        'flags' => $this->flagService->getEntityFlaggings($flag, $entity),
      ];
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->database->select('flagging', 'f');
    $query->fields('f', ['entity_id', 'entity_type', 'flag_id'])
      ->join('flagging__field_request_status', 'fs', 'fs.entity_id = f.id');

    $query->condition('fs.field_request_status_value', RequestStatus::OPEN)
      ->distinct(TRUE);

    $supported_entity_types = $this->requestHandler->getSupportedEntityTypes();
    foreach ($supported_entity_types as $type => $flag) {
      $definition = $this->entityTypeManager->getDefinition($type);
      $data_table = $definition->getDataTable();
      $entity_keys = $definition->get('entity_keys');

      // Since flag module doesn't use database relations and target the flagged entity using two columns (entity_id and entity_type)
      // to make the 'entity_id' field unique, we must concat it with the type of the entity.
      // This ensures an unique value since within the same type, the id must be unique
      $flag_join_field = 'CONCAT("' . $type . '",f.entity_id)';
      $entity_join_field = 'CONCAT("' . $type . '",' . $type . '.' . $entity_keys['id'] . ')';

      // Left join every supported entity types, this will allow us to filter on their attributes
      $query->leftJoin(
        $data_table, $type, ':entity_join = :flag_join', [
        ':entity_join' => $entity_join_field,
        ':flag_join' => $flag_join_field,
      ]);
    }

    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

}
