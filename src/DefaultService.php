<?php

namespace Drupal\anonymous_subscriptions;
use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\anonymous_subscriptions\Form\SettingsForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Class DefaultService.
 */
class DefaultService {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * Drupal\mailsystem\MailsystemManager definition.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new DefaultService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Mail\MailManagerInterface $manager_mail
   *   The Mail manager objects.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $manager_mail, LanguageManagerInterface $languageManager, LoggerInterface $logger) {
    $this->configFactory = $config_factory;
    $this->mailManager = $manager_mail;
    $this->logger = $logger;
    $this->languageManager = $languageManager;
  }

  /**
   * Simply send mail function.
   *
   * @param array $message with keys
   * - to
   * - from
   * - body
   * - subject
   */
  public function sendMail($message) {
    $siteName = $this->configFactory->get('system.site')->get('name');

    $to = $message['to'];
    $from = empty($message['from']) ? $this->getSender() : $message['from'];
    $params['from'] = $siteName . ' <' . $from . '>';
    $params['subject'] = $message['subject'];
    $params['body'] = $message['body'];

    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $result = $this->mailManager->mail('anonymous_subscriptions', 'anonymous_subscriptions_key', $to, $langcode, $params);

    if ($result['result'] !== TRUE) {
      $this->logger->warning(t('There was a problem sending email to %email', ['%email' => $to]));
      return FALSE;
    }
    return TRUE;
  }

  public function getUnsubscribeUrl(Subscription $subscription, $all = FALSE) {
    if ($all) {
      return Url::fromRoute('anonymous_subscriptions.cancel_all_subscriptions', [
        'subscription' => $subscription->id(),
        'code' => $subscription->code->value
      ])->setAbsolute()->toString();

    }
    return Url::fromRoute('anonymous_subscriptions.cancel_subscription', [
      'subscription' => $subscription->id(),
      'code' => $subscription->code->value
    ])->setAbsolute()->toString();
  }

  public function getConfirmUrl(Subscription $subscription) {
    return Url::fromRoute('anonymous_subscriptions.verify_subscription', [
      'subscription' => $subscription->id(),
      'code' => $subscription->code->value
    ])->setAbsolute()->toString();
  }

  public function getSender() {
    $site_email = $this->configFactory->get('system.site')->get('email');
    $sender_email = $this->configFactory->get(SettingsForm::$configName)->get('anonymous_subscriptions_sender');
    return empty($sender_email) ? $site_email : $sender_email;
  }

}
