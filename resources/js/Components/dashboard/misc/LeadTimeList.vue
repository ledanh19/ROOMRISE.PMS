<script setup>
import { computed } from "vue";

const props = defineProps({
  items: { type: Array, required: true },
  unit: { type: String, default: "bookings" },
  computePercent: { type: Boolean, default: true },
  barColor: { type: String, default: "primary" },
  trackColor: { type: String, default: "surface-variant" },
  height: { type: Number, default: 8 },
  rounded: { type: Boolean, default: true },
});

const total = computed(() =>
  props.items.reduce((s, it) => s + (Number(it.value) || 0), 0)
);

function pct(it) {
  return Math.round(it.percent ?? 0);
}

function fmtCount(v) {
  return (Number(v) || 0).toLocaleString("vi-VN");
}
</script>

<template>
  <div class="leadtime-list">
    <div v-for="(it, i) in props.items" :key="i" class="row">
      <div class="top">
        <div class="label">{{ it.label }}</div>
        <div class="right">
          <span class="count" v-if="it.value"
            >{{ fmtCount(it.value) }} {{ unit }}</span
          >
          <b class="percent">{{ pct(it) }}%</b>
        </div>
      </div>

      <v-progress-linear
        class="prog"
        :model-value="pct(it)"
        :color="it.color || barColor"
        :bg-color="trackColor"
        :height="height"
        :rounded="rounded"
      />
    </div>
  </div>
</template>

<style scoped>
.leadtime-list {
  display: grid;
  gap: 14px;
}

.row {
  display: grid;
  gap: 6px;
}

.top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.label {
  font-size: 13px;
  font-weight: 600;
}

.right {
  display: inline-flex;
  align-items: center;
  font-size: 12px;
  gap: 10px;
}

.count {
  opacity: 0.8;
}

.percent {
  font-weight: 800;
}
</style>
