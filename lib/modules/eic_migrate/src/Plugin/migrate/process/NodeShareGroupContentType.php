<?php

namespace Drupal\eic_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\eic_share_content\Service\ShareManager;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an eic_d7_node_share_group_content_type plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: eic_d7_node_share_group_content_type
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "eic_d7_node_share_group_content_type"
 * )
 */
class NodeShareGroupContentType extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The transliteration service.
   *
   * @var \Drupal\eic_share_content\Service\ShareManager
   */
  protected $shareManager;

  /**
   * Constructs a NodeShareGroupContentType plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_share_content\Service\ShareManager $share_manager
   *   The transliteration service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ShareManager $share_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->shareManager = $share_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('eic_share_content.share_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($group = $this->entityTypeManager->getStorage('group')->load($value)) {
      return $this->shareManager->defineGroupContentType($group);
    }
    return '';
  }

}
