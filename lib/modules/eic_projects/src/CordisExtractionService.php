<?php

namespace Drupal\eic_projects;

use Drupal\Core\State\StateInterface;
use GuzzleHttp\Client;


class CordisExtractionService {

  public Client $http_client;

  public StateInterface $state;

  public string $api_key;

  public string $base_domain;

  public string $request_url;

  public string $status_url;

  public function __construct(Client $httpClient) {
    $this->http_client = $httpClient;
    $this->api_key = '';
    $this->base_domain = 'https://cordis.europa.eu';
    $this->request_url = '/api/dataextractions/getExtraction';
    $this->status_url = '/api/dataextractions/getExtractionStatus';
  }

  public function requestExtraction($request_entity_id): void {
    $request_entity = \Drupal::entityTypeManager()
      ->getStorage('extraction_request')->load($request_entity_id);
    $extraction_options = [
      'query' => [
        'outputFormat' => 'xml',
        'key' => $this->api_key,
        'query' => $request_entity->get('query')->value, //agricultural sciences
      ],
    ];

    $result = $this->http_client->get($this->base_domain . $this->request_url, $extraction_options);
    $body = json_decode($result->getBody()
      ->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);
    if ($body['status'] === TRUE) {
      $task_id = $body['payload']['taskID'];
      $request_entity = \Drupal::entityTypeManager()
        ->getStorage('extraction_request')->load($request_entity_id);
      $request_entity
        ->set('task_id', $task_id)
        ->set('extraction_status', 'pending_extraction')
        ->save();
      \Drupal::state()->set('eic_projects.running_task_id', $task_id);
    }

  }

  public function getStatus($request_entity_id) {
    $task_id = \Drupal::entityTypeManager()
      ->getStorage('extraction_request')->load($request_entity_id)
      ->get('task_id')->value;
    $request_options = [
      'query' => [
        'taskId' => $task_id,
        'key' => $this->api_key,
      ],
    ];
    $result = $this->http_client->get($this->base_domain . $this->status_url, $request_options);
    $body = json_decode($result->getBody()
      ->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);
    if ($body['status']) {
      return $body['payload'];
    }
    return FALSE;
  }

}
