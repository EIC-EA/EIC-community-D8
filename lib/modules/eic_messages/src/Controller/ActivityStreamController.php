<?php

namespace Drupal\eic_messages\Controller;

use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
use Drupal\group\Entity\GroupInterface;
use Drupal\message\MessageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ActivityStreamController {

  /**
   * @param \Drupal\group\Entity\GroupInterface $group
   * @param \Drupal\message\MessageInterface $message
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteActivityItem(GroupInterface $group, MessageInterface $message) {
    if (!$message->hasField('field_group_ref')
      || $group->id() !== $message->get('field_group_ref')->entity->id()
    ) {
      return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
    }

    $template_name = $message->getTemplate()->get('template');
    $allowed_templates = ActivityStreamMessageTemplates::getAllowedTemplates();
    if (!in_array($template_name, $allowed_templates)) {
      return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
    }

    $message->delete();

    return new JsonResponse([]);
  }

}
