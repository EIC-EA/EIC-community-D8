<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\pathauto\AliasStorageHelperInterface;
use Drupal\pathauto\PathautoGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Pathauto.
 *
 * Implementation of pathauto hooks.
 */
class Pathauto implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The pathauto generator.
   *
   * @var \Drupal\pathauto\PathautoGeneratorInterface
   */
  protected $pathautoGenerator;

  /**
   * The alias storage helper service.
   *
   * @var \Drupal\pathauto\AliasStorageHelperInterface
   */
  protected $aliasStorageHelper;

  /**
   * Constructs a new GroupTokens object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator
   *   The pathauto generator.
   * @param \Drupal\pathauto\AliasStorageHelperInterface $alias_storage_helper
   *   The alias storage helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PathautoGeneratorInterface $pathauto_generator, AliasStorageHelperInterface $alias_storage_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pathautoGenerator = $pathauto_generator;
    $this->aliasStorageHelper = $alias_storage_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('pathauto.generator'),
      $container->get('pathauto.alias_storage_helper')
    );
  }

  /**
   * Implements hook_pathauto_alias_alter().
   */
  public function aliasAlter(&$alias, array &$context) {
    // Alters URL alias of book nodes that belong to a group.
    if (isset($context['data']['node'])) {
      if (($node = $context['data']['node']) && $node instanceof NodeInterface) {

        // We don't change alias for nodes other than books.
        if ($node->bundle() !== 'book') {
          return;
        }

        if ($group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($node)) {
          /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
          $group_content = reset($group_contents);
          if ($group_content->hasTranslation($node->language()->getId())) {
            $group = $group_content->getGroup()->getTranslation($node->language()->getId());
          }
          else {
            $group = $group_content->getGroup();
          }

          // If the group has an alias we use it as a prefix.
          if ($group_alias = $this->aliasStorageHelper->loadBySource($group->toUrl()->getInternalPath(), $group->language()->getId())) {
            $alias = "{$group_alias['alias']}/wiki";
            return;
          }

          // We use pathauto generator to retrieve the group alias so we can
          // use it as a prefix.
          $group_alias = $this->pathautoGenerator->createEntityAlias($group, 'return');

          $alias = "{$group_alias}/wiki";
        }
      }
    }
  }

}
