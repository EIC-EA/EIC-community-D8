<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_messages\MessageHelper;
use Drupal\eic_user\UserHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MessageCreatorBase.
 */
class MessageCreatorBase implements ContainerInjectionInterface {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EIC Message helper service.
   *
   * @var \Drupal\eic_messages\MessageHelper
   */
  protected $eicMessagesHelper;

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $eicUserHelper;

  /**
   * Constructs a new MessageCreatorBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_messages\MessageHelper $eic_messages_helper
   *   The EIC Message helper service.
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   *   The EIC User helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessageHelper $eic_messages_helper, UserHelper $eic_user_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eicMessagesHelper = $eic_messages_helper;
    $this->eicUserHelper = $eic_user_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_messages.helper'),
      $container->get('eic_user.helper')
    );
  }

  /**
   * Process an array of messages for queue notifications.
   *
   * @param array $messages
   */
  public function processMessages(array $messages) {
    foreach ($messages as $message) {
      try {
        // Create the message notify queue item.
        // @todo check if this type of message should live/stay in the DB.
        $this->eicMessagesHelper->queueMessageNotification($message);
      }
      catch (\Exception $e) {
        $logger = $this->getLogger('eic_messages');
        $logger->error($e->getMessage());
      }
    }
  }

}
