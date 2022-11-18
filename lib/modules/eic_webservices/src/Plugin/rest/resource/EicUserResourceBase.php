<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents EIC User Resource records as resources.
 */
abstract class EicUserResourceBase extends EntityResource {

  /**
   * The EIC Webservices helper class.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EIC Webservices helper class.
   *
   * @var \Drupal\eic_webservices\Utility\EicWsHelper
   */
  protected $wsHelper;

  /**
   * The EIC Webservices helper class.
   *
   * @var \Drupal\eic_webservices\Utility\WsRestHelper
   */
  protected $wsRestHelper;

  /**
   * The EIC SMED taxonomy helper class.
   *
   * @var \Drupal\eic_webservices\Utility\SmedTaxonomyHelper
   */
  protected $smedTaxonomyHelper;

  /**
   * The CAS user manager.
   *
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $casUserManager;

  /**
   * The CAS user manager.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

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
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->wsHelper = $container->get('eic_webservices.ws_helper');
    $instance->wsRestHelper = $container->get('eic_webservices.ws_rest_helper');
    $instance->smedTaxonomyHelper = $container->get('eic_webservices.taxonomy_helper');
    $instance->casUserManager = $container->get('cas.user_manager');
    $instance->emailValidator = $container->get('email.validator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function patch(EntityInterface $original_entity, EntityInterface $entity = NULL) {
    // Process SMED taxonomy fields to convert the SMED ID to Term ID.
    $this->smedTaxonomyHelper->convertEntitySmedTaxonomyIds($entity);

    // Process fields to be formatted according to their type.
    $this->wsRestHelper->formatEntityFields($entity);

    return parent::patch($original_entity, $entity);
  }

  /**
   * Check that the user entity has the required/valid information.
   *
   * @param \Drupal\user\UserInterface|null $entity
   *   The user entity.
   *
   * @return bool|\Exception
   *   TRUE if user entity is valid, Exception otherwise.
   */
  public function checkUserEntityIntegrity(UserInterface $entity = NULL) {
    // Check if entity is null.
    if (is_null($entity)) {
      return new \Exception("Entity is null.");
    }

    // Check if account has an account name.
    if (empty($entity->getAccountName())) {
      return new \Exception("Account name is empty.");
    }

    // Check that there is email address.
    if (empty($entity->getEmail())) {
      return new \Exception("Email address is empty.");
    }
    // Check that the email address is valid.
    elseif (!$this->emailValidator->isValid($entity->getEmail())) {
      return new \Exception("Email address is not valid.");
    }

    return TRUE;
  }

}
