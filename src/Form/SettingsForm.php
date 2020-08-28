<?php

namespace Drupal\anonymous_subscriptions\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

  public static $configName = 'anonymous_subscription.settings';

  /**
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var Token
   */
  protected $token;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, Token $token) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('token')
    );
  }

  /**
   * Gets the configuration names that will be editable.
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [SettingsForm::$configName];
  }

  /**
   * Returns a unique string identifying the form.
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'anonymous_subscription_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(SettingsForm::$configName);


    $form['subscription_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Subscription settings'),
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_send_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send by default'),
      '#default_value' => $config->get('anonymous_subscriptions_send_default'),
      '#description' => 'Tick this is you want the default option to be send emails.',
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_limit_window'] = [
      '#type' => 'select',
      '#title' => $this->t('How long to block flood attempts.'),
      '#default_value' => $config->get('anonymous_subscriptions_limit_window'),
      '#options' => [
        60 => '1 minute',
        300 => '5 minutes',
        600 => '10 minutes'
      ],
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_ip_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('How many attempts before blocking a user from a single IP.'),
      '#default_value' => $config->get('anonymous_subscriptions_ip_limit'),
      '#options' => [
        5 => '5 attempts',
        10 => '10 attempts',
        20 => '20 attempts'
      ],
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Node types enabled for subscription notifications'),
      '#options' => node_type_get_names(),
      '#default_value' => $config->get('anonymous_subscriptions_node_types') ?: [],
    ];

    $form['email_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General email settings'),
    ];

    $form['email_fieldset']['anonymous_subscriptions_site_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site URL'),
      '#description' => $this->t('The URL you want to use as the base URL sent in e-mails. Tokens available'),
      '#default_value' => $config->get('anonymous_subscriptions_site_url'),
    ];

    $form['email_fieldset']['anonymous_subscriptions_sender'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender'),
      '#description' => 'Subscription send from. If empty default site email will be used.',
      '#default_value' => $config->get('anonymous_subscriptions_sender'),
    ];

    $form['email_fieldset']['anonymous_subscriptions_subject_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject text'),
      '#description' => 'Tokens are available',
      '#default_value' => $config->get('anonymous_subscriptions_subject_text'),
    ];

    $form['email_fieldset']['anonymous_subscriptions_body_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body text'),
      '#description' => 'Tokens are available',
      '#default_value' => $config->get('anonymous_subscriptions_body_text'),
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['email_fieldset']['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['site', 'node'],
      ];
    }

    $form['verify_email_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Verify email settings'),
    ];

    $form['verify_email_fieldset']['anonymous_subscriptions_verify_subject_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject text'),
      '#description' => 'Tokens are available',
      '#default_value' => $config->get('anonymous_subscriptions_verify_subject_text'),
    ];

    $confirmation_email_description = $this->t("You can use the following replacement keywords: <br>
      <b>@type</b> - Node type machine name<br>
      <b>@confirm_ulr</b> - Url to confirm subscription<br>
      <b>@decline_url</b> - Url to decline subscription<br>
      ");
    $form['verify_email_fieldset']['anonymous_subscriptions_verify_body_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body text'),
      '#description' => $confirmation_email_description,
      '#default_value' => $config->get('anonymous_subscriptions_verify_body_text'),
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['verify_email_fieldset']['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['site'],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $url = $this->token->replace($form_state->getValue('anonymous_subscriptions_site_url'));
    $url = parse_url($url);
    if(!isset($url) || empty($url) || !isset($url['scheme']) || empty($url['scheme']) ) {
      $form_state->setError($form['email_fieldset']['anonymous_subscriptions_site_url'], $this->t('Please insert a valid URL or Token.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $confObject = $this->config(SettingsForm::$configName);
    foreach ($form_state->getValues() as $key => $value) {
      $confObject->set($key, $value);
    }
    $confObject->save();
    parent::submitForm($form, $form_state);
  }

}
