<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;
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
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new GroupTokens object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator
   *   The pathauto generator.
   * @param \Drupal\pathauto\AliasStorageHelperInterface $alias_storage_helper
   *   The alias storage helper service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PathautoGeneratorInterface $pathauto_generator, AliasStorageHelperInterface $alias_storage_helper, QueueFactory $queue_factory, StateInterface $state) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pathautoGenerator = $pathauto_generator;
    $this->aliasStorageHelper = $alias_storage_helper;
    $this->queueFactory = $queue_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('pathauto.generator'),
      $container->get('pathauto.alias_storage_helper'),
      $container->get('queue'),
      $container->get('state')
    );
  }

  /**
   * Implements hook_pathauto_alias_alter().
   */
  public function aliasAlter(&$alias, array &$context) {
    // Alters URL alias of book nodes that belong to a group.
    if (isset($context['data']['node'])) {
      if (($node = $context['data']['node']) && $node instanceof NodeInterface) {
        $this->nodeAliasAlter($alias, $context, $node);
      }
    }
    elseif (isset($context['data']['group'])) {
      if (($group = $context['data']['group']) && $group instanceof GroupInterface) {
        $this->groupAliasAlter($alias, $context, $group);
      }
    }
  }

  /**
   * Do changes in node alias before saving.
   *
   * @param string $alias
   *   The automatic alias after token replacement and strings cleaned.
   * @param array $context
   *   An associative array of additional options, with the following elements:
   *   - 'module': The module or entity type being aliased.
   *   - 'op': A string with the operation being performed on the object being
   *     aliased. Can be either 'insert', 'update', 'return', or 'bulkupdate'.
   *   - 'source': A string of the source path for the alias (e.g. 'node/1').
   *     This can be altered by reference.
   *   - 'data': An array of keyed objects to pass to token_replace().
   *   - 'type': The sub-type or bundle of the object being aliased.
   *   - 'language': A string of the language code for the alias (e.g. 'en').
   *     This can be altered by reference.
   *   - 'pattern': A string of the pattern used for aliasing the object.
   * @param \Drupal\node\NodeInterface $node
   *   The node entity object.
   */
  protected function nodeAliasAlter(&$alias, array &$context, NodeInterface $node) {
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

      // If group alias is set to manual, we use the manual alias instead.
      if (!$group->get('path')->pathauto) {
        $alias = "{$group->get('path')->alias}/wiki";
        return;
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

  /**
   * Do changes in group alias before saving.
   *
   * @param string $alias
   *   The automatic alias after token replacement and strings cleaned.
   * @param array $context
   *   An associative array of additional options, with the following elements:
   *   - 'module': The module or entity type being aliased.
   *   - 'op': A string with the operation being performed on the object being
   *     aliased. Can be either 'insert', 'update', 'return', or 'bulkupdate'.
   *   - 'source': A string of the source path for the alias (e.g. 'node/1').
   *     This can be altered by reference.
   *   - 'data': An array of keyed objects to pass to token_replace().
   *   - 'type': The sub-type or bundle of the object being aliased.
   *   - 'language': A string of the language code for the alias (e.g. 'en').
   *     This can be altered by reference.
   *   - 'pattern': A string of the pattern used for aliasing the object.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity object.
   */
  protected function groupAliasAlter(&$alias, array &$context, GroupInterface $group) {
    // If group alias has changed we add the group id into a queue so that
    // all group content url aliases can be updated at a later stage with
    // cron. If pathauto generator is being used to return path alias
    // (op = return), then we do nothing.
    if ($group->get('path')->alias !== $alias && $context['op'] !== 'return') {
      $this->createGroupUrlAliasUpdateQueueItem($group);
    }
  }

  /**
   * Creates an item in the queue CronOperations::GROUP_URL_ALIAS_UPDATE_QUEUE.
   *
   * @param \Drupal\group\Entity\GroupInterface $entity
   *   The group entity object.
   */
  public function createGroupUrlAliasUpdateQueueItem(GroupInterface $entity) {
    if (is_null($this->state->get(CronOperations::GROUP_URL_ALIAS_UPDATE_STATE_CACHE . $entity->id()))) {
      $queue = $this->queueFactory->get(CronOperations::GROUP_URL_ALIAS_UPDATE_QUEUE);
      $queue->createItem([
        'gid' => $entity->id(),
      ]);
      $this->state->set(CronOperations::GROUP_URL_ALIAS_UPDATE_STATE_CACHE . $entity->id(), TRUE);
    }
  }

}
