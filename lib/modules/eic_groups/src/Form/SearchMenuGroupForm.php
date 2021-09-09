<?php

namespace Drupal\eic_groups\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide a small search form next to the group menu
 *
 * @package Drupal\eic_groups\Form
 */
class SearchMenuGroupForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eic_groups_search_menu_group';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['search'] = [
      '#type' => 'textfield',
      '#size' => 0,
      '#theme_wrappers' => [],
      '#attributes' => [
        'placeholder' => $this->t('Search', [], ['context' => 'eic_groups']),
        'class' => [
          'ecl-text-input',
          'ecl-text-input--m',
          'ecl-search-form__text-input',
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#theme_wrappers' => [
        'eic_group_search_menu_field_search_submit'
      ],
      '#attributes' => [
        'class' => [
          'ecl-button',
          'ecl-button--search',
          'ecl-search-form__button',
        ],
      ],
    ];

    $form['#theme'] = 'eic_group_search_menu_block_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('eic_overviews.groups.overview_page.search', [
        'group' => \Drupal::service('eic_groups.helper')->getGroupFromRoute()->id(),
        'search' => $form_state->getValue('search'),
      ]
    );
  }

}
