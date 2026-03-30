import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute, NavigationRoute } from 'workbox-routing';
import { NetworkFirst, CacheFirst } from 'workbox-strategies';

// Кеширование ассетов (CSS, JS, изображения)
precacheAndRoute(self.__WB_MANIFEST);

// Кеширование главной страницы (Laravel Route)
registerRoute(
    ({ request }) => request.mode === 'navigate',
    new NetworkFirst({
        cacheName: 'pages-cache',
        networkTimeoutSeconds: 5, // Give the network enough time but fallback to cache if slow
    })
);

// Кеширование статики
registerRoute(
    ({ request }) => request.destination === 'style' || request.destination === 'script' || request.destination === 'worker',
    new CacheFirst({
        cacheName: 'static-resources',
    })
);

// Кеширование медиа
registerRoute(
    ({ request }) => request.destination === 'image',
    new CacheFirst({
        cacheName: 'image-cache',
    })
);

// Push Notifications
self.addEventListener('push', function (event) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    if (event.data) {
        const data = event.data.json();
        console.log('Push received:', data);

        const title = data.title || 'Новое уведомление';
        const options = {
            body: data.body || '',
            icon: data.icon || '/pwa-icon.png',
            badge: '/pwa-icon.png',
            data: data.data || {},
            actions: data.actions || [],
            vibrate: [200, 100, 200],
        };

        event.waitUntil(self.registration.showNotification(title, options));
    }
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const urlToOpen = event.notification.data.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});
