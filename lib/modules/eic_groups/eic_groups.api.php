<?php

/**
 * @file
 * Hooks provided by the EIC Groups module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Returns implementations for 'group_metrics' views field.
 *
 * This hook is invoked from \Drupal\eic_groups\Plugin\views\field\GroupMetrics
 * views field plugin.
 *
 * The following properties can/should be defined for each implementation:
 * - label: (required) the name of the metric.
 * - value_callback: (required) the callable function to use to get the metric
 *   value. It should return an integer or NULL if not applicable. The value
 *   callback will receive following arguments:
 *   - (string) metric_id: the name of the metric being called.
 *   - (GroupInterface) group: the group entity for which we require the metric.
 *   - (array) configuration: the views plugin configuration.
 * - conf_callback: (optional) the callable function to use to get the
 *   configuration for this metric.
 * - options: (optional) the options used by the configuration. This is required
 *   if conf_callback expects form elements submitted values. I should contain
 *   following structure:
 *   - element_name: the element name as key. 'default' is a reserved value and
 *     cannot be used here.
 *     - default_value: the default_value to set for this form element.
 *
 * @code
 * function my_module_get_group_followers(string $metric_id, GroupInterface $group, array $configuration) {
 *   if ($group->canBeFollowed) {
 *     return 123;
 *   }
 *   return NULL;
 * }
 *
 * function my_module_get_group_followers_configuration(string $metric_id, array $configuration) {
 *   $roles = ['authenticated'];
 *   $conf = [
 *     'roles' => [
 *     '#title' => $this->t('Select the role(s) to filter on'),
 *     '#description' => $this->t('If none selected, all roles will be returned.'),
 *     '#type' => 'checkboxes',
 *     '#options' => $roles,
 *     '#default_value' => $configuration[$metric_id . '_conf']['roles'] ?? [],
 *     ],
 *   ];
 *   return $conf;
 * }
 * @endcode
 *
 * @return array
 *   An array containing all the implementations.
 */
function hook_eic_groups_metrics_info() {
  return [
    'group_followers' => [
      'label' => t('Group followers'),
      'value_callback' => 'my_module_get_group_followers',
      'conf_callback' => 'my_module_get_group_followers_configuration',
      'options' => [
        'roles' => [
          'default_value' => [],
        ],
      ],
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
