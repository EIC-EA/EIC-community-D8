<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_webservices\Controller\SubRequestController;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a resource to update organisations through a POST method.
 *
 * @RestResource(
 *   id = "eic_webservices_organisation_update",
 *   label = @Translation("EIC Organisation Update Resource"),
 *   entity_type = "group",
 *   serialization_class = "Drupal\group\Entity\Group",
 *   uri_paths = {
 *     "canonical" = "/smed/api/v1/organisation/{group}",
 *     "create" = "/smed/api/v1/organisation/update/{group}"
 *   }
 * )
 */
class OrganisationUpdateResource extends ResourceBase {

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
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->requestStack = $container->get('request_stack');
    $instance->httpKernel = $container->get('http_kernel.basic');
    $instance->resourcePluginManager = $container->get('plugin.manager.rest');
    return $instance;
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
    $smed_id = $current_request->attributes->get('group');

    // Get the parent resource endpoint URI.
    $parent_resource = $this->resourcePluginManager->getDefinition('eic_webservices_organisation');
    $uri = str_replace('{group}', $smed_id, $parent_resource['uri_paths']['canonical']);
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

    return new ResourceResponse(Json::decode($response->getContent()), $response->getStatusCode());
  }

}
