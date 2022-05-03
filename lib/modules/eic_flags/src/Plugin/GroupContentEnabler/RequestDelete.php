<?php

namespace Drupal\eic_flags\Plugin\GroupContentEnabler;

use Drupal\eic_flags\RequestTypes;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\group\Annotation\GroupContentEnabler;

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
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = [];
    $account = \Drupal::currentUser();

    $url = $group->toUrl('new-request')
      ->setRouteParameter(
        'destination',
        \Drupal::request()->getRequestUri()
      )
      ->setRouteParameter('request_type', RequestTypes::DELETE);

    if ($url->access($account)) {
      $operations['group-request-delete'] = [
        'title' => $this->t('Request delete'),
        'url' => $url,
      ];
    }

    return $operations;
  }

}
