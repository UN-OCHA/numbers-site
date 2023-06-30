<?php

namespace Drupal\web_push_notification\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Notification subscription entity.
 *
 * @ingroup web_push_notification
 *
 * @ContentEntityType(
 *   id = "wpn_subscription",
 *   label = @Translation("Web Push Notification subscription"),
 *   handlers = {
 *     "storage_schema" = "Drupal\web_push_notification\SubscriptionStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\web_push_notification\Entity\SubscriptionViewsData",
 *     "form" = {
 *       "delete" = "Drupal\web_push_notification\Form\SubscriptionDeleteForm",
 *     },
 *   },
 *   base_table = "wpn_subscriptions",
 *   data_table = "wpn_subscriptions_field_data",
 *   admin_permission = "administer web push notification subscriptions",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/services/web-push-notification/subscriptions/{wpn_subscription}/delete",
 *   }
 * )
 */
class Subscription extends ContentEntityBase implements SubscriptionInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicKey() {
    return $this->get('key')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublicKey($key) {
    $this->set('key', $key);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->get('token')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setToken($token) {
    $this->set('token', $token);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoint() {
    return $this->get('endpoint')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndpoint($endpoint) {
    $this->set('endpoint', $endpoint);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParaIds() {
    return $this->get('para_ids')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getParaIdsArray() {
    // Format is "|123|456|".
    $ids = $this->getParaIds();
    $ids = trim($ids, '|');
    return explode('|', $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function setParaIds($para_ids) {
    if (is_array($para_ids)) {
      $para_ids = array_unique($para_ids);
      $para_ids = implode('|', $para_ids);
    }

    $this->set('para_ids', '|' . $para_ids . '|');

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addParaId($para_id) {
    $para_ids = $this->getParaIdsArray();
    $para_ids[] = $para_id;

    $this->setParaIds($para_ids);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeParaId($para_id) {
    if (empty($para_id)) {
      return $this;
    }

    $para_ids = $this->getParaIdsArray();

    if ($index = array_search($para_id, $para_ids)) {
      unset($para_ids[$index]);
    }

    $this->set('para_ids', $para_ids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Key'))
      ->setDescription(t('Key'))
      ->setSettings([
        'max_length' => 191,
      ])
      ->setRequired(TRUE);

    $fields['token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token'))
      ->setDescription(t('Token'))
      ->setSettings([
        'max_length' => 191,
      ])
      ->setRequired(TRUE);

    $fields['endpoint'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Endpoint'))
      ->setDescription(t('Communication endpoint.'))
      ->setSettings([
        'max_length' => 512,
      ])
      ->setRequired(TRUE);

    $fields['para_ids'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Paragraph Ids'))
      ->setDescription(t('Paragraph Ids, format |123|456|'))
      ->setSettings([
        'max_length' => 2048,
      ])
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the subscription was created.'));

    return $fields;
  }

}
