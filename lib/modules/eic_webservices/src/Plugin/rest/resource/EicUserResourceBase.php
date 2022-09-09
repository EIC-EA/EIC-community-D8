<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
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

}
