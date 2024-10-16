<?php

namespace Drupal\eic_vod\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_vod\Service\VODClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class VODController
 *
 * @package Drupal\eic_vod\Controller
 */
class VODController extends ControllerBase {

  private VODClient $client;

  /**
   * @param \Drupal\eic_vod\Service\VODClient $client
   */
  public function __construct(VODClient $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('eic_vod.client'));
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function proxyCookies(Request $request): JsonResponse {
    if (!$request->query->has('file')) {
      throw new NotFoundHttpException();
    }

    $file = $request->query->get('file');
    $cookies = $this->client->getCookies($file);
    if (!$cookies) {
      throw new NotFoundHttpException();
    }

    $query_strings = [];
    $header_mapping = [
      'CloudFront-Policy' => 'Policy',
      'CloudFront-Signature' => 'Signature',
      'CloudFront-Key-Pair-Id' => 'Key-Pair-Id',
    ];
    foreach ($cookies as $cookie) {
      [$cloudfront_cookie] = explode(';', $cookie);
      [$key, $value] = explode('=', $cloudfront_cookie);
      if (!isset($header_mapping[$key])) {
        continue;
      }
      
      $query_strings[$header_mapping[$key]] = $value;
    }

    return new JsonResponse([
      'cookies' => $cookies,
      'query_string' => '?' . http_build_query($query_strings),
      'stream' => $this->client->getStreamUrl($file),
    ]);
  }

}
