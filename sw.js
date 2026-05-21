// Waaagh! Paint - Service Worker
// Minimal SW: satisfies PWA installability requirement, no offline caching
self.addEventListener('install',  () => self.skipWaiting());
self.addEventListener('activate', e  => e.waitUntil(clients.claim()));
