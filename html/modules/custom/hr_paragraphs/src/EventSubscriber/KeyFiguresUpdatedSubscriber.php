<?php

namespace Drupal\hr_paragraphs\EventSubscriber;

use Drupal\Core\State\State;
use Drupal\ocha_key_figures\Event\KeyFiguresUpdated;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts on Social Auth events.
 */
class KeyFiguresUpdatedSubscriber implements EventSubscriberInterface {

  /**
   * State.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\State\State $state
   *   State.
   */
  public function __construct(State $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KeyFiguresUpdated::EVENT_UPDATED] = [
      'onDataUpdated',
    ];

    return $events;
  }

  /**
   * Set name to mail.
   *
   * @param \Drupal\ocha_key_figures\Event\KeyFiguresUpdated $event
   *   The Social Auth user fields event object.
   */
  public function onDataUpdated(KeyFiguresUpdated $event) {
    $data = $event->data;
    if (!isset($data['group'])) {
      return;
    }

    $combined = [];
    $existing = $this->state->get('hr_paragraphs_mailchimp_group_ids', []);

    if (empty($existing)) {
      $combined = $data['group'];
    }
    else {
      foreach ($data['group'] as $id => $info) {
        if (isset($existing[$id])) {
          $combined[$id]['new'] = array_merge($data['group'][$id]['new'], $existing[$id]['new']);
          $combined[$id]['updated'] = array_merge($data['group'][$id]['updated'], $existing[$id]['updated']);
        }
        else {
          $combined[$id] = $info;
        }
      }
    }

    $this->state->set('hr_paragraphs_mailchimp_group_ids', $combined);
  }

}
