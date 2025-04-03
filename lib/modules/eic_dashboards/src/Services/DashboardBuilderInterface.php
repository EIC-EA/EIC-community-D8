<?php

namespace Drupal\eic_dashboards\Services;

/**
 * Defines dashboards utility interface.
 */
interface DashboardBuilderInterface {

  /**
   * Build dashboard CTA card.
   */
  public function ctaCard($title, $link, $icon, $variant);

  /**
   * Build button that points to a View with an argument.
   */
  public function buttonToView($viewMachineName, $argumentId, $argumentValue, $buttonText);

  /**
   * Build dashboard title, number and link.
   */
  public function numberAndLink($title, $number, $link);

  /**
   * Build dashboard columns.
   */
  public function columns($items, $size);

  /**
   * Build button that points to a Route with a parameter.
   */
  public function buttonToRoute($buttonText, $route, $parameterId, $parameterValue);

}
