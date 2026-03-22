import { precacheAndRoute } from 'workbox-precaching';

// Внедрение манифеста Vite (кеширование ассетов)
precacheAndRoute(self.__WB_MANIFEST);

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
            actions: data.actions || []
        };

        event.waitUntil(self.registration.showNotification(title, options));
    }
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const urlToOpen = event.notification.data.url || '/admin/leads';

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
