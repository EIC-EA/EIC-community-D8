<?php

namespace Drupal\eic_flags\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler to request the removal of the group.
 *
 * @GroupContentEnabler(
 *   id = "group_request_delete",
 *   label = @Translation("Group Delete Request"),
 *   description = @Translation("Request the deletion of a group"),
 *   entity_type_id = "user",
 *   pretty_path_key = "request_delete",
 *   reference_label = @Translation("Requete delete"),
 *   reference_description = @Translation("Request delete."),
 * )
 */
class RequestDelete extends GroupContentEnablerBase {

  /**
   * {@inheritdoc }
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = [];

    $operations['group-request-delete'] = [
      'title' => $this->t('Request delete'),
      'url' => $group->toUrl('request-delete-form')->setRouteParameter('destination', \Drupal::request()
        ->getRequestUri()),
    ];

    return $operations;
  }

}
