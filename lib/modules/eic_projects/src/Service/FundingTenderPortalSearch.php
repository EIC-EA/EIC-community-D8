<?php

namespace Drupal\eic_projects\Service;

use Drupal\Component\Serialization\Json;
use Drupal\group\Entity\GroupInterface;
use GuzzleHttp\Client;

class FundingTenderPortalSearch {

  private string $url = 'https://api.tech.ec.europa.eu/search-api/prod/rest/search?apiKey=SEDIA_HORIZON&text=***';

  private string $search_result_url = 'https://ec.europa.eu/info/funding-tenders/opportunities/portal/screen/opportunities/horizon-results-platform/search?order=DESC&pageNumber=1&projectId=';

  public function __construct(private readonly Client $http_client) {

  }

  /**
   * Get the URL of the project in FTP by the group project entity ID.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *
   * @return false|string
   */
  public function getPortalUrl(GroupInterface $group): bool|string {
    $portal_results = $this->searchPortalByGroup($group);
    if ($portal_results) {
      return match ($portal_results['totalResults']) {
        0 => FALSE,
        default => $this->getProjectId($group) ? $this->search_result_url . $this->getProjectId($group) : FALSE,
      };
    }
    return FALSE;
  }

  /**
   * Search Portal by group project entity ID.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *
   * @return mixed
   */
  public function searchPortalByGroup(GroupInterface $group) {
    $project_id = $this->getProjectId($group);
    return $project_id ? $this->searchPortal($project_id) : [];
  }

  /**
   * Search FTP by the project ID.
   *
   * @param int $project_id
   *
   * @return bool|array
   */
  public function searchPortal(int $project_id): bool|array {
    $query = [
      "bool" => [
        "must" => [
          ["terms" => ["projectId" => ["$project_id"]]],
        ],
      ],
    ];

    // Prepare the multipart body
    $multipart = [
      [
        'name' => 'query',
        'contents' => json_encode($query),
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ],
    ];

    try {
      $response = $this->http_client->post($this->url, [
        'multipart' => $multipart
      ]);

      return Json::decode($response->getBody()->getContents());
    } catch (\Exception $exception) {

      return FALSE;
    }

  }

  /**
   * Gets the project ID of the group given.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *
   * @return int|null
   */
  private function getProjectId(GroupInterface $group): int|null {
    return (int) $group->get('field_project_grant_agreement_id')->value;
  }


}
