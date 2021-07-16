<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\eic_groups\Form\SearchMenuGroupForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a SearchMenuGroupBlock block.
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
class SearchMenuGroupBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /** @var \Drupal\Core\Form\FormBuilderInterface $formBuilder */
  private $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * SearchMenuGroupBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = $this->formBuilder->getForm(SearchMenuGroupForm::class);

    return $form;
  }

}
