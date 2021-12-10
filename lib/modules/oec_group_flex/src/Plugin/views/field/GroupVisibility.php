<?php

namespace Drupal\oec_group_flex\Plugin\views\field;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field for group visibility.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_visibility")
 */
class GroupVisibility extends FieldPluginBase {

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

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $group = $values->_entity;
    if (!$group instanceof GroupInterface) {
      return '';
    }

    try {
      return $this->oecGroupFlexHelper->getGroupVisibilityTagLabel($group);
    }
    catch (MissingDataException $e) {
      return '';
    }
  }

}
