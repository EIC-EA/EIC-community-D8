<?php

namespace Drupal\eic_user_login\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelTrait;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class that handles communication with SMED for user accounts.
 *
 * @package Drupal\eic_user_login
 */
class SmedUserConnection {

  use LoggerChannelTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * EIC SMED settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * API querying method.
   *
   * @var string
   */
  protected $method;

  /**
   * API endpoint URL.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * Authorisation token.
   *
   * @var string
   */
  protected $authToken;

  /**
   * Basic Auth username.
   *
   * @var string
   */
  protected $basicAuthUsername;

  /**
   * Basic Auth password.
   *
   * @var string
   */
  protected $basicAuthPassword;

  /**
   * Request timeout.
   *
   * @var int
   */
  protected $requestTimeout;

  /**
   * SmedUserConnection constructor.
   *
   * @param \Drupal\Core\Http\ClientFactory $http_client
   *   The HTTP client factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ClientFactory $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client->fromOptions();
    $this->config = $config_factory->get('eic_user_login.settings');
    $this->setRequestConfig();
  }

  /**
   * Sets the request configuration.
   *
   * @param array $config
   *   An array containing key/value pairs.
   */
  public function setRequestConfig(array $config = []) {
    $this->endpoint = $config['endpoint_url'] ?? $this->config->get('endpoint_url');
    $this->method = $config['method'] ?? 'POST';
    $this->authToken = $config['api_key'] ?? $this->config->get('api_key');
    $this->basicAuthUsername = $config['basic_auth_username'] ?? $this->config->get('basic_auth_username');
    $this->basicAuthPassword = $config['basic_auth_password'] ?? $this->config->get('basic_auth_password');
    $this->requestTimeout = $config['request_timeout'] ?? 30;
  }

  /**
   * Pings the SMED API for data.
   *
   * @param array $data
   *   The payload to be sent.
   *
   * @return array|string|string[]
   *   The processed result array.
   */
  public function queryEndpoint(array $data = []) {
    try {
      $response = $this->callEndpoint($data);
      $result = JSON::decode($response->getBody());
      return $this->processResponse($result);
    }
    catch (\Exception $e) {
      $this->getLogger('eic_user_login')->error($e->getMessage());
      return $e->getMessage();
    }
  }

  /**
   * Call the SMED API endpoint.
   *
   * @param array $data
   *   The payload to be sent.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The request response.
   */
  public function callEndpoint(array $data = []) {
    $headers = $this->generateHeaders();
    $client  = new GuzzleClient([
      'auth' => [$this->basicAuthUsername, $this->basicAuthPassword],
    ]);
    $request = new GuzzleRequest($this->method, $this->endpoint, $headers);
    try {
      return $client->send($request, [
        'timeout' => $this->requestTimeout,
        RequestOptions::JSON => $data,
      ]);
    }
    catch (GuzzleException $e) {
      $this->getLogger('eic_user_login')->error($e->getMessage());
    }
  }

  /**
   * Processes the response to return a sanitised array.
   *
   * @param array $result
   *   The decoded response body.
   *
   * @return array|string[]
   *   The sanitised result array.
   */
  protected function processResponse(array $result) {
    return [
      'field_user_status' => $result['user']['data']['attributes']['user_status'] ?? '',
      'name' => $result['user']['data']['attributes']['name'] ?? '',
      'mail' => $result['user']['data']['attributes']['mail'] ?? '',
      'field_smed_id' => $result['user']['data']['attributes']['field_smed_id'] ?? '',
      'field_first_name' => $result['user']['data']['attributes']['field_user_first_name'] ?? '',
      'field_last_name' => $result['user']['data']['attributes']['field_user_family_name'] ?? '',
      // @todo Check if status is relevant. This one gives 0 or 1.
      'status' => $result['user']['data']['attributes']['status'] ?? '',
    ];
  }

  /**
   * Builds an array of headers to pass to the SMED API.
   *
   * @return array
   *   An array of headers with key/value pairs.
   */
  protected function generateHeaders() {
    return [
      'Content-Type' => 'application/json',
      'Authorization2' => $this->authToken,
      'Cookie' => 'JSESSIONID=node01uwipbzg91yg1124gtxyxkk4ff197.node0',
    ];
  }

}
