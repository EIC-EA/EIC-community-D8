<?php

namespace Drupal\eic_projects\Constants;

/**
 * Defines constants around Projects.
 *
 * @package Drupal\eic_organisations\Constants
 */
final class Projects {

  /**
   * Machine name for the group Project bundle.
   */
  const GROUP_PROJECT_BUNDLE = 'project';

  /**
   * The internal mapping of terms to icons.
   */
  const EIC_TAXONOMY_FIELDS_OF_SCIENCE_TERMS = [
    'bed7011f-e1bc-4e64-82f6-d5c7c005b945' =>
      [
        'name' => 'Social sciences',
        'vid' => 'fields_of_science',
        'machine_readable_name' => 'social-sciences',
        'field_euroscivoc_code' => 29,
      ],
    '885fe589-b98d-44a9-ac65-2438c91d527b' =>
      [
        'name' => 'Natural sciences',
        'vid' => 'fields_of_science',
        'machine_readable_name' => 'natural-sciences',
        'field_euroscivoc_code' => 23,
      ],
    '2976d993-3ddb-43bc-ae8f-295632d7e1e8' =>
      [
        'name' => 'Engineering and technology',
        'vid' => 'fields_of_science',
        'machine_readable_name' => 'engineering-and-technology',
        'field_euroscivoc_code' => 25,
      ],
    '5b633204-256a-4ea4-a516-7179ba3c0f1d' =>
      [
        'name' => 'Humanities',
        'vid' => 'fields_of_science',
        'machine_readable_name' => 'humanities',
        'field_euroscivoc_code' => 31,
      ],
    '850ce6cb-5044-4135-9180-06b4f43fb610' =>
      [
        'name' => 'Agricultural sciences',
        'vid' => 'fields_of_science',
        'machine_readable_name' => 'agricultural-sciences',
        'field_euroscivoc_code' => 27,
      ],
    'ff8b5564-fcde-4406-a91d-65485f11d0b3' =>
      [
        'name' => 'Medical and health sciences',
        'vid' => 'fields_of_science',
        'machine_readable_name' => 'medical-and-health-sciences',
        'field_euroscivoc_code' => 21,
      ],
  ];

  // The base path to a CORDIS project, to be followed by its ID.
  const EIC_TAXONOMY_CORDIS_BASE_URL = 'https://cordis.europa.eu/project/id/';

}
