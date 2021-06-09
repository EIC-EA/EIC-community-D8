<?php

namespace Drupal\eic_flags;

use Exception;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\eic_flags\Service\DeleteRequestManager;
use Drupal\flag\FlaggingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of flags with type 'delete_request_*'.
 */
class DeleteRequestListBuilder extends EntityListBuilder {

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entityTypeManager, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);

    $this->entityTypeManager = $entityTypeManager;
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
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
      'requester' => [
        'data' => $this->t('Requester'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'reason' => $this->t('Reason'),
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
  public function buildRow(EntityInterface $entity) {
    /** @var FlaggingInterface $entity */
    $flaggedEntity = $this->getFlaggedEntiy($entity);

    $type = '';
    switch ($flaggedEntity->getEntityTypeId()) {
      case 'node':
        $type = $flaggedEntity->bundle();
        break;
      case 'group':
      case 'comment':
        $type = $flaggedEntity->getEntityTypeId();
        break;
    }

    $row['title'] = $flaggedEntity->label();
    $row['type'] = $type;
    $row['author']['data'] = [
      '#theme' => 'username',
      '#account' => $flaggedEntity->getOwner(),
    ];
    $row['requester']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['reason'] = $entity->get('field_deletion_reason')->value;
    $row['changed'] = $this->dateFormatter->format($flaggedEntity->getChangedTime(), 'short');
    $row['created'] = $this->dateFormatter->format($entity->get('created')->value, 'short');
    $row['operations']['data'] = $this->buildOperations($entity);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->condition('flag_id', array_values(DeleteRequestManager::$supportedEntityTypes), 'IN')
      ->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * @param \Drupal\flag\FlaggingInterface $flagging
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  private function getFlaggedEntiy(FlaggingInterface $flagging) {
    try {
      $entity = $this->entityTypeManager
        ->getStorage($flagging->getFlaggableType())
        ->load($flagging->getFlaggableId());
    } catch (Exception $exception) {
      return NULL;
    }

    return $entity ?? NULL;
  }

}
