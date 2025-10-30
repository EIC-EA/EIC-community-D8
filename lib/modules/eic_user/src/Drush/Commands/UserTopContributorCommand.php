<?php

namespace Drupal\eic_user\Drush\Commands;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class UserTopContributorCommand extends DrushCommands {

  use StringTranslationTrait;


  /**
   * Constructs an UserTopContributorCommand object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $connection,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'eic_user:top_contributor', aliases: ['user-top-contributor'])]
  #[CLI\Usage(name: 'eic_user:top_contributor', description: 'Checks all users for their contribution status.')]
  public function topContributionCheck() {
    $comm_qr = $this->connection->select('comment_field_data', 'cfd')
      ->fields('cfd', ['uid']);

    $node_qr = $this->connection->select('node_field_data', 'nfd')
      ->fields('nfd', ['uid']);

    $node_qr->union($comm_qr, 'ALL');

    $query = $this->connection->select($node_qr, 'combined');
    $query->addField('combined', 'uid');
    $query->addExpression('COUNT(*)', 'occurence_count');
    $query->groupBy('combined.uid');
    $query->havingCondition('occurence_count', \Drupal::service('settings')->get('top_contributor_limit') ?? 10, '>');

    $result = $query->execute()->fetchAllKeyed();
    $user_ids = array_keys($result);

    $batch = new BatchBuilder();

    $chunks = array_chunk($user_ids, 20);
    foreach ($chunks as $chunk) {
      $batch->addOperation([self::class, 'batchProcess'],
        [$chunk]);
    }
    $batch->setTitle($this->t('Updating users...'))
      ->setFinishCallback([self::class, 'finishProcess']);
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
      if ($account->get('field_top_contributor')->isEmpty() || !($account->get('field_top_contributor')->value)) {
        $account->set('field_top_contributor', TRUE);
        $account->save();
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
      \Drupal::logger('eic_user')
        ->error(t('An error occurred while processing @operation with arguments : @args',
            [
              '@operation' => $error_operation[0],
              '@args' => print_r($error_operation[0], TRUE),
            ])
        );
    }
  }

}
