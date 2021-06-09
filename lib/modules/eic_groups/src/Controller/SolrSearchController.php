<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\group\GroupMembership;
use Drupal\user\Entity\User;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SolrSearchController
 *
 * @package Drupal\eic_groups\Controller
 */
class SolrSearchController extends ControllerBase {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
   */
  public function search(Request $request) {
    $http_client = \Drupal::httpClient();
    $search_value = $request->query->get('search_value');
    $facets_value = $request->query->get('facets_value');
    $facets_value = json_decode($facets_value, TRUE);
    $page = $request->query->get('page');
    $offset = $request->query->get('offset');

    $query = [
      'q' => $search_value,

      'wt' => 'json',
      'fq' => 'ss_search_api_datasource:"entity:group" AND ' . $this->buildGroupVisibilityQuery(),
      'json.nl' => 'arrarr',
      'fl' => 'ss_group_label,ss_group_teaser,its_group_id,ss_group_type,ss_group_visibility,ss_group_url,ss_group_user_url,ss_group_user_image,ss_group_user_last_name,ss_group_user_first_name',
      'facet.field' => 'ss_group_topic_name',
      'facet' => 'on',
      'facet.sort' => 'false',
      'sort' => 'ss_group_label asc',
      'start' => ($page * $offset) - $offset,
      'rows' => $page * $offset,
    ];

    $facets_value = array_filter($facets_value, function ($value) {
      return $value;
    });

    $facets_value = array_keys($facets_value);

    if ($facets_value) {
      $query['fq'] .= ' AND ss_group_topic_name:"' . implode(' OR ', $facets_value) . '"';
    }

    try {
      $content = $http_client->get(Settings::get('solr_query_url'), [
        'query' => $query,
      ])->getBody()->getContents();
    } catch (ConnectException $e) {
      return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
    }

    return new Response($content, Response::HTTP_OK, [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    ]);
  }

  /**
   * Create the query string for SOLR to match with group visibility
   *
   * @return string
   */
  private function buildGroupVisibilityQuery() {
    $user_id = \Drupal::currentUser()->id();

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
      $query .= ' OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . ' AND ss_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . ':*'. "$user_id|$username" .'*)';
    }

    return "($query)";
  }

}
