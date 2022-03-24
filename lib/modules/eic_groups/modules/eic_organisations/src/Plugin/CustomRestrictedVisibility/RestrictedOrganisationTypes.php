<?php

namespace Drupal\eic_organisations\Plugin\CustomRestrictedVisibility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_content\Plugin\Field\FieldWidget\EntityTreeWidget;
use Drupal\eic_organisations\Constants\Organisations;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a 'restricted_organisation_types' custom restricted visibility.
 *
 * @CustomRestrictedVisibility(
 *  id = "restricted_organisation_types",
 *  label = @Translation("Specific organisation types"),
 *  weight = 10
 * )
 */
class RestrictedOrganisationTypes extends CustomRestrictedVisibilityBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getPluginForm():array {
    $form = parent::getPluginForm();

    $options = [
      'match_top_level_limit' => 0,
      'items_to_load' => 0,
      'auto_select_parents' => 0,
      'disable_top_choices' => 0,
      'load_all' => 1,
      'ignore_current_user' => 0,
      'target_bundles' => [Organisations::VOCABULARY_ORGANISATION_TYPE],
      'is_required' => 0,
    ];

    $form[$this->getPluginId()][$this->getPluginId() . '_conf'] = EntityTreeWidget::getEntityTreeFieldStructure(
      [],
      'taxonomy_term',
      '',
      0,
      Url::fromRoute('eic_content.entity_tree')->toString(),
      Url::fromRoute('eic_content.entity_tree_search')->toString(),
      Url::fromRoute('eic_content.entity_tree_children')->toString(),
      $options
    );
    $form[$this->getPluginId()][$this->getPluginId() . '_conf']['#title'] = $this->t('Organisation types');
    $form[$this->getPluginId()][$this->getPluginId() . '_conf']['#states'] = [
      'visible' => [
        ':input[name="' . $this->getPluginId() . '_status"]' => [
          'checked' => TRUE,
        ],
      ],
    ];
    $form[$this->getPluginId()][$this->getPluginId() . '_conf']['#weight'] = $this->getWeight() + 1;
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

      $restricted_organisation_types = $options[$conf_key];

      if (!$restricted_organisation_types) {
        return $pluginForm;
      }

      $terms = [];
      foreach ($restricted_organisation_types as $organisation_type) {
        if ($term = Term::load($organisation_type['target_id'])) {
          $terms[] = $term;
          $pluginForm[$conf_key]['#default_value'][] = $term;
        }
      }
      $pluginForm[$conf_key]['#attributes']['data-selected-terms'] = EntityTreeWidget::formatTaxonomyPreSelection($terms);
    }
    return $pluginForm;
  }

  /**
   * {@inheritdoc}
   */
  public function hasViewAccess(GroupInterface $entity, AccountInterface $account, GroupVisibilityRecordInterface $group_visibility_record) {
    $conf_key = $this->getPluginId() . '_conf';
    $options = $this->getOptionsForPlugin($group_visibility_record);
    $restricted_organisation_types = array_key_exists($conf_key, $options) ? $options[$conf_key] : '';
    $restricted_organisation_types = array_column($restricted_organisation_types, 'target_id');

    // Search for a matching organisation the user belongs to.
    $user_organisation_types = \Drupal::service('eic_organisations.helper')->getUserOrganisationTypes($account);

    // If the at least one type of the organisation matches the selected
    // types, user has access to this group.
    if (!empty(array_intersect($restricted_organisation_types, $user_organisation_types))) {
      return GroupAccessResult::allowed()
        ->addCacheableDependency($account)
        ->addCacheableDependency($entity);
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
    if (!empty($form_state->getValue($conf_key))) {
      return;
    }
    return $form_state->setError($element[$this->getPluginId() . '_conf'], $this->t('Please select at least 1 organisation type.'));
  }

}
