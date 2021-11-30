<?php

namespace Drupal\eic_groups\Constants;

/**
 * Defines constants for group visibility stuff.
 *
 * @package Drupal\eic_groups\Constants
 */
final class GroupVisibilityType {

  const GROUP_VISIBILITY_PUBLIC = 'public';

  const GROUP_VISIBILITY_PRIVATE = 'private';

  const GROUP_VISIBILITY_COMMUNITY = 'restricted_community_members';

  const GROUP_VISIBILITY_CUSTOM_RESTRICTED = 'custom_restricted';

  const GROUP_VISIBILITY_OPTION_TRUSTED_USERS = 'restricted_users';

  const GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN = 'restricted_email_domains';

  const GROUP_VISIBILITY_OPTION_ORGANISATIONS = 'restricted_organisations';

  const GROUP_VISIBILITY_OPTION_ORGANISATION_TYPES = 'restricted_organisation_types';

}
