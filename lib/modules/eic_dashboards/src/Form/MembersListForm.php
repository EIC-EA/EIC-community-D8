<?php

namespace Drupal\eic_dashboards\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_dashboards\Services\CommunityStatisticsInterface;
use Drupal\eic_dashboards\Services\DashboardsBuilderInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the members list page.
 */
class MembersListForm extends FormBase {

  use StringTranslationTrait;

  /**
   * The current group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The entities statistics service.
   *
   * @var \Drupal\eic_dashboards\Services\CommunityStatisticsInterface
   */
  protected CommunityStatisticsInterface $communityStatistics;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * The dashboard builder service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardsBuilderInterface
   */
  protected DashboardsBuilderInterface $dashboardBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    CommunityStatisticsInterface $community_statistics,
    DateFormatterInterface $date_formatter,
    DashboardsBuilderInterface $dashboardBuilder,
  ) {
    $this->communityStatistics = $community_statistics;
    $this->dateFormatter = $date_formatter;
    $this->dashboardBuilder = $dashboardBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_dashboards.community_statistics'),
      $container->get('date.formatter'),
      $container->get('eic_dashboards.builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commnunity_activity_report_form';
  }

  /**
   * Gets preset date ranges.
   */
  protected function getPresetDateRanges(): array {
    $current_year = $this->dateFormatter->format(strtotime('now'), 'custom', 'Y');
    $current_quarter = ceil($this->dateFormatter->format(strtotime('now'), 'custom', 'n') / 3);

    $ranges = [];

    // Last 30 days.
    $ranges['last_30_days'] = [
      'label' => $this->t('Last 30 days'),
      'from' => $this->dateFormatter->format(strtotime('-30 days'), 'custom', 'Y-m-d'),
      'to' => $this->dateFormatter->format(strtotime('now'), 'custom', 'Y-m-d'),
    ];

    // Current quarter.
    $current_quarter_start = new \DateTime(sprintf('%d-%d-1', $current_year, (($current_quarter - 1) * 3) + 1));
    $current_quarter_end = clone $current_quarter_start;
    $current_quarter_end->modify('+2 month')->modify('last day of this month');

    $ranges['current_quarter'] = [
      'label' => $this->t('Current quarter'),
      'from' => $current_quarter_start->format('Y-m-d'),
      'to' => $current_quarter_end->format('Y-m-d'),
    ];

    // Previous quarter.
    $prev_quarter_start = clone $current_quarter_start;
    $prev_quarter_start->modify('-3 month');
    $prev_quarter_end = clone $prev_quarter_start;
    $prev_quarter_end->modify('+2 month')->modify('last day of this month');

    $ranges['previous_quarter'] = [
      'label' => $this->t('Previous quarter'),
      'from' => $prev_quarter_start->format('Y-m-d'),
      'to' => $prev_quarter_end->format('Y-m-d'),
    ];

    // Current year.
    $ranges['current_year'] = [
      'label' => $this->t('Current year'),
      'from' => "$current_year-01-01",
      'to' => "$current_year-12-31",
    ];

    // Previous year.
    $prev_year = $current_year - 1;
    $ranges['previous_year'] = [
      'label' => $this->t('Previous year'),
      'from' => "$prev_year-01-01",
      'to' => "$prev_year-12-31",
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
      $url = Url::fromRoute('entity.group.dashboard.activity_report', [
        'group' => $this->group->id(),
      ]);
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
  public function buildForm(array $form, FormStateInterface $form_state, ?GroupInterface $group = NULL) {
    $this->group = $group;

    $form['date_range']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => 'Reporting period',
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
    $from_default = $this->getRequest()->query->get('from') ?: $this->dateFormatter->format(strtotime('-30 days'), 'custom', 'Y-m-d');
    $to_default = $this->getRequest()->query->get('to') ?: $this->dateFormatter->format(strtotime('now'), 'custom', 'Y-m-d');

    $form['date_range']['container']['from'] = [
      '#type' => 'date',
      '#title' => $this->t('From'),
      '#default_value' => $form_state->getValue('from', $from_default),
      '#required' => TRUE,
    ];

    $form['date_range']['container']['to'] = [
      '#type' => 'date',
      '#title' => $this->t('To'),
      '#default_value' => $form_state->getValue('to', $to_default),
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
      $start_date = $form_state->getValue('from', $from_default);
      $end_date = $form_state->getValue('to', $to_default);

      // Community members that joined in given period of time.
      $members = $this->communityStatistics->getCommunityMembersJoinedInGivenPeriod($group, $start_date, $end_date);
      $members_items = [];
      foreach ($members as $member) {
        $members_items[] = [
          'date' => $member['date'],
          'url' => $member['user']->toUrl()->toString(),
          'value' => $member['user']->getDisplayName(),
        ];
      }
      $members_joined = $this->dashboardBuilder->reportList('New members', '', $members_items);

      // Community discussions created in given period of time.
      $discussions = $this->communityStatistics->getCommunityContentOfBundleInGivenPeriod('discussion', $group, $start_date, $end_date);
      $discussions_items = [];
      foreach ($discussions as $discussion) {
        $discussions_items[] = [
          'date' => $discussion['date'],
          'url' => $discussion['content']->toUrl()->toString(),
          'value' => $discussion['content']->label(),
        ];
      }
      $discussions_created = $this->dashboardBuilder->reportList('New discussions', '', $discussions_items);

      // Community news created in given period of time.
      $news = $this->communityStatistics->getCommunityContentOfBundleInGivenPeriod('oe_news', $group, $start_date, $end_date);
      $news_items = [];
      foreach ($news as $news_item) {
        $news_items[] = [
          'date' => $news_item['date'],
          'url' => $news_item['content']->toUrl()->toString(),
          'value' => $news_item['content']->label(),
        ];
      }
      $news_created = $this->dashboardBuilder->reportList('New news', '', $news_items);

      // Community events created in given period of time.
      $events = $this->communityStatistics->getCommunityContentOfBundleInGivenPeriod('oe_event', $group, $start_date, $end_date);
      $events_items = [];
      foreach ($events as $event) {
        $events_items[] = [
          'date' => $event['date'],
          'url' => $event['content']->toUrl()->toString(),
          'value' => $event['content']->label(),
        ];
      }
      $events_created = $this->dashboardBuilder->reportList('New events', '', $events_items);

      // Community resources created in given period of time.
      $resources = $this->communityStatistics->getCommunityContentOfBundleInGivenPeriod('resource', $group, $start_date, $end_date);
      $resources_items = [];
      foreach ($resources as $resource) {
        $resources_items[] = [
          'date' => $resource['date'],
          'url' => $resource['content']->toUrl()->toString(),
          'value' => $resource['content']->label(),
        ];
      }
      $resources_created = $this->dashboardBuilder->reportList('New resources', '', $resources_items);

      $build = [
        'content' => [
          $members_joined,
          $discussions_created,
          $news_created,
          $events_created,
          $resources_created,
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
    $from_date = $form_state->getValue('from');
    $to_date = $form_state->getValue('to');

    if (strtotime($from_date) > strtotime($to_date)) {
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
