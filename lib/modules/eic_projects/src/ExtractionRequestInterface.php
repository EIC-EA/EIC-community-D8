<?php

namespace Drupal\eic_projects;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an extraction request entity type.
 */
interface ExtractionRequestInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
