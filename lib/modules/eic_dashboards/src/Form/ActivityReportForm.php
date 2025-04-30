<?php

namespace Drupal\eic_dashboards\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the activity report page.
 */
class ActivityReportForm extends FormBase {

  /**
   * The current group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * The dashboard builder service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardBuilderinterface
   */
  protected DashboardBuilderInterface $dashboardBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    DateFormatterInterface $dateFormatter,
    DashboardBuilderInterface $dashboardBuilder,
  ) {
    $this->dateFormatter = $dateFormatter;
    $this->dashboardBuilder = $dashboardBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('eic_dashboards.builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'activity_report_form';
  }

  /**
   * Gets preset date ranges.
   */
  protected function getPresetDateRanges(): array {
    $currentYear = $this->dateFormatter->format(strtotime('now'), 'custom', 'Y');
    $currentQuarter = ceil($this->dateFormatter->format(strtotime('now'), 'custom', 'n') / 3);

    $ranges = [];

    // Last 30 days.
    $ranges['last_30_days'] = [
      'label' => $this->t('Last 30 days'),
      'from' => $this->dateFormatter->format(strtotime('-30 days'), 'custom', 'Y-m-d'),
      'to' => $this->dateFormatter->format(strtotime('now'), 'custom', 'Y-m-d'),
    ];

    // Current quarter.
    $currentQuarterStart = new \DateTime(sprintf('%d-%d-1', $currentYear, (($currentQuarter - 1) * 3) + 1));
    $currentQuarterEnd = clone $currentQuarterStart;
    $currentQuarterEnd->modify('+2 month')->modify('last day of this month');

    $ranges['current_quarter'] = [
      'label' => $this->t('Current quarter'),
      'from' => $currentQuarterStart->format('Y-m-d'),
      'to' => $currentQuarterEnd->format('Y-m-d'),
    ];

    // Previous quarter.
    $prevQuarterStart = clone $currentQuarterStart;
    $prevQuarterStart->modify('-3 month');
    $prevQuarterEnd = clone $prevQuarterStart;
    $prevQuarterEnd->modify('+2 month')->modify('last day of this month');

    $ranges['previous_quarter'] = [
      'label' => $this->t('Previous quarter'),
      'from' => $prevQuarterStart->format('Y-m-d'),
      'to' => $prevQuarterEnd->format('Y-m-d'),
    ];

    // Current year.
    $ranges['current_year'] = [
      'label' => $this->t('Current year'),
      'from' => "$currentYear-01-01",
      'to' => "$currentYear-12-31",
    ];

    // Previous year.
    $prevYear = $currentYear - 1;
    $ranges['previous_year'] = [
      'label' => $this->t('Previous year'),
      'from' => "$prevYear-01-01",
      'to' => "$prevYear-12-31",
    ];

    return $ranges;
  }

  /**
   * Builds the preset date range links.
   */
  protected function buildPresetLinks(): array {
    $ranges = $this->getPresetDateRanges();
    $links = [];

    foreach ($ranges as $key => $range) {
      $url = Url::fromRoute('eic_dashboards.listings.activity_report');

      $url->setOption('query', [
        'from' => $range['from'],
        'to' => $range['to'],
      ]);

      $links[$key] = [
        '#type' => 'link',
        '#title' => $range['label'],
        '#url' => $url,
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $links,
      '#attributes' => [
        'class' => [
          'preset-date-ranges',
        ],
      ],
      '#prefix' => '<div class="preset-date-ranges-wrapper">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['date_range']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Reporting period'),
    ];

    $form['date_range']['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'date-range-form',
        ],
      ],
    ];

    // Get default values from URL parameters if available.
    $fromDefault = $this->getRequest()->query->get('from') ?: $this->dateFormatter->format(strtotime('-30 days'), 'custom', 'Y-m-d');
    $toDefault = $this->getRequest()->query->get('to') ?: $this->dateFormatter->format(strtotime('now'), 'custom', 'Y-m-d');

    $form['date_range']['container']['from'] = [
      '#type' => 'date',
      '#title' => $this->t('From'),
      '#default_value' => $form_state->getValue('from', $fromDefault),
      '#required' => TRUE,
    ];

    $form['date_range']['container']['to'] = [
      '#type' => 'date',
      '#title' => $this->t('To'),
      '#default_value' => $form_state->getValue('to', $toDefault),
      '#required' => TRUE,
    ];

    $form['date_range']['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update report'),
    ];

    // Add preset date range links at the bottom.
    $form['date_range']['preset_ranges'] = $this->buildPresetLinks();
    // Only load and display content if the form has been submitted.
    if ($this->getRequest()->query->has('from') || ($form_state->isSubmitted() && !$form_state->hasAnyErrors())) {
      $startDate = $form_state->getValue('from', $fromDefault);
      $endDate = $form_state->getValue('to', $toDefault);

      $build = [
        'content' => [
        ],
      ];

      $form['content'] = [
        'content' => $build,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fromDate = $form_state->getValue('from');
    $toDate = $form_state->getValue('to');

    if (strtotime($fromDate) > strtotime($toDate)) {
      $form_state->setErrorByName('from', $this->t('"From" date must be before "to" date.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

}
