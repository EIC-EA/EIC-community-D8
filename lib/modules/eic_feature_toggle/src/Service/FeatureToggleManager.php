<?php

declare(strict_types=1);

namespace Drupal\eic_feature_toggle\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service for managing EIC feature toggles.
 */
class FeatureToggleManager {

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a new FeatureToggleManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Gets the configuration object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration object.
   */
  protected function getConfig() {
    return $this->configFactory->get('eic_feature_toggle.settings');
  }

  /**
   * Checks if a cron operation is enabled.
   *
   * @param string $key
   *   The cron toggle key.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function isCronEnabled(string $key): bool {
    $value = $this->getConfig()->get('cron.' . $key);
    // Default to TRUE if not set (backward compatibility).
    return $value ?? TRUE;
  }

  /**
   * Checks if a queue worker is enabled.
   *
   * @param string $key
   *   The queue toggle key.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function isQueueEnabled(string $key): bool {
    $value = $this->getConfig()->get('queue.' . $key);
    // Default to TRUE if not set (backward compatibility).
    return $value ?? TRUE;
  }

  /**
   * Checks if an external service is enabled.
   *
   * @param string $key
   *   The external service toggle key.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function isExternalServiceEnabled(string $key): bool {
    $value = $this->getConfig()->get('external.' . $key);
    // Default to TRUE if not set (backward compatibility).
    return $value ?? TRUE;
  }

  /**
   * Gets all toggle settings.
   *
   * @return array
   *   Array with 'cron', 'queue', and 'external' keys.
   */
  public function getAllToggles(): array {
    $config = $this->getConfig();
    return [
      'cron' => $config->get('cron') ?? [],
      'queue' => $config->get('queue') ?? [],
      'external' => $config->get('external') ?? [],
    ];
  }

}
