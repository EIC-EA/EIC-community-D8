<?php

namespace Drupal\eic_private_message\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Mail\MailManager;
use Drupal\eic_private_message\Constants\PrivateMessage;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PrivateMessageForm
 *
 * @package Drupal\eic_private_message\Form
 */
class PrivateMessageForm extends FormBase {

  /** @var UserHelper $userHelper */
  private $userHelper;

  /** @var array $siteMail */
  private $systemSettings;

  /** @var MailManager $mailManager */
  private $mailManager;

  /** @var \Drupal\Core\Language\LanguageManager $languageManager */
  private $languageManager;

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\eic_private_message\Form\PrivateMessageForm|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_user.helper'),
      $container->get('config.factory'),
      $container->get('plugin.manager.mail'),
      $container->get('language_manager'),
    );
  }

  /**
   * PrivateMessageForm constructor.
   *
   * @param \Drupal\eic_user\UserHelper $user_helper
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\Core\Mail\MailManager $mail_manager
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   */
  public function __construct(
    UserHelper $user_helper,
    ConfigFactory $config_factory,
    MailManager $mail_manager,
    LanguageManager $language_manager
  ) {
    $this->userHelper = $user_helper;
    $this->systemSettings = $config_factory->get('system.site');
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'private_message_form';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user_id = $this->getRouteMatch()->getParameter('user');
    $group = $this->getRouteMatch()->getParameter('group');

    if ($user_id) {
      $user = User::load($user_id);

      if (!$user instanceof UserInterface) {
        $this->messenger()->addError($this->t(
          'User not found.',
          [],
          ['context' => 'eic_private_message']
        ));

        return [];
      }

      if (!$user->get(PrivateMessage::PRIVATE_MESSAGE_USER_ALLOW_CONTACT_ID)->value) {
        $this->messenger()->addError($this->t(
          'The user you are trying to contact has disabled private messages from its profile. The operation you are requesting cannot be completed.',
          [],
          ['context' => 'eic_private_message']
        ));

        return [];
      }
    }

    $form['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From', [], ['context' => 'eic_private_message']),
      '#value' => $this->t(
        '"@fullname at @site_name" @site_email',
        [
          '@fullname' => $this->userHelper->getFullName(),
          '@site_email' => $this->systemSettings->get('mail'),
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
      '#format' => 'plain_text',
      '#required' => TRUE,
    ];

    $form['send_copy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send me a copy', [], ['context' => 'eic_private_message']),
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
    $current_langauge = $this->languageManager->getCurrentLanguage()->getId();

    $recipients = $values['recipients'];

    if (array_key_exists('to_recipients', $values)) {
      $recipients = $values['to_recipients'];

      $recipients = array_map(function ($item) {
        return $item['target_id'];
      }, $recipients);
    }

    $recipients = array_map(function (int $id) {
      $user = User::load($id);

      return $user instanceof UserInterface ? $user->getEmail() : '';
    }, $recipients);

    $mail = $this->mailManager->mail(
      'eic_private_message',
      PrivateMessage::PRIVATE_MESSAGE_USER_MAIL_KEY,
      implode($recipients, ','),
      $current_langauge,
      $values
    );

    if ($mail['result']) {
      $this->messenger()->addMessage($this->t(
        'Your message was successfully sent!',
        [],
        ['context' => 'eic_private_message']
      ));
    }

    if ($values['send_copy']) {
      $values['subject'] = $this->t(
        'Self copy: @subject',
        ['@subject' => $values['subject']],
        ['eic_private_message']
      );

      $this->mailManager->mail(
        'eic_private_message',
        PrivateMessage::PRIVATE_MESSAGE_USER_MAIL_KEY,
        $this->currentUser()->getEmail(),
        $current_langauge,
        $values
      );
    }
  }

}
