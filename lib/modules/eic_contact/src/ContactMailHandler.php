<?php

namespace Drupal\eic_contact;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\contact\MailHandler as MailHandlerBase;
use Drupal\contact\MailHandlerInterface;
use Drupal\contact\MessageInterface;
use Psr\Log\LoggerInterface;

/**
 * Decorates the Drupal\contact\MailHandler class.
 */
class ContactMailHandler extends MailHandlerBase {

  /**
   * The original mail handler object.
   *
   * @var \Drupal\contact\MailHandler
   */
  protected $mailHandler;

  /**
   * Constructs a new \Drupal\eic_contact\ContactMailHandler object.
   *
   * @param \Drupal\contact\MailHandlerInterface $mail_handler
   *   The original mail handler object.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   String translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(MailHandlerInterface $mail_handler, MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, LoggerInterface $logger, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager) {
    $this->mailHandler = $mail_handler;
    parent::__construct($mail_manager, $language_manager, $logger, $string_translation, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   *
   * This is a verbatim copy of the original service, with customisation around
   * recipients.
   */
  public function sendMailMessages(MessageInterface $message, AccountInterface $sender) {
    // Clone the sender, as we make changes to mail and name properties.
    $sender_cloned = clone $this->userStorage->load($sender->id());
    $params = [];
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $recipient_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $contact_form = $message->getContactForm();

    if ($sender_cloned->isAnonymous()) {
      // At this point, $sender contains an anonymous user, so we need to take
      // over the submitted form values.
      $sender_cloned->name = $message->getSenderName();
      $sender_cloned->mail = $message->getSenderMail();

      // For the email message, clarify that the sender name is not verified; it
      // could potentially clash with a username on this site.
      $sender_cloned->name = $this->t('@name (not verified)', ['@name' => $message->getSenderName()]);
    }

    // Build email parameters.
    $params['contact_message'] = $message;
    $params['sender'] = $sender_cloned;

    if (!$message->isPersonal()) {
      // Send to the form recipient(s), using the site's default language.
      $params['contact_form'] = $contact_form;

      $to = implode(', ', $contact_form->getRecipients());
    }
    elseif ($recipient = $message->getPersonalRecipient()) {
      // Send to the user in the user's preferred language.
      $to = $recipient->getEmail();
      $recipient_langcode = $recipient->getPreferredLangcode();
      $params['recipient'] = $recipient;
    }
    else {
      throw new MailHandlerException('Unable to determine message recipient');
    }

    // Send email to the recipient(s).
    $key_prefix = $message->isPersonal() ? 'user' : 'page';
    // Check if we have recipient(s) to send mail to.
    // This is our custom check, with the contact form recipients field being
    // optional.
    if (!empty($to)) {
      $this->mailManager->mail('contact', $key_prefix . '_mail', $to, $recipient_langcode, $params, $sender_cloned->getEmail());
    }

    // If requested, send a copy to the user, using the current language.
    if ($message->copySender()) {
      $this->mailManager->mail('contact', $key_prefix . '_copy', $sender_cloned->getEmail(), $current_langcode, $params, $sender_cloned->getEmail());
    }

    // If configured, send an auto-reply, using the current language.
    if (!$message->isPersonal() && $contact_form->getReply()) {
      // User contact forms do not support an auto-reply message, so this
      // message always originates from the site.
      if (!$sender_cloned->getEmail()) {
        $this->logger->error('Error sending auto-reply, missing sender e-mail address in %contact_form', [
          '%contact_form' => $contact_form->label(),
        ]);
      }
      else {
        $this->mailManager->mail('contact', 'page_autoreply', $sender_cloned->getEmail(), $current_langcode, $params);
      }
    }

    if (!$message->isPersonal()) {
      $this->logger->notice('%sender-name (@sender-from) sent an email regarding %contact_form.', [
        '%sender-name' => $sender_cloned->getAccountName(),
        '@sender-from' => $sender_cloned->getEmail() ?? '',
        '%contact_form' => $contact_form->label(),
      ]);
    }
    else {
      $this->logger->notice('%sender-name (@sender-from) sent %recipient-name an email.', [
        '%sender-name' => $sender_cloned->getAccountName(),
        '@sender-from' => $sender_cloned->getEmail(),
        '%recipient-name' => $message->getPersonalRecipient()->getAccountName(),
      ]);
    }
  }

}
