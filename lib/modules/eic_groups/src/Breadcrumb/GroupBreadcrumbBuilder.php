<?php

namespace Drupal\eic_groups\Breadcrumb;

use Drupal\book\BookBreadcrumbBuilder;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_admin\Service\ActionFormsManager;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\eic_overviews\GroupOverviewPages;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a breadcrumb builder for groups and nodes that belong to groups.
 */
class GroupBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The book breadcrumb builder service.
   *
   * @var \Drupal\book\BookBreadcrumbBuilder
   */
  protected $bookBreadcrumbBuilder;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $eicUserHelper;

  /**
   * The EIC Flags request handler collector service.
   *
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  protected $requestHandlerCollector;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The EIC groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $eicGroupsHelper;

  /**
   * The action forms manager service.
   *
   * @var \Drupal\eic_admin\Service\ActionFormsManager
   */
  protected $actionFormsManager;

  /**
   * Constructs the GroupBreadcrumbBuilder.
   *
   * @param \Drupal\book\BookBreadcrumbBuilder $book_breadcrumb_builder
   *   The book breadcrumb builder service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   *   The EIC User helper service.
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $request_handler_collector
   *   The EIC Flags request handler collector service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The groups helper service.
   * @param \Drupal\eic_admin\Service\ActionFormsManager $action_forms_manager
   *   The action forms manager service.
   */
  public function __construct(
    BookBreadcrumbBuilder $book_breadcrumb_builder,
    AccountInterface $account,
    UserHelper $eic_user_helper,
    RequestHandlerCollector $request_handler_collector,
    RequestStack $request_stack,
    EICGroupsHelper $eic_groups_helper,
    ActionFormsManager $action_forms_manager
  ) {
    $this->bookBreadcrumbBuilder = $book_breadcrumb_builder;
    $this->account = $account;
    $this->requestHandlerCollector = $request_handler_collector;
    $this->requestStack = $request_stack;
    $this->eicUserHelper = $eic_user_helper;
    $this->eicGroupsHelper = $eic_groups_helper;
    $this->actionFormsManager = $action_forms_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $applies = FALSE;

    switch ($route_match->getRouteName()) {
      case 'entity.group.add_form':
      case 'entity.group.canonical':
      case 'entity.group_content.group_approve_membership':
      case 'entity.group_content.group_reject_membership':
      case 'ginvite.invitation.bulk':
      case 'ginvite.invitation.bulk.confirm':
      case 'ginvite.invitation.accept':
        $applies = TRUE;
        break;

      case 'entity.node.canonical':
        $node = $route_match->getParameter('node');

        if ($node instanceof NodeInterface) {
          $applies = GroupContent::loadByEntity($node) ? TRUE : FALSE;
        }
        break;

      case 'entity.group_content.new_request':
      case 'entity.group_content.user_close_request':
        $group_content = $route_match->getParameter('group_content');

        if (!$group_content instanceof GroupContentInterface) {
          break;
        }

        $request_handler = $this->requestHandlerCollector->getHandlerByType(
          $this->requestStack->getCurrentRequest()->get('request_type')
        );

        if (!$request_handler) {
          break;
        }

        if ($request_handler->getType() !== RequestTypes::TRANSFER_OWNERSHIP) {
          break;
        }

        $applies = $group_content->getContentPlugin()->getPluginId() === 'group_membership';
        break;

    }

    return $applies;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $group_type = 'group';
    $breadcrumb = new Breadcrumb();
    // Adds homepage link.
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');
    $group = $this->eicGroupsHelper->getGroupFromRoute();
    if ($group instanceof GroupInterface) {
      $group_type = $group->getGroupType()->id();
    }

    if ($route_match->getParameter('group_type') instanceof GroupTypeInterface) {
      // This has higher priority because of add group pages where a group
      // doesn't exist yet.
      $group_type = $route_match->getParameter('group_type')->id();
    }

    // Adds link to navigate back to the list of groups.
    $links[] = GlobalOverviewPages::getGlobalOverviewPageLink(
      GlobalOverviewPages::getOverviewPageIdFromGroupType($group_type)
    );

    switch ($route_match->getRouteName()) {
      case 'entity.node.canonical':
        $node = $route_match->getParameter('node');

        if ($node instanceof NodeInterface) {
          // Adds the user access as cacheable dependency.
          if ($access = $node->access('view', $this->account, TRUE)) {
            $breadcrumb->addCacheableDependency($access);
          }

          if ($group = $this->eicGroupsHelper->getOwnerGroupByEntity($node)) {
            $links[] = $group->toLink();

            switch ($node->bundle()) {
              case 'book':
              case 'wiki_page':
                $book_breadcrumb = $this->bookBreadcrumbBuilder->build(
                  $route_match
                );
                // Replace links with book breadcrumb links.
                $links = $book_breadcrumb->getLinks();
                // Places the group link right after the "Home" link.
                array_splice($links, 1, 0, [$group->toLink()]);
                // Places the groups overview link right after the "Home" link.
                array_splice(
                  $links,
                  1,
                  0,
                  [
                    Link::fromTextAndUrl(
                      $this->t('Groups'),
                      GlobalOverviewPages::getGlobalOverviewPageLink(
                        GlobalOverviewPages::GROUPS
                      )->getUrl()
                    ),
                  ]
                );
                // Replaces book link text with "Wiki".
                if (
                  $node->bundle() === 'wiki_page'
                  && isset($links[3])
                ) {
                  $links[3]->setText($this->t('Wiki'));
                }
                // We want to keep cache contexts and cache tags from book
                // breadcrumb.
                $breadcrumb->addCacheContexts(
                  $book_breadcrumb->getCacheContexts()
                );
                $breadcrumb->addCacheTags($book_breadcrumb->getCacheTags());
                break;

              case 'discussion':
                $links[] = Link::fromTextAndUrl(
                  $this->t('Discussions'),
                  GroupOverviewPages::getGroupOverviewPageUrl(
                    'discussions',
                    $group
                  )
                );
                break;

              case 'news':
                $links[] = Link::fromTextAndUrl(
                  $this->t('News'),
                  GroupOverviewPages::getGroupOverviewPageUrl(
                    'news',
                    $group
                  )
                );
                break;

              case 'event':
                $links[] = Link::fromTextAndUrl(
                  $this->t('Events'),
                  GroupOverviewPages::getGroupOverviewPageUrl(
                    'events',
                    $group
                  )
                );
                break;

              case 'document':
              case 'gallery':
              case 'video':
                $links[] = Link::fromTextAndUrl(
                  $this->t('Files'),
                  GroupOverviewPages::getGroupOverviewPageUrl('files', $group)
                );
                break;

            }

            // We add the node object as cacheable dependency.
            $breadcrumb->addCacheableDependency($node);
          }
        }
        break;

      case 'entity.group.canonical':
        if ($group instanceof GroupInterface) {
          // Adds the user access as cacheable dependency.
          if ($access = $group->access('view', $this->account, TRUE)) {
            $breadcrumb->addCacheableDependency($access);
          }
        }
        break;

      case 'entity.group_content.group_approve_membership':
      case 'entity.group_content.group_reject_membership':
      case 'ginvite.invitation.bulk':
      case 'ginvite.invitation.bulk.confirm':
        $links[] = $group->toLink();
        break;

      case 'entity.group_content.new_request':
      case 'entity.group_content.user_close_request':
      case 'ginvite.invitation.accept':
        /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
        $group_content = $route_match->getParameter('group_content');

        if (!$group_content instanceof GroupContentInterface) {
          break;
        }

        // Since we have multiple configs for the same route, we add the related
        // config as a dependency.
        if ($action_config = $this->actionFormsManager->getRouteConfig()) {
          $breadcrumb->addCacheableDependency($action_config);
        }

        // We add the group content object as cacheable dependency.
        $breadcrumb->addCacheableDependency($group_content);

        $group = $group_content->getGroup();
        $links[] = $group->toLink();
        break;

    }

    if ($group) {
      // We add the group as cacheable dependency.
      $breadcrumb->addCacheableDependency($group);
    }

    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['url.path']);

    return $breadcrumb;
  }

  /**
   * Returns the request handler type of the current request.
   *
   * @see \Drupal\eic_flags\RequestTypes
   *
   * @return false|string
   *   The handler type or FALSE if not found.
   */
  protected function getRequestHandlerType() {
    $request_handler = $this->requestHandlerCollector->getHandlerByType(
      $this->requestStack->getCurrentRequest()->get('request_type')
    );

    if (!$request_handler) {
      return FALSE;
    }

    return $request_handler->getType();
  }

}
