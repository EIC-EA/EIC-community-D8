<?php

namespace Drupal\eic_media_statistics\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure statistics settings for this site.
 *
 * @internal
 */
class StatisticsSettingsForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\statistics\StatisticsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eic_media_statistics_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['eic_media_statistics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('eic_media_statistics.settings');

    // Content counter settings.
    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('File downloading counter settings'),
      '#open' => TRUE,
    ];
    $form['content']['statistics_count_file_downloads'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Count file downloadss'),
      '#default_value' => $config->get('count_file_downloads'),
      '#description' => $this->t('Increment a counter each time a file is downloaded.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('eic_media_statistics.settings')
      ->set('count_file_downloads', $form_state->getValue('statistics_count_file_downloads'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
