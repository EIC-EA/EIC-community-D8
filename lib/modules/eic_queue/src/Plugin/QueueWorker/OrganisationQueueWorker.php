<?php

namespace Drupal\eic_queue\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\eic_webservices\Controller\SubRequestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\Core\Queue\QueueInterface;

/**
 * @QueueWorker(
 *   id = "EIC_ORG_QUEUE",
 *   title = @Translation("Organisation queue worker"),
 *   cron = {"time" = 60}
 * )
 */
class OrganisationQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Required to create subRequests
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Used for getCurrentRequest
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Used to get eic_webservices_organisation'
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourcePluginManager;

  /**
   * To switch to user 1 ( admin )
   *
   * @var Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * To create a session
   *
   * @var \Drupal\Core\Session\UserSession
   */
  protected $session;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger,
        HttpKernelInterface $httpKernel,
        RequestStack $requestStack,
        ResourcePluginManager $resourcePluginManager,
        AccountSwitcherInterface $accountSwitcher,
        Session $session,
        ConfigFactoryInterface $config,
        QueueInterface $project_id_queue
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->requestStack = $requestStack;
    $this->httpKernel = $httpKernel;
    $this->resourcePluginManager = $resourcePluginManager;
    $this->accountSwitcher = $accountSwitcher;
    $this->session = $session;
    $this->config = $config;
    $this->project_id_queue = $project_id_queue;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('logger.channel.queue_log'),
      $container->get('http_kernel.basic'),
      $container->get('request_stack'),
      $container->get('plugin.manager.rest'),
      $container->get('account_switcher'),
      $container->get('session'),
      $container->get('config.factory'),
      $container->get('queue')->get('extraction_request_project_id')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data) {
    $this->logger->notice(print_r(["Message received", $data], true));
    try {
      $this->postOrganisation($data);
    }
    catch (\UnprocessableEntityHttpException | \Exception $e) {
      // If POST failed, report it and continue processing. The message will be deleted from the queue by Drush\Drupal\Commands\core\QueueCommands
      $this->logger->error("Message not processed. [".get_class($e)."] ".$e->getMessage());
    }
  }

  public function postOrganisation($data) {
    $current_request = $this->requestStack->getCurrentRequest();

    // Force session start if we don't already have a session.
    if (!$this->session->isStarted()) {
      $this->session->migrate();
    }
    $current_request->setSession($this->session);

    // Because of
    // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Routing%21ContentTypeHeaderMatcher.php/8.9.x
    // https://github.com/symfony/http-foundation/blob/4.0/Request.php#L1286
    $current_request->headers->set('Content-Type', 'application/hal+json');
    $current_request->setFormat('hal_json', array('application/hal+json'));

    // Authenticate
    $api_key = $this->config->get('eic_webservices.settings')->get('api_key');
    $current_request->headers->set('X-EIC-Auth-Token', $api_key);

    // Get the parent resource endpoint URI.
    $parent_resource = $this->resourcePluginManager->getDefinition('eic_webservices_organisation');
    $uri = $current_request->getBasePath();
    $uri .= str_replace('{group}', $data['detail']['Id'][0], $parent_resource['uri_paths']['canonical']);

    $project_ids = [];
    if (is_array($data['detail']["Projects"][0])) {
      foreach ($data['detail']["Projects"][0]["ProjectId"] as $project_id) {
          $project_ids[] = array("value" => $project_id);
          // Enqueue project Id to get data from CORDIS
          $extraction_queue_item = new \stdClass();
          $extraction_queue_item->project_id = $project_id;
          $this->project_id_queue->createItem($extraction_queue_item);
      }
    }

    $data = json_encode(array("_links" => array("type" => array(
      "href" => $current_request->getSchemeAndHttpHost().$current_request->getBaseUrl()."/rest/type/group/organisation")),
      "field_organisation_pic" => array(array("value" => $data['detail']["EnterpriseId"][0])),
      "field_organisation_project_id" => $project_ids,
      )
    );

    $sub_request = new SubRequestController($this->httpKernel, $this->requestStack);

    // By default drush runs as anonymous ( uid = 0 , elevate to admin user
    $this->accountSwitcher->switchTo(new UserSession(['uid' => 1]));

    // Perform the sub-request and return the result.
    $response = $sub_request->subRequest(
      $uri,
      Request::METHOD_PATCH,
      [],
      [], // cookies
      [], // files
      $current_request->server->all(),
      $data,
      $current_request->headers->all()
    );

    // Switch back to anonymous ( uid = 0 )
    $this->accountSwitcher->switchBack();

    $this->logger->notice(print_r([Json::decode($response->getContent()), $response->getStatusCode()], true));
  }

}
