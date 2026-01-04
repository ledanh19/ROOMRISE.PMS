<script setup>
import ScrollToTop from "@core/components/ScrollToTop.vue";
import initCore from "@core/initCore";
import { initConfigStore, useConfigStore } from "@core/stores/config";
import { hexToRgb } from "@core/utils/colorConverter";
import { usePage } from "@inertiajs/vue3";
import { onMounted, onUnmounted } from "vue";
import { useTheme } from "vuetify";
import { initFcm } from "./firebase.js";
import { useChatStore } from "./views/apps/chat/useChatStore.js";

const chat = useChatStore();
chat.attachServiceWorkerListenerOnce();

const props = defineProps({
  inertiaApp: Object,
});

const { global } = useTheme();

initCore();
initConfigStore();

const configStore = useConfigStore();
const page = usePage();

let off = null;

onMounted(async () => {
  const res = await initFcm(import.meta.env.VITE_FIREBASE_VAPID_PUBLIC_KEY);
  off = res?.off;
});

onUnmounted(() => {
  if (typeof off === "function") off();
});
</script>

<template>
  <VLocaleProvider :rtl="configStore.isAppRTL">
    <VApp
      :style="`--v-global-theme-primary: ${hexToRgb(
        global.current.value.colors.primary
      )}`"
    >
      <div class="layout-wrapper layout-blank">
        <component :is="inertiaApp" />
        <ScrollToTop />
      </div>
    </VApp>
  </VLocaleProvider>
</template>
