<?php

namespace Drupal\eic_flags\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\eic_flags\FlaggedEntitiesListBuilder;
use Drupal\eic_flags\FlaggingListBuilder;
use Drupal\eic_flags\RequestTypes;

/**
 * Class FlagRequestController
 *
 * @package Drupal\eic_flags\Controller
 */
class FlagRequestController extends ControllerBase {

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function listing() {
    $definition = $this->entityTypeManager()->getDefinition('flagging');

    return $this->entityTypeManager()
      ->createHandlerInstance(FlaggedEntitiesListBuilder::class, $definition)
      ->render();
  }

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function detail() {
    $definition = $this->entityTypeManager()->getDefinition('flagging');

    return $this->entityTypeManager()
      ->createHandlerInstance(FlaggingListBuilder::class, $definition)
      ->render();
  }

  /**
   * Returns the title for the eic_flags.flagged_entities.list route
   *
   * @param $request_type
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getTitle($request_type) {
    $operation = $request_type === RequestTypes::DELETE ? 'delete' : 'archival';
    return $this->t('Pending @operation requests', ['@operation' => $operation]);
  }

  /**
   * @param string $entity_type_id
   * @param string $entity_id
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function publish(string $entity_type_id, string $entity_id) {
    $moderation_information = \Drupal::service('content_moderation.moderation_information');
    $entity = \Drupal::entityTypeManager()
      ->getStorage($entity_type_id)
      ->load($entity_id);

    if ($entity instanceof ContentEntityInterface) {
      if ($moderation_information->isModeratedEntity($entity)) {
        $entity->set('moderation_state', 'published');
      }
      else {
        $entity->set('status', TRUE);
      }

      $entity->save();
    }

    $destination = Url::fromUserInput(\Drupal::destination()->get());
    if ($destination->isRouted()) {
      return $this->redirect($destination->getRouteName());
    }
    else {
      $this->redirect('eic_flags.flagged_entities.list', [
        'request_type' => RequestTypes::ARCHIVE,
      ]);
    }
  }

}
