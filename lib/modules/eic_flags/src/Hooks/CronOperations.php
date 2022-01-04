<?php

namespace Drupal\eic_flags\Hooks;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\Service\HandlerInterface;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CronOperations.
 *
 * Implementations for hook_cron().
 */
class CronOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The request handler collector.
   *
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  protected $requestHandlerCollector;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a CronOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $request_handler_collector
   *   The request handler collector.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    RequestHandlerCollector $request_handler_collector,
    Connection $database
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->requestHandlerCollector = $request_handler_collector;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('eic_flags.handler_collector'),
      $container->get('database')
    );
  }

  /**
   * Process request timeouts.
   *
   * @todo In the future this method should be called by ultimate cron module.
   */
  public function processRequestTimeouts() {
    /** @var \Drupal\eic_flags\Service\HandlerInterface[] $request_handlers */
    $request_handlers = $this->requestHandlerCollector->getHandlers();

    $now = DrupalDateTime::createFromTimestamp(time());
    // Gets all request flags that have a timeout limit and close them.
    foreach ($request_handlers as $request_handler) {
      $supported_flags[$request_handler->getType()] = [];
      foreach ($request_handler->getSupportedEntityTypes() as $flag_id) {
        $fields = $this->entityFieldManager->getFieldDefinitions('flagging', $flag_id);

        if (
          isset($fields[HandlerInterface::REQUEST_TIMEOUT_FIELD]) &&
          !in_array($flag_id, $supported_flags[$request_handler->getType()])
        ) {

          $query = $this->database->select('flagging', 'f');
          $query->join('flagging__field_request_timeout', 'frt', 'f.id = frt.entity_id');
          $query->join('flagging__field_request_status', 'frs', 'f.id = frs.entity_id');
          $query->condition('f.flag_id', $flag_id);
          $query->condition('frs.field_request_status_value', RequestStatus::OPEN);
          $query->condition('frt.field_request_timeout_value', 0, '>');
          $query->where($now->getTimestamp() . ' >= ((frt.field_request_timeout_value * 86400) + f.created)');
          $query->fields('f', ['id', 'flag_id', 'entity_type', 'entity_id']);
          $result = $query->execute()->fetchAll();
          foreach ($result as $row) {
            /** @var \Drupal\flag\FlaggingInterface $flag */
            $flag = $this->entityTypeManager->getStorage('flagging')
              ->load($row->id);
            /** @var \Drupal\Core\Entity\ContentEntityInterface $flagged_entity */
            $flagged_entity = $this->entityTypeManager->getStorage($row->entity_type)
              ->load($row->entity_id);
            if (!$flag || !$flagged_entity) {
              continue;
            }
            $request_handler->requestTimeout($flag, $flagged_entity);
          }
        }
      }
    }
  }

}
