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

  /**
   * Get the URL of the project in FTP by the group project entity ID.
   *
   * @param int|string $gid
   *
   * @return false|string
   */
  public function getPortalUrl(int|string $gid): bool|string {
    $portal_results = $this->searchPortalByGid($gid);
    if ($portal_results) {
      return match ($portal_results['totalResults']) {
        0 => FALSE,
        default => $this->getProjectIdByGroupId($gid) ? $this->search_result_url . $this->getProjectIdByGroupId($gid) : FALSE,
      };
    }
    return FALSE;
  }

  /**
   * Search Portal by group project entity ID.
   *
   * @param int|string $gid
   *
   * @return mixed
   */
  public function searchPortalByGid(int|string $gid) {
    $project_id = $this->getProjectIdByGroupId($gid);
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
   * @param int|string $gid
   *
   * @return int|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getProjectIdByGroupId(int|string $gid): int|null {
    $group_project = $this->entityTypeManager->getStorage('group')
      ->load($gid);
    return (int) $group_project->get('field_project_grant_agreement_id')->value;
  }


}
