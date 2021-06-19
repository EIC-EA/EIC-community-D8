<?php

namespace Drupal\eic_flags\Plugin\GroupContentEnabler;

use Drupal\eic_flags\RequestTypes;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\group\Annotation\GroupContentEnabler;

/**
 * Provides a content enabler to request the archival of the group.
 *
 * @GroupContentEnabler(
 *   id = "group_request_archival",
 *   label = @Translation("Group Archival Request"),
 *   description = @Translation("Request the archival of a group"),
 *   entity_type_id = "user",
 *   pretty_path_key = "request_archival",
 *   reference_label = @Translation("Requete archival"),
 *   reference_description = @Translation("Request archival."),
 * )
 */
class RequestArchival extends GroupContentEnablerBase {

  /**
   * {@inheritdoc }
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = [];

    $operations['group-request-archival'] = [
      'title' => $this->t('Request archival'),
      'url' => $group->toUrl('new-request')
        ->setRouteParameter(
          'destination',
          \Drupal::request()->getRequestUri()
        )
        ->setRouteParameter('request_type', RequestTypes::ARCHIVE),
    ];

    return $operations;
  }

}
