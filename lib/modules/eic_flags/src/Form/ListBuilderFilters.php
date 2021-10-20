<?php

namespace Drupal\eic_flags\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\eic_flags\FlaggedEntitiesListBuilder;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\HandlerInterface;

/**
 * Class ListBuilderFilters.
 *
 * @package Drupal\eic_flags\Form
 */
class ListBuilderFilters extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'list_builder_filters';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?HandlerInterface $handler = NULL
  ) {
    $form_state->setMethod('get');

    // Currently this is fixed however this form can be used dynamically.
    // If needed change it and give to the form object fields it should render
    // You'll then have them within your list builder as query strings
    $form['#action'] = Url::fromRoute('<current>')->toString();
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form--inline'],
      ],
    ];
    $form['container']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#size' => 30,
    ];

    $form['container']['requester'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Requester'),
      '#target_type' => 'user',
      '#size' => 30,
    ];

    $form['container']['author'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Author'),
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#selection_handler' => 'default',
      '#validate_reference' => FALSE,
      '#size' => 30,
    ];

    if ($handler instanceof HandlerInterface) {
      $entities = array_keys($handler->getSupportedEntityTypes());

      $form['container']['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#options' => ['All' => '- Any -'] + array_combine(
            $entities,
            array_map('ucfirst', $entities)
          ),
      ];
    }

    $form['container']['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['form-actions'],
      ],
    ];

    $form['container']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    $views_type_option = $handler->getType() === RequestTypes::ARCHIVE
      ? FlaggedEntitiesListBuilder::VIEW_ARCHIVE_FLAG_ID
      : FlaggedEntitiesListBuilder::VIEW_DELETE_FLAG_ID;

    $form['closed_requests_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['tabledrag-toggle-weight-wrapper'],
      ],
    ];

    $form['closed_requests_container']['closed_requests'] = [
      '#type' => 'link',
      '#title' => $this->t('See handled requests'),
      '#url' => Url::fromRoute(
        FlaggedEntitiesListBuilder::CLOSED_REQUEST_VIEW,
        [
          'flag_id' => $views_type_option,
        ]
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form submits to the flagged entities list, so processing happens there.
  }

}
