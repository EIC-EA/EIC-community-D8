<?php

namespace Drupal\eic_search\Plugin\search_api\processor;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\group\GroupMembership;
use Drupal\search_api\Annotation\SearchApiProcessor;
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
 *     "pre_index_save" = -10,
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
    $field_query = $query->getParams()['fq'] ?: '';

    $visibility_query = empty($field_query) ?
      $visibility_condition :
      ' AND ' . $visibility_condition;

    $field_query .= $visibility_query;
    $query->addParam('fq', $field_query);
  }

  /**
   * Create the query string for SOLR to match with group visibility
   *
   * @return string
   */
  private function buildGroupVisibilityQuery(): string {
    $user_id = $this->currentUser->id();

    $user = User::load($user_id);
    $email = explode('@', $user->getEmail());
    $domain = array_pop($email) ?: 0;

    /** @var \Drupal\group\GroupMembershipLoader $group_membership_service */
    $group_membership_service = \Drupal::service('group.membership_loader');
    $groups = $group_membership_service->loadByUser($user);

    $group_ids = array_map(function (GroupMembership $group_membership) {
      return $group_membership->getGroup()->id();
    }, $groups);

    // If group is private, the user needs to be in group to view it
    $group_ids_formatted = !empty($group_ids) ? implode(' OR ', $group_ids) : 0;

    $query = '
    ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_PUBLIC . '
    OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_PRIVATE . ' AND its_group_id:(' . $group_ids_formatted . '))
    OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN . ' AND ss_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN . ':*' . $domain . '*)
    ';

    // Restricted community group, only trusted_user role can view
    if (!$user->isAnonymous() && $user->hasRole('trusted_user')) {
      $query .= ' OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_COMMUNITY . ')';
    }

    // Trusted users restriction
    if (!$user->isAnonymous()) {
      $username = $user->getAccountName();
      $query .= ' OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . ' AND ss_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . ':*' . "$user_id|$username" . '*)';
    }

    return "($query)";
  }

}
