<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\Group;
use Drupal\oec_group_features\GroupFeaturePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FormAlter.
 *
 * Implementations for entity hooks.
 */
class FormOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Group feature plugin manager.
   *
   * @var \Drupal\oec_group_features\GroupFeaturePluginManager
   */
  protected $groupFeaturePluginManager;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\oec_group_features\GroupFeaturePluginManager $group_feature_plugin_manager
   *   The Group feature plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GroupFeaturePluginManager $group_feature_plugin_manager) {
    $this->configFactory = $config_factory;
    $this->groupFeaturePluginManager = $group_feature_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.group_feature')
    );
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  public function groupGroupAddForm(&$form, FormStateInterface $form_state) {
    // We don't want the features field to be accessible during the group
    // creation process.
    $form['features']['#access'] = FALSE;
  }

  /**
   * Custom submit handler for form_group forms.
   */
  public function formGroupSubmit(&$form, FormStateInterface $form_state) {
    $group = $form_state->getFormObject()->getEntity();
    $this->enableDefaultFeatures($group);
  }

  /**
   * Enables default features for a group.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The group entity.
   */
  protected function enableDefaultFeatures(Group $group) {
    $group_type_id = $group->getGroupType()->id();
    $config = $this->configFactory->get("eic_groups.group_features.default_features.$group_type_id");

    foreach ($this->groupFeaturePluginManager->getDefinitions() as $definition) {
      if (in_array($definition['id'], $config->get('default_features'))) {
        // Enable the feature.
        $default_feature = $this->groupFeaturePluginManager->createInstance($definition['id']);
        $default_feature->enable($group);

        // Make sure the feature is enabled on field level.
        $feature_found = FALSE;
        foreach ($group->get('features')->getValue() as $item) {
          if ($item['value'] == $definition['id']) {
            $feature_found = TRUE;
            break;
          }
        }
        if (!$feature_found) {
          $value = $group->get('features')->getValue();
          $value[]['value'] = $definition['id'];
          $group->get('features')->setValue($value);
        }
      }
    }
    // Save enabled group features on field level.
    $group->save();
  }

}
