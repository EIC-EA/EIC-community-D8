<?php

namespace Drupal\eic_projects\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\Client;

class FundingTenderPortalSearch {

  private string $url = 'https://api.tech.ec.europa.eu/search-api/prod/rest/search?apiKey=SEDIA_HORIZON&text=***';

  private string $search_result_url = 'https://ec.europa.eu/info/funding-tenders/opportunities/portal/screen/opportunities/horizon-results-platform/search?order=DESC&pageNumber=1&projectId=';

  public function __construct(private readonly Client $http_client, private readonly EntityTypeManagerInterface $entityTypeManager) {

  }

  public function getPortalUrl(int|string $gid) {
    $portal_results = $this->searchPortalByGid($gid);
    if ($portal_results) {
      return match ($portal_results['totalResults']) {
        1 => $portal_results['results'][0]['url'],
        0 => FALSE,
        default => $this->search_result_url . $this->getProjectIdByGroupId($gid),
      };
    }
    return FALSE;
  }

  /**
   * Search Portal by Group Project ID.
   *
   * @param int|string $gid
   *
   * @return mixed
   */
  public function searchPortalByGid(int|string $gid) {
    $project_id = $this->getProjectIdByGroupId($gid);
    return $this->searchPortal($project_id);
  }

  /**
   *
   */
  public function searchPortal(int $project_id) {
    $query = [
      "bool" => [
        "must" => [
          ["terms" => ["type" => ["9"]]],
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

  private function getProjectIdByGroupId(int|string $gid) {
    $group_project = $this->entityTypeManager->getStorage('group')
      ->load($gid);
    return (int) $group_project->get('field_project_grant_agreement_id')->value;
  }


}
