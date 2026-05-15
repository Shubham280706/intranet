// Push Notification Client
// Handles service worker registration and push subscription

const PUBLIC_VAPID_KEY = 'BLtZVY4gMu9ZZLob0NoJyApxng_EdciUhfxD89ItD0BcfHqnhS5pHSbY1j1va7U8fbjokDNu9KEv7s0JaUnuXc8';

async function registerPushNotifications() {
  // Check browser support
  if (!('serviceWorker' in navigator)) {
    console.log('❌ Service Workers not supported');
    return false;
  }

  if (!('PushManager' in window)) {
    console.log('❌ Push Notifications not supported');
    return false;
  }

  try {
    console.log('📲 Registering service worker...');

    // Register service worker
    const registration = await navigator.serviceWorker.register(
      '/intranet/service-worker.js',
      { scope: '/intranet/' }
    );

    console.log('✅ Service worker registered');

    // Check if already subscribed
    const existingSub = await registration.pushManager.getSubscription();
    if (existingSub) {
      console.log('✅ Already subscribed to push notifications');
      return true;
    }

    // Request permission
    console.log('🔔 Requesting notification permission...');
    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
      console.log('⚠️ Push notification permission denied');
      return false;
    }

    console.log('✅ Permission granted');

    // Subscribe to push
    console.log('📱 Subscribing to push notifications...');
    const subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(PUBLIC_VAPID_KEY)
    });

    console.log('✅ Push subscription created');

    // Send subscription to server
    console.log('📤 Sending subscription to server...');
    await savePushSubscription(subscription);

    console.log('✅ Push notifications enabled!');
    return true;
  } catch (error) {
    console.error('❌ Push notification setup failed:', error);
    return false;
  }
}

async function savePushSubscription(subscription) {
  try {
    const response = await fetch('/intranet/api/push_subscribe.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify(subscription)
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error || 'Failed to save subscription');
    }

    return data;
  } catch (error) {
    console.error('Error saving subscription:', error);
    throw error;
  }
}

function urlBase64ToUint8Array(base64String) {
  try {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
      .replace(/-/g, '+')
      .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
  } catch (error) {
    console.error('Error converting VAPID key:', error);
    throw error;
  }
}

// Auto-register push notifications when script loads
document.addEventListener('DOMContentLoaded', function() {
  // Only register if user is logged in (check for session indicator)
  const isLoggedIn = document.body.innerText.includes('Dashboard') ||
                     document.body.innerText.includes('Profile') ||
                     document.body.innerText.includes('Logout');

  if (isLoggedIn) {
    registerPushNotifications().catch(error => {
      console.log('Push notifications not available:', error.message);
    });
  }
});

// Fallback: Register push on user interaction
document.addEventListener('click', function() {
  if ('serviceWorker' in navigator && 'PushManager' in window) {
    navigator.serviceWorker.getRegistration('/intranet/').then(reg => {
      if (reg && !reg.pushManager.getSubscription()) {
        registerPushNotifications().catch(() => {
          // Silent fail on retry
        });
      }
    });
  }
}, { once: true });
