/**
 * @file
 * Web Push Notifications.
 */

(function (Drupal) {
  Drupal.behaviors.webPushNotification = {
    subscribeText: Drupal.t('Get alerts'),
    unsubscribeText: Drupal.t('Disable alerts'),
    disabled: false,

    attach: function (context, settings) {

      if (!('serviceWorker' in navigator)) {
        console.warn('Service workers are not supported by this browser');
        return;
      }

      if (!('PushManager' in window)) {
        console.warn('Push notifications are not supported by this browser');
        return;
      }

      if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
        console.warn('Notifications are not supported by this browser');
        return;
      }

      // Check the current Notification permission.
      if (Notification.permission === 'denied') {
        console.warn('Notifications are denied by the user');
        this.disabled = true;
      }

      // Check for eligible paragraphs, excluding those which were already
      // processed in an earlier invocation of the behavior.
      const paragraphs = context.querySelectorAll('[data-push-allowed]:not(.web-push--processed)');
      if (!paragraphs) {
        return;
      }

      paragraphs.forEach((paragraph) => {
        // Create button.
        let pushButton = document.createElement('button');
        pushButton.setAttribute('data-push-id', paragraph.getAttribute('data-push-id'));
        pushButton.setAttribute('data-push-active', 0);
        pushButton.innerHTML = this.subscribeText;
        pushButton.classList.add(
          'cd-button',
          'cd-button--small',
        );

        if (this.disabled) {
          pushButton.setAttribute('disabled', 'disabled');
          pushButton.setAttribute('title', 'Push notifications are disabled in your browser settings. If you want to use this button, re-enable push notifications for this domain.');
        }

        pushButton.addEventListener('click', function(e) {
          if (e.target.getAttribute('data-push-active') == '1') {
            e.target.setAttribute('data-push-active', 0);
            this.push_unsubscribe(e.target.getAttribute('data-push-id'));
            pushButton.innerHTML = this.subscribeText;
          } else {
            e.target.setAttribute('data-push-active', 1);
            this.push_subscribe(e.target.getAttribute('data-push-id'));
            pushButton.innerHTML = this.unsubscribeText;
          }
        }.bind(this));

        // Insert button into DOM.
        paragraph.append(pushButton);

        // Mark paragraph as processed.
        paragraph.classList.add(
          'rw-key-figures__can-push',
          'web-push--processed',
        );
      });

      navigator.serviceWorker.register(settings.webPushNotification.serviceWorkerUrl).then(
        () => {
          console.log('[SW] Service worker has been registered');
          this.push_updateSubscription();
        },
        e => {
          console.error('[SW] Service worker registration failed', e);
        }
      );
    },

    urlBase64ToUint8Array: function (base64String) {
      const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
      const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');

      const rawData = window.atob(base64);
      const outputArray = new Uint8Array(rawData.length);

      for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
      }
      return outputArray;
    },

    checkNotificationPermission: function () {
      return new Promise((resolve, reject) => {
        if (Notification.permission === 'denied') {
          return reject(new Error('Push messages are blocked.'));
        }

        if (Notification.permission === 'granted') {
          return resolve();
        }

        if (Notification.permission === 'default') {
          return Notification.requestPermission().then(result => {
            if (result !== 'granted') {
              reject(new Error('Bad permission result'));
            } else {
              resolve();
            }
          });
        }

        return reject(new Error('Unknown permission'));
      });
    },

    push_subscribe: function (paraId) {
      return this.checkNotificationPermission()
        .then(() => navigator.serviceWorker.ready)
        .then(serviceWorkerRegistration =>
          serviceWorkerRegistration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: this.urlBase64ToUint8Array(drupalSettings.webPushNotification.publicKey),
          })
        )
        .then(subscription => {
          // Subscription was successful.
          return this.push_sendSubscriptionToServer(subscription, 'POST', paraId);
        })
        .then(subscription => subscription) // update your UI
        .catch(e => {
          if (Notification.permission === 'denied') {
            // The user denied the notification permission.
            console.warn('Notifications are denied by the user.');
            this.disabled = true;
          } else {
            // A problem occurred with the subscription; common reasons
            // include network errors or the user skipped the permission
            console.error('Impossible to subscribe to push notifications', e);
            this.disabled = true;
          }
        });
    },

    push_updateSubscription: function () {
      navigator.serviceWorker.ready
        .then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
        .then(subscription => {
          if (!subscription) {
            return;
          }

          // Keep your server in sync with the latest endpoint.
          return this.push_sendSubscriptionToServer(subscription, 'POST');
        })
        .then(subscription => subscription && this.set_buttonstates(subscription))
        .catch(e => {
          console.error('Error when updating the subscription', e);
        });
    },

    set_buttonstates: function (subscription) {
      const key = subscription.getKey('p256dh');
      const token = subscription.getKey('auth');
      const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];

      return fetch(drupalSettings.webPushNotification.subscribeUrl, {
        method: 'POST',
        body: JSON.stringify({
          endpoint: subscription.endpoint,
          key: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
          token: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null,
        }),
      })
      .then((response) => response.json())
      .then((data) => {
        if (!data.para_ids) {
          return;
        }

        let paraIds = data.para_ids.split(',');
        const pushButtons = document.querySelectorAll('button[data-push-id]');
        if (!pushButtons) {
          return;
        }

        pushButtons.forEach((pushButton) => {
          if (paraIds.includes(pushButton.getAttribute('data-push-id'))) {
            pushButton.setAttribute('data-push-active', true);
            pushButton.textContent = this.unsubscribeText;
          }
          else {
            pushButton.setAttribute('data-push-active', false);
          }
        });
      });
    },

    push_unsubscribe: function (paraId) {
      navigator.serviceWorker.ready
        .then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
        .then(subscription => {
          // Check that we have a subscription to unsubscribe.
          if (!subscription) {
            return;
          }

          // We have a subscription, unsubscribe.
          return this.push_sendSubscriptionToServer(subscription, 'DELETE', paraId);
        })
        .then(subscription => subscription.unsubscribe())
        .catch(e => {
          console.error('Error when unsubscribing the user', e);
        });
    },

    push_sendSubscriptionToServer: function (subscription, method, paraId) {
      const key = subscription.getKey('p256dh');
      const token = subscription.getKey('auth');
      const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];

      return fetch(drupalSettings.webPushNotification.subscribeUrl, {
        method,
        body: JSON.stringify({
          endpoint: subscription.endpoint,
          key: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
          token: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null,
          para_id: paraId,
        }),
      }).then(() => subscription);
    }

  }

})(Drupal);
