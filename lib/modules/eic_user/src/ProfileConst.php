<?php

namespace Drupal\eic_user;

/**
 * Defines eic_user module constants.
 */
final class ProfileConst {

  /**
   * Defines array of allowed social networks.
   */
  const ALLOWED_SOCIAL_NETWORKS = [
    'linkedin',
    'twitter',
    'facebook',
  ];

  /**
   * Defines the member profile type name.
   */
  const MEMBER_PROFILE_TYPE_NAME = 'member';

  /**
   * Defines the route to the member profile edit page.
   *
   * @var string
   */
  const MEMBER_PROFILE_EDIT_ROUTE_NAME = 'profile.user_page.single';

}
