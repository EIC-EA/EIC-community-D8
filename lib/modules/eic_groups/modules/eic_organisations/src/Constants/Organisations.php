<?php

namespace Drupal\eic_organisations\Constants;

/**
 * Defines constants around Organisations.
 *
 * @package Drupal\eic_organisations\Constants
 */
final class Organisations {

  /**
   * Machine name for the group Organisation bundle.
   */
  const GROUP_ORGANISATION_BUNDLE = 'organisation';

  /**
   * Machine name for the Organisation types taxonomy vocabulary.
   */
  const VOCABULARY_ORGANISATION_TYPE = 'organisation_types';

  /**
   * Machine name for the group "Organisation type" field.
   */
  const FIELD_ORGANISATION_TYPE = 'field_organisation_type';

  /**
   * Group owner role machine name for Organisations.
   */
  const GROUP_OWNER_ROLE = 'organisation-owner';

  /**
   * Group admin role machine name for Organisations.
   */
  const GROUP_ADMINISTRATOR_ROLE = 'organisation-admin';

  /**
   * Group member role machine name for Organisations.
   *
   * @todo Should we keep this?
   */
  const GROUP_MEMBER_ROLE = 'organisation-member';

}
