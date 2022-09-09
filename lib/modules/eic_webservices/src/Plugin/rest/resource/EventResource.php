<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_events\EventsHelper;
use Drupal\rest\ResourceResponse;

/**
 * Represents EIC Event Resource records as resources.
 *
 * @RestResource (
 *   id = "eic_webservices_event",
 *   label = @Translation("EIC Event Resource"),
 *   entity_type = "group",
 *   serialization_class = "Drupal\group\Entity\Group",
 *   uri_paths = {
 *     "canonical" = "/smed/api/v1/event/{group}",
 *     "create" = "/smed/api/v1/event"
 *   }
 * )
 */
class EventResource extends GroupResourceBase {

  /**
   * {@inheritdoc}
   */
  public function post(EntityInterface $entity = NULL) {
    /** @var \Drupal\group\Entity\GroupInterface $entity */

    // Get the field name that contains the SMED ID.
    $smed_id_field = $this->configFactory->get('eic_webservices.settings')->get('smed_id_field');

    // Check if event already exists.
    if (!empty($entity->{$smed_id_field}->value) &&
      $event = $this->wsHelper->getGroupBySmedId($entity->{$smed_id_field}->value, 'event')) {
      $url = $event->toUrl('canonical', ['absolute' => TRUE]);

      // Send custom response.
      $data = [
        'message' => 'Unprocessable Entity: validation failed. Event already exists.',
        'gid' => $event->id(),
        'label' => $event->label(),
        'uri' => $url->toString(TRUE)->getGeneratedUrl(),
        $smed_id_field => $event->{$smed_id_field}->value,
      ];

      return new ResourceResponse($data, 422);
    }

    // Process SMED taxonomy fields to convert the SMED ID to Term ID.
    $this->smedTaxonomyHelper->convertEntitySmedTaxonomyIds($entity);

    // Process fields to be formatted according to their type.
    $this->wsRestHelper->formatEntityFields($entity);

    $this->handleGroupOwner($entity);

    // Initialise required fields if not provided.
    EventsHelper::setRequiredFieldsDefaultValues($entity);

    return parent::post($entity);
  }

}
