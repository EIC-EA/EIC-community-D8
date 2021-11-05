<?php

namespace Drupal\eic_groups\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Provides a handler that renders links.
 *
 * @ViewsField("resend_invitation")
 */
class ResendInvitation extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    $group_content = $this->getEntity($row);
    $url = Url::fromRoute('eic_groups.group_content.resend_invite', [
      'group' => $group_content->getGroup()->id(),
      'group_content' => $group_content->id(),
    ]);

    $url->access(\Drupal::currentUser());

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    $this->options['alter']['query'] = $this->getDestinationArray();
    return parent::renderLink($row);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Resend invitation');
  }

}
