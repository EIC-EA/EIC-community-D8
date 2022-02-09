<?php

namespace Drupal\eic_comments\Plugin\Block;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Login block.
 *
 * @Block(
 *   id = "login_block",
 *   admin_label = @Translation("EIC Comment Login Block"),
 *   category = @Translation("Custom")
 * )
 */
class LoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $form['headline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Headline'),
      '#description' => $this->t('What would you like to have as headline?'),
      '#default_value' => isset($config['headline']) ? $config['headline'] : '',
    ];
    $form['login_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Log in button text'),
      '#description' => $this->t('Label for the Login button'),
      '#default_value' => isset($config['login_text']) ? $config['login_text'] : '',
    ];
    $form['register_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registration button text'),
      '#description' => $this->t('Label for the Registration button'),
      '#default_value' => isset($config['register_text']) ? $config['register_text'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('headline', $form_state->getValue('headline'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $title = $this->t('Please log in to see comments and contribute');
    if (!empty($config['headline'])) {
      $title = $config['headline'];
    }

    $login_text = $this->t('Log in');
    if (!empty($config['login_text'])) {
      $login_text = $config['login_text'];
    }

    $register_text = $this->t('Register');
    if (!empty($config['register_text'])) {
      $register_text = $config['register_text'];
    }

    $register_route = Url::fromRoute('user.register');
    $registration_link = Link::fromTextAndUrl($register_text, $register_route);

    $login_route = Url::fromRoute('user.login');
    $login_link = Link::fromTextAndUrl($login_text, $login_route);

    $build['title'] = $title;
    $build['login_link'] = $login_link->toRenderable();
    $build['login_link']['label'] = $login_link->getText();
    $build['login_link']['url'] = $login_link->getUrl();
    $build['registration_link'] = $registration_link->toRenderable();
    $build['registration_link']['label'] = $registration_link->getText();
    $build['registration_link']['url'] = $registration_link->getUrl();
    $build['#cache'] = [
      'contexts' => ['user'],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->nodeHasCommentsEnabled() && $account->isAnonymous()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Helper function to validate whether the node has comments enabled.
   *
   * @return bool
   *   Boolean whether node has comments with status 'open'.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function nodeHasCommentsEnabled(): bool {
    $node = $this->routeMatch->getParameter('node');

    if (FALSE === $node instanceof NodeInterface) {
      return FALSE;
    }

    switch ($node->bundle()) {
      case 'wiki_page':
        // Currently wiki pages don't have comments.
        // @todo We need to check if the comments should work for wiki pages or
        // not.
        return FALSE;

    }

    if (!$node->hasField('field_comments')) {
      return FALSE;
    }

    $field_comments = $node->get('field_comments')->first()->getValue();
    $status = intval($field_comments['status']);

    if ($status !== CommentItemInterface::OPEN) {
      return FALSE;
    }

    return TRUE;
  }

}
