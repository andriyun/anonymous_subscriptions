<?php

namespace Drupal\anonymous_subscriptions\Controller;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;

/**
 * Class SubscriptionController.
 */
class SubscriptionController extends ControllerBase {

  /**
   * @var DefaultService
   */
  protected $subscriptionService;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new SubscriptionController.
   *
   * @param DefaultService $subscriptionService
   *   Default subscription service.
   */
  public function __construct(DefaultService $subscriptionService, Token $token) {
    $this->subscriptionService = $subscriptionService;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('anonymous_subscriptions.default'),
      $container->get('token')
    );
  }

  /**
   * Subscription verifying page title callback.
   */
  public function verifyTitle() {
    return $this->t('Subscription verifying');
  }

  /**
   * {@inheritdoc}
   */
  public function verify(Subscription $subscription, $code) {
    if (strcmp($subscription->code->value, $code) === 0) {
      $subscription->set('verified', TRUE);
      $subscription->save();
      $status = $this->t('Your subscription is confirmed');
      $type = $subscription->type->value;
      $subject = $this->t('@site_name - Subscription confirmed', [
        '@site_name' => $this->config('system.site')->get('name')
      ]);
      $body = $this->t("You are now subscribed to receive updates on @subscription_subject.\n\rTo unsubscribe please visit @unsubscribe_url", [
        '@type' => $type,
        '@unsubscribe_url' => $this->subscriptionService->getUnsubscribeUrl($subscription),
      ]);
      $subject = $this->token->replace($subject);
      $body = $this->token->replace($body);

      $this->subscriptionService->sendMail([
        'to' => $subscription->email->value,
        'from' => $this->subscriptionService->getSender(),
        'subject' => $subject,
        'body' => $body,
      ]);
    }
    else {
      $status = $this->t('We could not confirm your subscription');
    }
    return [
      '#theme' => 'anonymous_subscriptions_message',
      '#attributes' => ['class' => ['text']],
      '#message' => $status,
      '#link' => Link::fromTextAndUrl($this->t('Click here to return to homepage'), Url::fromRoute('<front>')),
    ];
  }

}
