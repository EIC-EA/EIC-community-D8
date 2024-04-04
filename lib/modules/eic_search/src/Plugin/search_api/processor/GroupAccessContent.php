<?php

namespace Drupal\eic_search\Plugin\search_api\processor;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_user\UserHelper;
use Drupal\group\GroupMembership;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\user\Entity\User;
use Solarium\QueryType\Select\Query\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds content access checks for nodes and comments.
 *
 * @SearchApiProcessor(
 *   id = "group_content_access",
 *   label = @Translation("Group content access"),
 *   description = @Translation("Adds content access for group restriction."),
 *   stages = {
 *     "preprocess_query" = 0,
 *   },
 * )
 */
class GroupAccessContent extends ProcessorPluginBase {

  /**
   * The current_user service used by this plugin.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|null
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setCurrentUser($container->get('current_user'));

    return $processor;
  }

  /**
   * Retrieves the current user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The current user.
   */
  public function getCurrentUser() {
    return $this->currentUser ?: \Drupal::currentUser();
  }

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Group access information'),
        'description' => $this->t('Data needed to apply group restrictions.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'hidden' => FALSE,
        'is_list' => TRUE,
      ];
      $properties['search_api_solr_group_access'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSolrSearchQuery(Query $query) {
    $visibility_condition = $this->buildGroupVisibilityQuery();
    $field_query = $query->getParams()['fq'] ?? '';

    $visibility_query = empty($field_query) ?
      $visibility_condition :
      ' AND ' . $visibility_condition;

    $field_query .= $visibility_query;
    $query->addParam('fq', $field_query);
  }

  /**
   * Create the query string for SOLR to match with group visibility.
   *
   * @return string
   *   The group visibility query string to send to SOLR.
   */
  private function buildGroupVisibilityQuery(): string {
    $user_id = $this->currentUser->id();

    $user = User::load($user_id);

    /** @var \Drupal\group\GroupMembershipLoader $group_membership_service */
    $group_membership_service = \Drupal::service('group.membership_loader');
    $groups = $group_membership_service->loadByUser($user);

    $group_ids = array_map(function (GroupMembership $group_membership) {
      return $group_membership->getGroup()->id();
    }, $groups);

    // If group is private, the user needs to be in group to view it.
    $group_ids_formatted = !empty($group_ids) ? implode(' OR ', $group_ids) : 0;

    // Power user can access all groups.
    if (UserHelper::isPowerUser($user)) {

      return '(ss_group_visibility:*)';
    }

    $domain = '';

    if ($email = $user->getEmail()) {
      $email = explode('@', $email);
      $domain = array_pop($email) ?: 0;
    }

    // Get user organisation types.
    $user_organisation_types = \Drupal::service('eic_organisations.helper')->getUserOrganisationTypes($user);
    $user_organisation_types_formatted = !empty($user_organisation_types) ? implode(' OR ', $user_organisation_types) : 0;

    // Event group content can be only seen by member of groups event if the group is public.
    $query = '
    (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_PUBLIC . ' AND !ss_global_group_parent_type:event)
    OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_PUBLIC . ' AND ss_global_group_parent_type:(event) AND its_global_group_parent_id:(' . $group_ids_formatted . '))
    OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_PRIVATE . ' AND its_global_group_parent_id:(' . $group_ids_formatted . '))
    OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN . ' AND ss_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN . ':*' . $domain . '*)
    OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATIONS . ' AND itm_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATIONS . ':(' . $group_ids_formatted . '))
    OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATION_TYPES . ' AND itm_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATION_TYPES . ':(' . $user_organisation_types_formatted . '))
    ';

    // Group members can view their own groups regardless of the group visibility.
    if ($user->isAuthenticated()) {
      $query .= ' OR its_global_group_parent_id:(' . $group_ids_formatted . ')';
    }

    if ($user->hasRole('sensitive')) {
      $query .= ' OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_SENSITIVE . ')';
    }

    // Restricted community group, only trusted_user role can view.
    if (!$user->isAnonymous() && ($user->hasRole(UserHelper::ROLE_TRUSTED_USER) || UserHelper::isPowerUser($this->getCurrentUser()))) {
      $query .= ' OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_COMMUNITY . ')';
    }

    // Trusted users restriction.
    if (!$user->isAnonymous()) {
      $username = $user->getAccountName();
      $query .= ' OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . ' AND ss_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . ':*' . "$user_id|$username" . '*)';
    }

    return "($query)";
  }

}
