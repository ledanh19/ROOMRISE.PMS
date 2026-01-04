<template>
  <div ref="rootEl" class="chat-log pa-6">
    <div ref="topSentinel" class="top-sentinel" />

    <div
      v-for="(msgGrp, index) in msgGroups"
      :key="msgGrp.senderId + String(index)"
      class="chat-group d-flex align-start"
      :class="[
        {
          'flex-row-reverse': msgGrp.senderId !== contact.id,
          'mb-6': msgGroups.length - 1 !== index,
        },
      ]"
    >
      <div
        class="chat-avatar"
        :class="msgGrp.senderId !== contact.id ? 'ms-4' : 'me-4'"
      >
        <VAvatar
          :size="avatarSize"
          class="avatar--bordered"
          :class="
            msgGrp.senderId === contact.id
              ? !contact.avatar
                ? contactAvatarClass
                : ''
              : ''
          "
        >
          <VImg
            v-if="
              msgGrp.senderId === contact.id
                ? !!contact.avatar
                : !!adminAvatarSrc
            "
            :src="
              msgGrp.senderId === contact.id ? contact.avatar : adminAvatarSrc
            "
            alt="avatar"
            cover
          />
          <span
            v-else-if="msgGrp.senderId === contact.id"
            class="avatar--initials-text text-caption"
          >
            {{ contactAvatarInitials }}
          </span>
        </VAvatar>
      </div>

      <div
        class="chat-body d-inline-flex flex-column"
        :class="msgGrp.senderId !== contact.id ? 'align-end' : 'align-start'"
      >
        <template
          v-if="Array.isArray(msgGrp.messages) && msgGrp.messages.length"
        >
          <div
            v-for="(msgData, msgIndex) in msgGrp.messages"
            :key="msgData.time ?? msgGrp.senderId + '-' + msgIndex"
            class="chat-content elevation-2"
            :class="[
              msgGrp.senderId === contact.id ? 'chat-left' : 'chat-right',
              msgData.type === 'IMAGE'
                ? 'bubble-image'
                : msgGrp.senderId === contact.id
                ? 'bubble-in'
                : 'bubble-out',
              msgGrp.messages.length - 1 !== msgIndex ? 'mb-2' : 'mb-1',
            ]"
          >
            <p v-if="msgData.type !== 'IMAGE'" class="mb-0 text-base">
              {{ msgData.message }}
            </p>

            <VImg
              v-else
              :src="toLoadableUrl(msgData.message)"
              class="chat-image cursor-zoom-in"
              eager
              @click="openImage(msgData.message)"
              @load="onImageLoad"
            >
              <template #placeholder>
                <div class="pa-4 image-ph">
                  <VSkeletonLoader type="image" />
                </div>
              </template>
              <template #error>
                <div class="pa-3 text-caption text-disabled">
                  Không tải được ảnh
                </div>
              </template>
            </VImg>
          </div>

          <div :class="msgGrp.senderId !== contact.id ? 'text-right' : ''">
            <span v-if="lastMsg(msgGrp)" class="text-sm ms-2 text-disabled">
              {{ formatTime(lastMsg(msgGrp)?.time) }}
              <template v-if="lastMsg(msgGrp)?.username">
                <span v-if="lastMsg(msgGrp)?.isAdminSend"
                  >&nbsp;|&nbsp;Đã gửi bởi</span
                >
                <span v-else>&nbsp;|&nbsp;Đã đọc bởi </span>
                {{ lastMsg(msgGrp)?.username }}
              </template>
            </span>
          </div>
        </template>
      </div>
    </div>

    <!-- Image Viewer -->
    <VDialog
      v-model="imageViewer.open"
      transition="scale-transition"
      :max-width="`90vw`"
      :scrollable="true"
    >
      <VCard class="image-viewer-card">
        <div class="image-viewer-toolbar">
          <VBtn
            variant="text"
            icon="tabler-external-link"
            :href="imageViewer.src"
            target="_blank"
            :disabled="!imageViewer.src"
            :title="'Open in new tab'"
          />
          <VSpacer />
          <VBtn
            variant="text"
            icon="tabler-x"
            @click="closeImage"
            :title="'Close'"
          />
        </div>

        <div class="image-viewer-body">
          <VImg :src="imageViewer.src" class="image-viewer-img" eager>
            <template #placeholder>
              <div class="pa-6 loading-image">
                <VSkeletonLoader type="image" />
              </div>
            </template>
            <template #error>
              <div class="pa-4 text-caption text-disabled">
                Không tải được ảnh
              </div>
            </template>
          </VImg>
        </div>
      </VCard>
    </VDialog>
  </div>
</template>

<script setup>
import { initialsOf } from "@/utils/helper";
import { useChatStore } from "@/views/apps/chat/useChatStore";
import avatar from "@images/avatars/avatar-1.png";
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from "vue";

const DEFAULT_AVATAR = avatar;
const emit = defineEmits(["load-older"]);
const store = useChatStore();

const contact = computed(() => ({
  id: store.activeChat?.contact?.id,
  avatar: store.activeChat?.contact?.avatar,
  fullName: store.activeChat?.contact?.fullName || "Guest",
}));

const formatTime = (ts) => {
  if (!ts) return "";
  try {
    return new Date(ts).toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return "";
  }
};

const lastMsg = (grp) =>
  grp && Array.isArray(grp.messages) && grp.messages.length
    ? grp.messages[grp.messages.length - 1]
    : null;

const msgGroups = computed(() => {
  const list = Array.isArray(store.activeChat?.chat?.messages)
    ? store.activeChat.chat.messages
    : [];
  if (!list.length) return [];

  const groups = [];
  let currentSender = list[0]?.senderId;
  let group = { senderId: currentSender, messages: [] };

  list.forEach((m, i) => {
    const item = {
      type: m?.type || "TEXT",
      message: m?.message,
      time: m?.time,
      isAdminSend: m?.isAdmin ?? 0,
      feedback: m?.feedback,
      username: m?.username ?? null,
    };

    if (m?.senderId === currentSender) {
      group.messages.push(item);
    } else {
      groups.push(group);
      currentSender = m?.senderId;
      group = { senderId: currentSender, messages: [item] };
    }
    if (i === list.length - 1) groups.push(group);
  });

  return groups.map((g) => ({
    ...g,
    messages: Array.isArray(g.messages) ? g.messages : [],
  }));
});

function toLoadableUrl(u) {
  if (!u) return "";
  try {
    const url = new URL(u, window.location.origin);
    if (window.location.protocol === "https:" && url.protocol === "http:") {
      url.protocol = "https:";
    }
    return url.toString();
  } catch {
    return u;
  }
}

function hashString(s) {
  let h = 0;
  for (let i = 0; i < s.length; i++) h = ((h << 5) - h + s.charCodeAt(i)) | 0;
  return h;
}
function bucketOf(name) {
  if (!name) return 0;
  return Math.abs(hashString(name)) % 12;
}

const contactAvatarClass = computed(() => {
  const key =
    store.activeChat?.contact?.fullName ||
    String(store.activeChat?.contact?.id || "");
  return `avatar-bg-${bucketOf(key)}`;
});
const contactAvatarInitials = computed(() =>
  initialsOf(contact.value.fullName || "", 4)
);

const adminAvatarSrc = computed(() => {
  const p = store.activeChat?.chat?.lastMessage?.profilePhotoPath;
  const a = store.profileUser?.avatar;
  const src = p || a || DEFAULT_AVATAR;
  return toLoadableUrl(src);
});

const imageViewer = ref({ open: false, src: "" });
const openImage = (u) => {
  imageViewer.value.src = toLoadableUrl(u);
  imageViewer.value.open = true;
};
const closeImage = () => {
  imageViewer.value.open = false;
  imageViewer.value.src = "";
};

const rootEl = ref(null);
const topSentinel = ref(null);
let io = null;
let ioReady = false;
let ioCooldown = false;

const scrollEl = ref(null);
const isPinnedBottom = ref(true);
const BOTTOM_EPS = 24;

const getScrollContainer = (el) => {
  if (!el) return null;
  const ps = el.closest(".ps");
  if (ps) return ps;
  return el.parentElement ?? el;
};

const isNearBottom = () => {
  const sc = scrollEl.value;
  if (!sc) return true;
  const { scrollTop, clientHeight, scrollHeight } = sc;
  return scrollTop + clientHeight >= scrollHeight - BOTTOM_EPS;
};

const scrollToBottom = (smooth = false) => {
  const sc = scrollEl.value;
  if (!sc) return;
  sc.scrollTo({ top: sc.scrollHeight, behavior: smooth ? "smooth" : "auto" });
};

const onImageLoad = () => {
  if (isPinnedBottom.value) scrollToBottom(false);
};

/* ===== Responsive helpers (mobile) ===== */
const isMobile = ref(false);
const avatarSize = computed(() => (isMobile.value ? 28 : 32));

function handleResize() {
  isMobile.value = window.matchMedia("(max-width: 600px)").matches;
}

onMounted(async () => {
  handleResize();
  window.addEventListener("resize", handleResize);

  await nextTick();
  scrollEl.value = getScrollContainer(rootEl.value);

  scrollEl.value?.addEventListener(
    "scroll",
    () => {
      isPinnedBottom.value = isNearBottom();
    },
    { passive: true }
  );

  scrollToBottom(false);
  const root = scrollEl.value;
  io = new IntersectionObserver(
    (entries) => {
      const e = entries[0];
      if (!ioReady || !e?.isIntersecting) return;
      if (ioCooldown) return;
      ioCooldown = true;
      setTimeout(() => (ioCooldown = false), 350);
      emit("load-older");
    },
    { root, threshold: 0.01, rootMargin: "0px" }
  );

  if (topSentinel.value) io.observe(topSentinel.value);
  setTimeout(() => (ioReady = true), 200);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", handleResize);
  io?.disconnect?.();
  scrollEl.value?.removeEventListener?.("scroll", () => {});
});

watch(
  () => msgGroups.value.map((g) => g.messages.length).join("|"),
  async () => {
    await nextTick();
    if (isPinnedBottom.value) scrollToBottom(true);
  }
);
</script>

<style lang="scss">
.chat-log {
  .chat-body {
    max-inline-size: calc(100% - 6.75rem);

    .chat-content {
      border-end-end-radius: 6px;
      border-end-start-radius: 6px;

      p {
        overflow-wrap: anywhere;
      }

      &.chat-left {
        border-start-end-radius: 6px;
      }

      &.chat-right {
        border-start-start-radius: 6px;
      }

      &.bubble-in {
        background-color: rgb(var(--v-theme-surface));
        padding-block: 0.5rem;
        padding-inline: 1rem;
      }

      &.bubble-out {
        background-color: rgb(var(--v-theme-primary));
        color: #fff;
        padding-block: 0.5rem;
        padding-inline: 1rem;
      }

      &.bubble-image {
        overflow: hidden;
        padding: 0;
        background: transparent;
      }

      .chat-image {
        display: block;
        aspect-ratio: 13 / 9;
        inline-size: 100%;
        max-inline-size: 560px; // desktop cap
        object-fit: cover;
      }
    }
  }
}

.top-sentinel {
  block-size: 1px;
  inline-size: 100%;
}

.cursor-zoom-in {
  cursor: zoom-in;
}

.image-viewer-card {
  overflow: hidden;
  background: rgba(var(--v-theme-surface), 1);
}

.image-viewer-toolbar {
  display: flex;
  align-items: center;
  padding-block: 8px 0;
  padding-inline: 8px;
}

.image-viewer-body {
  display: flex;
  align-items: center;
  justify-content: center;
  padding-block: 8px 16px;
  padding-inline: 12px;
}

.image-viewer-img {
  max-block-size: 85vh;
  max-inline-size: 90vw;
}

.image-viewer-img .v-img__img {
  object-fit: contain !important;
}

.avatar--bordered {
  overflow: hidden;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.16);
  border-radius: 9999px;
}

.avatar--initials-text {
  color: #0f172a;
  font-weight: 700;
  letter-spacing: 0.2px;
  line-height: 1;
}

.loading-image {
  block-size: 60vh;
  inline-size: 80vw;
}

/* Placeholder block while image loads */
.image-ph {
  display: block;
  block-size: 180px;
  inline-size: min(260px, 80vw);
}

/* ===================== */

/*     MOBILE <= 600px   */

/* ===================== */
@media (max-width: 600px) {
  .chat-log {
    padding: 12px !important;

    .chat-body {
      /* avatar ~28px + margin ~2rem */
      max-inline-size: calc(100% - 3.75rem);
    }

    .chat-content {
      p {
        font-size: 0.95rem;
      }

      &.bubble-in,
      &.bubble-out {
        padding-block: 0.45rem;
        padding-inline: 0.75rem;
      }

      .chat-image {
        aspect-ratio: 4 / 3;
        max-block-size: 50vh;
        max-inline-size: 82vw; /* fit viewport */
      }
    }
  }

  .chat-group {
    &.d-flex.align-start {
      gap: 8px;
    }

    &.mb-6 {
      margin-block-end: 16px !important;
    }
  }

  .image-viewer-img {
    max-block-size: 80vh;
    max-inline-size: 100vw;
  }

  .image-viewer-toolbar {
    padding-inline: 4px;
  }
}
</style>
