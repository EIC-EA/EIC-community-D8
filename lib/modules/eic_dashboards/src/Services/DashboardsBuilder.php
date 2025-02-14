<?php

namespace Drupal\eic_dashboards\Services;

/**
 * Implements dashboards helpers.
 */
class DashboardsBuilder implements DashboardsBuilderInterface {


  /**
   * {@inheritdoc}
   */
  public function ctaCard($title, $link, $icon, $variant): array {
    $build = [];
    $build['cta_card'] = [
      '#theme' => 'cta_card',
      '#title' => $title,
      '#link' => $link,
      '#icon' => $icon,
      '#variant' => $variant,
    ];

    $build['#attached']['library'][] = 'eic_dashboards/cta_card';

    return $build;
  }
}

