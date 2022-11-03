<?php

namespace Drupal\eic_user\Hooks;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_content\Plugin\Field\FieldWidget\EntityTreeWidget;
use Drupal\eic_content\Services\EntityTreeManager;
use Drupal\eic_search\Search\Sources\UserInvitesListSourceType;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements form hooks.
 *
 * @package Drupal\eic_user\Hooks
 */
class FormAlter implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The EIC Content entity tree manager service.
   *
   * @var \Drupal\eic_content\Services\EntityTreeManager
   */
  private $treeManager;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\eic_content\Services\EntityTreeManager $entity_tree_manager
   *   The EIC Content entity tree manager service.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    EntityTreeManager $entity_tree_manager
  ) {
    $this->currentUser = $current_user;
    $this->treeManager = $entity_tree_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('eic_content.entity_tree_manager')
    );
  }

  /**
   * Form alter implementation for alterUserForm form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form ID.
   */
  public function alterUserForm(array &$form, FormStateInterface $form_state, string $form_id) {
    $current_user_is_admin = UserHelper::isPowerUser($this->currentUser->getAccount());
    // Deny access to metadata fields for non-power users.
    if (!$current_user_is_admin) {
      $form['field_is_deleted']['#access'] = FALSE;
      $form['field_is_deleted_anonymous']['#access'] = FALSE;
      $form['field_is_deleted_by_uid']['#access'] = FALSE;
      $form['field_is_invalid_email']['#access'] = FALSE;
      $form['field_is_organisation_user']['#access'] = FALSE;
      $form['field_is_spammer']['#access'] = FALSE;
    }

    // In case we are including the member profile form inside the user form.
    if (!empty($form['member_profiles'])) {
      /** @var \Drupal\user\UserInterface $user */
      $user = $form_state->getFormObject()->getEntity();

      // Disable fields for non power users.
      if (!$current_user_is_admin) {
        $form['member_profiles']['widget'][0]['entity']['field_vocab_job_title']['#disabled'] = TRUE;
        $form['member_profiles']['widget'][0]['entity']['field_vocab_user_type']['#disabled'] = TRUE;
      }
    }
  }

  /**
   * Form alter implementation for bulk_group_invitation form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form ID.
   */
  public function alterBulkGroupInvitation(array &$form, FormStateInterface $form_state, string $form_id) {
    $maximum_users = 50;
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = \Drupal::routeMatch()->getParameter('group');

    if (!$group instanceof GroupInterface) {
      return;
    }

    $existing_users = \Drupal::request()->request->get('existing_users');
    $existing_users = EntityTreeWidget::extractEntitiesFromWidget($existing_users);
    $default_values = [];

    foreach ($existing_users as $existing_user) {
      if ($user = User::load($existing_user['target_id'])) {
        $default_values[] = $user;
      }
    }

    $url_search = Url::fromRoute('eic_search.solr_search', [
      'datasource' => json_encode(['user']),
      'source_class' => UserInvitesListSourceType::class,
      'page' => 1,
    ])->toString();

    $options = [
      'selected_terms_label' => $this->t('Select existing platform users to invite', [], ['context' => 'eic_user']),
      'search_label' => $this->t('Select users', [], ['context' => 'eic_user']),
      'search_placeholder' => $this->t('Search for users', [], ['context' => 'eic_user']),
    ];

    // Existing users field.
    $form['existing_users'] = EntityTreeWidget::getEntityTreeFieldStructure(
      [],
      'user',
      $this->treeManager->getTreeWidgetProperty('user')->formatPreselection($default_values),
      $maximum_users,
      $url_search,
      $url_search,
      $url_search,
      $options
    );
    $form['existing_users']['#weight'] = -2;
    $form['existing_users']['#description'] = $this->t('You can select up to <strong>@count</strong> existing platform users.',
      ['@count' => $maximum_users],
      ['context' => 'eic_user']
    );

    // Input divider.
    $form['input_divider'] = [
      '#markup' => $this->t('You can also', [], ['context' => 'eic_user']),
      '#prefix' => '<div id="input-divider">',
      '#suffix' => '</div>',
      '#weight' => $form['existing_users']['#weight'] + 1,
    ];

    // Tweak email_address field.
    $form['email_address']['#title'] = $this->t('Select new users to invite to the platform',
      [],
      ['context' => 'eic_user']
    );
    $form['email_address']['#description'] = $this->t('You can copy/paste multiple email addresses, enter one email address per line.',
      [],
      ['context' => 'eic_user']
    );
    $form['email_address']['#attributes']['placeholder'] = $this->t('Recipients email addresses here',
      [],
      ['context' => 'eic_user']
    );
    $form['email_address']['#required'] = FALSE;

    $form['existing_users']['#attached']['library'][] = 'eic_community/react-tree-field';

    $form['#submit'][] = [$this, 'submitBulkInviteUsers'];
    // Remove the default validation.
    $form['#validate'] = [
      [$this, 'validateBulkInviteUsers'],
    ];
  }

  /**
   * Custom validation handler for bulk_group_invitation form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateBulkInviteUsers(array &$form, FormStateInterface $form_state) {
    $this->validate($form_state);
  }

  /**
   * Custom submit handler for bulk_group_invitation form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
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
   * Form alter implementation for profile_member form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form ID.
   */
  public function alterProfileMemberForm(array &$form, FormStateInterface $form_state, string $form_id) {
    $current_user_is_admin = UserHelper::isPowerUser($this->currentUser);

    // Disable fields for non power users.
    if (!$current_user_is_admin) {
      $form['field_vocab_job_title']['#disabled'] = TRUE;
      $form['field_vocab_user_type']['#disabled'] = TRUE;
    }
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

        return;
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

      $emails[] = $email;
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
