<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\eic_webservices\Controller\SubRequestController;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernel;

/**
 * Provides a resource to update users through a POST method.
 *
 * @RestResource(
 *   id = "eic_webservices_user_update",
 *   label = @Translation("EIC User Update Resource"),
 *   entity_type = "user",
 *   serialization_class = "Drupal\user\Entity\User",
 *   uri_paths = {
 *     "canonical" = "/smed/api/v1/user/update/{user}",
 *     "create" = "/smed/api/v1/user/update/{user}"
 *   }
 * )
 */
class EicUserUpdateResource extends ResourceBase {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernel
   */
  protected $httpKernel;

  /**
   * Resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourcePluginManager;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   A logger instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Symfony\Component\HttpKernel\HttpKernel $httpKernel
   *   The HTTP kernel.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resourcePluginManager
   *   The REST resource plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerChannelInterface $logger,
    RequestStack $requestStack,
    HttpKernel $httpKernel,
    ResourcePluginManager $resourcePluginManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats,
      $logger);
    $this->requestStack = $requestStack;
    $this->httpKernel = $httpKernel;
    $this->resourcePluginManager = $resourcePluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('request_stack'),
      $container->get('http_kernel.basic'),
      $container->get('plugin.manager.rest')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity.
   */
  public function post(EntityInterface $entity = NULL) {
    // Prepare the request.
    $sub_request = new SubRequestController($this->httpKernel, $this->requestStack);
    $current_request = $this->requestStack->getCurrentRequest();
    $smed_id = $current_request->attributes->get('user');

    // Get the parent resource endpoint URI.
    $parent_resource = $this->resourcePluginManager->getDefinition('eic_webservices_user');
    $uri = str_replace('{user}', $smed_id, $parent_resource['uri_paths']['canonical']);
    $uri .= '?_format=hal_json';

    // Perform the sub-request and return the result.
    $response = $sub_request->subRequest(
      $uri,
      Request::METHOD_PATCH,
      [],
      $current_request->cookies->all(),
      $current_request->files->all(),
      $current_request->getContent(),
      $current_request->headers->all()
    );

    return new ModifiedResourceResponse(Json::decode($response->getContent()), $response->getStatusCode());
  }

}
