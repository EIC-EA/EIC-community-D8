<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rest\ResourceResponse;

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
class EicUserResource extends EicUserResourceBase {

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

    // Check entity integrity.
    $is_valid = $this->checkUserEntityIntegrity($entity);
    if ($is_valid instanceof \Exception) {
      // Send custom response.
      $data = [
        'message' => "Unprocessable Entity: validation failed. " . $is_valid->getMessage(),
      ];

      return new ResourceResponse($data, 422);
    }

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
      $url = $user->toUrl('canonical', ['absolute' => TRUE]);
      // Send custom response.
      $data = [
        'message' => 'Unprocessable Entity: validation failed. User already exists.',
        'uid' => $user->id(),
        'username' => $entity->getAccountName(),
        'email' => $entity->getEmail(),
        'uri' => $url->toString(TRUE)->getGeneratedUrl(),
        $smed_id_field => $user->{$smed_id_field}->value,
      ];

      return new ResourceResponse($data, 422);
    }

    $response = parent::post($entity);

    if ($response->isSuccessful()) {
      // We need to add this new user to the authmap so it is recognised when
      // trying to log in through EU Login.
      $this->casUserManager->setCasUsernameForAccount($entity, $entity->getEmail());

      // Update the user profile.
      $this->wsHelper->updateUserProfileSubRequest(\Drupal::request(), $entity);
    }

    return $response;
  }

}
