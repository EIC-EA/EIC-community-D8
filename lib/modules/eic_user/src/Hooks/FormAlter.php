<?php

namespace Drupal\eic_user\Hooks;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_content\Plugin\Field\FieldWidget\EntityTreeWidget;
use Drupal\eic_search\Search\Sources\UserInvitesListSourceType;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Class FormAlter
 *
 * @package Drupal\eic_user\Hooks
 */
class FormAlter {

  use StringTranslationTrait;

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form_id
   */
  public function alterBulkGroupInvitation(&$form, FormStateInterface $form_state, $form_id) {
    $match_limit = 50;

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = \Drupal::routeMatch()->getParameter('group');

    if (!$group instanceof \Drupal\group\Entity\GroupInterface) {
      return;
    }

    $existing_users = \Drupal::request()->request->get('existing_users');
    $existing_users = EntityTreeWidget::extractEntitiesFromWidget($existing_users);
    $default_values = [];

    foreach ($existing_users as $existing_user) {
      $user = User::load($existing_user['target_id']);
      $default_values[] = [
        'name' => realname_load($user),
        'tid' => $user->id(),
        'parent' => -1,
      ];
    }

    $url_search = Url::fromRoute('eic_search.solr_search', [
      'datasource' => json_encode(['user']),
      'source_class' => UserInvitesListSourceType::class,
    ])->toString();

    $form['existing_users'] = EntityTreeWidget::getEntityTreeFieldStructure(
      [],
      'user',
      '',
      50,
      $url_search,
      $url_search,
      $url_search
    );

    $form['existing_users']['#attached']['library'][] = 'eic_community/react-tree-field';

    $form['email_address']['#required'] = FALSE;
    $form['#submit'][] = [$this, 'submitBulkInviteUsers'];
    // Remove the default validation.
    $form['#validate'] = [
      [$this, 'validateBulkInviteUsers'],
    ];
  }

  public function validateBulkInviteUsers(array &$form, FormStateInterface $form_state) {
    $this->validate($form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitBulkInviteUsers(array $form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('tempstore.private')
      ->get('ginvite_bulk_invitation');
    $tempstore_params = $tempstore->get('params');

    $existing_users_id = $form_state->getValue('existing_users');

    $emails = $tempstore_params['emails'];

    foreach ($existing_users_id as $existing_user_id) {
      $user = User::load($existing_user_id['target_id']);

      if (!$user instanceof UserInterface) {
        continue;
      }

      $emails[] = $user->getEmail();
    }

    // Remove empty string.
    $emails = array_filter($emails, function ($email) {
      return !empty($email);
    });

    $tempstore_params['emails'] = $emails;
    $tempstore->set('params', $tempstore_params);
  }

  /**
   * Validate emails, display error message if not valid.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  private function validate(FormStateInterface $form_state) {
    $invalid_emails = [];
    $unexisting_users = $form_state->getValue('email_address');

    $existing_users = $form_state->getValue('existing_users');
    $unexisting_users = $unexisting_users ?
      array_map('trim', array_unique(explode("\r\n", trim($unexisting_users)))) :
      [];

    $emails = [];
    foreach ($existing_users as $existing_user) {
      $user = User::load($existing_user['target_id']);

      if (!$user instanceof UserInterface) {
        $form_state->setErrorByName(
          'existing_users',
          $this->t(
            'User with id @user_id does not exists in the platform.',
            ['@user_id' => $existing_user['target_id']],
            ['context' => 'eic_user']
          )
        );
      }

      $email = $user->getEmail();

      // If user does not have an e-mail field.
      if (!$email) {
        $form_state->setErrorByName(
          'existing_users',
          $this->t(
            'User with id @user_id does not have an email.',
            ['@user_id' => $user->id()],
            ['context' => 'eic_user']
          )
        );

        return;
      }

      $emails[] = $user->getEmail();
    }

    $emails = array_merge($emails, $unexisting_users);

    /** @var \Drupal\group\GroupMembershipLoader $membership_loader */
    $membership_loader = \Drupal::service('group.membership_loader');
    /** @var \Drupal\ginvite\GroupInvitationLoader $invitation_loader */
    $invitation_loader = \Drupal::service('ginvite.invitation_loader');
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = \Drupal::routeMatch()->getParameter('group');
    $error_message = '<ul>';

    foreach ($emails as $email) {
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $invalid_emails[] = $email;
        $error_message .= "<li>$email is not a valid e-mail address.</li>";
      }

      if ($user = user_load_by_mail($email)) {
        $membership = $membership_loader->load($group, $user);

        if (!empty($membership)) {
          $invalid_emails[] = $email;

          $error_message .= "<li>" . $this->t(
              'User @email is already member of the group.',
              ['@email' => $email],
              ['context' => 'eic_user']
            ) . "</li>";
        }
      }

      if ($invitation_loader->loadByGroup($group, NULL, $email)) {
        $invalid_emails[] = $email;

        $error_message .= "<li>" . $this->t(
            'User @email already received an invitation.',
            ['@email' => $email],
            ['context' => 'eic_user']
          ) . "</li>";
      }
    }

    $error_message .= '</ul>';

    if (!empty($invalid_emails)) {
      $form_state->setErrorByName('existing_users', new FormattableMarkup($error_message, []));
    }
  }

  /**
   * Prepares form error message if there is invalid emails.
   *
   * @param array $invalid_emails
   *   List of invalid emails.
   * @param string $message_singular
   *   Error message for one invalid email.
   * @param string $message_plural
   *   Error message for multiple invalid emails.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  private function displayErrorMessage(
    array $invalid_emails,
    $message_singular,
    $message_plural,
    FormStateInterface $form_state
  ) {
    if (($count = count($invalid_emails)) > 1) {
      $error_message = '<ul>';
      foreach ($invalid_emails as $line => $invalid_email) {
        $error_message .= "<li>{$invalid_email} on line {$line}</li>";
      }
      $error_message .= '</ul>';
      $form_state->setErrorByName(
        'email_address',
        $this->formatPlural(
          $count,
          $message_singular,
          $message_plural,
          [
            '@error_message' => new FormattableMarkup($error_message, []),
          ]
        )
      );
    }
    elseif ($count == 1) {
      $error_message = reset($invalid_emails);
      $form_state->setErrorByName(
        'email_address',
        $this->formatPlural(
          $count,
          $message_singular,
          $message_plural,
          [
            '@error_message' => $error_message,
          ]
        )
      );
    }
  }

}
