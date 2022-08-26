<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\eic_topics\Constants\Topics;
use Drupal\eic_user\UserHelper;
use Drupal\eic_webservices\Utility\EicWsHelper;
use Drupal\eic_webservices\Utility\SmedTaxonomyHelper;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\ResourceResponse;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to update users through a POST method.
 *
 * Using the EntityResource is currently not possible due to an issue with group
 * module.
 *
 * @see https://www.drupal.org/project/group/issues/2872645
 *
 * @RestResource(
 *   id = "eic_webservices_profile_update",
 *   label = @Translation("EIC Profile Update Resource"),
 *   uri_paths = {
 *     "canonical" = "/smed/api/v1/profile",
 *     "create" = "/smed/api/v1/profile"
 *   }
 * )
 */
class EicProfileUpdateResource extends ResourceBase {

  /**
   * The EIC Webservices helper class.
   *
   * @var \Drupal\eic_webservices\Utility\EicWsHelper
   */
  protected $wsHelper;

  /**
   * @var \Drupal\eic_user\UserHelper
   */
  private $userHelper;

  /**
   * The SMED taxonomy helper class.
   *
   * @var \Drupal\eic_webservices\Utility\SmedTaxonomyHelper
   */
  protected $smedTaxonomyHelper;

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
   * @param EicWsHelper $ws_helper
   *   The webservice helper service.
   * @param UserHelper $user_helper
   *   The user helper service.
   * @param SmedTaxonomyHelper $smed_taxonomy_helper
   *   The smed taxonomy helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerChannelInterface $logger,
    EicWsHelper $ws_helper,
    UserHelper $user_helper,
    SmedTaxonomyHelper $smed_taxonomy_helper
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );

    $this->wsHelper = $ws_helper;
    $this->userHelper = $user_helper;
    $this->smedTaxonomyHelper = $smed_taxonomy_helper;
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
      $container->get('eic_webservices.ws_helper'),
      $container->get('eic_user.helper'),
      $container->get('eic_webservices.taxonomy_helper')
    );
  }

  /**
   * @param array $data
   *
   * @return \Drupal\rest\ModifiedResourceResponse|\Drupal\rest\ResourceResponse
   */
  public function post(array $data) {
    try {
      $smed_id = $data['smed_id'] ?? NULL;

      if (empty($smed_id)) {
        return new ResourceResponse(['message' => "You need to provide smed_id"], Response::HTTP_BAD_REQUEST);
      }

      $user = $this->wsHelper->getUserBySmedId($smed_id);

      if (!$user instanceof UserInterface) {
        return new ResourceResponse(
          ['message' => "Cannot find user with smed_id: $smed_id"], Response::HTTP_BAD_REQUEST
        );
      }

      $profile = $this->userHelper->getUserMemberProfile($user);

      if (!$profile instanceof ProfileInterface) {
        return new ResourceResponse(
          ['message' => "Cannot find a profile linked to the user."],
          Response::HTTP_BAD_REQUEST
        );
      }

      $about = $data['about'] ?? NULL;
      $interests = $data['interests'] ?? NULL;
      $expertises = $data['expertises'] ?? NULL;
      $country = $data['country'] ?? NULL;
      $city = $data['city'] ?? NULL;
      $linkedin = $data['social_linkedin'] ?? NULL;
      $twitter = $data['social_twitter'] ?? NULL;
      $facebook = $data['social_facebook'] ?? NULL;


      $profile->set('field_body', [
        'value' => $about,
        'format' => 'full_html',
      ])->save();

      $interests = array_map(function ($interest) {
        return $this->smedTaxonomyHelper->getTaxonomyTermIdBySmedId(
          $interest['id'],
          Topics::TERM_VOCABULARY_TOPICS_ID
        );
      }, $interests);

      $expertises = array_map(function ($expertise) {
        return $this->smedTaxonomyHelper->getTaxonomyTermIdBySmedId(
          $expertise['id'],
          Topics::TERM_VOCABULARY_TOPICS_ID
        );
      }, $expertises);

      $profile->set('field_vocab_topic_expertise', Term::loadMultiple($expertises))->save();
      $profile->set('field_vocab_topic_interest', Term::loadMultiple($interests))->save();
      $profile->set('field_location_address', [
        'country_code' => $country,
        'locality' => $city,
      ])->save();
      $profile->set('field_social_links', [
        [
          'social' => 'facebook',
          'link' => str_replace('https://www.facebook.com/', '', $facebook),
        ],
        [
          'social' => 'twitter',
          'link' => str_replace('https://twitter.com/', '', $twitter),
        ],
        [
          'social' => 'linkedin',
          'link' => str_replace('https://www.linkedin.com/', '', $linkedin),
        ],
      ])->save();

      return new ModifiedResourceResponse(['message' => 'Profile updated successfully.']);
    } catch (\Exception $exception) {
      return new ResourceResponse(['message' => $exception->getMessage()], $exception->getCode());
    }

  }

}
