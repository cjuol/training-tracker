// Service Worker — Training Tracker
// Phase 0 skeleton: loads Workbox v7 from Google CDN and wires a background-sync
// queue placeholder. Actual fetch handlers and queue consumers land in Phase 1
// (offline logueo of SetLog entries).

importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.1.0/workbox-sw.js');

// eslint-disable-next-line no-undef
workbox.setConfig({ debug: false });

// Placeholder queue — Phase 1 will attach a POST /api/v1/sessions/:id/sets handler
// to this queue via workbox.routing.registerRoute + BackgroundSyncPlugin.
// eslint-disable-next-line no-undef, no-unused-vars
const setlogQueue = new workbox.backgroundSync.Queue('setlog-queue', {
    maxRetentionTime: 24 * 60, // minutes → retry for up to 24h when offline
});

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});
