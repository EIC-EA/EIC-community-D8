<?php

namespace Drupal\eic_stakeholder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Stakeholder type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "stakeholder_type",
 *   label = @Translation("Stakeholder type"),
 *   label_collection = @Translation("Stakeholder types"),
 *   label_singular = @Translation("stakeholder type"),
 *   label_plural = @Translation("stakeholders types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count stakeholders type",
 *     plural = "@count stakeholders types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\eic_stakeholder\Form\StakeholderTypeForm",
 *       "edit" = "Drupal\eic_stakeholder\Form\StakeholderTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\eic_stakeholder\StakeholderTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer stakeholder types",
 *   bundle_of = "stakeholder",
 *   config_prefix = "stakeholder_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/stakeholder_types/add",
 *     "edit-form" = "/admin/structure/stakeholder_types/manage/{stakeholder_type}",
 *     "delete-form" = "/admin/structure/stakeholder_types/manage/{stakeholder_type}/delete",
 *     "collection" = "/admin/structure/stakeholder_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class StakeholderType extends ConfigEntityBundleBase {

  /**
   * The machine name of this stakeholder type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the stakeholder type.
   *
   * @var string
   */
  protected $label;

}
