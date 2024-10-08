<?php

namespace Drupal\eic_projects\EventSubscriber;


use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProjectStakeholderMigrateSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events = [];
    $events[MigrateEvents::POST_ROW_SAVE] = ['onPostRowSave'];
    return $events;
  }

  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    $row = $event->getRow();
    $project_id = $row->getSourceProperty('id');
    $project_group = \Drupal::entityTypeManager()->getStorage('group')
      ->loadByProperties([
        'field_project_grant_agreement_id' => $project_id,
      ]);
    $project_group = reset($project_group);
    /** @var \Drupal\group\Entity\GroupInterface $project_group */

    $coordinators = $row->getSourceProperty('stakeholder_coordinators');
    foreach ($coordinators as $coordinator) {
      $entity_coordinator = \Drupal::entityTypeManager()
        ->getStorage('stakeholder')
        ->loadByProperties([
          'label' => $coordinator['name'],
          'pic' => $coordinator['pic'],
          'project_id' => $project_id,
          'bundle' => 'coordinator',
        ]);

      if (empty($entity_coordinator)) {
        $entity_coordinator = \Drupal::entityTypeManager()
          ->getStorage('stakeholder')->create([
            'bundle' => 'coordinator',
            'label' => $coordinator['name'],
            'pic' => $coordinator['pic'],
            'project_id' => $project_id,
            'field_stakeholder_address' => [
              'country_code' => $this->getValidCountryCode($coordinator['country_code'], $coordinator['country_name']),
            ],
          ]);
        $entity_coordinator->save();
      }
      else {
        $entity_coordinator = reset($entity_coordinator);
      }
      $project_group->addContent($entity_coordinator, 'group_stakeholder:coordinator');
    }

    $participants = $row->getSourceProperty('stakeholder_participants');
    foreach ($participants as $participant) {
      $entity_participant = \Drupal::entityTypeManager()
        ->getStorage('stakeholder')
        ->loadByProperties([
          'label' => $participant['name'],
          'pic' => $participant['pic'],
          'project_id' => $project_id,
          'bundle' => 'participant',
        ]);
      if (empty($entity_participant)) {
        $entity_participant = \Drupal::entityTypeManager()->getStorage('stakeholder')
          ->create([
            'bundle' => 'participant',
            'label' => $participant['name'],
            'pic' => $participant['pic'],
            'project_id' => $project_id,
            'field_stakeholder_address' => [
              'country_code' => $this->getValidCountryCode($participant['country_code'], $participant['country_name']),
            ],
          ]);
        $entity_participant->save();
      }
      else {
        $entity_participant = reset($entity_participant);
      }
      $project_group->addContent($entity_participant, 'group_stakeholder:participant');
    }

  }

  private function getValidCountryCode($country_code, $country_name) {
    $countries = \Drupal::service('country_manager')->getList();
    if (array_key_exists($country_code,$countries)) {
      return $country_code;
    }
    else {
      // Filter the array for matches in the object's __toString() output
      $result = array_filter($countries, function($object) use ($country_name) {
        return str_contains((string) $object, $country_name);
      });
      if (!empty($result)) {
        return array_keys($result)[0];
      }
      return NULL;
    }
  }

}
