<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\address\Repository\CountryRepository;

/**
 * Provides functions for country list data.
 */
class CountryService implements CountryServiceInterface {
  /**
   * The country repository.
   *
   * @var \Drupal\address\Repository\CountryRepository
   */
  protected CountryRepository $countryRepository;

  /**
   * Constructs a new CountryService object.
   *
   * @param \Drupal\address\Repository\CountryRepository $country_repository
   *   The country repository.
   */
  public function __construct(CountryRepository $country_repository) {
    $this->countryRepository = $country_repository;
  }

  /**
   * Gets the country name from country code.
   *
   * @param string $countryCode
   *   The two-letter country code.
   *
   * @return string
   *   The country name, or empty string if not found.
   */
  public function getCountryName(string $countryCode): string {
    $countries = $this->countryRepository->getList();
    return $countries[mb_strtoupper($countryCode)] ?? '';
  }

  /**
   * Gets all available countries as code => name array.
   *
   * @return array
   *   Array of country names keyed by country code.
   */
  public function getAllCountries(): array {
    return $this->countryRepository->getList();
  }

}
