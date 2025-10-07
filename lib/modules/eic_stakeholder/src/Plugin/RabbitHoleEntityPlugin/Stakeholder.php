<?php

namespace Drupal\eic_stakeholder\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for stakeholder.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_eic_stakeholder",
 *  label = @Translation("Stakeholder"),
 *  entityType = "stakeholder"
 * )
 */
class Stakeholder extends RabbitHoleEntityPluginBase {

}
