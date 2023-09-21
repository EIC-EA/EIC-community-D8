<?php

namespace Drupal\oec_group_flex\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_flex\GroupFlexGroupSaver;
use Drupal\group_flex\Plugin\GroupVisibilityManager;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Drupal\oec_group_flex\Plugin\GroupVisibilityOptionsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom action to change groups visibility.
 *
 * @Action(
 *   id = "oec_group_visibility_change_action",
 *   label = @Translation("Change visibility of group"),
 *   type = "group",
 * )
 */
class VisibilityChange extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The group visibility manager.
   *
   * @var \Drupal\group_flex\Plugin\GroupVisibilityManager
   */
  protected $visibilityManager;

  /**
   * The group_flex saver service.
   *
   * @var \Drupal\group_flex\GroupFlexGroupSaver
   */
  protected $groupFlexSaver;

  /**
   * The OEC group_flex helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $groupFlexHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    GroupVisibilityManager $visibility_manager,
    GroupFlexGroupSaver $group_flex_saver,
    OECGroupFlexHelper $group_flex_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->visibilityManager = $visibility_manager;
    $this->groupFlexSaver = $group_flex_saver;
    $this->groupFlexHelper = $group_flex_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.group_visibility'),
      $container->get('group_flex.group_saver'),
      $container->get('oec_group_flex.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    foreach ($entities as $entity) {
      $this->groupFlexSaver->saveGroupVisibility($entity, $this->configuration['visibility']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];

    /** @var \Drupal\group_flex\Plugin\GroupVisibilityInterface $instance */
    foreach ($this->visibilityManager->getAllAsArrayForGroup() as $id => $instance) {
      // We skip configurable visibilities.
      if ($instance instanceof GroupVisibilityOptionsInterface) {
        continue;
      }

      $options[$id] = $this->groupFlexHelper->getVisibilityTagLabel($instance->getPluginId());
    }

    $form['visibility'] = [
      '#type' => 'radios',
      '#title' => $this->t('Change visibility to'),
      '#description' => $this->t('The visibility that the group should be changed to'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['visibility'] = $form_state->getValue('visibility');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIf($object instanceof GroupInterface)
      ->andIf(AccessResult::allowedIf($object->access('update')));
    return $return_as_object ? $result : $result->isAllowed();
  }

}
