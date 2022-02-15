<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents EIC Organisation Resource records as resources.
 */
abstract class GroupResourceBase extends EntityResource {

  /**
   * The EIC Webservices helper class.
   *
   * @var \Drupal\eic_webservices\Utility\EicWsHelper
   */
  protected $wsHelper;

  /**
   * The SMED taxonomy helper class.
   *
   * @var \Drupal\eic_webservices\Utility\SmedTaxonomyHelper
   */
  protected $smedTaxonomyHelper;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->wsHelper = $container->get('eic_webservices.ws_helper');
    $instance->smedTaxonomyHelper = $container->get('eic_webservices.taxonomy_helper');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function patch(EntityInterface $original_entity, EntityInterface $entity = NULL) {
    // Process SMED taxonomy fields to convert the SMED ID to Term ID.
    $this->smedTaxonomyHelper->convertEntitySmedTaxonomyIds($entity);
    return parent::patch($original_entity, $entity);
  }

  /**
   * Sets a valid group owner.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   */
  protected function handleGroupOwner(GroupInterface &$group) {
    $request_body = Json::decode($this->requestStack->getCurrentRequest()->getContent());
    $author_uid = NULL;

    // If the request is providing a specific user ID we need to convert it to
    // the Drupal user ID.
    if (!empty($request_body['uid'][0]['target_id'])) {
      if ($author = $this->wsHelper->getUserBySmedId($request_body['uid'][0]['target_id'])) {
        $author_uid = $author->id();
      }
    }

    // If we don't have a proper author UID, we check if we have a default one
    // defined.
    if (empty($author_uid)) {
      $author_uid = $this->configFactory->get('eic_webservices.settings')->get('group_author');
    }

    // If we have a proper author UID, we set it as the author of the group,
    // otherwise we do nothing.
    if (!empty($author_uid)) {
      $group->setOwnerId($author_uid);
    }

  }

}
