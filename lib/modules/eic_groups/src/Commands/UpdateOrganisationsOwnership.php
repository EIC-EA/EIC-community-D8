<?php

namespace Drupal\eic_groups\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_flags\Service\TransferOwnershipRequestHandler;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Entity\GroupInterface;
use Drush\Commands\DrushCommands;

/**
 * This class provides a drush command to transfer ownership of organisations.
 *
 * This will transfer ownership of organisations from Community Manager user
 * (uid: 3) to the best suited member, in order of preference:
 *   - CEO
 *   - CFO
 *   - Oldest member
 */
class UpdateOrganisationsOwnership extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The transfer ownership request handler.
   *
   * @var \Drupal\eic_flags\Service\TransferOwnershipRequestHandler
   */
  protected $transferOwnershipRequestHandler;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_flags\Service\TransferOwnershipRequestHandler $transfer_ownership_request_handler
   *   The transfer ownership request handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TransferOwnershipRequestHandler $transfer_ownership_request_handler) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->transferOwnershipRequestHandler = $transfer_ownership_request_handler;
  }

  /**
   * Transfers ownership of organisations.
   *
   * @usage eic_groups:update-organisations-ownership
   *   Transfer ownership of organisations from Community Manager user (uid: 3)
   *   to the best suited member.
   *
   * @command eic_groups:update-organisations-ownership
   * @aliases eic-uporgown
   */
  public function actionTransferOrganisationsOwnership() {
    // Get organisations IDs.
    $ids = \Drupal::entityQuery('group')
      ->condition('type', 'organisation')
      ->accessCheck(FALSE)
      ->execute();

    $count = count($ids);
    $updated_groups = 0;

    if ($this->confirm("$count organisations will be checked/updated. Proceed?")) {
      $this->io()->progressStart($count);
      foreach ($ids as $id) {
        $this->io()->progressAdvance();
        $group = $this->entityTypeManager->getStorage('group')->load($id);

        // Ignore groups that have a different owner from 'Community Manager'.
        if (EICGroupsHelper::getGroupOwner($group)->id() != 3) {
          continue;
        }

        if ($new_owner_membership = $this->determineNewOwner($group)) {
          $this->transferOwnershipRequestHandler->transferGroupOwnership($group, $new_owner_membership);
          $updated_groups++;
        }
      }
      $this->io()->progressFinish();
      $this->io()->success("Transferred ownership for $updated_groups organisations.");
    }
  }

  /**
   * Returns a suitable membership for the given organisation.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return \Drupal\group\Entity\GroupContentInterface|false
   *   The group membership of the new owner or FALSE if none could be found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function determineNewOwner(GroupInterface $group) {
    // General management / CEO.
    /** @var \Drupal\group\Entity\GroupContentInterface[] $ceos */
    $ceos = $this->entityTypeManager->getStorage('group_content')
      ->loadByProperties([
        'type' => 'organisation-group_membership',
        'gid' => $group->id(),
        'field_vocab_job_title' => 20,
      ]);

    if (!empty($ceos)) {
      return reset($ceos);
    }
    else {
      // Accounting / Finance / CFO.
      /** @var \Drupal\group\Entity\GroupContentInterface[] $cfos */
      $cfos = $this->entityTypeManager->getStorage('group_content')
        ->loadByProperties([
          'type' => 'organisation-group_membership',
          'gid' => $group->id(),
          'field_vocab_job_title' => 21,
        ]);
      if (!empty($cfos)) {
        return reset($cfos);
      }
      else {
        /** @var \Drupal\group\Entity\GroupContentInterface[] $old_memberships */
        $old_memberships = $this->entityTypeManager->getStorage('group_content')
          ->getQuery()
          ->condition('type', 'organisation-group_membership')
          ->condition('gid', $group->id())
          ->condition('entity_id', 3, '<>')
          ->sort('created', 'ASC')
          ->range(0, 1)
          ->execute();
        if (!empty($old_memberships)) {
          /** @var \Drupal\group\Entity\GroupContentInterface $old_membership */
          return $this->entityTypeManager->getStorage('group_content')->load(reset($old_memberships));
        }
      }
      return FALSE;
    }
  }

}
