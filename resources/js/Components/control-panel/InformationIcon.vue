<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    :stroke="color || 'currentColor'"
    :stroke-width="strokeWidth"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="info-icon"
    aria-hidden="true"
    focusable="false"
  >
    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
    <path d="M12 9h.01" />
    <path d="M11 12h1v4h1" />
  </svg>
</template>

<script setup>
defineProps({
  size: { type: [Number, String], default: 20 },
  strokeWidth: { type: [Number, String], default: 1.8 },
  color: { type: String, default: null },
});
</script>

<style scoped>
.info-icon {
  display: block;
  color: inherit;
}
</style>
