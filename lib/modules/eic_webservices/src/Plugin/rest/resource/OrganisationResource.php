<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_organisations\OrganisationsHelper;
use Drupal\rest\ResourceResponse;

/**
 * Represents EIC Organisation Resource records as resources.
 *
 * @RestResource (
 *   id = "eic_webservices_organisation",
 *   label = @Translation("EIC Organisation Resource"),
 *   entity_type = "group",
 *   serialization_class = "Drupal\group\Entity\Group",
 *   uri_paths = {
 *     "canonical" = "/smed/api/v1/organisation/{group}",
 *     "create" = "/smed/api/v1/organisation"
 *   }
 * )
 */
class OrganisationResource extends GroupResourceBase {

  /**
   * {@inheritdoc}
   */
  public function post(EntityInterface $entity = NULL) {
    /** @var \Drupal\group\Entity\GroupInterface $entity */

    // Get the field name that contains the SMED ID.
    $smed_id_field = $this->configFactory->get('eic_webservices.settings')->get('smed_id_field');

    // Check if organisation already exists.
    if (!empty($entity->{$smed_id_field}->value) &&
      $organisation = $this->wsHelper->getGroupBySmedId($entity->{$smed_id_field}->value, 'organisation')) {
      $url = $organisation->toUrl('canonical', ['absolute' => TRUE]);

      // Send custom response.
      $data = [
        'message' => 'Unprocessable Entity: validation failed. Organisation already exists.',
        'gid' => $organisation->id(),
        'label' => $organisation->label(),
        'uri' => $url->toString(TRUE)->getGeneratedUrl(),
        $smed_id_field => $organisation->{$smed_id_field}->value,
      ];

      return new ResourceResponse($data, 422);
    }

    // Process SMED taxonomy fields to convert the SMED ID to Term ID.
    $this->smedTaxonomyHelper->convertEntitySmedTaxonomyIds($entity);

    // Process fields to be formatted according to their type.
    $this->wsRestHelper->formatEntityFields($entity);

    $this->handleGroupOwner($entity);

    // Initialise required fields if not provided.
    OrganisationsHelper::setRequiredFieldsDefaultValues($entity);

    // Sets default group features.
    $this->setDefaultGroupFeatures($entity);

    return parent::post($entity);
  }

}
