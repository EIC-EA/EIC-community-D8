<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_webservices\Utility\EicWsHelper;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to update users through a POST method.
 *
 * Using the EntityResource is currently not possible due to an issue with group
 * module.
 *
 * @see https://www.drupal.org/project/group/issues/2872645
 *
 * @RestResource(
 *   id = "eic_webservices_group_membership",
 *   label = @Translation("EIC Group Membership Resource"),
 *   uri_paths = {
 *     "canonical" = "/smed/api/v1/membership",
 *     "create" = "/smed/api/v1/membership"
 *   }
 * )
 */
class MembershipResource extends ResourceBase {

  /**
   * Represents the expected data structure from the incoming request.
   *
   * @var array
   */
  protected const DATA_STRUCTURE = [
    'group_type' => 'string',
    'group_id' => 'int',
    'user_id' => 'int',
    'action' => 'string',
  ];

  /**
   * Represents the possible actions.
   *
   * @var array
   */
  protected const ACTIONS = [
    'add',
    'update',
    'delete',
  ];

  /**
   * The EIC Webservices helper class.
   *
   * @var \Drupal\eic_webservices\Utility\EicWsHelper
   */
  protected $wsHelper;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   A logger instance.
   * @param \Drupal\eic_webservices\Utility\EicWsHelper $eic_ws_helper
   *   The EIC Webservices helper class.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerChannelInterface $logger,
    EicWsHelper $eic_ws_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats,
      $logger);
    $this->wsHelper = $eic_ws_helper;
  }

  /**
   * {@inheritdoc}
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
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('eic_webservices.ws_helper')
    );
  }

  /**
   * Responds to POST requests.
   *
   * This method will create, update or delete group memberships depending on
   * the provided parameters.
   *
   * @param array $data
   *   The posted data.
   *
   * @return \Drupal\rest\ModifiedResourceResponse|\Drupal\rest\ResourceResponse|void
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function post(array $data) {
    // Check if we have the correct values.
    if (!$this->isValidData($data)) {
      $data = [
        'message' => 'Unprocessable Entity: validation failed. Provided data structure not correct.',
      ];
      return new ResourceResponse($data, 422);
    }

    // Check if we have an acceptable action.
    if (!in_array($data['action'], self::ACTIONS)) {
      // Send custom response.
      $message = "Unprocessable Entity: validation failed. Action '%s' not allowed.";
      $data = [
        'message' => sprintf($message, $data['action']),
      ];

      return new ResourceResponse($data, 422);
    }

    // Try to load the group entity.
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->wsHelper->getGroupBySmedId($data['group_id'], $data['group_type']);
    if (!$group) {
      // Send custom response.
      $data = [
        'message' => 'Unprocessable Entity: validation failed. No organisation found.',
      ];

      return new ResourceResponse($data, 422);
    }

    // Try to load the user entity.
    $user = $this->wsHelper->getUserBySmedId($data['user_id']);
    if (!$user) {
      // Send custom response.
      $data = [
        'message' => 'Unprocessable Entity: validation failed. No user found.',
      ];

      return new ResourceResponse($data, 422);
    }

    switch ($data['action']) {
      case 'add':
      case 'update':
        try {
          // Check if we have a role.
          $role = NULL;
          if (!empty($data['role'])) {
            $role = EICGroupsHelper::getGroupTypeRole($group->getEntityType()->id(), $data['role']);
          }

          // Check if the user is already a member of the group.
          if ($membership = $group->getMember($user)) {
            $group_content = $membership->getGroupContent();
            $group_content->group_roles->target_id = $role;
            $group_content->save();
          }
          else {
            $group->addMember($user, ['group_roles' => [$role]]);
            $group->save();
          }

          $message = 'User has been added to group successfully.';
          return new ModifiedResourceResponse(['message' => $message]);
        }
        catch (\Exception $exception) {
          return new ResourceResponse(['message' => $exception->getMessage()], $exception->getCode());
        }

      case 'delete':
        try {
          $group->removeMember($user);
        }
        catch (\Exception $exception) {
          return new ResourceResponse(['message' => $exception->getMessage()], $exception->getCode());
        }
        $message = 'User has been removed to group successfully.';
        return new ModifiedResourceResponse(['message' => $message]);

    }

  }

  /**
   * Validates the request data against the expected data structure.
   *
   * @param array $data
   *   The request data.
   *
   * @return bool
   *   TRUE if data is valid, FALSE otherwise.
   */
  protected function isValidData(array $data) {
    foreach (self::DATA_STRUCTURE as $variable => $type) {
      if (empty($data[$variable])) {
        return FALSE;
      }

      switch ($type) {
        case 'int':
          if (!is_int((int) $data[$variable])) {
            return FALSE;
          }
          break;

        default:
          if (gettype($data[$variable]) != $type) {
            return FALSE;
          }
          break;
      }
    }

    return TRUE;
  }

}
