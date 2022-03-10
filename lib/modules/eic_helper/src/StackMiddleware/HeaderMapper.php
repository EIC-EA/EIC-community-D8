<?php

namespace Drupal\eic_helper\StackMiddleware;

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class HeaderMapper
 * This class is intended to map non-standard reverse proxy headers
 * to X-Forwarded-* headers.
 * We then let the job to the ReverseProxyMiddleware from core.
 *
 * @package Drupal\eic_helper\StackMiddleware
 */
class HeaderMapper implements HttpKernelInterface {

  /**
   * Name of the settings where the mapping is stored.
   */
  const HEADER_MAPPING_SETTING = 'reverse_proxy_header_mapping';

  /**
   * List of supported headers this class maps values towards.
   */
  private const TRUSTED_HEADERS = [
    Request::HEADER_X_FORWARDED_FOR => 'X_FORWARDED_FOR',
    Request::HEADER_X_FORWARDED_HOST => 'X_FORWARDED_HOST',
    Request::HEADER_X_FORWARDED_PROTO => 'X_FORWARDED_PROTO',
    Request::HEADER_X_FORWARDED_PORT => 'X_FORWARDED_PORT',
  ];

  /**
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected HttpKernelInterface $httpKernel;

  /**
   * @var \Drupal\Core\Site\Settings
   */
  protected Settings $settings;

  /**
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   * @param \Drupal\Core\Site\Settings $settings
   */
  public function __construct(
    HttpKernelInterface $http_kernel,
    Settings $settings
  ) {
    $this->settings = $settings;
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(
    Request $request,
    $type = self::MASTER_REQUEST,
    $catch = TRUE
  ) {
    if (
      $this->settings->get('reverse_proxy') !== FALSE
      && !empty($this->settings->get(self::HEADER_MAPPING_SETTING))
    ) {
      $this->encodeRequestUri($request);
      $this->mapHeaders($request);
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * The platform relies on urls with special characters (e.g: create/group_node:book).
   * The reverse proxy might send a not encoded value for REQUEST_URI.
   * This method is intended to encode such values in any case.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return void
   */
  protected function encodeRequestUri(Request $request) {
    if (strpos($request->server->get('REQUEST_URI'), ':') !== FALSE) {
      $request->server->set('REQUEST_URI', str_replace(':', urlencode(':'), $request->server->get('REQUEST_URI')));
    }
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  protected function mapHeaders(Request $request) {
    $supported_headers = Settings::get(self::HEADER_MAPPING_SETTING);
    foreach ($supported_headers as $to => $from) {
      if (!self::TRUSTED_HEADERS[$to] || !$request->headers->has($from)) {
        continue;
      }

      $request->headers->set(
        self::TRUSTED_HEADERS[$to],
        $request->headers->get($from)
      );
    }
  }

}
