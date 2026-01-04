<script setup>
import { initialsOf, logoFromOta } from "@/utils/helper";
import { useChatStore } from "@/views/apps/chat/useChatStore";
import { computed } from "vue";

const props = defineProps({
  isChatContact: { type: Boolean, default: false },
  user: { type: Object, required: true },
});

const store = useChatStore();

const isChatContactActive = computed(() => {
  const isActive = store.activeChat?.contact?.id === props.user?.id;
  if (!props.isChatContact) return !store.activeChat?.chat && isActive;
  return isActive;
});

const propertyName = computed(
  () => props.user?.role || props.user?.propertyName || ""
);

const lastMessage = computed(() =>
  props.isChatContact && props.user?.chat?.lastMessage
    ? props.user.chat.lastMessage
    : null
);

const isAttachment = computed(() => {
  const lm = lastMessage.value;
  if (!lm) return false;
  if (lm.type === "IMAGE") return true;
  const m = lm.message || "";
  return /\.(png|jpe?g|gif|webp|svg|heic|heif)$/i.test(m);
});

const previewText = computed(() => {
  if (!props.isChatContact) return props.user?.about || "";
  if (!lastMessage.value) return "";
  return isAttachment.value ? "Attachment" : lastMessage.value.message || "";
});

const previewTime = computed(() => lastMessage.value?.time ?? null);
const avatarInitials = computed(
  () => props.user?.avatarText || initialsOf(props.user?.fullName || "", 4)
);

function hashString(s) {
  let h = 0;
  for (let i = 0; i < s.length; i++) h = ((h << 5) - h + s.charCodeAt(i)) | 0;
  return h;
}
const avatarKey = computed(
  () => props.user?.fullName || String(props.user?.id || "")
);
const avatarBgClass = computed(() => {
  const bucket = Math.abs(hashString(avatarKey.value)) % 12;
  return `avatar-bg-${bucket}`;
});
console.log(logoFromOta(props.user?.tag));
</script>

<template>
  <li
    class="chat-contact cursor-pointer d-flex align-center"
    :class="{ 'chat-contact-active': isChatContactActive }"
  >
    <VAvatar size="40" class="avatar--bordered">
      <VImg :src="logoFromOta(props.user?.otaName)" alt="Avatar" cover />
      <!-- <span v-else class="text-body-2 font-weight-medium avatar--initials-text">
        {{ avatarInitials }}
      </span> -->
    </VAvatar>

    <div class="flex-grow-1 ms-4 overflow-hidden">
      <p class="mb-0 text-truncate text-body-2">
        {{ propertyName }}
      </p>

      <p class="text-base text-high-emphasis mb-0">
        {{ props.user?.fullName }}
      </p>

      <p class="mb-0 text-truncate text-body-2 d-flex align-center">
        <template v-if="isAttachment">
          <VIcon icon="tabler-paperclip" size="16" class="me-1" />
          Attachment
        </template>
        <template v-else>
          {{ previewText }}
        </template>
      </p>
    </div>

    <div
      v-if="props.isChatContact && props.user?.chat"
      class="d-flex flex-column align-self-start"
    >
      <div
        class="text-body-2 text-disabled whitespace-no-wrap"
        v-if="previewTime"
      >
        {{ formatDateToMonthShort(previewTime) }}
      </div>

      <VBadge
        v-if="props.user.chat.unseenMsgs"
        color="error"
        inline
        :content="props.user.chat.unseenMsgs ? '!' : null"
        class="ms-auto"
      />
    </div>
  </li>
</template>

<style lang="scss">
@use "../../../../styles/styles";
@use "@core-scss/template/mixins" as templateMixins;
@use "@styles/variables/vuetify";
@use "@core-scss/base/mixins";
@use "vuetify/lib/styles/tools/states" as vuetifyStates;

.chat-contact {
  border-radius: vuetify.$border-radius-root;
  padding-block: 8px;
  padding-inline: 12px;

  @include mixins.before-pseudo;
  @include vuetifyStates.states($active: false);

  &.chat-contact-active {
    @include templateMixins.custom-elevation(var(--v-theme-primary), "sm");

    background: rgb(var(--v-theme-primary));
    color: #fff;

    --v-theme-on-background: #fff;
  }

  .v-badge--bordered .v-badge__badge::after {
    color: #fff;
  }
}

.avatar--bordered {
  overflow: hidden;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.16);
  border-radius: 9999px;
  background-color: white;
}

.avatar--initials-text {
  color: #0f172a;
  font-weight: 700;
  letter-spacing: 0.2px;
}
</style>
