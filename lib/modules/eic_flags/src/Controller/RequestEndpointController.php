<?php

namespace Drupal\eic_flags\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_flags\Service\HandlerInterface;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\flag\FlaggingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RequestEndpointController
 *
 * @package Drupal\eic_flags\Controller
 */
class RequestEndpointController extends ControllerBase {

  /**
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  private $handlerCollector;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $account;

  /**
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  public function __construct(
    RequestHandlerCollector $collector,
    AccountProxyInterface $account
  ) {
    $this->handlerCollector = $collector;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_flags.handler_collector'),
      $container->get('current_user')
    );
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function request(Request $request, RouteMatchInterface $route_match) {
    $handler = $this->handlerCollector->getHandlerByType($request->attributes->get('request_type'));
    $content = json_decode($request->getContent(), TRUE);
    if (
      !$handler instanceof HandlerInterface
      || !isset($content['reason'])) {
      return new JsonResponse(['result' => FALSE]);
    }

    $entity_type_id = $route_match->getRouteObject()
      ->getOption('entity_type_id');

    $entity = $this->entityTypeManager()
      ->getStorage($entity_type_id)
      ->load($request->attributes->get($entity_type_id));
    if (!$entity instanceof ContentEntityInterface) {
      return new JsonResponse([
        'result' => FALSE,
        'message' => $this->t('Invalid entity'),
      ]);
    }

    if ($handler->hasOpenRequest($entity, $this->account)) {
      return new JsonResponse([
        'result' => FALSE,
        'message' => $this->t('You already have an open request'),
      ]);
    }

    $result = $handler->applyFlag($entity, $content['reason']);

    return new JsonResponse([
      'result' => $result instanceof FlaggingInterface,
    ]);
  }

}
