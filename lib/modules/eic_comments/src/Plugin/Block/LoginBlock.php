<?php

namespace Drupal\eic_comments\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
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
    $form['login_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Log in button text'),
      '#description' => $this->t('Label for the Login button'),
      '#default_value' => isset($config['login_text']) ? $config['login_text'] : '',
    ];
    $form['register_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registration button text'),
      '#description' => $this->t('Label for the Registration button'),
      '#default_value' => isset($config['register_text']) ? $config['register_text'] : '',
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

    $title = $this->t('Please log in to see comment and contribute');
    if (!empty($config['headline'])) {
      $title = $config['headline'];
    }

    $login_text = $this->t('Log in');
    if (!empty($config['$login_text'])) {
      $login_text = $config['$login_text'];
    }

    $register_text = $this->t('Register');
    if (!empty($config['$register_text'])) {
      $register_text = $config['$register_text'];
    }

    $register_route = Url::fromRoute('user.register');
    $registration_link = Link::fromTextAndUrl($register_text, $register_route);

    $login_route = Url::fromRoute('user.login');
    $login_link = Link::fromTextAndUrl($login_text, $login_route);

    $build['title'] = $this->t($title);

    $build['login_link'] = $login_link;
    $build['registration_link'] = $registration_link;

    return $build;
  }

}
