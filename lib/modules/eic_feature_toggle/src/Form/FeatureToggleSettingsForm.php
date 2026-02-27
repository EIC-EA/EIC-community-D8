<?php

declare(strict_types=1);

namespace Drupal\eic_feature_toggle\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for EIC feature toggles.
 */
class FeatureToggleSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['eic_feature_toggle.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eic_feature_toggle_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('eic_feature_toggle.settings');

    $form['description'] = [
      '#markup' => '<p>' . $this->t('Enable or disable EIC features. Disabled features will not execute, which can help prevent unwanted side effects in the DDC environment.', [], ['context' => 'eic_feature_toggle']) . '</p>',
    ];

    // Cron operations section.
    $form['cron'] = [
      '#type' => 'details',
      '#title' => $this->t('Cron Operations', [], ['context' => 'eic_feature_toggle']),
      '#open' => TRUE,
    ];

    $cron_toggles = [
      'eic_statistics' => [
        'title' => $this->t('EIC Statistics', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Entity counters, Solr view counter reindexing.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_groups_url_alias' => [
        'title' => $this->t('EIC Groups URL Alias', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('URL alias updates for groups and group content.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_groups_approval_reminder' => [
        'title' => $this->t('EIC Groups Approval Reminder', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Pending group approval notifications.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_groups_invitation_reminder' => [
        'title' => $this->t('EIC Groups Invitation Reminder', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Pending invitation reminders.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_groups_solr_reindex' => [
        'title' => $this->t('EIC Groups Solr Reindex', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Content Solr reindex queue processing.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_flags' => [
        'title' => $this->t('EIC Flags', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Request timeout processing.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_projects_cordis' => [
        'title' => $this->t('EIC Projects CORDIS', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('CORDIS API synchronization.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_events' => [
        'title' => $this->t('EIC Events', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Event synchronization (every 12 hours).', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_subscription_digest' => [
        'title' => $this->t('EIC Subscription Digest', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Digest email sending.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_media_statistics' => [
        'title' => $this->t('EIC Media Statistics', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Daily counter reset.', [], ['context' => 'eic_feature_toggle']),
      ],
    ];

    foreach ($cron_toggles as $key => $toggle) {
      $form['cron'][$key] = [
        '#type' => 'checkbox',
        '#title' => $toggle['title'],
        '#description' => $toggle['description'],
        '#default_value' => $config->get('cron.' . $key) ?? TRUE,
      ];
    }

    // Queue workers section.
    $form['queue'] = [
      '#type' => 'details',
      '#title' => $this->t('Queue Workers', [], ['context' => 'eic_feature_toggle']),
      '#open' => TRUE,
    ];

    $queue_toggles = [
      'eic_message_notify_queue' => [
        'title' => $this->t('Message Notify Queue', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Email notifications queue.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_flags_notify_queue' => [
        'title' => $this->t('Flags Notify Queue', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Flag notifications queue.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_groups_group_content_url_alias_update' => [
        'title' => $this->t('Group Content URL Alias Update Queue', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('URL alias update queue for group content.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_groups_group_content_search_api' => [
        'title' => $this->t('Group Content Search API Queue', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Solr reindex queue for group content.', [], ['context' => 'eic_feature_toggle']),
      ],
      'eic_projects_cordis_extraction_worker' => [
        'title' => $this->t('CORDIS Extraction Worker Queue', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('CORDIS data extraction queue.', [], ['context' => 'eic_feature_toggle']),
      ],
      'subscription_digest' => [
        'title' => $this->t('Subscription Digest Queue', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Digest email sending queue.', [], ['context' => 'eic_feature_toggle']),
      ],
    ];

    foreach ($queue_toggles as $key => $toggle) {
      $form['queue'][$key] = [
        '#type' => 'checkbox',
        '#title' => $toggle['title'],
        '#description' => $toggle['description'],
        '#default_value' => $config->get('queue.' . $key) ?? TRUE,
      ];
    }

    // External services section.
    $form['external'] = [
      '#type' => 'details',
      '#title' => $this->t('External Services', [], ['context' => 'eic_feature_toggle']),
      '#open' => TRUE,
    ];

    $external_toggles = [
      'cordis_api' => [
        'title' => $this->t('CORDIS Data Extraction API', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('External API calls to CORDIS for project data.', [], ['context' => 'eic_feature_toggle']),
      ],
      'vod_cloudfront' => [
        'title' => $this->t('Video Streaming (CloudFront)', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Video on demand streaming service.', [], ['context' => 'eic_feature_toggle']),
      ],
      'smed_user_sync' => [
        'title' => $this->t('SME Dashboard User Sync', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('User synchronization with SME Dashboard.', [], ['context' => 'eic_feature_toggle']),
      ],
      'rest_webservices' => [
        'title' => $this->t('Inbound REST Endpoints', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Inbound REST API endpoints.', [], ['context' => 'eic_feature_toggle']),
      ],
      'profile_enforcement' => [
        'title' => $this->t('Profile Enforcement', [], ['context' => 'eic_feature_toggle']),
        'description' => $this->t('Require users to complete their profile before accessing the site.', [], ['context' => 'eic_feature_toggle']),
      ],
    ];

    foreach ($external_toggles as $key => $toggle) {
      $form['external'][$key] = [
        '#type' => 'checkbox',
        '#title' => $toggle['title'],
        '#description' => $toggle['description'],
        '#default_value' => $config->get('external.' . $key) ?? TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('eic_feature_toggle.settings');

    // Save cron toggles.
    $cron_keys = [
      'eic_statistics',
      'eic_groups_url_alias',
      'eic_groups_approval_reminder',
      'eic_groups_invitation_reminder',
      'eic_groups_solr_reindex',
      'eic_flags',
      'eic_projects_cordis',
      'eic_events',
      'eic_subscription_digest',
      'eic_media_statistics',
    ];
    foreach ($cron_keys as $key) {
      $config->set('cron.' . $key, (bool) $form_state->getValue($key));
    }

    // Save queue toggles.
    $queue_keys = [
      'eic_message_notify_queue',
      'eic_flags_notify_queue',
      'eic_groups_group_content_url_alias_update',
      'eic_groups_group_content_search_api',
      'eic_projects_cordis_extraction_worker',
      'subscription_digest',
    ];
    foreach ($queue_keys as $key) {
      $config->set('queue.' . $key, (bool) $form_state->getValue($key));
    }

    // Save external service toggles.
    $external_keys = [
      'cordis_api',
      'vod_cloudfront',
      'smed_user_sync',
      'rest_webservices',
      'profile_enforcement',
    ];
    foreach ($external_keys as $key) {
      $config->set('external.' . $key, (bool) $form_state->getValue($key));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
