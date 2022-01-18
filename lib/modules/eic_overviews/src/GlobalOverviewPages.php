<?php

namespace Drupal\eic_overviews;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\eic_overviews\Entity\OverviewPage;

/**
 * Service that provides functionality for global overview pages.
 *
 * @package Drupal\eic_overviews
 */
class GlobalOverviewPages {

  use StringTranslationTrait;

  /**
   * ID of the Global search overview page.
   */
  const GLOBAL_SEARCH = 1;

  /**
   * ID of the Groups overview page.
   */
  const GROUPS = 2;

  /**
   * ID of the Members overview page.
   */
  const MEMBERS = 3;

  /**
   * ID of the News & Stories overview page.
   */
  const NEWS_STORIES = 4;

  /**
   * ID of the Events overview page.
   */
  const EVENTS = 5;

  /**
   * ID of the Organisations overview page.
   */
  const ORGANISATIONS = 6;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a GlobalOverviewPages object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns the Link object for the given global overview page.
   *
   * @param int $page
   *   The page identifier for which we return the URL.
   *
   * @return \Drupal\Core\Link
   *   The URL object or NULL if does not apply.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function getGlobalOverviewPageLink(int $page): Link {
    $overview_entities = \Drupal::entityQuery('overview_page')
      ->condition('field_overview_id', $page)
      ->execute();

    $default_link = Link::fromTextAndUrl(
      t('Overview', [], ['context' => 'eic_overviews']),
      Url::fromRoute('<current>')
    );

    if (empty($overview_entities)) {
      return $default_link;
    }

    /** @var \Drupal\eic_overviews\Entity\OverviewPage $overview_entity */
    $overview_entity = OverviewPage::load(reset($overview_entities));

    if (!$overview_entity instanceof OverviewPage) {
      return $default_link;
    }

    return Link::fromTextAndUrl(
      $overview_entity->label(),
      $overview_entity->toUrl()
    );
  }

  /**
   * @param string $group_type
   *
   * @return int
   */
  public static function getOverviewPageIdFromGroupType(
    string $group_type
  ): int {
    switch ($group_type) {
      case 'event':
        $overview_id = GlobalOverviewPages::EVENTS;
        break;
      case 'organisation':
        $overview_id = GlobalOverviewPages::ORGANISATIONS;
        break;
      default:
        $overview_id = GlobalOverviewPages::GROUPS;
        break;
    }

    return $overview_id;
  }

  /**
   * Returns the operation links for the given global overview page.
   *
   * @param int $page
   *   The page identifier for which we return the URL.
   *
   * @return array
   *   The array of operation links composed by:
   *   - title: the operation link title;
   *   - url: the operation URL string.
   */
  public function getGlobalOverviewPageOperations(int $page) {
    switch ($page) {
      case GlobalOverviewPages::GROUPS:
        $entity_id = 'group';
        $bundle = 'group';
        $add_route = "entity.$entity_id.add_form";
        $access_handler = $this->entityTypeManager->getAccessControlHandler($entity_id);

        if ($access_handler->createAccess($bundle)) {
          $url = is_callable($add_route)
            ? call_user_func($add_route, $entity_id, $bundle)
            : Url::fromRoute($add_route, [$entity_id . '_type' => $bundle]);

          $operations[] = [
            'title' => $this->t("Create a new group"),
            'url' => $url->toString(),
          ];
        }
        break;

    }

    return $operations;
  }

  /**
   * Return the current overview page ID.
   *
   * @return int|null
   */
  public function getCurrentOverviewPageId(): ?int {
    $current_overview = \Drupal::routeMatch()->getParameter('overview_page');

    return $current_overview instanceof OverviewPage ? $current_overview->get('field_overview_id')->value : NULL;
  }

}
