<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Implements dashboards helpers.
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

}
