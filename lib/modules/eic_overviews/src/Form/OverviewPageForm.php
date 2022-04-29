<?php

namespace Drupal\eic_overviews\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the overview page entity edit forms.
 */
class OverviewPageForm extends ContentEntityForm {

  private RendererInterface $renderer;

  /**
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Component\Datetime\TimeInterface $time
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    RendererInterface $renderer
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => $this->renderer->render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New overview page %label has been created.', $message_arguments));
      $this->logger('eic_overviews')->notice('Created new overview page %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The overview page %label has been updated.', $message_arguments));
      $this->logger('eic_overviews')->notice('Updated new overview page %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.overview_page.canonical', ['overview_page' => $entity->id()]);

    return $result;
  }

}
