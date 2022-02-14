<?php

namespace Drupal\eic_webservices\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a way to handle subrequests.
 *
 * @package Drupal\eic_webservices\Controller
 */
class SubRequestController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Symfony\Component\HttpKernel\HttpKernelInterface definition.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(HttpKernelInterface $http_kernel, RequestStack $request_stack) {
    $this->httpKernel = $http_kernel;
    $this->requestStack = $request_stack;
  }

  /**
   * Performs a subrequest.
   *
   * @param string $path
   *   Path to use for subrequest.
   * @param string $method
   *   The HTTP method to use, eg. Get, Post.
   * @param array $parameters
   *   The query parameters.
   * @param array $cookies
   *   The request cookies ($_COOKIE).
   * @param array $files
   *   The request files ($_FILES).
   * @param string|null $content
   *   The raw body data.
   * @param array $headers
   *   Additional headers to use in the request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Exception
   */
  public function subRequest(
    string $path,
    string $method = Request::METHOD_GET,
    array $parameters = [],
    array $cookies = [],
    array $files = [],
    string $content = NULL,
    array $headers = []
  ) {
    $sub_request = Request::create(
      $path,
      $method,
      $parameters,
      $cookies,
      $files,
      [],
      $content
    );

    // Set headers if any.
    if (!empty($headers)) {
      foreach ($headers as $key => $value) {
        $sub_request->headers->set($key, $value);
      }
    }

    $sub_request->setSession($this->requestStack->getCurrentRequest()->getSession());

    return $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST, FALSE);
  }

}
