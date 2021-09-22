<?php

namespace Drupal\eic_flags;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FlaggingListBuilder.
 *
 * @package Drupal\eic_flags
 */
class FlaggingListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
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
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The request handler for the current request type.
   *
   * @var \Drupal\eic_flags\Service\HandlerInterface|null
   */
  protected $requestHandler;

  /**
   * FlaggingListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Current entity type.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Current entity type's storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   *   The request collector service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    EntityTypeManagerInterface $entityTypeManager,
    DateFormatterInterface $date_formatter,
    Request $request,
    RequestHandlerCollector $collector
  ) {
    parent::__construct($entity_type, $storage);

    $this->entityTypeManager = $entityTypeManager;
    $this->dateFormatter = $date_formatter;
    $this->currentRequest = $request;
    $this->requestHandler = $collector->getHandlerByType(
      $this->currentRequest->get('request_type')
    );
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
      $container->get('date.formatter'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('eic_flags.handler_collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Enable language column and filter if multiple languages are added.
    $header = [
      'reason' => $this->t('Reason'),
      'requester' => [
        'data' => $this->t('Requester'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'created' => [
        'data' => $this->t('Created'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return $this->t(
      'Requests for content @request-type',
      ['@request-type' => $this->currentRequest->get('request_type')]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['reason'] = $entity->get('field_request_reason')->value;
    $row['requester ']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['created'] = $this->dateFormatter->format(
      $entity->get('created')->value,
      'short'
    );

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entity_type = $this->currentRequest->attributes->get('entity_type');
    $entity_id = $this->currentRequest->attributes->get('entity_id');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $target_entity */
    $target_entity = $this->entityTypeManager->getStorage($entity_type)
      ->load($entity_id);

    return $this->getHeaderActions($target_entity) + parent::render();
  }

  /**
   * Returns the entity ids to display.
   *
   * @return array|int
   *   The result of the query.
   */
  protected function getEntityIds() {
    $entity_type = $this->currentRequest->attributes->get('entity_type');
    $entity_id = $this->currentRequest->attributes->get('entity_id');

    $query = $this->getStorage()->getQuery()
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->condition('field_request_status', RequestStatus::OPEN)
      ->condition(
        'flag_id',
        $this->requestHandler->getSupportedEntityTypes(),
        'IN'
      )
      ->accessCheck(TRUE)
      ->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * Returns an array of header actions.
   *
   * @return array
   *   Array with header actions.
   */
  private function getHeaderActions(ContentEntityInterface $entity) {
    $actions = $this->requestHandler->getActions($entity);
    $items = [];
    foreach ($actions as $name => $action) {
      $items[$name] = [
        '#type' => 'link',
        '#url' => $action['url'],
        '#title' => $action['title'],
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
            'button--small',
          ],
        ],
      ];
    }

    $build['actions'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => $items,
      '#attributes' => [
        'class' => ['action-links'],
      ],
    ];

    return $build;
  }

}
