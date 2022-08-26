<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_webservices\Controller\SubRequestController;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
   * The EIC Webservices helper class.
   *
   * @var \Drupal\eic_webservices\Utility\EicWsHelper
   */
  protected $wsHelper;

  /**
   * The CAS user manager.
   *
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $casUserManager;

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
    $instance->wsHelper = $container->get('eic_webservices.ws_helper');
    $instance->casUserManager = $container->get('cas.user_manager');
    return $instance;
  }

  /**
   * Responds to POST requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function post(EntityInterface $entity = NULL) {
    // Prepare the request.
    $sub_request = new SubRequestController($this->httpKernel, $this->requestStack);
    $current_request = $this->requestStack->getCurrentRequest();
    $smed_id = $current_request->attributes->get('user');

    // Get the parent resource endpoint URI.
    $parent_resource = $this->resourcePluginManager->getDefinition('eic_webservices_user');
    $uri = $current_request->getBasePath();
    $uri .= str_replace('{user}', $smed_id, $parent_resource['uri_paths']['canonical']);
    $uri .= '?_format=hal_json';

    // Perform the sub-request and return the result.
    $response = $sub_request->subRequest(
      $uri,
      Request::METHOD_PATCH,
      [],
      $current_request->cookies->all(),
      $current_request->files->all(),
      $current_request->server->all(),
      $current_request->getContent(),
      $current_request->headers->all()
    );

    $this->updateProfile($sub_request, $smed_id);

    // If user was updated, make sure we update the authmap as well with the new
    // email address.
    if ($response->getStatusCode() == 200) {
      // Get the user being updated.
      /** @var \Drupal\user\UserInterface $account */
      if ($account = $this->wsHelper->getUserBySmedId($smed_id)) {
        // Update the authmap with the new email address.
        $this->casUserManager->setCasUsernameForAccount($account, $account->getEmail());
      }
    }

    return new ModifiedResourceResponse(Json::decode($response->getContent()), $response->getStatusCode());
  }

  /**
   * @param \Drupal\eic_webservices\Controller\SubRequestController $sub_request
   *
   * @throws \Exception
   */
  private function updateProfile(SubRequestController $sub_request, int $smed_id){
    $current_request = $this->requestStack->getCurrentRequest();
    // Get the parent resource endpoint URI.
    $uri = '/smed/api/v1/profile?_format=hal_json';
    $content = json_decode($current_request->getContent(), TRUE);
    $content['_embedded']['profile']['smed_id'] = $smed_id;

    $sub_request->subRequest(
      $uri,
      Request::METHOD_POST,
      [],
      $current_request->cookies->all(),
      $current_request->files->all(),
      $current_request->server->all(),
      json_encode($content['_embedded']['profile']),
      $current_request->headers->all()
    );
  }

}
