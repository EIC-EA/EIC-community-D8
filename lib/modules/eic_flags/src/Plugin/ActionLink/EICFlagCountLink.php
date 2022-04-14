<?php

namespace Drupal\eic_flags\Plugin\ActionLink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagCountManagerInterface;
use Drupal\flag\FlagInterface;
use Drupal\flag\Plugin\ActionLink\AJAXactionLink;
use Drupal\flag\Service\FlagFloodControlServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Count link type.
 *
 * This class is an extension of the Ajax link type, but modified to
 * provide flag count.
 *
 * @ActionLinkType(
 *   id = "eic_count_link",
 *   label = @Translation("EIC Count link"),
 *   description = "An AJAX action link which displays the count with the flag."
 * )
 */
class EICFlagCountLink extends AJAXactionLink {

  /**
   * The flag count manager.
   *
   * @var \Drupal\flag\FlagCountManagerInterface
   */
  private $flagCountManager;

  /**
   * Build a new link type instance and sets the configuration.
   *
   * @param array $configuration
   *   The configuration array with which to initialize this plugin.
   * @param string $plugin_id
   *   The ID with which to initialize this plugin.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\flag\Service\FlagFloodControlServiceInterface $flood_control
   *   The flood control service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request from the request stack.
   * @param \Drupal\flag\FlagCountManagerInterface $flag_count
   *   The flag count manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    AccountInterface $current_user,
    FlagFloodControlServiceInterface $flood_control,
    Request $request,
    FlagCountManagerInterface $flag_count
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $current_user,
      $flood_control,
      $request
    );
    $this->flagCountManager = $flag_count;
  }

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpParamsInspection.
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('flag.flood_control'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('flag.count')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity) {
    $build = [];

    try {
      $action = $this->getAction($flag, $entity);
      $access = $flag->actionAccess($action, $this->currentUser, $entity);
      if ($access->isAllowed()) {
        // Get the render array.
        $build = parent::getAsFlagLink($flag, $entity);

        // Normally, you'd just override flag.html.twig in your site's theme.
        // For this example module, we do something more advanced:
        // Provide a new @ActionLinkType that changes the default theme function.
        $build['#theme'] = 'eic_flag_count';
      }
      else {
        $action = 'view';
        $access = $flag->actionAccess($action, $this->currentUser, $entity);
        if ($access->isAllowed()) {
          $build['#flag_id'] = $flag->id();
          $entity_flag_counts = $this->flagCountManager->getEntityFlagCounts($entity);
          $build['#flag_count'] = $entity_flag_counts[$flag->id()] ?? 0;
          $build['#title'] = $flag->label();
          $build['#theme'] = 'eic_flag_count_text';
        }
      }

      // Return the modified render array.
      return $build;
    } catch (\Exception $exception) {
      return $build;
    }
  }

}
