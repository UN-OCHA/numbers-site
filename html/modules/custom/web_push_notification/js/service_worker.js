/**
 * @file
 * Service Worker for web push notifications.
 */

self.addEventListener('install', function(event) {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function(event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function (event) {

  if (!(self.Notification && self.Notification.permission === 'granted')) {
    return;
  }

  var data = {
    body: '',
    message: '',
    icon: ''
  };

  if (event.data) {
    data = event.data.json();
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: data.icon,
      data: {
        url: data.url
      }
    });
  }
});

self.addEventListener('notificationclick', function (event) {
  event.waitUntil(
    self.clients.matchAll({ type: 'window' })
      .then(function (clientList) {
        var url = event.notification.data.url || '/';
        for (var i = 0; i < clientList.length; i++) {
          var client = clientList[i];
          if (client.url == url && 'focus' in client) {
            return client.focus();
          }
        }
        return self.clients.openWindow(url);
      })
  );
});
