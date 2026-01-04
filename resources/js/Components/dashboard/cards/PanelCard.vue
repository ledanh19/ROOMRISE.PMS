<script setup>
defineProps({
  title: { type: String, default: "" },
  icon: { type: String, default: "" }, // ví dụ: "tabler-chart-line"
  titleColor: { type: String, default: "var(--v-theme-on-surface)" },
  borderColor: {
    type: String,
    default: "color-mix(in oklab,var(--v-theme-on-surface) 10%,transparent)",
  },
  bgColor: { type: String, default: "var(--v-theme-surface)" },
  padding: { type: [Number, String], default: 12 },
});
</script>

<template>
  <VCard
    class="panel-card"
    :style="{
      '--bd': borderColor,
      '--bg': bgColor,
      '--tc': titleColor,
      paddingInline: typeof padding === 'number' ? padding + 'px' : padding,
      paddingBlock: typeof padding === 'number' ? padding + 'px' : padding,
    }"
  >
    <div class="panel-head" v-if="title || $slots.actions || icon">
      <div class="head-left">
        <VIcon v-if="icon" :icon="icon" size="22" class="head-ico" />
        <div class="head-title" :style="{ color: 'var(--tc)' }">
          {{ title }}
        </div>
      </div>
      <div class="head-actions">
        <slot name="actions" />
      </div>
    </div>
    <div class="panel-body">
      <slot />
    </div>
  </VCard>
</template>

<style scoped>
.panel-card {
  border: 1px solid var(--bd);
  border-radius: 12px;
  background: var(--bg);
  block-size: 100%;
}

.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-block-end: 10px;
}

.head-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.head-ico {
  display: grid;
  border: 1px solid color-mix(in oklab, var(--v-theme-primary) 16%, transparent);
  border-radius: 10px;
  background: color-mix(in oklab, var(--v-theme-primary) 8%, transparent);
  block-size: 30px;
  color: color-mix(in oklab, var(--v-theme-primary) 24%, transparent);
  inline-size: 30px;
  place-items: center;
}

.head-title {
  font-weight: 600;
}

.panel-body {
  min-block-size: 0;
}

.panel-head {
  margin-block-end: 0;
}
</style>
