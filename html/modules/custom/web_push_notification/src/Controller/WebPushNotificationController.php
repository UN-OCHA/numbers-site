<?php

namespace Drupal\web_push_notification\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\web_push_notification\Entity\Subscription;
use Drupal\web_push_notification\KeysHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Provides a push notification responses.
 */
class WebPushNotificationController extends ControllerBase {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Keys helper.
   *
   * @var \Drupal\web_push_notification\KeysHelper
   */
  protected $keysHelper;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * WebPushNotificationController constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   The module handler service.
   * @param \Drupal\web_push_notification\KeysHelper $keysHelper
   *   The notification keys helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(ModuleHandler $moduleHandler, KeysHelper $keysHelper, EntityTypeManagerInterface $entity_type_manager) {
    $this->moduleHandler = $moduleHandler;
    $this->keysHelper = $keysHelper;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('web_push_notification.keys_helper'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Gets the service worker javascript handler.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The service worker content.
   */
  public function serviceWorker() {
    $module_path = $this->moduleHandler->getModule('web_push_notification')->getPath();
    $uri = "{$module_path}/js/service_worker.js";

    if (!file_exists($uri)) {
      throw new NotFoundHttpException();
    }

    return BinaryFileResponse::create($uri, 200, [
      'Content-Type' => 'text/javascript',
      'Content-Length' => filesize($uri),
    ]);
  }

  /**
   * Subscribe.
   */
  public function subscribe(Request $request) {
    // Cannot accept a user confirmation when push keys are empty.
    if (!$this->keysHelper->isKeysDefined()) {
      throw new ServiceUnavailableHttpException();
    }

    $subscription = FALSE;
    $input = json_decode($request->getContent(), TRUE);
    $key = $input['key'];
    $token = $input['token'];
    $endpoint = $input['endpoint'];
    $para_id = $input['para_id'] ?? '';

    if (!empty($key) && !empty($token)) {
      $ids = $this->entityTypeManager->getStorage('wpn_subscription')->getQuery()
        ->condition('key', $key)
        ->condition('token', $token)
        ->execute();
      if (empty($ids)) {
        $subscription = Subscription::create([
          'key' => $key,
          'token' => $token,
          'endpoint' => $endpoint,
          'para_ids' => $para_id,
        ]);
        $subscription->save();
      }
      else {
        $subscription = Subscription::load(reset($ids));
        if (!empty($para_id)) {
          $para_ids = $subscription->para_ids->value;
          $para_ids = explode(',', $para_ids);
          $para_ids[] = $para_id;
          $para_ids = array_unique($para_ids);
          $subscription->para_ids = implode(',', $para_ids);
          $subscription->save();
        }
      }
      if ($subscription) {
        return new JsonResponse(['para_ids' => $subscription->para_ids->value]);
      }
    }
    else {
      throw new BadRequestHttpException();
    }

    return new JsonResponse(['status' => TRUE]);
  }

  /**
   * Remove a paragraph id.
   */
  public function unsubscribe(Request $request) {
    // Cannot accept a user confirmation when push keys are empty.
    if (!$this->keysHelper->isKeysDefined()) {
      throw new ServiceUnavailableHttpException();
    }

    $input = json_decode($request->getContent(), TRUE);
    $key = $input['key'];
    $token = $input['token'];
    $endpoint = $input['endpoint'];
    $para_id = $input['para_id'];

    if (!empty($key) && !empty($token) && !empty($endpoint)) {
      $ids = $this->entityTypeManager->getStorage('wpn_subscription')->getQuery()
        ->condition('key', $key)
        ->condition('token', $token)
        ->execute();
      if (!empty($ids)) {
        $subscription = Subscription::load(reset($ids));
        $para_ids = $subscription->para_ids->value;
        $para_ids = explode(',', $para_ids);

        if ($index = array_search($para_id, $para_ids)) {
          unset($para_ids[$index]);
          $subscription->para_ids = implode(',', $para_ids);
          $subscription->save();
        }
      }
    }
    else {
      throw new BadRequestHttpException();
    }

    return new JsonResponse(['status' => TRUE]);
  }

  /**
   * Add a pragraph id.
   */
  public function update(Request $request) {
    // Cannot accept a user confirmation when push keys are empty.
    if (!$this->keysHelper->isKeysDefined()) {
      throw new ServiceUnavailableHttpException();
    }

    $input = json_decode($request->getContent(), TRUE);
    $key = $input['key'];
    $token = $input['token'];

    if (!empty($key) && !empty($token)) {
      $ids = $this->entityTypeManager->getStorage('wpn_subscription')->getQuery()
        ->condition('key', $key)
        ->condition('token', $token)
        ->execute();
      if (empty($ids)) {
        throw new BadRequestHttpException();
      }
      else {
        $subscription = Subscription::load(reset($ids));
        return new JsonResponse(['para_ids' => $subscription->para_ids->value]);
      }
    }
    else {
      throw new BadRequestHttpException();
    }
  }

  /**
   * Return active paragraph ids.
   */
  public function info(Request $request) {
    // Cannot accept a user confirmation when push keys are empty.
    if (!$this->keysHelper->isKeysDefined()) {
      throw new ServiceUnavailableHttpException();
    }

    $input = json_decode($request->getContent(), TRUE);
    $key = $input['key'];
    $token = $input['token'];

    if (!empty($key) && !empty($token) && !empty($endpoint)) {
      $ids = $this->entityTypeManager->getStorage('wpn_subscription')->getQuery()
        ->condition('key', $key)
        ->condition('token', $token)
        ->execute();
      if (empty($ids)) {
        throw new BadRequestHttpException();
      }
      else {
        $subscription = Subscription::load(reset($ids));
        return new JsonResponse(['para_ids' => $subscription->para_ids->value]);
      }
    }
    else {
      throw new BadRequestHttpException();
    }

  }

}
