<?php

namespace Drupal\eic_user\Commands;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_user_login\Service\SmedUserManager;
use Drush\Commands\DrushCommands;

/**
 * Drush command class that contains a command to find and remove
 * nodes that are no longer part of a group.
 */
class SmedUserUpdateCommand extends DrushCommands {

  use StringTranslationTrait;

  protected EntityTypeManagerInterface $entityTypeManager;

  protected ConfigFactoryInterface $configFactory;

  /**
   * The SMED user manager.
   *
   * @var \Drupal\eic_user_login\Service\SmedUserManager
   */
  protected $smedUserManager;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface     $configFactory,
    SmedUserManager            $smedUserManager,
  ) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->smedUserManager = $smedUserManager;
  }


  /**
   * Finds all users with empty field_smed_id value and retrieves the data from
   * SMED.
   *
   * @usage eic_user:smed_update
   *   Finds all users with empty field_smed_id value, creates a batch process
   *     to ask from SMED the ID and update the user entity.
   *
   * @command eic_user:smed_update
   * @aliases eic-smed
   */
  public function actionCheckSmedId() {
    $user_ids = $this->entityTypeManager->getStorage('user')
      ->getQuery()->notExists('field_smed_id')->execute();
    $batch = new BatchBuilder();

    $chunks = array_chunk($user_ids, 20);
    foreach ($chunks as $chunk) {
      $batch->addOperation([SmedUserUpdateCommand::class, 'batchProcess'],
        [$chunk]);
    }
    $batch->setTitle($this->t('Updating users...'))
      ->setFinishCallback([SmedUserUpdateCommand::class, 'finishProcess']);
    batch_set($batch->toArray());
    // 5. Process the batch sets.
    $this->logger()->notice("Start the batch process.");
    drush_backend_batch_process();

    return DrushCommands::EXIT_SUCCESS;

  }

  public static function batchProcess($chunk, &$context) {
    foreach ($chunk as $user_id) {
      // Keep track of progress.
      $context['results'][] = $user_id;
      $account = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->load($user_id);
      if (\Drupal::configFactory()
        ->get('eic_user_login.settings')
        ->get('check_sync_user')) {
        $missing_smed_id = ($account->hasField('field_smed_id') && $account->get('field_smed_id')
            ->isEmpty());
        $missing_smed_status = ($account->hasField('field_user_status') && $account->get('field_user_status')
            ->isEmpty());

        // We only check/sync user against SMED if account is not active or is
        // missing information.
        // This is to avoid too much load on the SMED.
        if (!$account->isActive() || $missing_smed_id || $missing_smed_status) {
          // Fetch missing information from SMED.
          $data = [
            'email' => $account->getEmail(),
            'username' => $account->getAccountName(),
          ];
          // Check if we have a proper value for the SMED ID.
          if ($account->hasField('field_smed_id') && !$account->get('field_smed_id')
              ->isEmpty()) {
            $data['user_dashboard_id'] = $account->field_smed_id->value;
          }

          if ($result = \Drupal::service('eic_user_login.smed_user_connection')
            ->queryEndpoint($data)) {
            // Update the user status.
            \Drupal::service('eic_user_login.smed_user_connection')
              ->updateUserInformation($account, $result);
          }
        }
      }
    }
  }

  public static function finishProcess(
    $success,
    array $results,
    array $operations,
    $elapsed
  ) {
    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of users we processed...
      \Drupal::messenger()->addMessage(t('@count users edited.', [
        '@count' => count($results),
      ]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      \Drupal::messenger()
        ->addError(t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]));
      \Drupal::logger('whotelier')
        ->error(t('An error occurred while processing @operation with arguments : @args',
            [
              '@operation' => $error_operation[0],
              '@args' => print_r($error_operation[0], TRUE),
            ])
        );
    }
  }

}
