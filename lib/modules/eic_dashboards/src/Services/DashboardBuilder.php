<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * @file
 * Contains the DashboardBuilder service for building dashboard UI components.
 *
 * This class provides reusable render arrays and helpers for generating
 * common dashboard elements such as cards, charts, tables, and buttons,
 * based on custom theme implementations in the eic_dashboards module.
 */
class DashboardBuilder implements DashboardBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function ctaCard($title, $link, $icon, $variant): array {
    $path = '/' . \Drupal::service('extension.path.resolver')->getPath('module', 'eic_dashboards') . '/images/';
    $build = [];
    $build['cta_card'] = [
      '#theme' => 'cta_card',
      '#title' => $title,
      '#link' => $link,
      '#icon' => $icon,
      '#variant' => $variant,
      '#path' => $path,
    ];
    $build['#attached']['library'][] = 'eic_dashboards/cta_card';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buttonToView($viewMachineName, $argumentId, $argumentValue, $buttonText): Link {
    $options = [
      'query' => [
        $argumentId => $argumentValue,
      ],
      'attributes' => [
        'class' => [
          'ecl-button',
          'ecl-button--primary',
        ],
      ],
    ];

    $url = Url::fromRoute($viewMachineName, [], $options);

    return Link::fromTextAndUrl($buttonText, $url);
  }

  /**
   * {@inheritdoc}
   */
  public function numberAndLink($title, $number, $link): array {
    return [
      '#theme' => 'number_and_link',
      '#title' => $title,
      '#number' => $number,
      '#link' => $link,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function columns($items, $size): array {
    return [
      '#theme' => 'columns',
      '#items' => $items,
      '#size' => $size,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buttonToRoute($buttonText, $route, $parameterId, $parameterValue): Link {
    $parameters = [
      $parameterId => $parameterValue,
    ];

    $options = [
      'attributes' => [
        'class' => [
          'ecl-button',
          'ecl-button--primary',
        ],
      ],
    ];

    $url = Url::fromRoute($route, $parameters, $options);

    return Link::fromTextAndUrl($buttonText, $url);
  }

  /**
   * {@inheritdoc}
   */
  public function reportList($title, $link, $items): array {
    $build = [];
    $build['report_list'] = [
      '#theme' => 'report_list',
      '#title' => $title,
      '#link' => $link,
      '#items' => $items,
    ];

    $build['#attached']['library'][] = 'eic_dashboards/report-list';

    return $build;
  }

    /**
     * {@inheritdoc}
     */
  public function chartPie($title, $data, $size): array {
      return [
          '#theme' => 'chart_pie',
          '#title' => $title,
          '#data' => $data,
          '#size' => $size,
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function chartWithMenu($chart, $menu): array {
      return [
          '#theme' => 'chart_with_menu',
          '#chart' => $chart,
          '#menu' => $menu,
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function chartColumn($title, $data, $vertical): array {
    return [
      '#theme' => 'chart_column',
      '#title' => $title,
      '#categories' => $data['categories'],
      '#series' => $data['series'],
      '#vertical' => $vertical,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function jumpMenu($title, $placeholder, $links): array {
      if (!empty($links)) {
          return [
              '#theme' => 'jump_menu',
              '#wrapper_attributes' => [
                  'class' => [
                      'jump-menu',
                  ],
              ],
              '#title' => $title,
              '#title_attributes' => [
                  'class' => [
                      'jump-menu__label',
                  ],
              ],
              '#placeholder' => $placeholder,
              '#items' => $links,
              '#attributes' => [
                  'class' => [
                      'ecl-select',
                      'form-select',
                      'jump-menu__select',
                      'js-jump-menu',
                  ],
              ],
          ];
      }
      else {
          return [];
      }
  }

  /**
   * {@inheritdoc}
   */
  public function titleLinkList($title, $link, $list): array {
    return [
      '#theme' => 'title_link_list',
      '#title' => $title,
      '#link' => $link,
      '#list' => $list,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function table($header, $rows, $class): array {
    $build = [];

    $build['table'] = [
      '#type' => 'table',
      '#theme' => 'table__with_fields',
      '#header' => $header,
      '#rows'   => $rows,
      '#attributes' => [
        'class' => [
          'tablesorter',
          $class,
        ]
      ],
    ];

    $build['#attached']['library'][] = 'eic_dashboards/tablesorter';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function tabs($title, $items): array {
    return [
      '#theme' => 'tabs',
      '#title' => $title,
      '#items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function dashboardSection($title, $link, $content, $icon, $hasBorder = TRUE): array {
    $path = '/' . \Drupal::service('extension.path.resolver')->getPath('module', 'eic_dashboards') . '/images/';

    return [
      '#theme' => 'dashboard_section',
      '#title' => $title,
      '#link' => $link,
      '#content' => $content,
      '#icon' => $icon,
      '#path' => $path,
      '#has_border' => $hasBorder,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function chartLine($title, $data): array {
    return [
      '#theme' => 'chart_line',
      '#title' => $title,
      '#categories' => $data['categories'],
      '#series' => $data['series'],
    ];
  }

}
