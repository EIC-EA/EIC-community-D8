<?php

namespace Drupal\eic_flags\Controller;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\eic_flags\FlaggedEntitiesListBuilder;
use Drupal\eic_flags\FlaggingListBuilder;
use Drupal\eic_flags\RequestTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller class for request flags.
 *
 * @package Drupal\eic_flags\Controller
 */
class FlagRequestController extends ControllerBase {

  /**
   * The content moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  private $moderationInformation;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  private $currentRequest;

  /**
   * FlagRequestController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The content moderation information service.
   */
  public function __construct(
    RequestStack $request_stack,
    ModerationInformationInterface $moderation_information
  ) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * Responds to the route to list flagged entities for delete & archival requests.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function listing() {
    $definition = $this->entityTypeManager()->getDefinition('flagging');

    return $this->entityTypeManager()
      ->createHandlerInstance(FlaggedEntitiesListBuilder::class, $definition)
      ->render();
  }

  /**
   * Responds to the flag detail route.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function detail() {
    $definition = $this->entityTypeManager()->getDefinition('flagging');

    return $this->entityTypeManager()
      ->createHandlerInstance(FlaggingListBuilder::class, $definition)
      ->render();
  }

  /**
   * Returns the title for the eic_flags.flagged_entities.list route.
   *
   * @param string $request_type
   *   Type of the request (see RequestTypes.php).
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title to display.
   */
  public function getTitle(string $request_type) {
    $operation = $request_type === RequestTypes::DELETE ? 'delete' : 'archival';
    return $this->t(
      'Pending @operation requests',
      ['@operation' => $operation]
    );
  }

  /**
   * Publishes the given entity.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $entity_id
   *   The entity id.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function publish(string $entity_type_id, string $entity_id) {
    $entity = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->load($entity_id);

    if ($entity instanceof ContentEntityInterface) {
      if ($this->moderationInformation->isModeratedEntity($entity)) {
        $entity->set('moderation_state', 'published');
      }
      else {
        $entity->set('status', TRUE);
      }

      $entity->save();
    }

    $destination = Url::fromUserInput($this->currentRequest->getRequestUri());
    if ($destination->isRouted()) {
      return $this->redirect($destination->getRouteName());
    }
    else {
      return $this->redirect(
        'eic_flags.flagged_entities.list',
        [
          'request_type' => RequestTypes::ARCHIVE,
        ]
      );
    }
  }

  /**
   * Returns the title for the various request routes.
   *
   * @param string $request_type
   *   Type of the request (see RequestTypes.php).
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title to display.
   */
  public function getRequestTitle(string $request_type) {
    switch ($request_type) {
      case RequestTypes::ARCHIVE:
        $operation = $this->t('archival');
        break;

      case RequestTypes::DELETE:
        $operation = $this->t('deletion');
        break;

      default:
        $operation = $request_type;
        break;

    }

    $operation = str_replace('_', ' ', $operation);

    if ($request_type === RequestTypes::BLOCK) {
      return ucfirst($operation);
    }

    return $this->t(
      'Request @operation',
      [
        '@operation' => $operation,
      ],
      [
        'context' => $request_type,
      ],
    );
  }

}
