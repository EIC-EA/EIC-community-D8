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
   * Constructs a new GroupTokens object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The Token service.
   * @param \Drupal\eic_seo\AliasCleaner $alias_cleaner
   *   The EIC AliasCleaner service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Token $token_service, AliasCleaner $alias_cleaner) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tokenService = $token_service;
    $this->eicAliasCleaner = $alias_cleaner;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('token'),
      $container->get('eic_seo.alias_cleaner')
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

    // Node tokens.
    if ($type == 'node' && !empty($data['node'])) {
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'node_group_url':
            if (isset($data['node'])) {
              if (($node = $data['node']) && $node instanceof NodeInterface) {
                // If node belongs to a group.
                if ($group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($node)) {
                  /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
                  $group_content = reset($group_contents);

                  if ($group_content->hasTranslation($node->language()->getId())) {
                    $group = $group_content->getGroup()->getTranslation($node->language()->getId());
                  }
                  else {
                    $group = $group_content->getGroup();
                  }

                  $replacements[$original] = $group->toUrl()->toString();
                }
              }
            }
            break;

        }

      }
    }

    // Group tokens.
    if ($type == 'group' && !empty($data['group']) && $data['group'] instanceof GroupInterface) {
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
