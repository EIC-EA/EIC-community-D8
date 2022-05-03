<?php

namespace Drupal\oec_group_flex\Plugin\views\field;

use Drupal\oec_group_flex\OECGroupFlexHelper;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for views group flex fields.
 *
 * @ingroup views_field_handlers
 */
abstract class GroupFlexFieldPluginBase extends FieldPluginBase {

  /**
   * The OEC Group Flex service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $oec_group_flex_helper
   *   The OEC Group Flex service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OECGroupFlexHelper $oec_group_flex_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('oec_group_flex.helper')
    );
  }

}
