<?php

namespace Drupal\eic_share_content\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_share_content\Service\ShareManager;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Solarium\Core\Query\DocumentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SolrDocumentDecorator implements ContainerInjectionInterface {

  /**
   * @var \Drupal\eic_share_content\Service\ShareManager
   */
  private $shareManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @param \Drupal\eic_share_content\Service\ShareManager $manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
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
    return new static($container->get('eic_share_content.share_manager'),
      $container->get('entity_type.manager'));
  }

  /**
   * @param \Solarium\Core\Query\DocumentInterface $document
   *
   * @return \Solarium\Core\Query\DocumentInterface
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

    if (!$shares = $this->shareManager->getShares($entity)) {
      return $document;
    }

    $shared_groups = [];
    foreach ($shares as $share) {
      $group = $share->get('field_group_ref')->entity;
      if (!$group instanceof GroupInterface) {
        continue;
      }

      $shared_groups[] = $group->id();
    }

    $document->setField('itm_shared_groups', $shared_groups);

    return $document;
  }

}
