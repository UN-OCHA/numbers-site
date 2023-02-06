<?php

namespace Drupal\web_push_notification;

use Drupal\Component\Serialization\Json;

/**
 * Notification queue item.
 */
class NotificationItem {

  /**
   * The list of Subscription entity ID.
   *
   * @var int[]
   */
  public $ids = [];

  /**
   * The notification title.
   *
   * @var string
   */
  public $title = '';

  /**
   * The notification message (body).
   *
   * @var string
   */
  public $body = '';

  /**
   * The notification url.
   *
   * @var string
   */
  public $url = '';

  /**
   * The notification image/icon.
   *
   * @var string
   */
  public $icon = '';

  /**
   * Bundle name.
   *
   * @var string
   */
  public $bundle = '';

  /**
   * NotificationItem constructor.
   *
   * @param string $title
   *   The notification title.
   * @param string $body
   *   The notification message (body).
   */
  public function __construct($title = '', $body = '') {
    $this->title = $title;
    $this->body = $body;
  }

  /**
   * Converts the item to a web push payload.
   *
   * @return string
   *   A JSON encoded payload.
   */
  public function payload() {
    return Json::encode([
      'title' => $this->title,
      'body' => $this->body,
      'url' => $this->url,
      'icon' => $this->icon,
    ]);
  }

}
