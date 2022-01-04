<?php

namespace Drupal\oec_group_flex\Entity\Form;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Form\GroupForm as GroupFormBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\oec_group_flex\Plugin\GroupVisibilityOptionsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OEC Form controller extension for group forms.
 *
 * @ingroup group
 */
class GroupForm extends GroupFormBase {

  /**
   * The group flex settings array.
   *
   * @var array
   */
  protected $groupFlexSettings;

  /**
   * The group type flex service.
   *
   * @var \Drupal\group_flex\GroupFlexGroupType
   */
  protected $groupTypeFlex;

  /**
   * The group flex service.
   *
   * @var \Drupal\group_flex\GroupFlexGroup
   */
  protected $groupFlex;

  /**
   * The group visibility manager.
   *
   * @var \Drupal\group_flex\Plugin\GroupVisibilityManager
   */
  protected $visibilityManager;

  /**
   * The flex group type saver.
   *
   * @var \Drupal\group_flex\GroupFlexGroupSaver
   */
  protected $groupFlexSaver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $form */
    $form = parent::create($container);
    $form->groupTypeFlex = $container->get('group_flex.group_type');
    $form->groupFlex = $container->get('group_flex.group');
    $form->visibilityManager = $container->get('plugin.manager.group_visibility');
    $form->groupFlexSaver = $container->get('group_flex.group_saver');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->getEntity();

    /** @var \Drupal\group\Entity\GroupTypeInterface $groupType */
    $groupType = $group->getGroupType();

    // Sets default form state values from private tempstore.
    $this->setFormStateValuesFromTempStore($form, $form_state);

    // The group flex logic is enabled for this group type.
    if ($this->groupTypeFlex->hasFlexEnabled($groupType)) {
      $visibilityPlugins = $this->visibilityManager->getAllAsArrayForGroup();
      $groupVisibility = $this->groupTypeFlex->getGroupTypeVisibility($groupType);
      $form['footer']['group_visibility'] = [
        '#title' => $this->t('Visibility'),
        '#type' => 'item',
        '#weight' => isset($form['actions']['#weight']) ? ($form['actions']['#weight'] - 1) : -1,
      ];

      // The group visibility is flexible on a group level.
      if ($this->groupTypeFlex->hasFlexibleGroupTypeVisibility($groupType)) {
        // Initialize list of array options for the visibility radio buttons.
        $visibilityOptions = [];

        // Loops through each group visibility plugin to set the labels for
        // the radio buttons and the plugin forms in case it implements the
        // interface GroupVisibilityOptionsInterface.
        foreach ($visibilityPlugins as $id => $pluginInstance) {
          $visibilityOptions[$id] = $pluginInstance->getGroupLabel($groupType);

          if ($pluginInstance instanceof GroupVisibilityOptionsInterface) {
            $form['footer']['group_visibility_options'][$id] = [
              '#type' => 'container',
              '#states' => [
                'visible' => [
                  ':input[name="group_visibility"]' => [
                    'value' => $id,
                  ],
                ],
              ],
              '#weight' => $form['footer']['group_visibility']['#weight'] + 1,
            ];
            $form['footer']['group_visibility_options'][$id][] = $pluginInstance->getPluginOptionsForm($form_state);
          }
        }

        if (!empty($visibilityOptions)) {
          $form['footer']['group_visibility']['#required'] = TRUE;
          $form['footer']['group_visibility']['#type'] = 'radios';
          $form['footer']['group_visibility']['#options'] = $visibilityOptions;

          // Sets default visibility from the form_state.
          if ($form_state->hasValue('group_visibility')) {
            $default = $form_state->getValue('group_visibility');
          }
          else {
            $default = $this->groupFlex->getGroupVisibility($group);
          }
          $form['footer']['group_visibility']['#default_value'] = $default;
        }
      }

      // The group type visibility cannot be changed on group level.
      if (array_key_exists($groupVisibility,
          $visibilityPlugins) && $this->groupTypeFlex->hasFlexibleGroupTypeVisibility($groupType) === FALSE) {
        $pluginInstance = $visibilityPlugins[$groupVisibility];

        $visExplanation = $pluginInstance->getValueDescription($groupType);
        $visDescription = $this->t('The @group_type_name visibility is @visibility_value', [
          '@group_type_name' => $groupType->label(),
          '@visibility_value' => $pluginInstance->getLabel(),
        ]);
        if ($visExplanation) {
          $form['footer']['group_visibility']['#markup'] = '<p>' . $visDescription . ' (' . $visExplanation . ')' . '</p>';
        }
      }

      // The group joining method can be changed on group level.
      if ($this->groupTypeFlex->canOverrideJoiningMethod($groupType)) {
        $enabledMethods = $this->groupTypeFlex->getEnabledJoiningMethodPlugins($groupType);
        $methodOptions = [];
        foreach ($enabledMethods as $id => $pluginInstance) {
          $methodOptions[$id] = $pluginInstance->getLabel();
        }
        $form['footer']['group_joining_methods'] = [
          '#title' => $this->t('Joining methods'),
          '#type' => 'radios',
          '#options' => $methodOptions,
          '#weight' => $form['footer']['group_visibility']['#weight'] + 2,
        ];
        try {
          $defaultOptions = $this->groupFlex->getDefaultJoiningMethods($group);
        }
        catch (MissingDataException $e) {
          $defaultOptions = [];
        }
        $form['footer']['group_joining_methods']['#default_value'] = !empty($defaultOptions) ? reset($defaultOptions) : array_key_first($methodOptions);

        // Availability of join method depends on the group visibility.
        if (isset($visibilityOptions)) {
          /** @var \Drupal\group_flex\Plugin\GroupJoiningMethodBase $joiningMethod */
          foreach ($enabledMethods as $id => $joiningMethod) {
            $allowedVisOptions = $joiningMethod->getVisibilityOptions();
            if (!empty($allowedVisOptions)) {
              foreach ($visibilityOptions as $visibilityOptionId => $unusedLabel) {
                if (!in_array($visibilityOptionId, $allowedVisOptions, TRUE)) {
                  $form['footer']['group_joining_methods'][$id]['#states']['disabled'][][':input[name="group_visibility"]'] = ['value' => $visibilityOptionId];
                }
              }
            }
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Create an array of group flex settings.
    $groupFlexSettings = [
      'visibility' => [
        'plugin_id' => $form_state->getValue('group_visibility'),
      ],
      'joining_methods' => $form_state->getValue('group_joining_methods'),
    ];

    if ($groupFlexSettings['visibility']['plugin_id']) {
      $groupVisibilityPlugin = $this->visibilityManager->createInstance($groupFlexSettings['visibility']['plugin_id']);

      if ($groupVisibilityPlugin instanceof GroupVisibilityOptionsInterface) {
        $groupFlexSettings['visibility']['visibility_options'] = $groupVisibilityPlugin->getFormStateValues($form_state);
      }
    }

    $this->groupFlexSettings = $groupFlexSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $group = $this->entity;
    $return = parent::save($form, $form_state);

    if (!$groupFlexSettings = $this->getGroupFlexSettingsFormValues($form, $form_state)) {
      return $return;
    }

    if (empty($groupFlexSettings['settings']) || empty($groupFlexSettings['group'])) {
      return $return;
    }

    $group = $groupFlexSettings['group'];
    if (!$group instanceof GroupInterface) {
      return $return;
    }

    foreach ($groupFlexSettings['settings'] as $key => $value) {
      switch ($key) {
        case 'visibility':
          if (!isset($value['plugin_id'])) {
            break;
          }

          $visibility_options = [];

          // Gets group visibility options.
          if (isset($value['visibility_options'])) {
            $visibility_options = $value['visibility_options'];
          }

          // Saves the group visibility. Note that Group flex service is being
          // decorated by OECGroupFlexGroupSaverDecorator and the method
          // saveGroupVisibility can receive a 3rd argument in case we need to
          // save extra visibility options.
          $this->groupFlexSaver->saveGroupVisibility($group, $value['plugin_id'], $visibility_options);
          break;

        case 'joining_methods':
          // Because we can change the group visibility to private of existing
          // group causing the joining method not to be disabled after this.
          if ($value === NULL) {
            $value = [];
          }
          // This is needed to support the use of radios.
          if (is_string($value)) {
            $value = [$value => $value];
          }
          $this->groupFlexSaver->saveGroupJoiningMethods($group, $value);
          break;
      }
    }

    $this->clearTempStore($form, $form_state);
    return $return;
  }

  /**
   * Gets the group flex settings from the form object or tempstore.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|bool
   *   The array of group flex settings. FALSE if there are no settings.
   */
  protected function getGroupFlexSettingsFormValues(array $form, FormStateInterface $form_state) {
    $wizard_id = 'group_creator';
    if ($form_state->get('group_wizard') && $form_state->get('group_wizard_id') === $wizard_id) {
      $store_id = $form_state->get('store_id');
      $store = $this->privateTempStoreFactory->get($wizard_id);

      if (!($group_type = $this->entityTypeManager->getStorage('group_type')->load($store_id))) {
        return FALSE;
      }

      if (!$group_type instanceof GroupTypeInterface) {
        return FALSE;
      }

      // See if the group type is configured to ask the creator to fill out
      // their membership details. Also pass this info to the form state.
      $creatorMustComplete = $group_type->creatorMustCompleteMembership();
      if ($creatorMustComplete) {
        $store = $this->privateTempStoreFactory->get('group_creator_flex');
        $store_id = $form_state->get('store_id');

        $formObject = $form_state->getFormObject();

        if (!$formObject instanceof EntityFormInterface) {
          return FALSE;
        }

        return [
          'settings' => [
            'visibility' => $store->get("$store_id:visibility"),
            'joining_methods' => $store->get("$store_id:joining_methods"),
          ],
          'group' => $this->entity,
        ];
      }
    }

    if (empty($this->groupFlexSettings)) {
      return FALSE;
    }

    return [
      'settings' => $this->groupFlexSettings,
      'group' => $this->entity,
    ];
  }

  /**
   * Sets form state values from private tempstore.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function setFormStateValuesFromTempStore(array $form, FormStateInterface $form_state) {
    if ($group_flex_settings = $this->getGroupFlexSettingsFormValues($form, $form_state)) {
      if (empty($group_flex_settings['settings']['visibility'])) {
        return;
      }

      $group_visibility_settings = $group_flex_settings['settings']['visibility'];

      if (isset($group_visibility_settings['plugin_id'])) {
        $group_visibility_plugin = $this->visibilityManager->createInstance($group_visibility_settings['plugin_id']);

        $form_state->setValue('group_visibility', $group_visibility_settings['plugin_id']);

        // Sets form state values for RestrictedGroupVisibility plugins.
        if ($group_visibility_plugin instanceof GroupVisibilityOptionsInterface) {
          if (isset($group_visibility_settings['visibility_options'])) {
            foreach ($group_visibility_plugin->getPluginFormElementsFieldNames() as $key => $option_field_name) {
              if (in_array($key, array_keys($group_visibility_settings['visibility_options']))) {
                $form_state->setValue($option_field_name,
                  $group_visibility_settings['visibility_options'][$key]);
              }
            }
          }
        }
      }
    }
  }

  /**
   * Clears group flex settings from the private tempstore.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function clearTempStore(array &$form, FormStateInterface $form_state) {
    $store = $this->privateTempStoreFactory->get('group_creator_flex');
    $storeId = $form_state->get('store_id');
    $group_flex_settings = $this->getGroupFlexSettingsFormValues($form, $form_state);

    foreach ($group_flex_settings['settings'] as $key => $value) {
      if ($value !== NULL) {
        try {
          $store->delete("$storeId:$key");
        }
        catch (TempStoreException $exception) {
          return;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function store(array &$form, FormStateInterface $form_state) {
    parent::store($form, $form_state);
    $store = $this->privateTempStoreFactory->get('group_creator_flex');
    $storeId = $form_state->get('store_id');

    foreach ($this->groupFlexSettings as $key => $value) {
      if ($value !== NULL) {
        try {
          $store->set("$storeId:$key", $value);
        }
        catch (TempStoreException $exception) {
          return;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $this->clearTempStore($form, $form_state);
    parent::cancel($form, $form_state);
  }

}
