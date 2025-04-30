<?php

namespace Drupal\eic_dashboards\Plugin\views\filter;


use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\BooleanOperator;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by user profile completion status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("user_profile_completion_status")
 */
class UserProfileCompletionStatus extends BooleanOperator {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, private readonly ViewsHandlerManager $joinHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): UserProfileCompletionStatus {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.views.join')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    if ($this->value === "1") {
        /** @var \Drupal\mysql\Driver\Database\mysql\Connection $connection */
        $connection = $this->query->getConnection();
        $subquery = $connection->select('profile', 'p');
        $subquery->addField('p', 'uid');
        // Join profile fields.
        $subquery->innerJoin('profile__field_vocab_topic_expertise', 'pvte', 'p.profile_id = pvte.entity_id');
        $subquery->innerJoin('profile__field_vocab_topic_interest', 'pvti', 'p.profile_id = pvti.entity_id');
        $subquery->innerJoin('profile__field_location_address', 'pla', 'p.profile_id = pla.entity_id');
        $subquery->distinct();

        $configuration = [
          'type' => 'INNER',
          'table formula' => $subquery,
          'field' => 'uid',
          'left_table' => 'users_field_data',
          'left_field' => 'uid',
          'operator' => '=',
        ];

        $join = $this->joinHandler->createInstance('standard', $configuration);

        // There is no $base as we are joining to a query.
        $this->query->addRelationship('user_profile_completion_status', $join, NULL, $this->relationship);
    }
  }

  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    unset($form['value']['#options'][0]);
  }

  public function getValueOptions() {
    $this->valueOptions = [
      1 => $this->t('Completed'),
      0 => $this->t('Incomplete'),
    ];
  }

}
