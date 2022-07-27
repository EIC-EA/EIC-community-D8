<?php

namespace Drupal\eic_webservices\Plugin\Validation\Constraint;

use Drupal\address\Plugin\Validation\Constraint\AddressFormatConstraint as ExternalAddressFormatConstraint;

/**
 * Overrides the Address format constraint.
 *
 * @Constraint(
 *   id = "AddressFormat",
 *   label = @Translation("Address Format", context = "Validation"),
 *   type = { "address" }
 * )
 */
class AddressFormatConstraint extends ExternalAddressFormatConstraint {

}
