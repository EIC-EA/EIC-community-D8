<?php

namespace Drupal\eic_flags\Plugin\ActionLink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\FlagInterface;

/**
 * Provides the Count link type as a button.
 *
 * This class is an extension of the Ajax link type, but modified to
 * provide flag count.
 *
 * @ActionLinkType(
 *   id = "eic_count_link_button",
 *   label = @Translation("EIC Count link (show as button)"),
 *   description = "An AJAX action link button which displays the count with the flag."
 * )
 */
class EICFlagCountLinkButton extends EICFlagCountLink {

  /**
   * {@inheritdoc}
   */
  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity) {
    $build = parent::getAsFlagLink($flag, $entity);
    $build['#showAsButton'] = TRUE;
    // Return the modified render array.
    return $build;
  }

}
