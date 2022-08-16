<?php

namespace Drupal\eic_webservices\Plugin\Validation\Constraint;

use Drupal\address\Plugin\Validation\Constraint\AddressFormatConstraintValidator as ExternalValidator;
use Drupal\eic_webservices\Utility\WsRestHelper;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Validates the address format constraint.
 */
class AddressFormatConstraintValidator extends ExternalValidator implements ContainerInjectionInterface {

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // If request is coming from SMED, we avoid the validation.
    $current_request = $this->requestStack->getCurrentRequest();
    if (WsRestHelper::isSmedRestRequest($current_request)) {
      return;
    }

    parent::validate($value, $constraint);
  }

}
