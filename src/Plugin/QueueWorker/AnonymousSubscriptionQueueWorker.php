<?php

/**
 * Handles sending of email.
 */

namespace Drupal\anonymous_subscriptions\Plugin\QueueWorker;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 * id = "anonymous_subscriptions_queue",
 * title = "Anonymous subscription email sending queue worker",
 * cron = {"time" = 10}
 * )
 */
class AnonymousSubscriptionQueueWorker extends QueueWorkerBase  implements ContainerFactoryPluginInterface {

  /**
   * @var DefaultService
   */
  protected $subscriptionService;

  /**
   * Constructs a new ScheduledTransitionJob.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param DefaultService $subscriptionService
   *   Default subscription service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, DefaultService $subscriptionService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->subscriptionService = $subscriptionService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('anonymous_subscriptions.default')
    );
  }

  /**
   * Processes a single item of Queue.
   * Finds if the creating or update of a certain node must trigger the notification email sending.
   *
   * @param mixed $data
   */
  public function processItem($data) {
    $this->subscriptionService->sendMail($data);
  }

}
