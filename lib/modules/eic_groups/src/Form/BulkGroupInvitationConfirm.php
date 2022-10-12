<?php

namespace Drupal\eic_groups\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ginvite\Form\BulkGroupInvitationConfirm as BulkGroupInvitationConfirmBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Bulk operations related with invitation entity.
 */
class BulkGroupInvitationConfirm extends BulkGroupInvitationConfirmBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {

    $email_list_markup = "";
    foreach ($this->tempstore['emails'] as $email) {
      $invitee = $email;
      // If user already exist on the platform, display the username instead of
      // the email address.
      /** @var \Drupal\user\UserInterface $user */
      if ($user = user_load_by_mail($email)) {
        $invitee = $user->toLink($user->getDisplayName())->toString();
      }
      $email_list_markup .= "{$invitee} <br />";
    }

    $description = $this->t("Invitation recipients: <br /> @email_list",
      [
        '@email_list' => new FormattableMarkup($email_list_markup, []),
      ]
    );

    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $batch = [
      'title' => $this->t('Inviting Members'),
      'operations' => [],
      'init_message'     => $this->t('Sending Invites'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message'    => $this->t('An error occurred during processing'),
      'finished' => 'Drupal\eic_groups\Form\BulkGroupInvitationConfirm::batchFinished',
    ];

    foreach ($this->tempstore['emails'] as $email) {
      $values = [
        'type' => $this->tempstore['plugin'],
        'gid' => $this->tempstore['gid'],
        'invitee_mail' => $email,
        'entity_id' => 0,
      ];
      $batch['operations'][] = [
        '\Drupal\ginvite\Form\BulkGroupInvitationConfirm::batchCreateInvite',
        [$values],
      ];
    }

    batch_set($batch);
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      try {
        $tempstore = \Drupal::service('tempstore.private')->get('ginvite_bulk_invitation');
        $destination = new Url('view.eic_group_invitations.page_1', ['group' => $tempstore->get('params')['gid']]);
        if (!$destination->access()) {
          $destination = new Url('entity.group.canonical', ['group' => $tempstore->get('params')['gid']]);
        }
        $redirect = new RedirectResponse($destination->toString());
        $tempstore->delete('params');
        $redirect->send();
      }
      catch (\Exception $error) {
        \Drupal::service('logger.factory')->get('ginvite')->alert(new TranslatableMarkup('@err', ['@err' => $error]));
      }

    }
    else {
      $error_operation = reset($operations);
      \Drupal::service('messenger')->addMessage(new TranslatableMarkup('An error occurred while processing @operation with arguments : @args', [
        '@operation' => $error_operation[0],
        '@args' => print_r($error_operation[0]),
      ]));
    }
  }

}
