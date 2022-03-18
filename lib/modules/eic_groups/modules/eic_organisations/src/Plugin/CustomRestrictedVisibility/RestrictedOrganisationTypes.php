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

    // Get the terms.
    // @todo Use a constant for the vocabulary name.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree(Organisations::VOCABULARY_ORGANISATION_TYPE);
    $options = [];
    foreach ($terms as $term_item) {
      $options[$term_item->tid] = $term_item->name;
    }

    // @todo Make use of a hierarchical widget.
    $options = [
      'match_top_level_limit' => 0,
      'items_to_load' => 0,
      'auto_select_parents' => 0,
      'disable_top_choices' => 0,
      'load_all' => 1,
      'ignore_current_user' => 0,
      'target_bundles' => ['organisation_types'],
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

      // Set the default values.
      $pluginForm[$conf_key]['#default_value'] = $this->getEnabledOptions($options[$conf_key]);
    }
    return $pluginForm;
  }

  /**
   * {@inheritdoc}
   */
  public function hasViewAccess(GroupInterface $entity, AccountInterface $account, GroupVisibilityRecordInterface $group_visibility_record) {
    $conf_key = $this->getPluginId() . '_conf';
    $options = $this->getOptionsForPlugin($group_visibility_record);
    $restricted_organisation_types = $this->getEnabledOptions($options[$conf_key]);

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
    if (!empty($this->getEnabledOptions($form_state->getValue($conf_key)))) {
      return;
    }
    return $form_state->setError($element[$this->getPluginId() . '_conf'], $this->t('Please select at least 1 organisation type.'));
  }

  /**
   * Returns the enabled options as an array of term IDs.
   *
   * @param array $value
   *   The array as provided by the 'checkboxes' form element.
   *
   * @return array
   *   Array of term IDs.
   */
  protected function getEnabledOptions(array $value) {
    $result = [];

    foreach ($value as $term_id => $status) {
      // Status can be either 'O' for disabled options, and match the term ID
      // for enabled options.
      if ($term_id == $status) {
        $result[] = $term_id;
      }
    }

    return $result;
  }

}
