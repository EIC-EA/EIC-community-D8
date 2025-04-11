<?php

namespace Drupal\eic_dashboards\Plugin\views\field;


use Drupal\Core\Database\Connection;
use Drupal\eic_user\UserHelper;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_profile_completion_status")
 */
class UserProfileCompletionStatus extends FieldPluginBase {

  public function __construct($configuration, $plugin_id, $plugin_definition, private readonly UserHelper $userHelper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_user.helper')
    );
  }

  /**
   * @inheritDoc
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @inheritdoc
   */
  public function render(ResultRow $values) {
    $user = $values->_entity;
    if ($user instanceof UserInterface) {
      $is_completed = $this->userHelper->isUserProfileCompleted($user);
      if ($is_completed) {
        return $this->t("Completed");
      }
      return $this->t("Incomplete");
    }
    return $this->t("N/A");
  }

}
