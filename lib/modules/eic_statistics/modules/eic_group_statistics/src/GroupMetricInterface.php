<?php

namespace Drupal\eic_group_statistics;

use Drupal\group\Entity\GroupInterface;

/**
 * Interface for group_metric plugins.
 */
interface GroupMetricInterface {

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID.
   */
  public function id(): string;

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * Returns the config definition.
   *
   * @return array
   *   An array defining the available options as a key and possibl default
   *   value:
   *   - config_name: the name of the config
   *     - default_value: (optional) the default value to apply.
   */
  public function getConfigDefinition(): array;

  /**
   * Returns the configuration form elements.
   *
   * @param array $values
   *   The current selected values.
   *
   * @return array
   *   An array of form elements to be used.
   */
  public function getConfig(array $values = []): array;

  /**
   * Returns the metric counter.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param array $configuration
   *   The current configuration.
   *
   * @return int|null
   *   The counter for the given group and configuration.
   */
  public function getValue(GroupInterface $group, array $configuration = []);

}
