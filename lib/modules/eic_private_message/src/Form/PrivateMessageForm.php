<?php

namespace Drupal\eic_private_message\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_messages\Service\MessageBus;
use Drupal\eic_private_message\Constants\PrivateMessage;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a private message form.
 *
 * @package Drupal\eic_private_message\Form
 */
class PrivateMessageForm extends FormBase {

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  private $userHelper;

  /**
   * The system site configurations.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $systemSettings;

  /**
   * The message bus service.
   *
   * @var \Drupal\eic_messages\Service\MessageBus
   */
  private $bus;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_user.helper'),
      $container->get('config.factory'),
      $container->get('eic_messages.message_bus')
    );
  }

  /**
   * PrivateMessageForm constructor.
   *
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The EIC User helper service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param MessageBus $bus
   *   The message bus service.
   */
  public function __construct(
    UserHelper $user_helper,
    ConfigFactory $config_factory,
    MessageBus $bus
  ) {
    $this->userHelper = $user_helper;
    $this->systemSettings = $config_factory->get('system.site');
    $this->bus = $bus;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'private_message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user_id = $this->getRouteMatch()->getParameter('user');
    $group = $this->getRouteMatch()->getParameter('group');
    if ($user_id) {
      $user = User::load($user_id);

      if (!$user instanceof UserInterface) {
        $this->messenger()->addError(
          $this->t(
            'User not found.',
            [],
            ['context' => 'eic_private_message']
          )
        );

        return [];
      }

      if (!$user->get(PrivateMessage::PRIVATE_MESSAGE_USER_ALLOW_CONTACT_ID)->value) {
        $this->messenger()->addError(
          $this->t(
            'The user you are trying to contact has disabled private messages from its profile. The operation you are requesting cannot be completed.',
            [],
            ['context' => 'eic_private_message']
          )
        );

        return [];
      }
    }

    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Contact @user', ['@user' => $this->userHelper->getFullName($user)])
    ];

    $form['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From', [], ['context' => 'eic_private_message']),
      '#value' => $this->t(
        '"@fullname at @site_name" @site_email',
        [
          '@fullname' => $this->userHelper->getFullName(),
          '@site_email' => '<' . $this->systemSettings->get('mail') . '>',
          '@site_name' => $this->systemSettings->get('name'),
        ],
        ['context' => 'eic_private_message']
      ),
      '#required' => TRUE,
      '#attributes' => [
        'readonly' => 'readonly',
      ],
    ];

    if ($group instanceof GroupInterface) {
      $form['to_recipients'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Select users in group', [], ['context' => 'eic_private_message']),
        '#description' => $this->t('Seperate users by ",".', [], ['context' => 'eic_private_message']),
        '#tags' => TRUE,
        '#maxlength' => NULL,
        '#target_type' => 'group_content',
        '#selection_handler' => 'default:group_membership',
        '#selection_settings' => [
          'filter' => [
            'gid' => $group->id(),
          ],
        ],
      ];
    }

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject', [], ['context' => 'eic_private_message']),
      '#required' => TRUE,
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body', [], ['context' => 'eic_private_message']),
      '#format' => 'basic_text',
      '#required' => TRUE,
      '#allowed_formats' => ['basic_text'],
    ];

    $form['send_copy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send me a copy', [], ['context' => 'eic_private_message']),
      '#default_value' => TRUE,
    ];

    $form['recipients'] = [
      '#type' => 'hidden',
      '#value' => [$user_id],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send', [], ['context' => 'eic_private_message']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Send mails to selected users. Also send a copy if needed.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $recipients = $values['recipients'];

    if (array_key_exists('to_recipients', $values)) {
      $recipients = $values['to_recipients'];

      $recipients = array_map(function ($item) {
        return $item['target_id'];
      }, $recipients);
    }

    foreach ($recipients as $recipient) {
      $this->bus->dispatch([
        'template' => 'notify_contact_user',
        'field_sender' => ['target_id' => $this->currentUser()->id()],
        'field_body' => $form_state->getValue('body'),
        'field_subject' => $form_state->getValue('subject'),
        'uid' => $recipient,
      ]);
    }

    $this->messenger()->addMessage(
      $this->t(
        'Your message was successfully sent!',
        [],
        ['context' => 'eic_private_message']
      )
    );

    if ($values['send_copy']) {
      $this->bus->dispatch([
        'template' => 'notify_contact_user_copy',
        'field_body' => $form_state->getValue('body'),
        'field_subject' => $form_state->getValue('subject'),
        'uid' => $this->currentUser()->id(),
      ]);
    }
  }

}
