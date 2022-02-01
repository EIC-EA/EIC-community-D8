<?php

namespace Drupal\eic_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SearchController
 *
 * @package Drupal\eic_groups\Controller
 */
class SearchController extends ControllerBase {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function global(Request $request) {
    return [];
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function groups(Request $request) {
    return [];
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function events(Request $request) {
    return [];
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function people(Request $request) {
    return [];
  }

}
