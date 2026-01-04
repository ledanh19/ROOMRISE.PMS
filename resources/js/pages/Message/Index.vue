<template>
  <Head title="Tin nhắn | Room Rise" />
  <Layout>
    <VLayout class="chat-app-layout">
      <VNavigationDrawer
        v-model="isUserProfileSidebarOpen"
        data-allow-mismatch
        temporary
        touchless
        absolute
        class="user-profile-sidebar"
        location="start"
        width="370"
      >
        <ChatUserProfileSidebarContent
          @close="isUserProfileSidebarOpen = false"
        />
      </VNavigationDrawer>

      <VNavigationDrawer
        v-model="isLeftSidebarOpen"
        data-allow-mismatch
        absolute
        touchless
        location="start"
        width="370"
        :temporary="$vuetify.display.smAndDown"
        class="chat-list-sidebar"
        :permanent="$vuetify.display.mdAndUp"
      >
        <ChatLeftSidebarContent
          v-model:is-drawer-open="isLeftSidebarOpen"
          v-model:search="q"
          @open-chat-of-contact="openChatOfContact"
          @show-user-profile="isUserProfileSidebarOpen = true"
          @close="isLeftSidebarOpen = false"
        />
      </VNavigationDrawer>

      <VMain class="chat-content-container">
        <div v-if="store.activeChat" class="d-flex flex-column h-100">
          <div class="active-chat-header d-flex align-center bg-surface">
            <div class="header-left d-md-none">
              <VBtn
                icon
                variant="text"
                density="comfortable"
                :ripple="false"
                @click="isLeftSidebarOpen = true"
                aria-label="Open chat list"
              >
                <VIcon icon="tabler-arrow-left" />
              </VBtn>
            </div>
            <VTooltip
              :text="mobileInfoFullText"
              location="bottom"
              open-on-hover
              open-on-click
              :disabled="!vuetifyDisplays.smAndDown.value"
            >
              <template #activator="{ props }">
                <div
                  class="header-user d-flex align-center cursor-pointer"
                  v-bind="props"
                  @click="onHeaderUserClick"
                >
                  <VAvatar
                    :size="vuetifyDisplays.smAndDown.value ? 36 : 40"
                    class="avatar--bordered cursor-pointer"
                    :class="
                      !store.activeChat.contact.avatar
                        ? activeAvatarBgClass
                        : ''
                    "
                  >
                    <VImg
                      v-if="store.activeChat.contact.avatar"
                      :src="store.activeChat.contact.avatar"
                      :alt="store.activeChat.contact.fullName"
                      cover
                    />
                    <span v-else class="avatar--initials-text">{{
                      activeAvatarInitials
                    }}</span>
                  </VAvatar>

                  <div class="user-text ms-3 overflow-hidden">
                    <div class="text-h6 mb-0 font-weight-regular text-truncate">
                      {{ store.activeChat.contact.fullName }}
                    </div>

                    <div
                      class="meta-one-line text-body-2 text-medium-emphasis"
                      :title="mobileInfoFullText"
                    >
                      {{ mobileInfoFullText }}
                    </div>

                    <div class="meta-mobile" aria-hidden="true"></div>
                  </div>
                </div>
              </template>
            </VTooltip>

            <VSpacer />

            <div
              class="header-actions text-medium-emphasis"
              v-if="store.activeChat.contact.state === 'active'"
            >
              <div class="d-none d-sm-flex align-center">
                <VBtn
                  color="error"
                  variant="tonal"
                  size="small"
                  class="rounded-pill text-none mr-3"
                  prepend-icon="tabler-x"
                  @click="openConfirmClose"
                >
                  Close conversation
                </VBtn>

                <VBtn
                  color="primary"
                  variant="tonal"
                  size="small"
                  class="rounded-pill text-none"
                  @click="
                    handleDetailBooking(store.activeChat.contact.bookingId)
                  "
                >
                  Chi tiết
                </VBtn>
              </div>

              <!-- Mobile -->
              <div class="d-flex d-sm-none align-center">
                <VBtn
                  icon
                  variant="tonal"
                  color="error"
                  density="comfortable"
                  :ripple="false"
                  class="me-1 rounded-pill"
                  aria-label="Close conversation"
                  @click="openConfirmClose"
                >
                  <VIcon icon="tabler-x" />
                </VBtn>
                <VBtn
                  icon
                  variant="tonal"
                  color="primary"
                  density="comfortable"
                  :ripple="false"
                  class="ms-1 rounded-pill"
                  aria-label="Open booking detail"
                  @click="
                    handleDetailBooking(store.activeChat.contact.bookingId)
                  "
                >
                  <VIcon icon="tabler-info-circle" />
                </VBtn>
              </div>
            </div>
          </div>

          <VDivider />

          <PerfectScrollbar
            :key="activeChatKey"
            ref="chatLogPS"
            tag="ul"
            :options="{ wheelPropagation: false }"
            class="flex-grow-1"
            @ps-y-reach-start="onReachTop"
            @ps-scroll-y="onScrollY"
          >
            <ChatLog :key="activeChatKey" />
          </PerfectScrollbar>

          <VSnackbar
            :model-value="!!store.isErrorSendMessage"
            timeout="3500"
            color="error"
            location="bottom right"
            variant="flat"
            @update:model-value="
              (val) => {
                if (!val) store.isErrorSendMessage = null;
              }
            "
          >
            {{ store.isErrorSendMessage }}
          </VSnackbar>
          <VForm
            class="chat-log-message-form mb-5 mx-5"
            @submit.prevent="sendMessage"
            v-if="store.activeChat.contact.state === 'active'"
          >
            <div
              v-if="previewUrl"
              class="mt-2 d-flex align-start gap-3 align-center"
            >
              <VAvatar
                size="100"
                rounded="lg"
                class="elevation-1 overflow-hidden"
              >
                <VImg :src="previewUrl" alt="preview" cover />
              </VAvatar>

              <div class="flex-grow-1">
                <div class="text-caption text-medium-emphasis">
                  {{ pendingFile?.name }}
                </div>
              </div>

              <div class="d-flex gap-2">
                <VBtn
                  size="small"
                  variant="text"
                  prepend-icon="tabler-replace"
                  @click="refInputEl?.click()"
                >
                  Chọn ảnh khác
                </VBtn>
                <VBtn
                  size="small"
                  color="error"
                  variant="tonal"
                  prepend-icon="tabler-x"
                  @click="removePendingImage"
                >
                  Xoá
                </VBtn>
              </div>
            </div>
            <VTextField
              :key="store.activeChat?.contact.id"
              v-model="msg"
              variant="solo"
              density="default"
              class="chat-message-input"
              placeholder="Type your message..."
              autofocus
            >
              <template #append-inner>
                <div class="d-flex gap-1">
                  <IconBtn @click="refInputEl?.click()">
                    <VIcon icon="tabler-paperclip" size="22" />
                  </IconBtn>
                  <div class="d-none d-md-block">
                    <VBtn
                      append-icon="tabler-send"
                      :loading="isLoadingSendMessage"
                      @click="sendMessage"
                      rounded
                    >
                      Send
                    </VBtn>
                  </div>
                </div>
              </template>
            </VTextField>

            <input
              ref="refInputEl"
              type="file"
              name="file"
              accept=".jpeg,.png,.jpg,GIF"
              hidden
              @change="onPickImageReplace"
            />
          </VForm>
        </div>

        <div
          v-else
          class="d-flex h-100 align-center justify-center flex-column"
        >
          <VAvatar size="98" variant="tonal" color="primary" class="mb-4">
            <VIcon size="50" class="rounded-0" icon="tabler-message-2" />
          </VAvatar>
          <VBtn
            v-if="$vuetify.display.smAndDown"
            rounded="pill"
            @click="startConversation"
          >
            Start Conversation
          </VBtn>

          <p
            v-else
            style="max-inline-size: 40ch; text-wrap: balance"
            class="text-center text-disabled"
          >
            Start connecting with the people by selecting one of the contact on
            left
          </p>
        </div>
        <VDialog v-model="showConfirmClose" max-width="410">
          <VCard>
            <VCardText class="text-body-2 v-card-text">
              Bạn có muốn đóng cuộc hội thoại này với khách hàng
            </VCardText>

            <VCardActions class="justify-">
              <VBtn
                color="error"
                variant="tonal"
                size="small"
                class="rounded-pill text-none"
                @click="showConfirmClose = false"
                :disabled="isClosing"
              >
                Huỷ
              </VBtn>
              <VBtn
                color="primary"
                variant="tonal"
                size="small"
                class="rounded-pill text-none"
                :loading="isClosing"
                :disabled="isClosing"
                @click="confirmCloseConversation"
              >
                Đồng ý
              </VBtn>
            </VCardActions>
          </VCard>
        </VDialog>
      </VMain>
    </VLayout>
  </Layout>
</template>

<script setup>
import Layout from "@/layouts/blank.vue";
import { initialsOf } from "@/utils/helper";
import ChatLeftSidebarContent from "@/views/apps/chat/ChatLeftSidebarContent.vue";
import ChatLog from "@/views/apps/chat/ChatLog.vue";
import ChatUserProfileSidebarContent from "@/views/apps/chat/ChatUserProfileSidebarContent.vue";
import { useChatStore } from "@/views/apps/chat/useChatStore";
import { Head } from "@inertiajs/vue3";
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { useDisplay } from "vuetify";

const vuetifyDisplays = useDisplay();
const store = useChatStore();

const { isLeftSidebarOpen } = useResponsiveLeftSidebar(
  vuetifyDisplays.smAndDown
);

const q = ref("");
const isLoadingSendMessage = ref(false);
const msg = ref("");
const chatLogPS = ref();
const refInputEl = ref();
const showConfirmClose = ref(false);
const isClosing = ref(false);

const PAGE_SIZE = 10;
const msgPage = ref(1);
const loadingOlder = ref(false);
const noMoreOlder = ref(false);

const isActiveChatUserProfileSidebarOpen = ref(false);
const isUserProfileSidebarOpen = ref(false);

const props = defineProps({
  property_id: Number,
  error: String,
  url: String,
});

const getScrollEl = () => chatLogPS.value?.$el || chatLogPS.value || null;

const hardResetViewport = async () => {
  chatLogPS.value?.update?.();
  const el = getScrollEl();
  if (!el) return;
  el.scrollTop = 0;
  el.scrollTop = el.scrollHeight;
};

const scrollToBottomInChatLog = () => {
  const el = getScrollEl();
  if (!el) return;
  el.scrollTop = el.scrollHeight;
};

const openChatOfContact = async (payload) => {
  const userId = payload?.id ?? payload?.userId ?? payload;
  if (!userId) return;

  msgPage.value = 1;
  noMoreOlder.value = false;

  await store.getChat(userId);
  await nextTick();

  if (vuetifyDisplays.smAndDown.value) {
    isLeftSidebarOpen.value = false;
  }

  await hardResetViewport();
};

watch(
  () => store.activeChat?.contact?.id,
  async () => {
    await nextTick();
    await hardResetViewport();
  }
);

const onScrollY = () => {
  const el = chatLogPS.value?.$el || chatLogPS.value;
  if (!el) return;
  if (el.scrollTop <= 80) loadOlder();
};

const onReachTop = () => {
  loadOlder();
};

const pendingFile = ref(null);
const previewUrl = ref(null);

const MAX_IMAGE_SIZE = 10 * 1024 * 1024;

const readableSize = (n) => {
  if (n == null) return "";
  const units = ["B", "KB", "MB", "GB"];
  let i = 0,
    s = n;
  while (s >= 1024 && i < units.length - 1) {
    s /= 1024;
    i++;
  }
  return `${s.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
};

const revokePreview = () => {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  previewUrl.value = null;
};

const removePendingImage = () => {
  revokePreview();
  pendingFile.value = null;
  if (refInputEl.value) refInputEl.value.value = "";
};

const onPickImageReplace = (evt) => {
  const input = evt?.target;
  const file = input?.files?.[0];
  if (!file) return;

  const isImage =
    /^image\//i.test(file.type) || /\.(jpe?g|png|gif|webp)$/i.test(file.name);
  if (!isImage) {
    store.isErrorSendMessage = "Chỉ hỗ trợ ảnh (jpg, jpeg, png, gif, webp).";
    if (input) input.value = "";
    return;
  }
  if (file.size > MAX_IMAGE_SIZE) {
    store.isErrorSendMessage = `Ảnh quá lớn (>${readableSize(
      MAX_IMAGE_SIZE
    )}).`;
    if (input) input.value = "";
    return;
  }

  revokePreview();
  pendingFile.value = file;
  previewUrl.value = URL.createObjectURL(file);
};

const loadOlder = async () => {
  if (loadingOlder.value || noMoreOlder.value) return;

  const historyId = store.activeChat?.chat?.id;
  const contactId = store.activeChat?.contact?.id;
  if (!historyId || !contactId) return;

  loadingOlder.value = true;

  const el = chatLogPS.value?.$el || chatLogPS.value;
  const prevTop = el?.scrollTop ?? 0;
  const prevHeight = el?.scrollHeight ?? 0;

  try {
    const res = await store.fetchMessageDetails(historyId, contactId, {
      page: msgPage.value + 1,
      size: PAGE_SIZE,
      mode: "prepend",
    });

    if (res?.items?.length) {
      msgPage.value += 1;

      await nextTick();
      const newHeight = el?.scrollHeight ?? prevHeight;
      el.scrollTop = newHeight - prevHeight + prevTop;

      noMoreOlder.value = !res.hasMore;
    } else {
      noMoreOlder.value = true;
    }
  } finally {
    loadingOlder.value = false;
  }
};

const sendMessage = async () => {
  const text = (msg.value || "").trim();
  const file = pendingFile.value;

  if (!text && !file) return;
  isLoadingSendMessage.value = true;
  msg.value = "";
  pendingFile.value = null;
  try {
    if (file) {
      revokePreview();
      await store.sendMsg({
        type: "IMAGE",
        file,
      });
      removePendingImage();
    }
    if (text) {
      await store.sendMsg(text);
    }

    await nextTick();
    scrollToBottomInChatLog();
    isLoadingSendMessage.value = false;
  } catch (err) {
    isLoadingSendMessage.value = false;
    console.error("send message error:", err);
    store.isErrorSendMessage = "Gửi tin nhắn thất bại. Vui lòng thử lại.";
  }
};

const onPickImage = async (evt) => {
  const input = evt?.target;
  const file = input?.files?.[0];
  if (!file) return;

  try {
    await store.sendMsg({ type: "IMAGE", file });
    if (input) input.value = "";

    await nextTick();
    scrollToBottomInChatLog();
  } catch (err) {
    console.error("send image error:", err);
  }
};

const openConfirmClose = () => {
  showConfirmClose.value = true;
};

const confirmCloseConversation = async () => {
  if (!store.activeChat?.contact?.messageThreadId) return;
  try {
    isClosing.value = true;
    await handleCloseConversation(store.activeChat.contact.messageThreadId);
    showConfirmClose.value = false;
  } finally {
    isClosing.value = false;
  }
};

const handleCloseConversation = async (messageThreadId) => {
  if (!messageThreadId) return;
  try {
    await store.closeConversationByHistoryId(messageThreadId);
  } catch (e) {
    console.error(e);
  }
};

const startConversation = () => {
  isLeftSidebarOpen.value = true;
};

const handleDetailBooking = (bookingId) => {
  if (!bookingId) return;
  window.open(
    route("bookings.show", bookingId),
    "_blank",
    "noopener,noreferrer"
  );
};

defineExpose({ openChatOfContact, loadOlder, sendMessage, onPickImage });

const activeChatKey = computed(() => {
  const c = store.activeChat?.contact?.id ?? "none";
  const h = store.activeChat?.chat?.id ?? "none";
  return `${c}-${h}`;
});

const parseDate = (date) => {
  const d = new Date(date);
  const dd = String(d.getDate()).padStart(2, "0");
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const yyyy = d.getFullYear();
  return `${dd}-${mm}-${yyyy}`;
};

function hashString(s) {
  let h = 0;
  for (let i = 0; i < s.length; i++) h = ((h << 5) - h + s.charCodeAt(i)) | 0;
  return h;
}

const activeAvatarInitials = computed(() =>
  initialsOf(store.activeChat?.contact?.fullName || "", 4)
);

const activeAvatarBgClass = computed(() => {
  const key =
    store.activeChat?.contact?.fullName ||
    String(store.activeChat?.contact?.id || "");
  const bucket = Math.abs(hashString(key)) % 12; // 0..11
  return `avatar-bg-${bucket}`;
});
onBeforeUnmount(() => {
  revokePreview();
});

const mobileNightsRooms = computed(() => {
  const n = store.activeChat?.contact?.nightsCount;
  const r = store.activeChat?.contact?.roomsCount;
  const hasN = n === 0 || !!n;
  const hasR = r === 0 || !!r;

  if (hasN && hasR) return `${n} đêm · ${r} phòng`;
  if (hasN) return `${n} đêm`;
  if (hasR) return `${r} phòng`;
  return "";
});

const mobileInfoFullText = computed(() => {
  const c = store.activeChat?.contact;
  if (!c) return "";

  const parts = [];

  if (c.checkInDate) parts.push(`Đến: ${parseDate(c.checkInDate)}`);

  const n = c?.nightsCount;
  const r = c?.roomsCount;
  const hasN = n === 0 || !!n;
  const hasR = r === 0 || !!r;

  if (hasN) parts.push(`${n} đêm`);
  if (hasR) parts.push(`${r} phòng`);

  if (c.propertyName) parts.push(c.propertyName);

  return parts.join(" | ");
});

const mobileInfoShortText = computed(() => {
  return mobileInfoFullText.value;
});
</script>

<style lang="scss">
@use "../../../styles/styles";
@use "@styles/variables/vuetify";
@use "@core-scss/base/mixins";
@use "@layouts/styles/mixins" as layoutsMixins;

$chat-app-header-height: 76px;

%chat-header {
  display: flex;
  align-items: center;
  min-block-size: $chat-app-header-height;
  padding-inline: 1.5rem;
}

.chat-start-conversation-btn {
  cursor: default;
}

.chat-app-layout {
  border-radius: vuetify.$card-border-radius;
  block-size: 80vh;

  @include mixins.elevation(vuetify.$card-elevation);

  $sel-chat-app-layout: &;

  @at-root {
    .skin--bordered {
      @include mixins.bordered-skin($sel-chat-app-layout);
    }
  }

  .active-chat-user-profile-sidebar,
  .user-profile-sidebar {
    .v-navigation-drawer__content {
      display: flex;
      flex-direction: column;
    }
  }

  .chat-list-header,
  .active-chat-header {
    @extend %chat-header;
  }

  .chat-list-sidebar {
    .v-navigation-drawer__content {
      display: flex;
      flex-direction: column;
    }
  }
}

.chat-content-container {
  background-color: v-bind(chatcontentcontainerbg);

  .chat-message-input {
    .v-field__input {
      font-size: 0.9375rem !important;
      line-height: 1.375rem !important;
      padding-block: 0.6rem 0.5rem;
    }

    .v-field__append-inner {
      align-items: center;
      padding-block-start: 0;
    }

    .v-field--appended {
      padding-inline-end: 8px;
    }
  }
}

.avatar--bordered {
  overflow: hidden;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.16);
  border-radius: 9999px;
}

.avatar--white-bordered {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.16);
  background-color: #fff !important;
}

.avatar--white-bordered span {
  color: rgb(var(--v-theme-on-surface));
}

.avatar--initials-text {
  color: #0f172a;
  font-weight: 700;
  letter-spacing: 0.2px;
  line-height: 1;
}

.active-chat-header {
  gap: 8px;
  padding-block: 10px;
  padding-inline: 12px;

  .header-left {
    flex: 0 0 auto;
  }

  .header-user {
    flex: 1 1 auto;
    min-inline-size: 0; // ĐỂ truncate hoạt động trong flex
    .user-text {
      min-inline-size: 0;
    }
  }

  .header-actions {
    flex: 0 0 auto;
  }

  .sep {
    opacity: 0.6;
    padding-inline: 6px;
  }

  .dot {
    opacity: 0.6;
  }
}

.meta-block {
  display: grid;
  gap: 2px;
}

.meta-line {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  line-height: 1.25;
  white-space: nowrap;
}

.meta-line .sep {
  opacity: 0.6;
}

.meta-property {
  overflow: hidden;
  margin-block-start: 2px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.meta-mobile {
  display: none;
}

.text-ellipsis {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (max-width: 600px) {
  .active-chat-header {
    gap: 6px;
    padding-block: 8px;
    padding-inline: 10px;

    .header-user .user-text .text-h6 {
      font-size: 1rem;
      line-height: 1.25;
    }
  }

  .meta-block.meta-desktop {
    display: none;
  }

  .meta-mobile {
    display: block;

    .meta-line {
      display: flex;
      align-items: center;
      line-height: 1.25;
      white-space: nowrap;
    }
  }
}

.v-card-text {
  color: black;
  font-size: 1.2em !important;
}

.meta-one-line {
  overflow: hidden;
  line-height: 1.25;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.active-chat-header .header-user .user-text .meta-one-line {
  margin-block-start: 2px;
}

@media (max-width: 600px) {
  .meta-mobile {
    display: none !important;
  }
}
</style>
