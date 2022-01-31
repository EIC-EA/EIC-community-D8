<?php

namespace Drupal\eic_flags;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\eic_flags\Form\ListBuilderFilters;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\flag\FlagService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a class to build a listing of flagged entities for the current
 * request_type.
 */
class FlaggedEntitiesListBuilder extends EntityListBuilder {

  const CLOSED_REQUEST_VIEW = 'view.closed_requests.page_1';

  const VIEW_ARCHIVE_FLAG_ID = 1;

  const VIEW_DELETE_FLAG_ID = 2;

  /**
   * The database driver.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter class.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Flag service provided by the flag module.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The request type used for the list.
   *
   * @var string
   */
  protected $requestType;

  /**
   * The handler of the current request type.
   *
   * @var \Drupal\eic_flags\Service\HandlerInterface|null
   */
  protected $requestHandler;

  /**
   * FlaggedEntitiesListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database driver.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatted class.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request object.
   * @param \Drupal\flag\FlagService $flag_service
   *   Flag service provided by the flag module.
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   *   The request handler collector.
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
    $this->currentRequest = $request;
    $this->requestType = $request->get('request_type');
    $this->requestHandler = $collector->getHandlerByType($this->requestType);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
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
  public function render() {
    $build['filters'] = $this->getFilters();
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t(
        'There are no @label yet.',
        ['@label' => $this->entityType->getPluralLabel()]
      ),
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
   * Returns a built form to use for filtering purposes.
   *
   * @return array
   *   Available filters.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  private function getFilters() {
    $form_state = (new FormState())
      ->setMethod('get')
      ->addBuildInfo('args', [$this->requestHandler])
      ->setAlwaysProcess()
      ->disableRedirect();

    return \Drupal::formBuilder()->buildForm(
      ListBuilderFilters::class,
      $form_state
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Enable language column and filter if multiple languages are added.
    $header = [
      'title' => [
        'data' => $this->t('Title'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'type' => [
        'data' => $this->t('Type'),
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
        'data' => $this->t('Last updated'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'field' => 'changed',
        'specifier' => 'changed',
      ],
      'created' => [
        'data' => $this->t('Created'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'field' => 'created',
        'specifier' => 'created',
      ],
    ];

    return $header + parent::buildHeader();
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
      $flag = $this->flagService->getFlagById(
        $supported_entity_types[$result['entity_type']]
      );

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
    $query_strings = $this->currentRequest->query->all();
    $supported_entity_types = $this->requestHandler->getSupportedEntityTypes();
    $requested_type = !empty($query_strings['type'])
    && $query_strings['type'] !== 'All' ? $query_strings['type'] : NULL;

    /** @var \Drupal\Core\Database\Query\SelectInterface[] $sub_queries */
    $sub_queries = [];
    foreach ($supported_entity_types as $type => $flag) {
      // If the user requested a certain entity type, just query for it.
      if (!empty($requested_type) && $type !== $requested_type) {
        continue;
      }

      $definition = $this->entityTypeManager->getDefinition($type);
      $data_table = $definition->getDataTable();
      $entity_keys = $definition->get('entity_keys');
      $id_field = $entity_keys['id'];

      $query = $this->database->select('flagging', 'f');

      $query->fields('f', ['id', 'entity_id', 'entity_type', 'flag_id'])
        ->condition('fs.field_request_status_value', RequestStatus::OPEN)
        ->condition('f.flag_id', $flag);

      $query->join(
        'flagging__field_request_status',
        'fs',
        'fs.entity_id = f.id'
      );

      $query->leftJoin(
        $data_table,
        $type,
        "$type.$id_field = f.entity_id"
      );

      // Add fields for the sorting options.
      $query->addField($type, 'created', 'entity_created');
      $query->addField($type, 'changed', 'entity_changed');
      switch ($type) {
        case 'comment':
          $query->leftJoin(
            $type . '__comment_body',
            $type . '_body',
            "$type.$id_field = {$type}_body.entity_id"
          );
          $query->addField($type . '_body', 'comment_body_value', 'title');
          break;

        case 'group':
          $query->addField($type, 'label', 'title');
          break;

        default:
          $query->addField($type, 'title', 'title');
          break;
      }

      if (!empty($query_strings['title'])) {
        $query->condition(
          $type . '.' . $entity_keys['label'],
          '%' . $query_strings['title'] . '%',
          'LIKE'
        );
      }

      if (!empty($query_strings['requester'])) {
        $requester_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput(
          $query_strings['requester']
        );

        $query->condition(
          'f.uid',
          $requester_id
        );
      }

      if (!empty($query_strings['author'])) {
        $author_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput(
          $query_strings['author']
        );

        $query->condition(
          $type . '.' . $entity_keys['owner'],
          $author_id
        );
      }

      $sub_queries[] = $query;
    }

    // Mix it up !
    $query = array_shift($sub_queries);
    foreach ($sub_queries as $sub_query) {
      $query->union($sub_query);
    }

    $query = $query->extend(PagerSelectExtender::class);
    $query->limit($this->limit);

    // Apply sorting.
    if (!empty($query_strings['sort']) && !empty($query_strings['sort'])) {
      $sort_dirrection = strtoupper($query_strings['sort']);
      if (in_array($sort_dirrection, ['ASC', 'DESC'])) {
        $sort_field = strtolower(str_replace(' ', '_', $query_strings['order']));
        switch ($sort_field) {
          case 'created':
            $query->orderBy('entity_created', $sort_dirrection);
            break;

          case 'changed':
          case 'last_updated':
            $query->orderBy('entity_changed', $sort_dirrection);
            break;

          case 'title':
            $query->orderBy('title', $sort_dirrection);
            break;

          default:
            $query->orderBy('entity_created', $sort_dirrection);
            break;

        }
      }
    }
    else {
      $query->orderBy('entity_created');
    }

    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
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
    $title = $flagged_entity->label();
    switch ($flagged_entity->getEntityTypeId()) {
      case 'node':
        $type = $flagged_entity->bundle();
        break;

      case 'group':
        $type = $flagged_entity->getEntityTypeId();
        $title = Unicode::truncate(
          $flagged_entity->label(),
          100,
          TRUE,
          TRUE
        );
        break;

      case 'comment':
        $type = $flagged_entity->getEntityTypeId();
        $title = Unicode::truncate(
          $flagged_entity->get('comment_body')->value,
          100,
          TRUE,
          TRUE
        );
        break;

    }

    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $title,
      '#url' => Url::fromRoute(
        'eic_flags.flagged_entity.detail',
        [
          'request_type' => $this->requestHandler->getType(),
          'entity_type' => $entity_type_id,
          'entity_id' => $flagged_entity->id(),
        ]
      ),
    ];
    $row['type'] = $type;
    $row['author']['data'] = [
      '#theme' => 'username',
      '#account' => $flagged_entity->getOwner(),
    ];
    $row['request_number'] = $request_count;
    $row['changed'] = $this->dateFormatter->format(
      $flagged_entity->getChangedTime(),
      'short'
    );
    $row['created'] = $this->dateFormatter->format(
      $flagged_entity->get('created')->value,
      'short'
    );
    $row['operations']['data'] = $this->buildOperations($flagged_entity);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = [];
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $this->ensureDestination($entity->toUrl('edit-form')),
      ];
    }

    return $operations;
  }

}
