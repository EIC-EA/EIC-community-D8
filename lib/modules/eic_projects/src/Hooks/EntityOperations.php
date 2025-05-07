<?php

namespace Drupal\eic_projects\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

use Drupal\eic_projects\Service\FundingTenderPortalSearch;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityOperations implements ContainerInjectionInterface {


  public function __construct(protected FundingTenderPortalSearch $fundingTenderPortalSearch) {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_projects.ftp_search'),
    );
  }

  public function projectGroupPreSave(GroupInterface $group) {
    if ($group->get('field_project_horizon_results')->isEmpty()) {
      $portal_url = $this->fundingTenderPortalSearch->getPortalUrl($group->id());
      if ($portal_url) {
        $link = [
          'uri' => $portal_url,
          'title' => 'Horizon Results Platform',
        ];
        $group->set('field_project_horizon_results', $link);
      }
    }
  }


}
