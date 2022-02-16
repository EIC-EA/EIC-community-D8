<?php

namespace Drupal\eic_group_membership\Plugin\views\field;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides TransferOwnershipStatus field handler.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eic_group_membership_transfer_ownership_status")
 */
class TransferOwnershipStatus extends FieldPluginBase {

  /**
   * The request collector service.
   *
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  protected $requestHandlerCollector;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new TransferOwnershipStatus instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $request_handler_collector
   *   The request collection service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RequestHandlerCollector $request_handler_collector,
    DateFormatterInterface $date_formatter,
    TimeInterface $time
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestHandlerCollector = $request_handler_collector;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_flags.handler_collector'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    $group_content = $this->getEntity($values);
    $handler = $this->requestHandlerCollector->getHandlerByType(RequestTypes::TRANSFER_OWNERSHIP);
    $output = [];

    // We return empty output if the group content entity is not a group
    // membership.
    if ($group_content->getContentPlugin()->getPluginId() !== 'group_membership') {
      return $output;
    }

    if ($handler->hasOpenRequest($group_content, $group_content->getEntity())) {

      $open_requests = $handler->getOpenRequests($group_content);
      $request = reset($open_requests);

      if ($handler->hasExpiration($request)) {
        $timeout = $request->get('field_request_timeout')->value * 86400;
        $timeout += $request->get('created')->value;

        // If request has expired, we show when it expired.
        if ($handler->hasExpired($request)) {
          $timeout_formatted = $this->dateFormatter->format($timeout, 'eu_short_date_hour');
          $output = [
            '#markup' => $this->t('ownership transfer expired <br>on @expiration_date', ['@expiration_date' => $timeout_formatted]),
          ];
        }
        else {
          $request_time = $this->time->getRequestTime();

          if ($timeout > $request_time) {
            $timeout_formatted = $this->dateFormatter->formatDiff($request_time, $timeout, [
              'granularity' => 2,
            ]);
          }
          else {
            $timeout_formatted = $this->dateFormatter->formatDiff($timeout, $request_time, [
              'granularity' => 2,
            ]);
          }
          $output = [
            '#markup' => $this->t('pending ownership transfer <br>expires in @expiration_date', ['@expiration_date' => $timeout_formatted]),
          ];
        }
      }
    }

    return $output;
  }

}
