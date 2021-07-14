<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\eic_groups\Form\SearchMenuGroupForm;

/**
 * Provides an SearchMenuGroupBlock block.
 *
 * @Block(
 *   id = "eic_groups_search_menu_group",
 *   admin_label = @Translation("EIC Search Menu Group"),
 *   category = @Translation("European Innovation Council"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE)
 *   }
 * )
 */
class SearchMenuGroupBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm(SearchMenuGroupForm::class);

    return $form;
  }

}
