<?php

namespace Drupal\eic_webservices\Authentication;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authentication provider to validate requests with token in header.
 */
class AuthToken implements AuthenticationProviderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache kill switch service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $cacheKillSwitch;

  /**
   * Constructs the EIC AuthToken authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $cache_kill_switch
   *   The entity type manager service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    KillSwitch $cache_kill_switch
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheKillSwitch = $cache_kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $request->headers->has('X-EIC-Auth-Token');
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    // We want avoid caching for all REST requests.
    $this->cacheKillSwitch->trigger();

    // Make sure we have a valid API Key.
    $api_key = $this->configFactory->get('eic_webservices.settings')->get('api_key');
    if (empty($api_key)) {
      return NULL;
    }

    // Make sure we have a valid Webservice user account.
    $webservice_user_account_uid = $this->configFactory->get('eic_webservices.settings')->get('webservice_user_account');
    if (empty($webservice_user_account_uid)) {
      return NULL;
    }
    if (!$account = $this->entityTypeManager->getStorage('user')->load($webservice_user_account_uid)) {
      return NULL;
    }

    // Validate the token.
    $token = $request->headers->get('X-EIC-Auth-Token');
    if ($token === $api_key) {
      return $account;
    }

    return NULL;
  }

}
