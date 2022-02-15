<?php

namespace Drupal\eic_user_login\Service;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzleRequest;


/**
 * Class SmedUserConnection.
 *
 * @package Drupal\eic_user_login
 */
class SmedUserConnection {

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
  protected $auth_token;

  /**
   * Basic Auth username.
   *
   * @var string
   */
  protected $basic_auth_username;

  /**
   * Basic Auth password.
   *
   * @var string
   */
  protected $basic_auth_password;

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
   */
  public function setRequestConfig(array $config = []) {
    $this->endpoint = $config['endpoint_url'] ?? $this->config->get('endpoint_url');
    $this->method = $config['method'] ?? 'POST';
    $this->endpoint = $config['api_key'] ?? $this->config->get('api_key');
    $this->basic_auth_username = $config['basic_auth_username'] ?? $this->config->get('basic_auth_username');
    $this->basic_auth_password = $config['basic_auth_password'] ?? $this->config->get('basic_auth_password');

    $this->request_timeout = !is_null($request_timeout) ? $request_timeout : $smed_feeder['smed_feeder_timeout'];
  }

  protected static function getDefaultConfig() {
    return [
      //'auth' => ['', ''],
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ];
  }

  /**
   * Pings the Iguana API for data.
   *
   * @param string $endpoint division endpoint to query
   * @param array  $options for Url building
   *
   * @return object
   */
  public function queryEndpoint($endpoint, $options = []) {
    try {
      $response = $this->callEndpoint($endpoint, $options);
      return json_decode($response->getBody());
    } catch (\Exception $e) {
      watchdog_exception('iguana', $e);
      return (object) [
        'response_type' => '',
        'response_data' => [],
        'pagination'    => (object) [
          'total_count'    => 0,
          'current_limit'  => 0,
          'current_offset' => 0,
        ],
      ];
    }
  }

  /**
   * Call the Iguana API endpoint.
   *
   * @param string $endpoint
   * @param array  $options
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function callEndpoint($endpoint, $options = []) {
    $headers = $this->generateHeaders($this->requestUri($endpoint));
    $url     = isset($options['next_page']) ?
      $options['next_page'] : $this->requestUrl($endpoint, $options)
        ->toString();
    $client  = new GuzzleClient();
    $request = new GuzzleRequest($this->method, $url, $headers);
    return $client->send($request, ['timeout' => 30]);
  }

  /**
   * Build the URI part of the URL based on the endpoint and configuration.
   *
   * @param string $endpoint to the API data
   *
   * @return string
   */
  protected function requestUri($endpoint) {
    $division = $this->getConfig('division');
    return '/services/rest/' . $this->version . '/json/' . $division
      . '/' . $endpoint . '/';
  }

  /**
   * Build an array of headers to pass to the Iguana API such as the
   * signature and account.
   *
   * @param string $request_uri to the API endpoint
   *
   * @return array
   */
  protected function generateHeaders($request_uri) {
    $username       = $this->getConfig('username');
    $password       = $this->getConfig('password');
    $private_key    = $this->getConfig('private_key');
    $request_method = 'GET';
    // Date must be UTC or signature will be invalid
    $original_timezone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $message = $request_uri . $request_method . date('mdYHi');
    $headers = [
      'x-signature' => $this->generateXSignature($message, $private_key),
      'x-account'   => $this->generateXAccount($username, $password),
    ];
    date_default_timezone_set($original_timezone);
    return $headers;
  }

}
