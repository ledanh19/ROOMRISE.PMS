<script setup>
import { usePropertyStore } from "@/stores/usePropertyStore";
import ChatContact from "@/views/apps/chat/ChatContact.vue";
import { useChatStore } from "@/views/apps/chat/useChatStore";
import { storeToRefs } from "pinia";
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  search: { type: String, required: true },
  isDrawerOpen: { type: Boolean, required: true },
});
const emit = defineEmits([
  "openChatOfContact",
  "showUserProfile",
  "close",
  "update:search",
]);

const propertyStore = usePropertyStore();
const { selectedProperty } = storeToRefs(propertyStore);
const store = useChatStore();

const currentTab = ref("tab-1");
const tabStatus = computed(() =>
  currentTab.value === "tab-1" ? "ACTIVE" : "CLOSED"
);

watch(
  tabStatus,
  (v) => {
    store.filterStatus = v;
  },
  { immediate: true }
);

const PAGE_SIZE = 10;
const page = ref(1);
const isLoading = ref(false);
const noMore = ref(false);

const psRef = ref(null);
const getScrollEl = () => psRef.value?.$el || psRef.value || null;
const hardResetViewport = async () => {
  await nextTick();
  const el = getScrollEl();
  if (!el) return;
  el.scrollTop = 0;
  el.scrollTop = el.scrollHeight;
};

const fullList = computed(() =>
  Array.isArray(store.chatsContacts) ? store.chatsContacts : []
);
const displayList = computed(() => {
  const wantState = tabStatus.value === "ACTIVE" ? "active" : "closed";
  return fullList.value.filter((c) => c.state === wantState);
});
const displayCount = computed(() => displayList.value.length);
const listKey = computed(
  () => `list-${tabStatus.value}-${selectedProperty.value?.value ?? "all"}`
);
console.log(fullList);
let debounceTimer = null;
let fetchToken = 0;
let lastSig = ref("");

const doFetch = async ({ force = false, append = false } = {}) => {
  if (debounceTimer) clearTimeout(debounceTimer);

  return new Promise((resolve) => {
    debounceTimer = setTimeout(async () => {
      const pVal = selectedProperty?.value ?? null;
      const sig = `${tabStatus.value}|${props.search}|${page.value}|${pVal}`;
      if (!force && sig === lastSig.value) return resolve();

      lastSig.value = sig;
      const myToken = ++fetchToken;

      isLoading.value = true;
      store.filterStatus = tabStatus.value;

      try {
        await store.fetchChatsAndContacts(
          props.search,
          page.value,
          PAGE_SIZE,
          pVal,
          tabStatus.value,
          append
        );
      } catch (e) {
      } finally {
        if (myToken === fetchToken) {
          noMore.value = !store._hasMore;
          isLoading.value = false;
        }
        resolve();
      }
    }, 120);
  });
};

const ensureScrollableOnFirstLoad = async () => {
  await nextTick();
  const rootEl = getScrollEl();
  if (!rootEl) return;

  let tries = 0;
  while (tries < 3) {
    const canScroll = rootEl.scrollHeight > rootEl.clientHeight + 8;
    if (canScroll || noMore.value) break;
    page.value += 1;
    await doFetch({ append: true, force: true });
    await nextTick();
    tries++;
  }
};

const resetAndFetch = async (withAutoFill = false) => {
  page.value = 1;
  noMore.value = false;
  lastSig.value = "";
  await doFetch({ force: true, append: false });
  if (withAutoFill) await ensureScrollableOnFirstLoad();
};

watch([currentTab, () => props.search], () => {
  resetAndFetch(false);
});

let scrollListener = null;
let scrollCooldown = false;
const onScroll = async (e) => {
  const el = e?.target || getScrollEl();
  if (!el || isLoading.value || noMore.value) return;

  const nearBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 80;
  if (!nearBottom) return;

  if (scrollCooldown) return;
  scrollCooldown = true;
  setTimeout(() => (scrollCooldown = false), 300);

  page.value += 1;
  await doFetch({ append: true });
};

onMounted(async () => {
  if (!store._swBound) store.attachServiceWorkerListenerOnce();
  await resetAndFetch(true);

  await nextTick();
  const rootEl = getScrollEl();
  if (rootEl) {
    scrollListener = onScroll;
    rootEl.addEventListener("scroll", scrollListener, { passive: true });
  }
});

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
  const rootEl = getScrollEl();
  if (rootEl && scrollListener)
    rootEl.removeEventListener("scroll", scrollListener);
});

watch(
  selectedProperty,
  async (val, oldVal) => {
    fetchToken++;
    page.value = 1;
    noMore.value = false;
    lastSig.value = "";

    try {
      await doFetch({ force: true, append: false });
    } finally {
      await nextTick();
      await hardResetViewport();
      await ensureScrollableOnFirstLoad();
    }
  },
  { immediate: true }
);
</script>
<template>
  <VCard class="chat-list-card">
    <VTabs v-model="currentTab" grow stacked class="chat-list-header">
      <VTab value="tab-1"
        ><VIcon icon="tabler-message-2" class="mb-2" /><span>Active</span></VTab
      >
      <VTab value="tab-2"
        ><VIcon icon="tabler-archive" class="mb-2" /><span>Closed</span></VTab
      >
    </VTabs>

    <VProgressLinear
      v-show="isLoading"
      indeterminate
      height="2"
      color="primary"
      class="progress-thin"
    />

    <VCardText class="chat-list-body pa-0">
      <div class="chat-scroll-box">
        <PerfectScrollbar
          ref="psRef"
          tag="div"
          class="chat-contacts-scroll"
          :options="{ wheelPropagation: false }"
        >
          <div class="chat-contacts-list px-1 py-1">
            <VWindow v-model="currentTab" :key="listKey">
              <VWindowItem value="tab-1">
                <template v-if="isLoading && displayCount === 0">
                  <VSkeletonLoader
                    v-for="i in 6"
                    :key="`skel-a-${i}`"
                    type="list-item-two-line, avatar"
                    class="mx-4 my-2"
                  />
                </template>
                <template v-else-if="displayCount > 0">
                  <ChatContact
                    v-for="contact in displayList"
                    :key="`chat-${tabStatus}-${contact.id}`"
                    :user="contact"
                    is-chat-contact
                    @click="$emit('openChatOfContact', contact.id)"
                  />
                </template>
                <span v-else class="no-chat-items-text text-disabled"
                  >No active chats</span
                >
              </VWindowItem>

              <VWindowItem value="tab-2">
                <template v-if="isLoading && displayCount === 0">
                  <VSkeletonLoader
                    v-for="i in 6"
                    :key="`skel-c-${i}`"
                    type="list-item-two-line, avatar"
                    class="mx-4 my-2"
                  />
                </template>
                <template v-else-if="displayCount > 0">
                  <ChatContact
                    v-for="contact in displayList"
                    :key="`chat-${tabStatus}-${contact.id}`"
                    :user="contact"
                    is-chat-contact
                    @click="$emit('openChatOfContact', contact.id)"
                  />
                </template>
                <span v-else class="no-chat-items-text text-disabled"
                  >No closed chats</span
                >
              </VWindowItem>
            </VWindow>

            <div class="load-more-sentinel py-3 text-center">
              <VProgressCircular
                v-if="isLoading && displayCount > 0 && !noMore"
                indeterminate
                size="20"
                width="2"
              />
              <span
                v-else-if="noMore && displayCount > 0"
                class="text-disabled text-caption"
              >
                No more chats
              </span>
            </div>
          </div>
        </PerfectScrollbar>
      </div>
    </VCardText>
  </VCard>
</template>

<style lang="scss">
.chat-list-card {
  display: grid;
  block-size: 100%;
  grid-template-rows: auto auto 1fr;
  min-block-size: 0;
}

.progress-thin {
  margin-block-start: -2px;
}

.chat-list-body {
  overflow: hidden;
  min-block-size: 0;
}

.chat-scroll-box {
  overflow: hidden;
  block-size: var(--chat-scroll-height, 40rem);
  min-block-size: 0;
}

.chat-contacts-scroll.ps {
  position: relative;
  block-size: 100% !important;
}

.chat-contacts-list {
  --chat-content-spacing-x: 16px;

  padding-block-end: 0.75rem;

  .chat-contact-header {
    margin-block: 0.5rem 0.25rem;
  }

  .chat-contact-header,
  .no-chat-items-text {
    margin-inline: var(--chat-content-spacing-x);
  }
}

.load-more-sentinel {
  min-block-size: 40px;
}
</style>
