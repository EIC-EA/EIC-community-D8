<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FormAlter.
 *
 * Implementations for entity hooks.
 */
class FormAlter implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  protected $eicGroupsHelper;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $eic_groups_helper
   *   The EIC Groups helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EICGroupsHelperInterface $eic_groups_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eicGroupsHelper = $eic_groups_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_groups.helper')
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

}
