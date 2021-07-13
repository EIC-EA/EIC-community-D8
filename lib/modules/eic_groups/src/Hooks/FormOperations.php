<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_content_wiki_page\WikiPageBookManager;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\oec_group_features\GroupFeaturePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class FormAlter.
 *
 * Implementations for entity hooks.
 */
class FormOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Group feature plugin manager.
   *
   * @var \Drupal\oec_group_features\GroupFeaturePluginManager
   */
  protected $groupFeaturePluginManager;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  protected $eicGroupsHelper;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\oec_group_features\GroupFeaturePluginManager $group_feature_plugin_manager
   *   The Group feature plugin manager.
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $eic_groups_helper
   *   The EIC Groups helper service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    GroupFeaturePluginManager $group_feature_plugin_manager,
    EICGroupsHelperInterface $eic_groups_helper,
    RouteMatchInterface $route_match,
    RequestStack $request_stack
  ) {
    $this->configFactory = $config_factory;
    $this->groupFeaturePluginManager = $group_feature_plugin_manager;
    $this->eicGroupsHelper = $eic_groups_helper;
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.group_feature'),
      $container->get('eic_groups.helper'),
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  public function groupGroupAddForm(&$form, FormStateInterface $form_state) {
    // We don't want the features field to be accessible during the group
    // creation process.
    $form['features']['#access'] = FALSE;
  }

  /**
   * Implements hook_form_node_wiki_page_form_alter() and hook_form_node_wiki_page_edit_form_alter().
   */
  public function groupWikiPageFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $group = $this->eicGroupsHelper->getGroupFromRoute();

    // If this not a group wiki page form, we do nothing.
    if (!$group) {
      return;
    }

    // We don't allow users to select the book. The parent book is set
    // automatically from the query parameters.
    $form['book']['bid']['#access'] = FALSE;

    if ($this->routeMatch->getRouteName() !== 'entity.group_content.create_form') {
      return;
    }

    // Get the NID of the book page that belong to the group.
    if (!($parent_nid = $this->eicGroupsHelper->getGroupBookPage($group))) {
      return;
    }

    $query = $this->requestStack->getCurrentRequest()->query;

    // If the key "parent" is presented in the request query and it corresponds
    // to the right book page NID, then we don't need to do any redirection.
    if ($query->has('parent') && (is_numeric($query->get('parent')) && $query->get('parent') === $parent_nid)) {
      return;
    }

    // We accept parents until the 4th level, otherwise it will keep the top
    // level book page NID as default.
    if ($query->has('parent') && is_numeric($query->get('parent'))) {
      $wiki_page = Node::load($query->get('parent'));

      if ($wiki_page->bundle() === 'wiki_page') {
        // If the book ID of the parent wiki page is the right book page NID
        // of the group and the parent wiki page depth doesn't reach the
        // maximum defined, then we accept the parent wiki page.
        if ($wiki_page->book['bid'] === $parent_nid && !$wiki_page->book['p' . (WikiPageBookManager::BOOK_MAX_DEPTH + 1)]) {
          $parent_nid = $wiki_page->id();
        }
      }
    }

    // Update "parent" key with the right book or wiki page NID.
    $query->set('parent', $parent_nid);

    // Generate a new url for the current form with the correct query
    // parameters and redirect the user. This will let the book module set the
    // right book parent in the book field. This way we prevent users from
    // creating group wiki pages in the wrong book.
    $redirect_url = Url::fromRouteMatch($this->routeMatch)
      ->setOption('query', $query->all());
    $redirect = new RedirectResponse($redirect_url->toString());
    $redirect->send();
  }

  /**
   * Custom submit handler for form_group forms.
   */
  public function formGroupSubmit(&$form, FormStateInterface $form_state) {
    $group = $form_state->getFormObject()->getEntity();
    $this->enableDefaultFeatures($group);
  }

  /**
   * Enables default features for a group.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The group entity.
   */
  protected function enableDefaultFeatures(Group $group) {
    $group_type_id = $group->getGroupType()->id();
    $config = $this->configFactory->get("eic_groups.group_features.default_features.$group_type_id");

    foreach ($this->groupFeaturePluginManager->getDefinitions() as $definition) {
      if (in_array($definition['id'], $config->get('default_features'))) {
        // Enable the feature.
        $default_feature = $this->groupFeaturePluginManager->createInstance($definition['id']);
        $default_feature->enable($group);

        // Make sure the feature is enabled on field level.
        $feature_found = FALSE;
        foreach ($group->get('features')->getValue() as $item) {
          if ($item['value'] == $definition['id']) {
            $feature_found = TRUE;
            break;
          }
        }
        if (!$feature_found) {
          $value = $group->get('features')->getValue();
          $value[]['value'] = $definition['id'];
          $group->get('features')->setValue($value);
        }
      }
    }
    // Save enabled group features on field level.
    $group->save();
  }

}
