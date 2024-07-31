<?php

declare(strict_types=1);

namespace Drupal\eic_stakeholder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a stakeholder entity type.
 */
interface StakeholderInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
