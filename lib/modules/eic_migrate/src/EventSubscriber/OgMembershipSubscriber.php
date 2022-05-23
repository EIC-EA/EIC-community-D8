<?php

namespace Drupal\eic_migrate\EventSubscriber;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for OgMembership related migrations.
 *
 * @package Drupal\eic_migrate
 */
class OgMembershipSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection to the migrate DB.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new OgMembershipSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = Database::getConnection('default', 'migrate');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_ROW_SAVE][] = ['forceDeclinedInvitations'];
    return $events;
  }

  /**
   * Force the status of invitation.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The migrate post row save event object.
   */
  public function forceDeclinedInvitations(MigratePostRowSaveEvent $event) {
    if ($event->getMigration()->getBaseId() != 'upgrade_d7_group_declined_invitations') {
      return;
    }

    // Try to load the group invitation.
    $destination = $event->getDestinationIdValues();
    if (!$group_content = $this->entityTypeManager->getStorage('group_content')->load($destination[0])) {
      return;
    }

    // We are migrating declined invitations. Since the ginvite module
    // automatically sets the invitations status to pending when inserting new
    // group_content, we need to resave the rejected status here.
    $group_content->set('invitation_status', GroupInvitation::INVITATION_REJECTED)->save();
  }

}
