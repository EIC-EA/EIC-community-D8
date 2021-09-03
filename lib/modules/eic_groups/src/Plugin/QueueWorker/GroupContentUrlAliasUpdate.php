<?php

namespace Drupal\eic_groups\Plugin\QueueWorker;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\group\Entity\GroupContent;
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
      $this->pathautoGenerator->updateEntityAlias($node, 'update');
      // Create redirection for the old node URL.
      Redirect::create([
        'redirect_source' => $node->get('path')->alias,
        'redirect_redirect' => 'internal:/' . $node->toUrl()->getInternalPath(),
        'language' => $node->language()->getId(),
        'status_code' => '200',
      ])->save();
      Cache::invalidateTags($node->getCacheTags());
    }

    // Update url alias of the group content.
    $this->pathautoGenerator->updateEntityAlias($group_content, 'update');
    // Create redirection for the old group content URL.
    Redirect::create([
      'redirect_source' => $group_content->get('path')->alias,
      'redirect_redirect' => 'internal:/' . $group_content->toUrl()->getInternalPath(),
      'language' => $group_content->language()->getId(),
      'status_code' => '200',
    ])->save();
    Cache::invalidateTags($group_content->getCacheTags());
  }

}
