<?php
/**
 * @file
 * Contains \Drupal\anonymous_subscriptions\Form\SubscribeForm.
 */


namespace Drupal\anonymous_subscriptions\Form;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubscribeForm extends FormBase {

  /**
   * @var DefaultService
   */
  protected $subscriptionService;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DefaultService $subscription_service, EntityTypeManager $entityTypeManager) {
    $this->setConfigFactory($config_factory);
    $this->subscriptionService = $subscription_service;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('anonymous_subscriptions.default'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_subscriptions_subscribe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
    $config = $this->configFactory->get(SettingsForm::$configName);
    $valid_types = $config->get('anonymous_subscriptions_node_types') ?: [];
    if (empty($valid_types[$type])) {
      throw new NotFoundHttpException();
    }

    $description = $this->t('You are going to subscribe for updates on content type @type.', [
      '@type' => $type,
    ]);
    $form['description'] = array(
      '#type' => 'markup',
      '#markup' => '<p>' . $description . '</p>',
    );

    $form['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Your email'),
      '#required' => TRUE,
    );

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
    ];

    $form['type'] = [
      '#type' => 'hidden',
      '#default_value' => $type,
    ];

    $form['#tree'] = TRUE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $type = $form_state->getValue('type');

    $query = \Drupal::entityQuery('anonymous_subscription')
      ->condition('email', $email);
    if (!empty($type)) {
      $query->condition('type', $type);
    }
    $ids = $query->execute();
    if (!empty(Subscription::loadMultiple($ids))) {
      $form_state->setError($form['email'], $this->t('Email address @email already subscribed for updates on content type @type', [
        '@email' => $email,
        '@type' => $type,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $type = $form_state->getValue('type');
    /** @var Subscription $subscription */
    $subscription = Subscription::create([
      'email' => $email,
      'code' => Crypt::randomBytesBase64(20),
      'type' => $type,
    ]);
    $subscription->save();

    $tokens = [
      '@type',
      '@confirm_url',
      '@decline_url',
    ];

    $replacements = [
      $type,
      $this->subscriptionService->getConfirmUrl($subscription),
      $this->subscriptionService->getUnsubscribeUrl($subscription),
    ];

    $config = $this->configFactory->get(SettingsForm::$configName);
    if ($this->subscriptionService->sendMail([
      'to' => $subscription->email->value,
      'from' => $this->subscriptionService->getSender(),
      'subject' => str_replace($tokens, $replacements, $config->get("anonymous_subscriptions_verify_subject_text")),
      'body' => str_replace($tokens, $replacements, $config->get("anonymous_subscriptions_verify_body_text")),
    ])) {
      $this->messenger()->addStatus($this->t('There was sent confirmation email with your subscription to your address. Please confirm your subscription by using confirmation url from this email.'));
    }
    else {
      $this->messenger()->addError($this->t('Something went wrong. Please contact website administrator to check your subscription.'));
    }
  }
}
