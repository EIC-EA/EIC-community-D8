<?php

namespace Drupal\eic_groups\Plugin\QueueWorker;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\node\NodeInterface;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\redirect\Entity\Redirect;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'eic_groups_group_content_url_alias_update' queue worker.
 *
 * @QueueWorker(
 *   id = "eic_groups_group_content_url_alias_update",
 *   title = @Translation("Task worker: Update group content url alias"),
 *   cron = {"time" = 60}
 * )
 */
class GroupContentUrlAliasUpdate extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The pathauto generator.
   *
   * @var \Drupal\pathauto\PathautoGeneratorInterface
   */
  protected $pathautoGenerator;

  /**
   * Constructs a new GroupContentUrlAliasUpdate instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator
   *   The pathauto generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PathautoGeneratorInterface $pathauto_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pathautoGenerator = $pathauto_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('pathauto.generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\group\Entity\GroupContent $group_content */
    $group_content = GroupContent::load($data);

    // Update url alias of node that belongs to the group content that is
    // being processed.
    if (($node = $group_content->getEntity()) && $node instanceof NodeInterface) {
      $this->updateNodeAlias($node);
    }

    // Update url alias of group content.
    $this->updateGroupContentAlias($group_content);
  }

  /**
   * Updates node URL alias and creates a new redirect.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   */
  private function updateNodeAlias(NodeInterface $node) {
    $current_alias = $node->get('path')->alias;
    // Deletes old alias.
    \Drupal::service('pathauto.alias_storage_helper')->deleteEntityPathAll($node);
    // Re-create entity alias.
    $new_alias = $this->pathautoGenerator->createEntityAlias($node, 'insert');
    // Delete all redirects having the same source as this alias.
    redirect_delete_by_path($new_alias['alias'], $new_alias['langcode'], FALSE);
    // Creates a new redirect.
    if ($current_alias != $new_alias['alias']) {
      if (!redirect_repository()->findMatchingRedirect($current_alias, [], $node->language()->getId())) {
        $redirect = Redirect::create();
        $redirect->setSource($node->get('path')->alias);
        $redirect->setRedirect($new_alias['alias']);
        $redirect->setLanguage($new_alias['langcode']);
        $redirect->setStatusCode(303);
        $redirect->save();
      }
    }
    // Invalidates node cache tags.
    Cache::invalidateTags($node->getCacheTags());
  }

  /**
   * Updates group content URL alias.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity.
   */
  private function updateGroupContentAlias(GroupContentInterface $group_content) {
    // Deletes old group content URL alias.
    \Drupal::service('pathauto.alias_storage_helper')->deleteEntityPathAll($group_content);
    // Re-create group content URL alias.
    $this->pathautoGenerator->createEntityAlias($group_content, 'insert');
    Cache::invalidateTags($group_content->getCacheTags());
  }

}
