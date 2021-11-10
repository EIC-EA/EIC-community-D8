<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\eic_seo\AliasCleaner;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
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
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Token $token_service,
    AliasCleaner $alias_cleaner,
    RequestStack $request_stack
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tokenService = $token_service;
    $this->eicAliasCleaner = $alias_cleaner;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('token'),
      $container->get('eic_seo.alias_cleaner'),
      $container->get('request_stack')
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
    ];
    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];
    $base_url = $this->requestStack->getCurrentRequest()->getBaseUrl();

    // Node tokens.
    if ($type === 'node' && !empty($data['node'])) {
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
    }

    // Group tokens.
    if ($type === 'group' && !empty($data['group']) && $data['group'] instanceof GroupInterface) {
      // Provide replacements for truncated title tokens.
      foreach ($this->tokenService->findWithPrefix($tokens, 'group_truncated_title') as $value => $token_name) {
        // Check that we have a numeric value and that it's not above 100.
        if (!is_numeric($value) || $value > 100) {
          $value = 100;
        }
        $customConfig = [
          'max_component_length' => $value,
        ];
        $replacements[$token_name] = $this->eicAliasCleaner->cleanString($data['group']->label(), [], $customConfig);
      }
    }
    return $replacements;
  }

}
