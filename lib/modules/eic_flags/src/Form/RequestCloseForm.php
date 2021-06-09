<?php

namespace Drupal\eic_flags\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\eic_flags\RequestTypes;

/**
 * Class RequestCloseForm
 *
 * @package Drupal\eic_flags\Form
 */
class RequestCloseForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('eic_flags.flagged_entities.list', ['flag_type' => RequestTypes::DELETE]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @entity-type %label?', [
      '@entity-type' => $this->getEntity()->getEntityType()->getSingularLabel(),
      '%label' => $this->getEntity()->label(),
    ]);
  }

}
