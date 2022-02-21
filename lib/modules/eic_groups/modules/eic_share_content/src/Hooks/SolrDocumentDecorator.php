<?php

namespace Drupal\eic_share_content\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_share_content\Service\ShareManager;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Solarium\Core\Query\DocumentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class that decorates Solr documents for sharing feature.
 */
class SolrDocumentDecorator implements ContainerInjectionInterface {

  /**
   * The share manager.
   *
   * @var \Drupal\eic_share_content\Service\ShareManager
   */
  private $shareManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a new SolrDocumentDecorator object.
   *
   * @param \Drupal\eic_share_content\Service\ShareManager $manager
   *   The share manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    ShareManager $manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->shareManager = $manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_share_content.share_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Alters the search api documents to include sharing information.
   *
   * @param \Solarium\Core\Query\DocumentInterface $document
   *   The Search API document.
   *
   * @return \Solarium\Core\Query\DocumentInterface
   *   The altered Search API document.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function decorate(DocumentInterface $document): DocumentInterface {
    $fields = $document->getFields();
    $entity = $this->entityTypeManager
      ->getStorage('node')
      ->load($fields['its_content_nid']);
    if (
      !$entity instanceof NodeInterface
      || !$this->shareManager->isSupported($entity->bundle())
    ) {
      return $document;
    }

    if (!$shares = $this->shareManager->getSharedEntities($entity)) {
      return $document;
    }

    $shared_groups = [];
    foreach ($shares as $share) {
      $group = $share->get('gid')->entity;
      if (!$group instanceof GroupInterface) {
        continue;
      }

      $shared_groups[] = $group->id();
    }

    $document->setField('itm_shared_groups', $shared_groups);

    return $document;
  }

}
