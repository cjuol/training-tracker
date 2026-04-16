// Fase 0: plain Service Worker registration. Phase 1 upgrades this to
// workbox-window (for update notifications + queue messaging) once AssetMapper
// has the package vendored locally.

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            console.error('Service Worker registration failed:', err);
        });
    });
}
