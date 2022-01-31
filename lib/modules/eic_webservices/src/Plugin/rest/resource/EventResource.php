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
      $organisation = $this->wsHelper->getGroupBySmedId($entity->{$smed_id_field}->value, 'event')) {

      // Send custom response.
      $data = [
        'message' => 'Unprocessable Entity: validation failed. Event already exists.',
        $smed_id_field => $organisation->{$smed_id_field}->value,
      ];

      return new ResourceResponse($data, 422);
    }

    // Process SMED taxonomy fields to convert the SMED ID to Term ID.
    $this->smedTaxonomyHelper->convertEntitySmedTaxonomyIds($entity);

    // Set the author of the group.
    if ($author_uid = $this->configFactory->get('eic_webservices.settings')->get('group_author')) {
      $entity->setOwnerId($author_uid);
    }

    // Initialise required fields if not provided.
    EventsHelper::setRequiredFieldsDefaultValues($entity);

    return parent::post($entity);
  }

}
