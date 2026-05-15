// WorkSpace Intranet Service Worker
// Handles push notifications in the background

self.addEventListener('push', function(e) {
  if (!e.data) return;

  try {
    const data = e.data.json();
    const options = {
      body: data.body || '',
      icon: '/intranet/assets/img/logo.png',
      badge: '/intranet/assets/img/badge.png',
      vibrate: [200, 100, 200],
      tag: data.tag || 'notification',
      data: {
        url: data.url || '/intranet/dashboard.php',
        timestamp: Date.now()
      },
      actions: [
        {action: 'open', title: 'Open'},
        {action: 'close', title: 'Dismiss'}
      ]
    };

    e.waitUntil(
      self.registration.showNotification(data.title || 'WorkSpace', options)
    );
  } catch (error) {
    console.error('Push notification error:', error);
  }
});

self.addEventListener('notificationclick', function(e) {
  e.notification.close();

  const urlToOpen = e.notification.data.url || '/intranet/dashboard.php';

  e.waitUntil(
    clients.matchAll({
      type: 'window',
      includeUncontrolled: true
    }).then(function(clientList) {
      // Check if app is already open
      for (let i = 0; i < clientList.length; i++) {
        const client = clientList[i];
        if (client.url === urlToOpen && 'focus' in client) {
          return client.focus();
        }
      }
      // Open new window if not found
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});

self.addEventListener('notificationclose', function(e) {
  // Handle notification dismiss
  console.log('Notification dismissed');
});

self.addEventListener('install', function(e) {
  console.log('Service Worker installing...');
  e.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function(e) {
  console.log('Service Worker activating...');
  e.waitUntil(self.clients.claim());
});

// Periodic background sync (optional)
self.addEventListener('sync', function(e) {
  if (e.tag === 'sync-notifications') {
    e.waitUntil(syncNotifications());
  }
});

async function syncNotifications() {
  try {
    const response = await fetch('/intranet/api/poll.php');
    const data = await response.json();
    // Handle any background sync logic here
    return Promise.resolve();
  } catch (error) {
    console.error('Sync error:', error);
    return Promise.reject(error);
  }
}
