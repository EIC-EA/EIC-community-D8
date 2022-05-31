<?php

namespace Drupal\eic_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an eic_d7_duplicated_redirect plugin.
 *
 * This module helps to check if a redirect already exists.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: eic_d7_duplicated_redirect
 *     source: source
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "eic_d7_duplicated_redirect"
 * )
 */
class DuplicatedRedirect extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return FALSE;
    }

    if (!is_string($value)) {
      return FALSE;
    }

    $ids = $this->entityTypeManager->getStorage('redirect')->getQuery()
      ->condition('redirect_source.path', $value, 'LIKE')
      ->execute();

    return !empty($ids);
  }

}
