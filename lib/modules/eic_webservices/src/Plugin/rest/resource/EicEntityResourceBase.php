<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a custom base class for Entity resource.
 *
 * @RestResource(
 *   id = "eic_webservices_resource_base",
 *   label = @Translation("EIC Entity Resource Base"),
 *   serialization_class = "Drupal\Core\Entity\Entity",
 *   deriver = "Drupal\rest\Plugin\Deriver\EntityDeriver",
 *   uri_paths = {
 *     "canonical" = "/entity/{entity_type}/{entity}",
 *     "create" = "/entity/{entity_type}"
 *   }
 * )
 */
class EicEntityResourceBase extends EntityResource {

  /**
   * {@inheritdoc}
   */
  public function get(EntityInterface $entity, Request $request) {
    $response = parent::get($entity, $request);

    // We disable cache for GET requests.
    $disable_cache = new CacheableMetadata();
    $disable_cache->setCacheMaxAge(0);
    $response->addCacheableDependency($disable_cache);

    return $response;
  }

}
