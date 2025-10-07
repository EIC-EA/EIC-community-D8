<?php

namespace Drupal\eic_user\Plugin\Condition;

use Drupal\Core\Condition\Attribute\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'User Group Sensitive' condition.
 *
 * @see https://github.com/openeuropa/oe_authentication/issues/234
 *   why we need a configuration form.
 */
#[Condition(
  id: "user_group_sensitive",
  label: new TranslatableMarkup("User Group Sensitive"),
  context_definitions: [
    "user" => new EntityContextDefinition(
      data_type: "entity:user",
      label: new TranslatableMarkup("User"),
    ),
  ],
)]
class UserGroupSensitive extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Random checkbox'),
      '#default_value' => $this->configuration['enable'],
      '#description' => $this->t('This is a random field as oe_authentication requires conditions to have a config form. The value of this field does nothing.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'enable' => [],
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['enable'] = $form_state->getValue('enable');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['negate'])) {
      return $this->t('The user is not a member of a sensitive group');
    }
    else {
      return $this->t('The user is a member of a sensitive group');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->getContextValue('user');

    /** @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorage $group_visibility_storage */
    $group_visibility_storage = \Drupal::service('oec_group_flex.group_visibility.storage');
    if ($sensitive_group_ids = $group_visibility_storage->loadByType('sensitive')) {
      $dbqry = \Drupal::database()->select('group_content_field_data', 'gcfd')
        ->condition('gcfd.type', '%-group_membership', 'LIKE')
        ->condition('gcfd.gid', $sensitive_group_ids, 'IN')
        ->condition('gcfd.entity_id', $user->id());
      $dbqry->addField('gcfd', 'id');
      $results = $dbqry->execute()->fetchAll();

      return !empty($results);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
//  public function getCacheContexts() {
//    // Optimize cache context, if a user cache context is provided, only use
//    // user.roles, since that's the only part this condition cares about.
//    $contexts = [];
//    foreach (parent::getCacheContexts() as $context) {
//      $contexts[] = $context == 'user' ? 'user.roles' : $context;
//    }
//    return $contexts;
//  }

}
