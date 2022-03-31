<?php

namespace Drupal\eic_groups\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeForm;

/**
 * Form handler for the node content edit forms.
 */
class AlterNodeContentForm extends NodeForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $node = $this->entity;
    $insert = $node->isNew();
    $node->save();
    $node_link = $node->toLink($this->t('View'))->toString();
    $context = ['@type' => $node->getType(), '%title' => $node->label(), 'link' => $node_link];
    $t_args = ['@type' => node_get_type_label($node), '%title' => $node->toLink()->toString()];
    $is_group_content_route = strpos(\Drupal::routeMatch()->getRouteName(), 'entity.group_content.') !== FALSE;

    if ($insert) {
      $this->logger('content')->notice('@type: added %title.', $context);
      // Do not show message status for group content node.
      !$is_group_content_route ?
        $this->messenger()->addStatus($this->t('@type %title has been created.', $t_args)) :
        NULL;
    }
    else {
      $this->logger('content')->notice('@type: updated %title.', $context);
      // Do not show message status for group content node.
      !$is_group_content_route ?
        $this->messenger()->addStatus($this->t('@type %title has been updated.', $t_args)) :
        NULL;
    }

    if ($node->id()) {
      $form_state->setValue('nid', $node->id());
      $form_state->set('nid', $node->id());
      if ($node->access('view')) {
        $form_state->setRedirect(
          'entity.node.canonical',
          ['node' => $node->id()]
        );
      }
      else {
        $form_state->setRedirect('<front>');
      }

      // Remove the preview entry from the temp store, if any.
      $store = $this->tempStoreFactory->get('node_preview');
      $store->delete($node->uuid());
    }
    else {
      // In the unlikely case something went wrong on save, the node will be
      // rebuilt and node form redisplayed the same way as in preview.
      $this->messenger()->addError($this->t('The post could not be saved.'));
      $form_state->setRebuild();
    }
  }

}
