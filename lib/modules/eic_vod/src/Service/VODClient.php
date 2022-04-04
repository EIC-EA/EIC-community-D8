<?php

namespace Drupal\eic_vod\Service;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Class VODClient
 *
 * @package Drupal\eic_vod\Service
 */
class VODClient {

  private ClientInterface $httpClient;

  private array $config = [];

  private MessengerInterface $messenger;

  private MimeTypeGuesserInterface $mimeTypeGuesser;

  /**
   * @param \GuzzleHttp\ClientInterface $http_client
   * @param \Drupal\Core\Site\Settings $settings
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * @param \Symfony\Component\Mime\MimeTypeGuesserInterface $mime_guesser
   */
  public function __construct(
    ClientInterface $http_client,
    Settings $settings,
    MessengerInterface $messenger,
    MimeTypeGuesserInterface $mime_guesser
  ) {
    $this->httpClient = $http_client;
    $this->config = $settings->get('eic_vod', []);
    $this->messenger = $messenger;
    $this->mimeTypeGuesser = $mime_guesser;
  }

  /**
   * @param string $action
   * @param string $file
   *
   * @return string|null
   */
  public function getPresignedUrl(string $action, string $file): ?string {
    $url = $this->config['cloudfront_url'];
    if (StreamWrapperManager::getScheme($file)) {
      $file = StreamWrapperManager::getTarget($file);
    }

    try {
      $response = $this->httpClient->request('GET', "https://$url/source/$action", [
        'query' => [
          'file' => $file,
        ],
        'headers' => [
          'X-Api-Key' => $this->config['cloudfront_api_key'],
        ],
      ]);

      if ($response->getStatusCode() !== Response::HTTP_OK) {
        return NULL;
      }

      return str_replace('"', '', (string) $response->getBody());
    } catch (\Throwable $exception) {
      $this->messenger->addError(
        sprintf('Something went wrong while getting presigned %s url. Error: %s', $action, $exception->getMessage())
      );
    }

    return NULL;
  }

  /**
   * @param string $source
   * @param string $destination
   *
   * @return string|null
   */
  public function putVideo(string $source, string $destination): ?string {
    $file_name = basename($destination);
    $upload_url = $this->getPresignedUrl('upload', $file_name);
    if (!$upload_url) {
      return FALSE;
    }

    try {
      $response = $this->httpClient->request('PUT', $upload_url, [
        'headers' => [
          'Content-Type' => $this->mimeTypeGuesser->guessMimeType($source),
        ],
        'body' => fopen($source, 'r'),
      ]);

      return $response->getStatusCode() === Response::HTTP_OK;
    } catch (\Throwable $exception) {
      $this->messenger->addError(
        sprintf('Something went wrong while uploading video. Error: %s', $exception->getMessage())
      );
    }

    return FALSE;
  }

  /**
   * @param string $file
   *
   * @return array|null
   */
  public function getCookies(string $file): ?array {
    try {
      $url = $this->config['cloudfront_url'];
      $response = $this->httpClient->request('GET', "https://$url/stream/download", [
        'query' => [
          'file' => $file,
        ],
        'headers' => [
          'X-Api-Key' => $this->config['cloudfront_api_key'],
        ],
      ]);

      if ($response->getStatusCode() !== Response::HTTP_OK || !$response->hasHeader('Set-Cookie')) {
        return NULL;
      }
    } catch (\Throwable $exception) {
      return NULL;
    }

    return $response->getHeader('Set-Cookie');
  }

  /**
   * @param string $file
   *
   * @return string|null
   */
  public function getStreamUrl(string $file): ?string {
    if (!isset($this->config['cloudfront_url'])) {
      return NULL;
    }

    $url = $this->config['cloudfront_url'];

    return "https://$url/streams/$file/AppleHLS1/$file.m3u8";
  }

}
