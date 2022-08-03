<?php

namespace Drupal\oec_group_comments\Plugin\Field\FieldFormatter;

use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_comments\GroupPermissionChecker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'comment_group_content' formatter.
 *
 * @FieldFormatter(
 *   id = "comment_group_content",
 *   label = @Translation("Comment on group content"),
 *   field_types = {
 *     "comment"
 *   }
 * )
 */
class CommentGroupContentFormatter extends CommentDefaultFormatter {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The group permission checker.
   *
   * @var \Drupal\oec_group_comments\GroupPermissionChecker
   */
  protected $groupPermissionChecker;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_route_match'),
      $container->get('entity_display.repository'),
      $container->get('renderer'),
      $container->get('oec_group_comments.group_permission_checker')
    );
  }

  /**
   * Constructs a new CommentDefaultFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer interface.
   * @param \Drupal\oec_group_comments\GroupPermissionChecker $groupPermissionChecker
   *   The group permission checker.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    AccountInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFormBuilderInterface $entity_form_builder,
    RouteMatchInterface $route_match,
    EntityDisplayRepositoryInterface $entity_display_repository,
    RendererInterface $renderer,
    GroupPermissionChecker $groupPermissionChecker
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings,
      $current_user,
      $entity_type_manager,
      $entity_form_builder,
      $route_match,
      $entity_display_repository
    );

    $this->renderer = $renderer;
    $this->groupPermissionChecker = $groupPermissionChecker;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $output = parent::viewElements($items, $langcode);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $items->getEntity();

    // Exclude entities without the set id.
    if (!empty($entity->id())) {
      $group_contents = GroupContent::loadByEntity($entity);
    }

    // Check if the content is posted in groups.
    if (empty($group_contents)) {
      return $output;
    }

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = reset($group_contents)->getGroup();

    // Add cache contexts.
    $output['#cache']['contexts'][] = 'route.group';
//    $output['#cache']['contexts'][] = 'user.group_permissions';

    if ($group instanceof GroupInterface) {
      $output['#cache']['contexts'][] = 'user.is_group_member:' . $group->id();
    }

    $account = $this->currentUser;

    $access_post_comments = $this->groupPermissionChecker->getPermissionInGroups('post comments', $account, $group_contents, $output);
    if ($access_post_comments->isForbidden()) {
      $output[0]['comment_form'] = [];
    }

    $access_view_comments = $this->groupPermissionChecker->getPermissionInGroups('view comments', $account, $group_contents, $output);
    if ($access_view_comments->isForbidden()) {
      $output[0]['comments'] = [];
    }

    return $output;
  }

}
