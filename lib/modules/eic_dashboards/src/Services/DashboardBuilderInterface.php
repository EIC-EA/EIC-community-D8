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

  /**
   * Build dashboard report list.
   */
  public function reportList($title, $link, $items);

  /**
   * Build dashboard pie chart.
   */
  public function chartPie($title, $data, $size);

  /**
   * Build dashboard jump menu.
   */
  public function chartWithMenu($chart, $menu);

  /**
   * Build dashboard column chart.
   */
  public function chartColumn($title, $data, $vertical);

  /**
   * Build dashboard jump menu.
   */
  public function jumpMenu($title, $placeholder, $links);

  /**
   * Build dashboard list with title and link.
   */
  public function titleLinkList($title, $link, $list);

  /**
   * Build dashboard table.
   */
  public function table($header, $rows, $class);

  /**
   * Build dashboard tabs.
   */
  public function tabs($title, $items);

  /**
   * Build dashboard section.
   */
  public function dashboardSection($title, $link, $content, $icon, $hasBorder);

}
