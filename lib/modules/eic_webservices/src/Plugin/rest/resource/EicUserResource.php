<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to get, create and update users.
 *
 * @RestResource(
 *   id = "eic_webservices_user",
 *   label = @Translation("EIC User Resource"),
 *   entity_type = "user",
 *   serialization_class = "Drupal\user\Entity\User",
 *   uri_paths = {
 *     "canonical" = "/smed/api/v1/user/{user}",
 *     "create" = "/smed/api/v1/user"
 *   }
 * )
 */
class EicUserResource extends EntityResource {

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
    $instance->wsHelper = $container->get('eic_webservices.ws_helper');
    $instance->casUserManager = $container->get('cas.user_manager');
    return $instance;
  }

  /**
   * Responds to POST requests.
   *
   * @param \Drupal\user\UserInterface|null $entity
   *   The entity.
   *
   * @return \Drupal\rest\ResourceResponseInterface
   *   The response.
   */
  public function post(EntityInterface $entity = NULL) {
    // Get the field name that contains the SMED ID.
    $smed_id_field = $this->configFactory->get('eic_webservices.settings')->get('smed_id_field');

    // Check if user already exists.
    $user_exists = FALSE;
    if ($user = user_load_by_mail($entity->getEmail())) {
      $user_exists = TRUE;
    }
    elseif ($user = user_load_by_name($entity->getAccountName())) {
      $user_exists = TRUE;
    }
    elseif (!empty($entity->{$smed_id_field}->value) &&
      $user = $this->wsHelper->getUserBySmedId($entity->{$smed_id_field}->value)) {
      $user_exists = TRUE;
    }

    // If user already exists, return a customised response.
    if ($user_exists) {
      // Send custom response.
      $data = [
        'message' => 'Unprocessable Entity: validation failed. User already exists.',
        $smed_id_field => $user->{$smed_id_field}->value,
      ];

      return new ResourceResponse($data, 422);
    }

    parent::post($entity);

    // We need to add this new user to the authmap so it is recognised when
    // trying to log in through EU Login.
    $this->casUserManager->setCasUsernameForAccount($entity, $entity->getEmail());

    return new ResourceResponse($entity);
  }

}
