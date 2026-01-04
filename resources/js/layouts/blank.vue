<script setup>
import AppLoadingIndicator from "@/Components/AppLoadingIndicator.vue";
import DefaultLayoutWithVerticalNav from "@/layouts/components/DefaultLayoutWithVerticalNav.vue";
import { usePage } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { toast } from "vue3-toastify";

const page = usePage();

const { injectSkinClasses } = useSkins();

watchEffect(() => {
  const flash = page.props.flash;

  const flashHandlers = {
    success: { type: "success", theme: "colored" },
    created: { type: "success", theme: "colored" },
    updated: { type: "info", theme: "colored" },
    deleted: { type: "error", theme: "colored" },
    signed: { type: "success", theme: "colored" },
    canceled: { type: "error", theme: "colored" },
    registered: { type: "success", theme: "colored" },
    error: { type: "error", theme: "colored" },
  };

  for (const key in flashHandlers) {
    if (flash[key]) {
      toast(flash[key], flashHandlers[key]);
      flash[key] = null; // Reset flash message
    }
  }
});

// ℹ️ This will inject classes in body tag for accurate styling
injectSkinClasses();

// SECTION: Loading Indicator
const isFallbackStateActive = ref(false);
const refLoadingIndicator = ref(null);

watch(
  [isFallbackStateActive, refLoadingIndicator],
  () => {
    if (isFallbackStateActive.value && refLoadingIndicator.value)
      refLoadingIndicator.value.fallbackHandle();
    if (!isFallbackStateActive.value && refLoadingIndicator.value)
      refLoadingIndicator.value.resolveHandle();
  },
  { immediate: true }
);
// !SECTION
</script>

<template>
  <AppLoadingIndicator ref="refLoadingIndicator" />
  <DefaultLayoutWithVerticalNav>
    <slot />
  </DefaultLayoutWithVerticalNav>
  <div class="layout-wrapper layout-blank" data-allow-mismatch></div>
</template>

<style>
.layout-wrapper.layout-blank {
  flex-direction: column;
}
</style>
