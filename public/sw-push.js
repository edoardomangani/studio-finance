// Handler Web Push, importato dal service worker generato da vite-plugin-pwa
// (workbox.importScripts). File statico: niente bundling, niente hash.

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch {
        // Payload non JSON: notifica generica.
    }

    event.waitUntil(
        self.registration.showNotification(payload.title || 'StudioFinance', {
            body: payload.body,
            tag: payload.tag,
            icon: payload.icon || '/pwa-192x192.png',
            badge: '/pwa-192x192.png',
            data: payload.data || {},
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = new URL(
        (event.notification.data && event.notification.data.url) || '/',
        self.location.origin,
    ).href;

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clients) => {
                const client = clients.find(
                    (c) => new URL(c.url).origin === self.location.origin,
                );

                if (client) {
                    return client
                        .focus()
                        .then((c) => c.navigate(url))
                        .catch(() => self.clients.openWindow(url));
                }

                return self.clients.openWindow(url);
            }),
    );
});
