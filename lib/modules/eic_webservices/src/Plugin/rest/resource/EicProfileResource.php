<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides a resource to get, create and update profiles.
 *
 * @RestResource(
 *   id = "eic_webservices_profile",
 *   label = @Translation("EIC Profile Resource"),
 *   entity_type = "profile",
 *   serialization_class = "Drupal\profile\Entity\Profile",
 *   uri_paths = {
 *     "canonical" = "/smed/api/v1/profile/{profile}",
 *     "create" = "/smed/api/v1/profile"
 *   }
 * )
 */
class EicProfileResource extends EntityResource {

  /**
   * The EIC Webservices REST helper class.
   *
   * @var \Drupal\eic_webservices\Utility\WsRestHelper
   */
  protected $wsRestHelper;

  /**
   * The SMED taxonomy helper class.
   *
   * @var \Drupal\eic_webservices\Utility\SmedTaxonomyHelper
   */
  protected $smedTaxonomyHelper;

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
    $instance->wsRestHelper = $container->get('eic_webservices.ws_rest_helper');
    $instance->smedTaxonomyHelper = $container->get('eic_webservices.taxonomy_helper');
    return $instance;
  }

  /**
   * Responds to POST requests.
   *
   * @param \Drupal\profile\Entity\ProfileInterface|null $entity
   *   The entity.
   *
   * @return \Drupal\rest\ResourceResponseInterface
   *   The response.
   */
  public function post(EntityInterface $entity = NULL) {
    if ($entity == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }

    // Process SMED taxonomy fields to convert the SMED ID to Term ID.
    $this->smedTaxonomyHelper->convertEntitySmedTaxonomyIds($entity);

    // Process fields to be formatted according to their type.
    $this->wsRestHelper->formatEntityFields($entity);

    return parent::post($entity);
  }

  /**
   * Responds to PATCH requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface $original_entity
   *   The original entity.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity.
   *
   * @return \Drupal\rest\ResourceResponseInterface
   *   The response.
   */
  public function patch(EntityInterface $original_entity, EntityInterface $entity = NULL) {
    if ($entity == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }

    // Process SMED taxonomy fields to convert the SMED ID to Term ID.
    $this->smedTaxonomyHelper->convertEntitySmedTaxonomyIds($entity);

    // Process fields to be formatted according to their type.
    $this->wsRestHelper->formatEntityFields($entity);

    return parent::patch($original_entity, $entity);
  }

}
