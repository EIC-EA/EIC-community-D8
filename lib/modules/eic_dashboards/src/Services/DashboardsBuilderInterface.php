<?php

namespace Drupal\eic_dashboards\Services;

/**
 * Defines dashboards utility interface.
 */
interface DashboardsBuilderInterface {

  /**
   * Build dashboard CTA card.
   */
  public function ctaCard($title, $link, $icon, $variant);

}
