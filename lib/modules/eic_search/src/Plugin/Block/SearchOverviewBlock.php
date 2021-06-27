<?php

namespace Drupal\eic_search\Plugin\Block;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\eic_search\Search\Sources\GroupSourceType;
use Drupal\eic_search\Search\Sources\SourceTypeInterface;
use Drupal\eic_search\Search\Sources\UserSourceType;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides an SearchOverviewBlock block.
 *
 * @Block(
 *   id = "eic_search_overview",
 *   admin_label = @Translation("EIC Search Overview"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class SearchOverviewBlock extends BlockBase {

  const AVAILABLE_TYPES = ['group', 'story', 'event', 'organisation', 'global'];

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\eic_search\Search\Sources\SourceTypeInterface $current_source */
    $current_source_value = $this->configuration['source_type'] ?: GroupSourceType::class;
    $current_source = new $current_source_value;

    $form['search'] = [
      '#type' => 'details',
      '#title' => $this->t('Search overview', [], ['context' => 'eic_search']),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $form['search']['source_type'] = [
      '#type' => 'select',
      '#default_value' => $current_source_value,
      '#title' => $this->t('Source type', [], ['context' => 'eic_search']),
      '#description' => $this->t('For which entity type do you want to create the view for ?', [], ['context' => 'eic_search']),
      '#options' => [
        GroupSourceType::class => GroupSourceType::getLabel(),
        UserSourceType::class => UserSourceType::getLabel(),
      ],
      '#ajax' => [
        'callback' => [$this, 'updateSourceConfig'],
        'disable-refocus' => FALSE,
        // Or TRUE to prevent re-focusing on the triggering element.
        'event' => 'change',
        'wrapper' => 'source-configuration',
        // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading new configuration ...', [], ['context' => 'eic_search']),
        ],
      ],
    ];

    $this->addConfigurationRenderer($form, $current_source);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'search_overview_block',
      '#url' => Url::fromRoute('eic_groups.solr_search')->toString(),
      '#isAnonymous' => \Drupal::currentUser()->isAnonymous(),
      '#translations' => [
        'public' => $this->t('Public', [], ['context' => 'eic_group']),
        'private' => $this->t('Private', [], ['context' => 'eic_group']),
        'filter' => $this->t('Filter', [], ['context' => 'eic_group']),
        'topics' => $this->t('Topics', [], ['context' => 'eic_group']),
        'search_text' => $this->t('Search for a group', [], ['context' => 'eic_group']),
        'no_results' => $this->t('No results', [], ['context' => 'eic_group']),
        'members' => $this->t('Members', [], ['context' => 'eic_group']),
        'reactions' => $this->t('Reactions', [], ['context' => 'eic_group']),
        'documents' => $this->t('Documents', [], ['context' => 'eic_group']),
      ],
    ];
  }

  public function updateSourceConfig(array &$form, FormStateInterface $form_state) {
    $current_source_value = $form_state->getValue('settings')['search']['source_type'] ?: GroupSourceType::class;
    $current_source = new $current_source_value;
    $response = new AjaxResponse();

    $this->addConfigurationRenderer($form, $current_source);

    $response->addCommand(new ReplaceCommand('#source-configuration-sort', $form['search']['configuration']['sort_by']));
    $response->addCommand(new ReplaceCommand('#source-configuration-facets', $form['search']['configuration']['facets']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    $this->configuration['source_type'] = $values['search']['source_type'];
    $this->configuration['facets'] = $values['search']['configuration']['facets'];
    $this->configuration['sort_options'] = $values['search']['configuration']['sort_options'];
  }

  /**
   * @param array $form
   * @param SourceTypeInterface
   */
  private function addConfigurationRenderer(array &$form, SourceTypeInterface $current_source) {
    $form['search']['configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration', [], ['context' => 'eic_search']),
      '#open' => TRUE,
      '#weight' => 4,
      '#prefix' => '<div id="source-configuration">',
      '#suffix' => '</div>',
    ];

    $form['search']['configuration']['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Facets', [], ['context' => 'eic_search']),
      '#open' => TRUE,
    ];

    $form['search']['configuration']['filter']['facets'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Facets', [], ['context' => 'eic_search']),
      '#description' => $this->t('Filters that you want to be available on the overview', [], ['context' => 'eic_search']),
      '#default_value' => $this->configuration['facets'],
      '#options' => $this->generateFacetsOptions($current_source),
      '#prefix' => '<div id="source-configuration-facets">',
      '#suffix' => '</div>',
    ];

    $form['search']['configuration']['sorts'] = [
      '#type' => 'details',
      '#title' => $this->t('Sorts', [], ['context' => 'eic_search']),
      '#open' => TRUE,
    ];

    $form['search']['configuration']['sorts']['sort_options'] = [
      '#type' => 'checkboxes',
      '#default_value' => $this->configuration['sort_options'],
      '#title' => $this->t('Sorting', [], ['context' => 'eic_search']),
      '#description' => $this->t('Choose available sorting options on the overview', [], ['context' => 'eic_search']),
      '#options' => $this->generateSortOptions($current_source),
      '#prefix' => '<div id="source-configuration-sort">',
      '#suffix' => '</div>',
    ];

    $form['search']['configuration']['pagination'] = [
      '#type' => 'details',
      '#title' => $this->t('Pagination', [], ['context' => 'eic_search']),
      '#open' => TRUE,
    ];

    $form['search']['configuration']['pagination']['results_per_page'] = [
      '#type' => 'number',
      '#default_value' => $this->configuration['results_per_page'] ?: 15,
      '#title' => $this->t('Results per page', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @param \Drupal\eic_search\Search\Sources\SourceTypeInterface $current_source
   *
   * @return array
   */
  private function generateFacetsOptions(SourceTypeInterface $current_source): array {
    $available_facets = [];

    foreach ($current_source::getAvailableFacets() as $facet) {
      $available_facets[$facet] = t($facet, [], ['context' => 'eic_search']);
    }

    return $available_facets;
  }

  /**
   * @param \Drupal\eic_search\Search\Sources\SourceTypeInterface $current_source
   *
   * @return array
   */
  private function generateSortOptions(SourceTypeInterface $current_source): array {
    $available_sorts = [];

    foreach ($current_source::getAvailableSortOptions() as $sort_option) {
      $available_sorts[$sort_option] = t($sort_option, [], ['context' => 'eic_search']);
    }

    return $available_sorts;
  }

}
