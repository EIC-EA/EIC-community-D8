<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_seo\AliasCleaner;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class GroupTokens.
 *
 * Implementations of token hooks.
 */
class GroupTokens implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The Token service.
   *
   * @var \Drupal\eic_seo\AliasCleaner
   */
  protected $eicAliasCleaner;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $eicGroupsHelper;

  /**
   * Array of group types to use in tokens.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface[]
   */
  protected $groupTypes = [];

  /**
   * Constructs a new GroupTokens object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The Token service.
   * @param \Drupal\eic_seo\AliasCleaner $alias_cleaner
   *   The EIC AliasCleaner service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The EIC Groups helper service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Token $token_service,
    AliasCleaner $alias_cleaner,
    RequestStack $request_stack,
    EICGroupsHelper $eic_groups_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tokenService = $token_service;
    $this->eicAliasCleaner = $alias_cleaner;
    $this->requestStack = $request_stack;
    $this->eicGroupsHelper = $eic_groups_helper;

    $this->groupTypes = $entity_type_manager->getStorage('group_type')
      ->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('token'),
      $container->get('eic_seo.alias_cleaner'),
      $container->get('request_stack'),
      $container->get('eic_groups.helper')
    );
  }

  /**
   * Implements hook_token_info().
   */
  public function tokenInfo() {
    $info = [];
    $info['tokens']['node'] = [
      'node_group_url' => [
        'name' => $this->t('Node group url'),
        'description' => $this->t('The url of the group this node belongs to'),
      ],
    ];
    $info['tokens']['group'] = [
      'group_truncated_title' => [
        'name' => $this->t('Truncated group title'),
        'description' => $this->t('The truncated group title to the given limit. Maximum value is 100.'),
        'dynamic' => TRUE,
      ],
      'group_owner' => [
        'name' => $this->t('Group owner'),
        'description' => $this->t('The user entity which is the owner of the group.'),
      ],
    ];

    // Create tokens to grab list of groups a user belongs to, per group type.
    foreach ($this->groupTypes as $group_type) {
      $info['tokens']['user']["group-{$group_type->id()}-entities"] = [
        'name' => $this->t('User group @group_type entities.', ['@group_type' => $group_type->id()]),
        'description' => $this->t('List of group @group_type entities the user belongs to.', ['@group_type' => $group_type->id()]),
      ];
    }

    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  public function tokens(
    $type,
    $tokens,
    array $data,
    array $options,
    BubbleableMetadata $bubbleable_metadata
  ) {
    $replacements = [];

    switch ($type) {
      case 'node':
        // Node tokens.
        if (!empty($data['node'])) {
          $replacements = $this->nodeTokens($tokens, $data, $options, $bubbleable_metadata);
        }
        break;

      case 'group':
        // Group tokens.
        if (!empty($data['group']) && $data['group'] instanceof GroupInterface) {
          $replacements = $this->groupTokens($tokens, $data, $options, $bubbleable_metadata);
        }
        break;

      case 'user':
        // User tokens.
        if (!empty($data['user']) && $data['user'] instanceof UserInterface) {
          $replacements = $this->userTokens($tokens, $data, $options, $bubbleable_metadata);
        }
        break;

    }

    return $replacements;
  }

  /**
   * Replace node tokens.
   *
   * @param mixed $tokens
   *   An array of tokens to be replaced.
   * @param array $data
   *   An associative array of data objects to be used when generating
   *   replacement values.
   * @param array $options
   *   An associative array of options for token replacement.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata.
   *
   * @return array
   *   An associative array of replacement values.
   */
  private function nodeTokens(
    $tokens,
    array $data,
    array $options,
    BubbleableMetadata $bubbleable_metadata
  ) {
    $replacements = [];
    $base_url = $this->requestStack->getCurrentRequest()->getBaseUrl();

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'node_group_url':
          if (($node = $data['node']) && !$node instanceof NodeInterface) {
            break;
          }

          // If node belongs to a group.
          if ($group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($node)) {
            /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
            $group_content = reset($group_contents);

            $has_translation = $group_content->hasTranslation($node->language()->getId());
            $group = $has_translation
             ? $group_content->getGroup()->getTranslation($node->language()->getId())
             : $group_content->getGroup();

            $group_url = $group->toUrl()->toString();

            // If base path is presented in group URL, we need to remove
            // it in order to avoid duplicated base paths.
            $has_base_path = substr($group_url, 0, strlen($base_url)) === $base_url;
            $replacements[$original] = $has_base_path
              ? substr_replace($group_url, '', 0, strlen($base_url))
              : $group_url;
          }
          break;

      }
    }

    return $replacements;
  }

  /**
   * Replace group tokens.
   *
   * @param mixed $tokens
   *   An array of tokens to be replaced.
   * @param array $data
   *   An associative array of data objects to be used when generating
   *   replacement values.
   * @param array $options
   *   An associative array of options for token replacement.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata.
   *
   * @return array
   *   An associative array of replacement values.
   */
  private function groupTokens(
    $tokens,
    array $data,
    array $options,
    BubbleableMetadata $bubbleable_metadata
  ) {
    $replacements = [];

    $find_tokens = [
      'group_truncated_title',
      'group_owner',
    ];

    foreach ($find_tokens as $find_token_name) {
      $found_tokens = $this->tokenService->findWithPrefix($tokens, $find_token_name);

      // Provide replacements for found group tokens.
      foreach ($found_tokens as $value => $token_name) {
        switch ($find_token_name) {
          case 'group_truncated_title':
            // Check that we have a numeric value and that it's not above 100.
            if (!is_numeric($value) || $value > 100) {
              $value = 100;
            }
            $customConfig = [
              'max_component_length' => $value,
            ];
            $replacements[$token_name] = $this->eicAliasCleaner->cleanString($data['group']->label(), [], $customConfig);
            break;

          case 'group_owner':
            $group_owner = $this->eicGroupsHelper->getGroupOwner($data['group']);
            if (!$group_owner) {
              break;
            }
            // Generates replacements for user tokens in the list.
            $replacements = $this->tokenService->generate(
              'user',
              $found_tokens,
              [
                'user' => $group_owner,
              ],
              $options,
              $bubbleable_metadata);
            break;

        }
      }
    }

    return $replacements;
  }

  /**
   * Replace user tokens.
   *
   * @param mixed $tokens
   *   An array of tokens to be replaced.
   * @param array $data
   *   An associative array of data objects to be used when generating
   *   replacement values.
   * @param array $options
   *   An associative array of options for token replacement.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata.
   *
   * @return array
   *   An associative array of replacement values.
   */
  private function userTokens(
    $tokens,
    array $data,
    array $options,
    BubbleableMetadata $bubbleable_metadata
  ) {
    $replacements = [];

    // Replace list of user group entities token as links.
    foreach ($this->groupTypes as $group_type) {
      $find_token_name = "group-{$group_type->id()}-entities";

      if (!isset($tokens[$find_token_name])) {
        continue;
      }

      /** @var \Drupal\group\Entity\GroupContentInterface[] $user_memberships */
      $user_memberships = $this->entityTypeManager->getStorage('group_content')
        ->loadByProperties([
          'type' => "{$group_type->id()}-group_membership",
          'entity_id' => $data['user']->id(),
        ]);

      if (empty($user_memberships)) {
        continue;
      }

      $user_groups = array_map(function ($user_membership) {
        return $user_membership->getGroup()
          ->toLink()
          ->toString();
      }, $user_memberships);

      $replacements[$tokens[$find_token_name]] = Markup::create(implode(', ', $user_groups));
    }

    return $replacements;
  }

}
