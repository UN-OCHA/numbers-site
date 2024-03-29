<?php

namespace Drupal\web_push_notification\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Notification subscription entities.
 *
 * @ingroup web_push_notification
 */
interface SubscriptionInterface extends ContentEntityInterface {

  /**
   * Gets the subscription public key.
   *
   * @return string
   *   Returns the public key.
   */
  public function getPublicKey();

  /**
   * Sets the subscription public key.
   *
   * @param string $key
   *   The subscription key.
   *
   * @return $this
   */
  public function setPublicKey($key);

  /**
   * Gets the subscription token.
   *
   * @return string
   *   Returns the token.
   */
  public function getToken();

  /**
   * Sets the subscription token.
   *
   * @param string $token
   *   The subscription token.
   *
   * @return $this
   *   Allow chaining.
   */
  public function setToken($token);

  /**
   * Gets the subscription endpoint.
   *
   * @return string
   *   Returns the endpoint.
   */
  public function getEndpoint();

  /**
   * Sets the subscription endpoint.
   *
   * @param string $endpoint
   *   The subscription endpoint.
   *
   * @return $this
   */
  public function setEndpoint($endpoint);

  /**
   * Gets the Notification subscription creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Notification subscription.
   */
  public function getCreatedTime();

  /**
   * Sets the Notification subscription creation timestamp.
   *
   * @param int $timestamp
   *   The Notification subscription creation timestamp.
   *
   * @return \Drupal\web_push_notification\Entity\SubscriptionInterface
   *   The called Notification subscription entity.
   */
  public function setCreatedTime($timestamp);

}
