<?php

namespace Drupal\eic_webservices\Utility;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\eic_user\UserHelper;
use Drupal\eic_webservices\Controller\SubRequestController;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides helper functions for EIC Webservices module.
 */
class EicWsHelper {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourcePluginManager;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The EIC User helper class.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * The defined SMED ID field.
   *
   * @var string
   */
  protected $smedField;

  /**
   * Class constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resource_plugin_manager
   *   The REST resource plugin manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   An instance of ConfigFactory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   *   The EIC User helper class.
   */
  public function __construct(
    RequestStack $request_stack,
    HttpKernelInterface $http_kernel,
    ResourcePluginManager $resource_plugin_manager,
    ConfigFactory $config_factory,
    EntityTypeManager $entity_type_manager,
    UserHelper $eic_user_helper
  ) {
    $this->requestStack = $request_stack;
    $this->httpKernel = $http_kernel;
    $this->resourcePluginManager = $resource_plugin_manager;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->userHelper = $eic_user_helper;
    $this->smedField = $this->configFactory->get('eic_webservices.settings')->get('smed_id_field');
  }

  /**
   * Performs a sub-request to update the user's member profile.
   *
   * @param \Symfony\Component\HttpFoundation\Request $initial_request
   *   The initial request object.
   * @param \Drupal\user\UserInterface $account
   *   The user account being updated.
   *
   * @return \Symfony\Component\HttpFoundation\Response|void
   *   The response object from the sub-request.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function updateUserProfileSubRequest(Request $initial_request, UserInterface $account) {
    // Prepare the request.
    $sub_request = new SubRequestController($this->httpKernel, $this->requestStack);

    // Get the user profile.
    $this->userHelper->ensureUserMemberProfile($account);
    if (!$profile = $this->userHelper->getUserMemberProfile($account)) {
      return;
    }

    // Get the profile resource endpoint URI.
    $profile_resource = $this->resourcePluginManager->getDefinition('eic_webservices_profile');
    $uri = str_replace('{profile}', $profile->id(), $profile_resource['uri_paths']['canonical']);
    $uri .= '?_format=hal_json';

    // Get the embedded profile content.
    $content = Json::decode($initial_request->getContent(), TRUE);
    if (empty($content['_embedded']['profile'])) {
      return;
    }
    $profile_content = $content['_embedded']['profile'];

    // Perform the sub-request and return the result.
    return $sub_request->subRequest(
      $uri,
      Request::METHOD_PATCH,
      [],
      $initial_request->cookies->all(),
      $initial_request->files->all(),
      $initial_request->server->all(),
      Json::encode($profile_content),
      $initial_request->headers->all()
    );
  }

  /**
   * Returns a user object based on SMED ID field.
   *
   * @param int $smed_id
   *   The SME Dashboard ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The user entity or NULL if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUserBySmedId(int $smed_id) {
    // Find if a user account matches the given SMED ID.
    $entity_query = $this->entityTypeManager->getStorage('user')->getQuery();
    $entity_query->condition($this->getSmedIdFieldName(), $smed_id);
    $entity_query->range(NULL, 1);
    $uids = $entity_query->execute();
    return empty($uids) ? NULL : $this->entityTypeManager->getStorage('user')->load(reset($uids));
  }

  /**
   * Returns a group object based on SMED ID field.
   *
   * In SMED each type has its own 'serial' id. We can hence have the same ID
   * for different bundles. This is why we need to filter per bundle.
   *
   * @param int $smed_id
   *   The SME Dashboard ID.
   * @param string $group_type
   *   The group type.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The group entity or NULL if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupBySmedId(int $smed_id, string $group_type) {
    // Find if a user account matches the given SMED ID.
    $entity_query = $this->entityTypeManager->getStorage('group')->getQuery();
    $entity_query->condition('type', $group_type);
    $entity_query->condition($this->getSmedIdFieldName(), $smed_id);
    $entity_query->range(NULL, 1);
    $ids = $entity_query->execute();
    return empty($ids) ? NULL : $this->entityTypeManager->getStorage('group')->load(reset($ids));
  }

  /**
   * Determines if an entity has been created through SMED.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if it was created by SMED, FALSE otherwise.
   */
  public function isCreatedThroughSmed(ContentEntityInterface $entity) {
    if (!$entity->hasField($this->getSmedIdFieldName())) {
      return FALSE;
    }

    if ($entity->get($this->getSmedIdFieldName())->isEmpty()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns the URL to the SMED based on the type.
   *
   * @param string $type
   *   The type of link to get.
   * @param string|null $smed_id
   *   The SMED ID of the entity if applicable.
   *
   * @return string
   *   The URL to the SMED.
   */
  public function getSmedLink(string $type, string $smed_id = NULL) {
    $url = FALSE;

    $smed_base_url = $this->configFactory->get('eic_webservices.settings')->get('smed_url');

    switch ($type) {
      case 'event-manage':
        $url = $smed_base_url . '/form/' . $smed_id . '/manage-event';
        break;
    }

    return $url;
  }

  /**
   * Returns the configured SMED ID field.
   *
   * @return string
   *   The name of the field.
   */
  public function getSmedIdFieldName() {
    return $this->smedField;
  }

}
