<?php

namespace Drupal\anonymous_subscriptions\Form;

use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SubscriptionCancelAllForm extends SubscriptionCancelForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = \Drupal::entityQuery('anonymous_subscriptions')
      ->condition('email', $this->subscription->email->value);
    $ids = $query->execute();

    $storage_handler = \Drupal::entityTypeManager()->getStorage("anonymous_subscriptions");
    $subscriptions = $storage_handler->loadMultiple($ids);
    $storage_handler->delete($subscriptions);

    $url = Url::fromRoute('<front>');
    $response = new RedirectResponse($url->toString());
    $response->send();

    $this->messenger()->addStatus($this->t('Your email was removed from all subscriptions.'));

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "anonymous_subscriptions_confirm_cancel_all";
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Do you want to cancel all subscriptions of email %email?', ['%email' => $this->subscription->email->value]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Cancelling all subscriptions');
  }
}
