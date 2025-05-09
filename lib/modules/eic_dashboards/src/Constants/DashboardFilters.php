<?php

namespace Drupal\eic_dashboards\Constants;

/**
 * Defines constants around Dashboard Views filters.
 *
 * @package Drupal\eic_dashboards\Constants
 */
final class DashboardFilters {
  const DASHBOARD_MEMBERS_LIST_COUNTRY = 'field_location_address_country_code[]';
  const DASHBOARD_MEMBERS_LIST_USER_TYPE = 'field_vocab_user_type_target_id[]';
  const DASHBOARD_MEMBERS_LIST_TOPIC_OF_INTEREST = 'field_vocab_topic_interest_target_id[]';
  const DASHBOARD_MEMBERS_LIST_TOPIC_OF_EXPERTISE = 'field_vocab_topic_expertise_target_id[]';
  const DASHBOARD_MEMBERS_LIST_GROUP_ID = 'gid';
  const DASHBOARD_PROJECTS_LIST_COUNTRY = 'field_stakeholder_address_country_code[]';
}
