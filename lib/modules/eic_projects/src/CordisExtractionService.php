<?php

namespace Drupal\eic_projects;

use Drupal\Core\State\StateInterface;
use Drupal\eic_projects\Entity\ExtractionRequest;
use GuzzleHttp\Client;


class CordisExtractionService {

  public Client $httpClient;

  public StateInterface $state;

  public string $apiKey;

  public string $baseDomain;

  public string $requestUrl;

  public string $statusUrl;

  public string $deleteUrl;

  public function __construct(Client $httpClient) {
    $this->httpClient = $httpClient;
    $this->apiKey = \Drupal::config('eic_projects.settings')->get('api_key');
    $this->baseDomain = 'https://cordis.europa.eu';
    $this->requestUrl = '/api/dataextractions/getExtraction';
    $this->statusUrl = '/api/dataextractions/getExtractionStatus';
    $this->deleteUrl = '/api/dataextractions/deleteExtraction';
  }

  public function requestExtraction($request_entity_id): void {
    $count_entities = \Drupal::entityTypeManager()
      ->getStorage('extraction_request')->getQuery()
      ->condition('extraction_status', 'pending_extraction')
      ->count()
      ->execute();
    if ($count_entities === 0) {
      $request_entity = \Drupal::entityTypeManager()
        ->getStorage('extraction_request')->load($request_entity_id);
      $extraction_options = [
        'query' => [
          'outputFormat' => 'xml',
          'key' => $this->apiKey,
          'query' => $request_entity->get('query')->value,
        ],
      ];

      $result = $this->httpClient->get($this->baseDomain . $this->requestUrl, $extraction_options);
      $body = json_decode($result->getBody()
        ->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);
      if ($body['status'] === TRUE) {
        $task_id = $body['payload']['taskID'];
        $request_entity
          ->set('task_id', $task_id)
          ->set('extraction_status', 'pending_extraction')
          ->save();
      }
    }

  }

  public function getStatus($request_entity_id) {
    $entity = \Drupal::entityTypeManager()
      ->getStorage('extraction_request')->load($request_entity_id);
    if ($entity) {
      $task_id = $entity->get('task_id')->value;
      $request_options = [
        'query' => [
          'taskId' => $task_id,
          'key' => $this->apiKey,
        ],
      ];
      $result = $this->httpClient->get($this->baseDomain . $this->statusUrl, $request_options);
      $body = json_decode($result->getBody()
        ->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);
      if ($body['status']) {
        return $body['payload'];
      }
    }
    return FALSE;
  }

  public function deleteExtraction($request_entity_id) {
    $task_id = \Drupal::entityTypeManager()
      ->getStorage('extraction_request')->load($request_entity_id)
      ->get('task_id')->value;
    $extraction_options = [
      'query' => [
        'key' => $this->apiKey,
        'taskId' => $task_id,
      ],
    ];

    $this->httpClient->delete($this->baseDomain . $this->deleteUrl, $extraction_options);
  }

}
