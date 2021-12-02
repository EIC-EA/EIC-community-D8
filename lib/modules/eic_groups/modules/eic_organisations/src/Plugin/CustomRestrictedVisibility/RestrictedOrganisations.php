<?php

namespace Drupal\eic_organisations\Plugin\CustomRestrictedVisibility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_organisations\Constants\Organisations;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Group;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityBase;

/**
 * Provides a 'restricted_organisations' custom restricted visibility.
 *
 * @CustomRestrictedVisibility(
 *  id = "restricted_organisations",
 *  label = @Translation("Specific organisations"),
 *  weight = 10
 * )
 */
class RestrictedOrganisations extends CustomRestrictedVisibilityBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getPluginForm():array {
    $form = parent::getPluginForm();

    // @todo Make use of the entity_tree widget once available for
    // organisations.
    $form[$this->getPluginId()][$this->getPluginId() . '_conf'] = [
      '#title' => $this->t('Organisations'),
      '#description' => $this->t('Add multiple organisations separating them with a comma'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'group',
      '#tags' => TRUE,
      '#selection_settings' => [
        'target_bundles' => [Organisations::GROUP_ORGANISATION_BUNDLE],
        'sort' => [
          'field' => 'label',
          'direction' => 'ASC',
        ],
      ],
      '#states' => [
        'visible' => [
          ':input[name="' . $this->getPluginId() . '_status"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
      '#weight' => $this->getWeight() + 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultFormValues(array &$pluginForm, GroupVisibilityRecordInterface $group_visibility_record = NULL): array {
    if (is_null($group_visibility_record)) {
      return $pluginForm;
    }

    $options = $this->getOptionsForPlugin($group_visibility_record);
    if (array_key_exists($this->getStatusKey(), $options) && $options[$this->getStatusKey()] === 1) {
      $pluginForm[$this->getStatusKey()]['#default_value'] = 1;
      $conf_key = $this->getPluginId() . '_conf';

      $restricted_organisations = $options[$conf_key];
      if ($restricted_organisations) {
        foreach ($restricted_organisations as $organisation) {
          $pluginForm[$conf_key]['#default_value'][] = Group::load($organisation['target_id']);
        }
      }
    }
    return $pluginForm;
  }

  /**
   * {@inheritdoc}
   */
  public function hasViewAccess(GroupInterface $entity, AccountInterface $account, GroupVisibilityRecordInterface $group_visibility_record) {
    $conf_key = $this->getPluginId() . '_conf';
    $options = $this->getOptionsForPlugin($group_visibility_record);
    $restricted_organisations = array_key_exists($conf_key, $options) ? $options[$conf_key] : '';
    $restricted_organisations = array_column($restricted_organisations, 'target_id');

    // Allow access if user belongs to referenced organisations.
    foreach ($restricted_organisations as $organisation_id) {
      // Load the organisation.
      /** @var \Drupal\group\Entity\GroupInterface $organisation */
      $organisation = $this->entityTypeManager->getStorage('group')->load($organisation_id);
      if (!$organisation) {
        continue;
      }

      // If user is member of this group.
      if ($organisation->getMember($account)) {
        return GroupAccessResult::allowed()
          ->addCacheableDependency($account)
          ->addCacheableDependency($entity);
      }
    }
    // Fallback to neutral access.
    return parent::hasViewAccess($entity, $account, $group_visibility_record);
  }

  /**
   * {@inheritdoc}
   */
  public function validatePluginForm(array &$element, FormStateInterface $form_state) {
    // If plugin status is disabled, we do nothing.
    if (parent::validatePluginForm($element, $form_state)) {
      return;
    }
    $conf_key = $this->getPluginId() . '_conf';
    if ($form_state->getValue($conf_key)) {
      return;
    }
    return $form_state->setError($element[$this->getPluginId() . '_conf'], $this->t('Please select at least 1 organisation.'));
  }

}
