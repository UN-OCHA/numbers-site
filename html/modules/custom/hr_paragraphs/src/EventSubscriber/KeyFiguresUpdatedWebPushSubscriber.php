<?php

namespace Drupal\hr_paragraphs\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ocha_key_figures\Event\KeyFiguresUpdated;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\web_push_notification\NotificationItem;
use Drupal\web_push_notification\NotificationQueue;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts on Social Auth events.
 */
class KeyFiguresUpdatedWebPushSubscriber implements EventSubscriberInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Queue.
   *
   * @var \Drupal\web_push_notification\NotificationQueue
   */
  protected $queue;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Queue.
   * @param \Drupal\web_push_notification\NotificationQueue $queue
   *   Queue.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, NotificationQueue $queue) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    if (class_exists('Drupal\ocha_key_figures\Event\KeyFiguresUpdated')) {
      $events[KeyFiguresUpdated::EVENT_UPDATED] = [
        'onDataUpdated',
      ];
    }

    return $events;
  }

  /**
   * Queue notifications.
   *
   * @param \Drupal\ocha_key_figures\Event\KeyFiguresUpdated $event
   *   The event object.
   */
  public function onDataUpdated(KeyFiguresUpdated $event) {
    $data = $event->data;
    if (!isset($data['paragraph'])) {
      return;
    }

    foreach ($data['paragraph'] as $id => $info) {
      // Get parent.
      $parent = $this->entityTypeManager->getStorage('paragraph')->load($id);
      while ($parent instanceof Paragraph) {
        $parent = $parent->getParentEntity();
      }

      // Get first group: new or updated.
      $figure = reset($info);

      // Get first figure.
      $figure = reset($figure);

      $query = $this->entityTypeManager->getStorage('wpn_subscription')->getQuery();
      $query->condition('para_ids', '|' . $id . '|', 'CONTAINS');

      $start = 0;
      $limit = 30;

      while ($ids = $query->range($start, $limit)->execute()) {
        $item = new NotificationItem();
        $item->ids = $ids;
        $item->title = $figure['country'] . ' - ' . $figure['name'] . ': ' . $figure['value'];
        $item->body = 'Some numbers are updated on ' . $parent->label();
        $item->url = $parent->toUrl('canonical', ['absolute' => TRUE])->toString();

        $this->queue->getQueue()->createItem($item);
        $start += $limit;
      }
    }
  }

}
