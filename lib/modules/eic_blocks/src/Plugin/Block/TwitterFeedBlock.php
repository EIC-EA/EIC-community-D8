<?php

namespace Drupal\eic_blocks\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "eic_social_feed",
 *   admin_label = @Translation("EIC Twitter feed"),
 *   category = @Translation("European Innovation Council")
 * )
 */
class TwitterFeedBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'service' => 'smk',
      'type' => 'user',
      'display_user' => TRUE,
      'display_user_pic' => TRUE,
      'auto_expand_photo' => FALSE,
      'auto_expand_video' => FALSE,
      'target' => FALSE,
      'screen_name' => 'EUeic',
      'count' => 3,
      'include_rts' => FALSE,
      'rts_display_original' => FALSE,
      'exclude_replies' => TRUE,
      'tweet_more_btn' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of tweets'),
      '#min' => 0,
      '#step' => 3,
      '#default_value' => $this->configuration['count'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['count'] = $form_state->getValue('count');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // We use webtools to render the twitter feed based
    // on the block configurations.
    $build['content'] = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => Markup::create(Json::encode($this->configuration)),
      '#attributes' => ['type' => 'application/json'],
      '#attached' => [
        'library' => ['oe_webtools/drupal.webtools-smartloader'],
      ],
    ];
    return $build;
  }

}
