<?php

declare(strict_types = 1);

namespace Drupal\eic_community\ValueObject;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;

/**
 * Interface implemented by all field type value objects.
 */
interface ValueObjectInterface extends \ArrayAccess, RefinableCacheableDependencyInterface {

  /**
   * Get value object as an array.
   *
   * @return array
   *   An array of property values, keyed by property name.
   */
  public function getArray(): array;

  /**
   * Build and return a value object from a given array.
   *
   * @param array $values
   *   List of values.
   *
   * @return \Drupal\eic_community\ValueObject\ValueObjectInterface
   *   A new ValueObject object.
   */
  public static function fromArray(array $values = []): ValueObjectInterface;

}
