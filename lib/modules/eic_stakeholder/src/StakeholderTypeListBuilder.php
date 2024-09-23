<?php

namespace Drupal\eic_stakeholder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of stakeholder type entities.
 *
 * @see \Drupal\eic_stakeholder\Entity\StakeholderType
 */
class StakeholderTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Label');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No stakeholder types available. <a href=":link">Add stakeholder type</a>.',
      [':link' => Url::fromRoute('entity.stakeholder_type.add_form')->toString()]
    );

    return $build;
  }

}
