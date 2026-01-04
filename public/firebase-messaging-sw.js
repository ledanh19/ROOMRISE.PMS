self.FIREBASE_CONFIG = {
  apiKey: "AIzaSyCCDxTlME1lzRl-Amx7HiydKN5zfF57BZ8",
  authDomain: "roomrise-f39d6.firebaseapp.com",
  projectId: "roomrise-f39d6",
  storageBucket: "roomrise-f39d6.appspot.com",
  messagingSenderId: "857960618037",
  appId: "1:857960618037:web:7ae3603e41c4534e4c797c",
  measurementId: "G-XMDG40HNXC",
};

importScripts(
  "https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js"
);
importScripts(
  "https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js"
);

self.addEventListener("install", () => self.skipWaiting());
self.addEventListener("activate", (e) => e.waitUntil(self.clients.claim()));

try {
  if (!firebase.apps || !firebase.apps.length)
    firebase.initializeApp(self.FIREBASE_CONFIG);
} catch (e) {
  console.error("[SW][FCM] initializeApp error:", e);
}

let messaging = null;
try {
  messaging = firebase.messaging();
} catch (e) {
  console.error("[SW][FCM] messaging init error:", e);
}

async function broadcastToClients(kind, info) {
  const list = await clients.matchAll({
    type: "window",
    includeUncontrolled: true,
  });
  for (const c of list) c.postMessage({ source: "fcm-sw", kind, info });
}

const shownIds = new Set();
function isDuplicate(payload) {
  const d = payload?.data || {};
  const id =
    payload?.messageId ||
    d.messageId ||
    d.msg_id ||
    (d.id && d.updatedAt ? `${d.id}-${d.updatedAt}` : null);
  if (!id) return false;
  if (shownIds.has(id)) return true;
  shownIds.add(id);
  setTimeout(() => shownIds.delete(id), 5 * 60 * 1000);
  return false;
}

function buildNotif(payload) {
  console.log(payload);
  const n = (payload && payload.notification) || {};
  const d = (payload && payload.data) || {};

  const title = n.title || d.title || d.notification_title || "Thông báo";
  const options = {
    body: n.body || d.body || d.notification_body || d.text || "",
    icon: n.icon || d.icon || "/favicon.ico",
    image: n.image || d.image || undefined,
    badge: n.badge || d.badge || undefined,
    tag: d.tag || "app-general",
    renotify:
      d.renotify === true || d.renotify === "true" || d.renotify === "1",
    requireInteraction: true,
    silent: false,
    timestamp: Date.now(),
    data: { url: d.url || d.link || d.click_action || "/", ...d },
  };

  try {
    return { title, options: JSON.parse(JSON.stringify(options)) };
  } catch {
    return {
      title,
      options: {
        body: options.body,
        icon: options.icon,
        tag: options.tag,
        requireInteraction: true,
        silent: false,
        timestamp: Date.now(),
        data: { url: options.data?.url || "/" },
      },
    };
  }
}

if (messaging) {
  messaging.onBackgroundMessage(async (payload) => {
    try {
      await broadcastToClients("background-message", payload);

      if (!isDuplicate(payload)) {
        // const { title, options } = buildNotif(payload);
        // await self.registration.showNotification(title, options);
      }
    } catch (e) {
      console.error("[SW] onBackgroundMessage error:", e);
    }
  });
}
self.addEventListener("notificationclick", (event) => {
  const url = event.notification?.data?.url || "/";
  event.notification.close();

  event.waitUntil(
    (async () => {
      const allClients = await clients.matchAll({
        type: "window",
        includeUncontrolled: true,
      });

      if (/^https?:\/\//i.test(url)) {
        await clients.openWindow(url);
        return;
      }

      let client = allClients.find(
        (c) => new URL(c.url).origin === self.location.origin
      );

      if (client) {
        await client.focus();
        if ("navigate" in client) {
          await client.navigate(url);
        } else {
          client.postMessage({ type: "OPEN_URL", url });
        }
      } else {
        await clients.openWindow(url);
      }
    })()
  );
});

self.addEventListener("notificationclose", () => {});
