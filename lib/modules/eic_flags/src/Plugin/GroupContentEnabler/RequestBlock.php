<?php

namespace Drupal\eic_flags\Plugin\GroupContentEnabler;

use Drupal\eic_flags\RequestTypes;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler to request the block of the group.
 *
 * @GroupContentEnabler(
 *   id = "group_request_block",
 *   label = @Translation("Group Block Request"),
 *   description = @Translation("Request the block of a group"),
 *   entity_type_id = "user",
 *   pretty_path_key = "request_block",
 *   reference_label = @Translation("Requeste block"),
 *   reference_description = @Translation("Request block."),
 * )
 */
class RequestBlock extends GroupContentEnablerBase {

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
      ->setRouteParameter('request_type', RequestTypes::BLOCK);

    if ($url->access($account)) {
      $operations['group-request-block'] = [
        'title' => $this->t('Block'),
        'url' => $url,
      ];
    }

    return $operations;
  }

}
