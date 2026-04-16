// ============================================================================
// ANU Lost & Found - Service Worker
// Enables offline functionality, caching, and push notifications
// ============================================================================

const CACHE_NAME = 'anu-lost-found-v1';
const STATIC_ASSETS = [
  '/lost_and_found/index.php',
  '/lost_and_found/dashboard.php',
  '/lost_and_found/search.php',
  '/lost_and_found/assets/css/style.css',
  '/lost_and_found/assets/js/app.js',
  '/lost_and_found/assets/images/anu-logo.png',
  '/lost_and_found/assets/images/lostfound.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('Service Worker installing...');
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('Caching static assets');
      return cache.addAll(STATIC_ASSETS).catch((err) => {
        console.warn('Some assets could not be cached:', err);
        // Continue even if some assets fail to cache
      });
    })
  );
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('Service Worker activating...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip cross-origin requests
  if (!url.origin.includes(location.origin)) {
    return;
  }

  // Don't cache dynamic PHP pages - always fetch fresh from network
  if (url.pathname.endsWith('.php')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Don't cache PHP responses, but return them
          return response;
        })
        .catch(() => {
          // Network error, try cache as fallback
          return caches.match(request);
        })
    );
    return;
  }

  // Handle GET requests for static assets
  if (request.method === 'GET') {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        // Return cached response if available
        if (cachedResponse) {
          // Update cache in background
          fetch(request)
            .then((response) => {
              if (response && response.status === 200) {
                const responseClone = response.clone();
                caches.open(CACHE_NAME).then((cache) => {
                  cache.put(request, responseClone);
                });
              }
            })
            .catch(() => {
              // Network error, use cached version
            });

          return cachedResponse;
        }

        // No cache, fetch from network
        return fetch(request)
          .then((response) => {
            // Don't cache non-successful responses
            if (!response || response.status !== 200 || response.type === 'error') {
              return response;
            }

            // Cache successful responses
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone);
            });

            return response;
          })
          .catch(() => {
            // Network error and no cache - return offline page
            return new Response(offlineHTML, {
              headers: { 'Content-Type': 'text/html' },
            });
          });
      })
    );
  }
});

// Background sync for messages
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-messages') {
    event.waitUntil(syncMessages());
  }
});

async function syncMessages() {
  try {
    const response = await fetch('/lost_and_found/api/sync-messages.php', {
      method: 'POST',
    });
    if (response.ok) {
      console.log('Messages synced successfully');
    }
  } catch (error) {
    console.error('Failed to sync messages:', error);
    throw error; // Retry
  }
}

// Offline page HTML
const offlineHTML = `
<!DOCTYPE html>
<html>
<head>
  <title>Offline - ANU Lost & Found</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(135deg, #ed1c24 0%, #fac923 100%);
      margin: 0;
      padding: 20px;
    }
    .offline-container {
      background: white;
      border-radius: 12px;
      padding: 40px 20px;
      text-align: center;
      max-width: 400px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .offline-icon {
      font-size: 48px;
      margin-bottom: 20px;
    }
    h1 {
      color: #333;
      font-size: 24px;
      margin: 0 0 10px 0;
    }
    p {
      color: #666;
      font-size: 16px;
      line-height: 1.6;
      margin: 10px 0;
    }
    .info {
      background: #f5f5f5;
      padding: 15px;
      border-radius: 8px;
      margin-top: 20px;
      font-size: 14px;
      color: #555;
    }
  </style>
</head>
<body>
  <div class="offline-container">
    <div class="offline-icon">📡</div>
    <h1>You're Offline</h1>
    <p>ANU Lost & Found requires an internet connection.</p>
    <p>Check your connection and try again.</p>
    <div class="info">
      Some cached pages may still be available. Try refreshing the page.
    </div>
  </div>
</body>
</html>
`;
