<?php

namespace Drupal\hr_paragraphs\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts on config import.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Mail manager.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * SocialAuthSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config.
   * @param \Drupal\Core\Mail\MailManager $mail_manager
   *   The mail manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManager $mail_manager) {
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE] = [
      'onConfigSave',
    ];

    $events[ConfigEvents::DELETE] = [
      'onConfigDelete',
    ];

    $events[ConfigEvents::IMPORT] = [
      'onConfigImport',
    ];

    return $events;
  }

  /**
   * Set name to mail.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    \Drupal::logger('onConfigSave')->notice($config->getName());
  }

  public function onConfigDelete(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    \Drupal::logger('onConfigDelete')->notice($config->getName());
  }

  public function onConfigImport(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    \Drupal::logger('onConfigImport')->notice($config->getName());
  }

  /**
   * Set name to mail.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config event.
   */
  public function xonConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    \Drupal::logger('my_module')->notice($config->getName());
    if ($config->get('module') === 'views') {
      \Drupal::logger('my_module')->notice($config->getName());
      $view = \Drupal::languageManager()->getLanguageConfigOverride('fr', $config->getName());
//      $view->set('display.page_1.display_options.menu.title', t($config->get('display.page_1.display_options.menu.title')));
//      $view->save();
    }
  }


}
