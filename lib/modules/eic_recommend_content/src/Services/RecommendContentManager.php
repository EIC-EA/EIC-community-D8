<?php

namespace Drupal\eic_recommend_content\Services;

use Drupal\Component\Utility\EmailValidator;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_content\Services\EntityTreeManager;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_search\Search\Sources\UserRecommendSourceType;
use Drupal\flag\Entity\Flag;
use Drupal\flag\FlagCountManagerInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\group_flex\GroupFlexGroupType;
use Drupal\oec_group_flex\OECGroupFlexHelper;

/**
 * Provides a service to manage content recommendations.
 */
class RecommendContentManager {

  use StringTranslationTrait;

  /**
   * Array of allowed HTML tags to send on the recommendation email.
   */
  const RECOMMEND_ALLOWED_HTML_TAGS = ['a', 'em', 'u', 's', 'p', 'br', 'strong'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The OEC Group Flex helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * The email validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidator
   */
  protected $emailValidator;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The flag count manager service.
   *
   * @var \Drupal\flag\FlagCountManagerInterface
   */
  protected $flagCountManager;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $eicGroupsHelper;

  /**
   * The group type flex service.
   *
   * @var \Drupal\group_flex\GroupFlexGroupType
   */
  protected $groupTypeFlex;

  /**
   * Constructs a RecommendContentManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $oec_group_flex_helper
   *   The OEC Group Flex helper service.
   * @param \Drupal\Component\Utility\EmailValidator $email_validator
   *   The email validator service.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\flag\FlagCountManagerInterface $flag_count_manager
   *   The flag count manager service.
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The EIC Groups helper service.
   * @param \Drupal\group_flex\GroupFlexGroupType $group_type_flex
   *   The group type flex service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    OECGroupFlexHelper $oec_group_flex_helper,
    EmailValidator $email_validator,
    FlagServiceInterface $flag_service,
    FlagCountManagerInterface $flag_count_manager,
    EICGroupsHelper $eic_groups_helper,
    GroupFlexGroupType $group_type_flex
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
    $this->emailValidator = $email_validator;
    $this->flagService = $flag_service;
    $this->flagCountManager = $flag_count_manager;
    $this->eicGroupsHelper = $eic_groups_helper;
    $this->groupTypeFlex = $group_type_flex;
  }

  /**
   * Gets recommend content link as renderable array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return array|null
   *   The renderable array of recommend content link.
   */
  public function getRecommendContentLink(EntityInterface $entity) {
    if (!$this->isRecommendationEnabled($entity)) {
      return NULL;
    }

    $support_entity_types = self::getSupportedEntityTypes();
    $flag = $this->flagService->getFlagById(
      $support_entity_types[$entity->getEntityTypeId()]
    );

    $endpoint_url = Url::fromRoute(
      'eic_recommend_content.recommend',
      [
        'entity_type' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
      ]
    );
    $can_recommend = TRUE;
    $can_recommend_external_users = TRUE;

    $flag_counter = $this->flagCountManager->getEntityFlagCounts($entity);
    if (!isset($flag_counter[$flag->id()])) {
      $flag_counter[$flag->id()] = 0;
    }

    $user_url = Url::fromRoute('eic_search.solr_search', [
      'datasource' => json_encode(['user']),
      'source_class' => UserRecommendSourceType::class,
      'page' => 1,
    ])->toString();

    $get_users_url_parameters = [
      'endpoint' => $user_url,
      'target_entity' => 'user',
    ];

    switch ($entity->getEntityTypeId()) {
      case 'node':
        if (!$endpoint_url->access($this->currentUser)) {
          $can_recommend = FALSE;
        }

        if ($group = $this->eicGroupsHelper->getOwnerGroupByEntity($entity)) {
          $get_users_url_parameters['current_group'] = $group->id();
          $can_recommend_external_users = $this->canRecommendExternalUsers($group);
        }
        break;

      case 'group':
        if (!$endpoint_url->access($this->currentUser)) {
          $can_recommend = FALSE;
        }

        $can_recommend_external_users = $this->canRecommendExternalUsers($entity);
        $get_users_url_parameters['current_group'] = $entity->id();
        break;

    }

    return ($endpoint_url && $can_recommend) ? [
      '#theme' => 'eic_recommend_content_link',
      '#entity_type' => $entity->getEntityTypeId(),
      '#entity_id' => $entity->id(),
      '#endpoint' => $endpoint_url->toString(),
      '#can_recommend' => $can_recommend,
      '#can_recommend_external_users' => $can_recommend_external_users,
      '#translations' => [
        'link_label' => $this->t('Recommend'),
        'modal_title' => $this->t('Recommend this content'),
        'modal_description' => $can_recommend_external_users ?
          $this->t('Select exisiting members or people outside of the platform you wish to recommend this content to. Those will be presented to you as long as they have the permission to view the content you want to share.') :
          $this->t('Select exisiting members of the platform you wish to recommend this content to. Those will be presented to you as long as they have the permission to view the content you want to share.'),
        'modal_success_title' => $this->t('Recommendation sent!'),
        'modal_success_description' => $this->t("
          <p>The content you've recommended was successfully sent to the users you've selected. They will receive an email notification informing them of this action.</p>
          <p>You can always recommend this content again at a later stage.</p>"
        ),
      ],
      '#tree_settings' => $get_users_url_parameters,
      '#tree_translations' => ['your_values' => $this->t('Selected members')->render()] + EntityTreeManager::getTranslationsWidget(),
    ] : NULL;
  }

  /**
   * Recommends content to another user(s).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that will be recommended.
   * @param \Drupal\user\UserInterface[] $users
   *   The user accounts to recommend.
   * @param array $external_emails
   *   Array of external emails to recommend.
   * @param string|null $message
   *   The message that goes in the recommendation.
   */
  public function recommend(
    EntityInterface $entity,
    array $users = [],
    array $external_emails = [],
    ?string $message = ''
  ) {
    if (empty($users) && empty($external_emails)) {
      throw new \InvalidArgumentException('You need to provide users to recommend this content to.');
    }

    $user_emails = [];
    // Get platform user emails.
    foreach ($users as $user) {
      if (!$entity->access('view', $user)) {
        continue;
      }

      $user_emails[] = $user->getEmail();
    }

    $support_entity_types = self::getSupportedEntityTypes();
    if (!array_key_exists($entity->getEntityTypeId(), $support_entity_types)) {
      throw new \InvalidArgumentException('Content recommendation is not enabled for this entity.');
    }

    $flag = $this->flagService->getFlagById(
      $support_entity_types[$entity->getEntityTypeId()]
    );

    if (!$flag instanceof Flag) {
      throw new \InvalidArgumentException('Content recommendation is not enabled for this entity.');
    }

    switch ($entity->getEntityTypeId()) {
      case 'node':
      case 'group':
        if (!$this->canRecommendExternalUsers($entity)) {
          break;
        }

        // Grab external emails.
        foreach ($external_emails as $external_email) {
          if (!$this->emailValidator->isValid($external_email)) {
            continue;
          }
          $user_emails[] = $external_email;
        }
        break;

    }

    $flagging = $this->entityTypeManager->getStorage('flagging')->create([
      'uid' => $this->currentUser->id(),
      'session_id' => NULL,
      'flag_id' => $flag->id(),
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'global' => $flag->isGlobal(),
      'field_recommend_emails' => implode(',', $user_emails),
      'field_recommend_message' => [
        'value' => Markup::create(Xss::filter($message, self::RECOMMEND_ALLOWED_HTML_TAGS)),
        'format' => 'basic_text',
      ],
    ]);
    $flagging->save();
    // Invalidate entity cache.
    Cache::invalidateTags($entity->getCacheTagsToInvalidate());
  }

  /**
   * Checks if an entity can be recommended to external users.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   TRUE if entity can be recommended to external users.
   */
  public function canRecommendExternalUsers(EntityInterface $entity) {
    $allowed_group_visibilities = [
      'public',
    ];
    $can_recommend = TRUE;

    switch ($entity->getEntityTypeId()) {
      case 'node':
        /** @var \Drupal\group\Entity\GroupInterface $group */
        $group = $this->eicGroupsHelper->getOwnerGroupByEntity($entity);

        if (empty($group)) {
          break;
        }

        // External users cannot be recommended if group doesn't use group
        // flex feature.
        if (!$this->groupTypeFlex->hasFlexibleGroupTypeVisibility($group->getGroupType())) {
          $can_recommend = FALSE;
          break;
        }

        $visibility_settings = $this->oecGroupFlexHelper->getGroupVisibilitySettings($group);
        if (!in_array($visibility_settings['plugin_id'], $allowed_group_visibilities)) {
          $can_recommend = FALSE;
        }
        break;

      case 'group':
        // External users cannot be recommended if group doesn't use group
        // flex feature.
        if (!$this->groupTypeFlex->hasFlexibleGroupTypeVisibility($entity->getGroupType())) {
          $can_recommend = FALSE;
          break;
        }

        $visibility_settings = $this->oecGroupFlexHelper->getGroupVisibilitySettings($entity);
        if (!in_array($visibility_settings['plugin_id'], $allowed_group_visibilities)) {
          $can_recommend = FALSE;
        }
        break;

    }

    return $can_recommend;
  }

  /**
   * Gets the list of supported entity types followed by the flag machine name.
   *
   * @return array
   *   Array of supported entity types.
   */
  public static function getSupportedEntityTypes() {
    return [
      'node' => 'recommend_content',
      'group' => 'recommend_content_group',
    ];
  }

  /**
   * Check if a given entity can be recommended.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   TRUE if entity can be recommended.
   */
  public function isRecommendationEnabled(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();

    $support_entity_types = RecommendContentManager::getSupportedEntityTypes();
    if (!array_key_exists($entity_type, $support_entity_types)) {
      return FALSE;
    }

    $flag = $this->flagService->getFlagById(
      $support_entity_types[$entity_type]
    );

    if (!$flag instanceof Flag) {
      return FALSE;
    }

    $enabled_bundles = $flag->getBundles();
    // Check if entity bundle is enabled in the flag.
    if (
      !empty($enabled_bundles) &&
      !in_array($entity->bundle(), $enabled_bundles)
    ) {
      return FALSE;
    }

    return TRUE;
  }

}
