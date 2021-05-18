<?php

namespace Drupal\eic_comments\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides an Login block.
 *
 * @Block(
 *   id = "login_block",
 *   admin_label = @Translation("EIC Comment Login Block"),
 *   category = @Translation("Custom")
 * )
 */
class LoginBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $form['headline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Headline'),
      '#description' => $this->t('What would you like to have as headline?'),
      '#default_value' => isset($config['headline']) ? $config['headline'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('headline', $form_state->getValue('headline'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $headline = $this->t('Please log in to see comment and contribute');
    if (!empty($config['headline'])) {
      $headline = $config['headline'];
    }

    $register_route = Url::fromRoute('user.register');
    $registration_link = Link::fromTextAndUrl('Register', $register_route);


    $login_route = Url::fromRoute('user.login');
    $login_link = Link::fromTextAndUrl('Log in', $login_route);

    return [
      ['#markup' => $this->t($headline)],
      [$login_link->toRenderable()],
      [$registration_link->toRenderable()],
    ];
  }

}
