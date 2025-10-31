<?php

namespace Drupal\eic_eca\Plugin\Action;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Describes the eic_eca user_no_communities action.
 *
 * @Action(
 *   id = "eic_eca_user_no_communities",
 *   label = @Translation("User joined no communities"),
 *   description = @Translation("Add to token the new users that haven't joined a community."),
 *   eca_version_introduced = "1.0.0",
 *   type = "entity"
 *   )
 */
class UserNoCommunities extends ConfigurableActionBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setConnection($container->get('database'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($group = NULL): void {
    $items = (int) $this->configuration['items'];

    $duration = strtotime($this->configuration['duration']);

    $query = $this->connection->select('users_field_data', 'ufd');
    $query->addField('ufd', 'uid');
    $query->condition('ufd.created', $duration, '<=')
      ->condition('ufd.uid', 0, '<>')
      ->range(0, $items);

    // Check for profile completion status
    $subquery = $this->connection->select('profile', 'p');
    $subquery->addField('p', 'uid');
    // Join profile fields.
    $subquery->innerJoin('profile__field_vocab_topic_expertise', 'pvte', 'p.profile_id = pvte.entity_id');
    $subquery->innerJoin('profile__field_vocab_topic_interest', 'pvti', 'p.profile_id = pvti.entity_id');
    $subquery->innerJoin('profile__field_location_address', 'pla', 'p.profile_id = pla.entity_id');
    $subquery->distinct();
    $subquery->where('[p].[uid] = [ufd].[uid]');

    // @see \Drupal\KernelTests\Core\Database\SelectSubqueryTest::testExistsSubquerySelect
    $query->exists($subquery);

    // Check for not received the email already.
    $subquery = $this->connection->select('flagging', 'f')
      ->condition('f.flag_id', $this->configuration['flag_id']);
    $subquery->addField('f', 'entity_id');
    $subquery->where('[f].[entity_id] = [ufd].[uid]');

    // @see \Drupal\KernelTests\Core\Database\SelectSubqueryTest::testNotExistsSubquerySelect
    $query->notExists($subquery);


    // Check for existing group membership.
    $subquery = $this->connection->select('group_content_field_data', 'gcfd')
      ->fields('gcfd', ['entity_id'])
      ->condition('gcfd.type', 'group-group_membership');
    $subquery->where('[gcfd].[entity_id] = [ufd].[uid]');

    $query->notExists($subquery);

    $uids = $query->execute()->fetchAllAssoc('uid');
    $uids = array_column($uids, 'uid');

    $this->tokenService->addTokenData(
      $this->configuration['object'], $this->entityTypeManager
      ->getStorage('user')->loadMultiple($uids)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'items' => 30,
        'flag_id' => 'user_joined_communities',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['items'] = [
      '#type' => 'number',
      '#title' => $this->t('Items to load'),
      '#description' => $this->t('Enter how many items it should load. Max # is 50'),
      '#default_value' => $this->configuration['items'],
      '#max' => 50,
    ];

    $form['flag_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Flag ID to record the transaction.'),
      '#default_value' => $this->configuration['flag_id'],
      '#options' => [
        'user_joined_communities' => $this->t('User joined communities'),
      ],
    ];

    $form['duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Duration'),
      '#description' => $this->t('Enter the duration.'),
      '#default_value' => $this->configuration['duration'],
      '#required' => TRUE,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['items'] = $form_state->getValue('items');
    $this->configuration['flag_id'] = $form_state->getValue('flag_id');
    $this->configuration['duration'] = $form_state->getValue('duration');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    if (strtotime($form_state->getValue('duration')) === FALSE) {
      $form_state->setErrorByName('duration', $this->t('Not a valid duration string!'));
    }
  }

  /**
   * Set the database service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database service.
   */
  public function setConnection(Connection $connection): void {
    $this->connection = $connection;
  }


}
