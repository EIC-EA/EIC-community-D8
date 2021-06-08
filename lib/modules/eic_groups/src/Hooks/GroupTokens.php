<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
   * Constructs a new GroupTokens object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Implements hook_token_info().
   */
  public function tokenInfo() {
    return [
      'types' => [
        'eic_groups_tokens' => [
          'name' => $this->t('EIC Group tokens'),
          'description' => $this->t('Custom EIC Group tokens.'),
        ],
      ],
      'tokens' => [
        'eic_groups_tokens' => [
          'node_group_url' => [
            'name' => $this->t('Node group url'),
            'description' => $this->t('The url of the group this node belongs to'),
          ],
        ],
      ],
    ];
  }

  /**
   * Implements hook_tokens().
   */
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];
    if ($type == 'eic_groups_tokens') {
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
    return $replacements;
  }

}
