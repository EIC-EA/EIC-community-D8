<?php

namespace Drupal\eic_dashboards\Services;

/**
 * Defines country service utility interface.
 */
interface CountryServiceInterface {

  /**
   * Gets the country name from country code.
   */
  public function getCountryName(string $countryCode);

  /**
   * Gets all available countries as code => name array.
   */
  public function getAllCountries();

}
