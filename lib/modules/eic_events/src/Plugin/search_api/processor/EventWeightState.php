<?php

namespace Drupal\eic_events\Plugin\search_api\processor;

use Drupal\eic_events\Constants\Event;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds event weight state.
 *
 * @SearchApiProcessor(
 *   id = "event_weight_state",
 *   label = @Translation("Event weight state"),
 *   description = @Translation("Add the weight state of event."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class EventWeightState extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Event weight state'),
        'description' => $this->t('The event weight state id for ongoing, future, past'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
        'datasource_id' => 'content',
      ];
      $properties[Event::SEARCH_API_FIELD_ID_WEIGHT_STATE] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $datasourceId = $item->getDatasourceId();
    if ($datasourceId === 'entity:node' || $datasourceId === 'entity:group') {
      /** @var \Drupal\node\NodeInterface|\Drupal\group\Entity\GroupInterface $entity */
      $entity = $item->getOriginalObject()->getValue();

      if (
        ($entity instanceof NodeInterface && 'event' === $entity->getType()) ||
        ($entity instanceof GroupInterface && 'event' === $entity->bundle())
      ) {
        $start_date = strtotime($entity->get('field_date_range')->value);
        $end_date = strtotime($entity->get('field_date_range')->end_value);
        $now = time();
        // We set a weight value depending the state of the event: 1.ongoing 2.future 3.past
        // so we can sort easily in different overviews.
        // By default we set it as past event
        $weight_event_state = Event::WEIGHT_STATE_PAST;

        if ($now < $start_date) {
          $weight_event_state = Event::WEIGHT_STATE_FUTURE;
        }

        if ($now >= $start_date && $now <= $end_date) {
          $weight_event_state = Event::WEIGHT_STATE_ONGOING;
        }
        $fields = $this->getFieldsHelper()->filterForPropertyPath(
          $item->getFields(),
          NULL,
          Event::SEARCH_API_FIELD_ID_WEIGHT_STATE
        );

        foreach ($fields as $field) {
          $field->addValue($weight_event_state);
        }
      }
    }
  }

}
