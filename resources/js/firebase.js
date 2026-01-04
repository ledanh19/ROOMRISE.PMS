import { initializeApp } from "firebase/app";
import * as FCM from "firebase/messaging";
import { useChatStore } from "./views/apps/chat/useChatStore";

const app = initializeApp({
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
  storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID,
});

if (!window.__FCM_SINGLETON__) {
  window.__FCM_SINGLETON__ = { inited: false, off: null, token: null };
}

export async function initFcm(vapidKey) {
  if (window.__FCM_SINGLETON__.inited) {
    return {
      token: window.__FCM_SINGLETON__.token ?? null,
      off: window.__FCM_SINGLETON__.off || (() => {}),
    };
  }
  window.__FCM_SINGLETON__.inited = true;

  const supported = await FCM.isSupported().catch(() => false);
  if (!supported) return { token: null, off: () => {} };

  if ("serviceWorker" in navigator) {
    await navigator.serviceWorker.register("/firebase-messaging-sw.js", {
      scope: "/",
    });
  }

  if (Notification.permission !== "granted") {
    const p = await Notification.requestPermission();
    if (p !== "granted") return { token: null, off: () => {} };
  }

  const messaging = FCM.getMessaging(app);
  const reg = await navigator.serviceWorker.ready;

  let token = null;
  try {
    token = await FCM.getToken(messaging, {
      vapidKey,
      serviceWorkerRegistration: reg,
    });
  } catch (e) {
    console.warn("[FCM] getToken error:", e);
  }

  if (typeof window.__FCM_SINGLETON__.off === "function") {
    try {
      window.__FCM_SINGLETON__.off();
    } catch {}
    window.__FCM_SINGLETON__.off = null;
  }

  const off = FCM.onMessage(messaging, async (payload) => {
    console.log(payload);
    try {
      const chat = useChatStore();
      await chat.handleIncomingPush(payload);
    } catch (e) {
      console.error("handleIncomingPush error:", e);
    }

    const d = payload?.data || {},
      n = payload?.notification || {};
    const title = n?.title || d.title || "Thông báo";
    const body = n?.body || d.body || d.text || "";
    const icon = n?.icon || d.icon || "/favicon.ico";
    try {
      const reg = await navigator.serviceWorker.ready;
      await reg.showNotification(title, { body, icon });
    } catch {}
  });

  window.__FCM_SINGLETON__.off = off;
  window.__FCM_SINGLETON__.token = token;

  return { token, off };
}
