<?php

namespace Drupal\anonymous_subscriptions\Form;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SubscriptionCancelForm extends ConfirmFormBase {

  /**
   * @var DefaultService
   */
  protected $subscriptionService;

  /**
   * @var Subscription
   */
  protected $subscription;

  /**
   * Constructs a SubscriptionCancelForm object.
   *
   * @param \Drupal\anonymous_subscriptions\DefaultService $subscription_service
   *   Subscription server.
   */
  public function __construct(DefaultService $subscription_service) {
    $this->subscriptionService = $subscription_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('anonymous_subscriptions.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Subscription $subscription = NULL, $code = NULL) {
    if (strcmp($subscription->code->value, $code) === 0) {
      $this->subscription = $subscription;
      $form = parent::buildForm($form, $form_state);
      $form['description'] = [
        '#type' => 'container',
        'markup' => [
          '#markup' => '<p>' . $this->getDescription() . '</p>',
        ],
      ];

      $form['actions']['cancel']['#attributes'][] = 'button';
      return $form;
    }
    else {
      $url = Url::fromRoute('<front>');
      $response = new RedirectResponse($url->toString());
      $response->send();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->subscription->delete();
    $url = Url::fromRoute('<front>');
    $response = new RedirectResponse($url->toString());
    $response->send();
    $type = $this->subscription->type->value;
    $this->messenger()->addStatus($this->t('Your email was removed from subscription for @subscription_subject', [
      '@type' => $type,
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "anonymous_subscriptions_confirm_cancel";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->getTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $type = $this->subscription->type->value;
    return $this->t('Do you want to cancel your subscription for @type ?', [
      '@type' => $type,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Cancelling subscription');
  }
}
