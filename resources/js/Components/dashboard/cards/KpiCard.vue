<script setup>
import { Icon } from "@iconify/vue";
import { computed } from "vue";

const props = defineProps({
  icon: { type: String, default: "" },
  title: { type: String, required: true },
  value: { type: [String, Number], required: true },

  notePercent: { type: String, default: null },
  note: { type: String, default: "" },
  trend: { type: Number, default: null },
  compact: { type: Boolean, default: false },

  titleTone: { type: String, default: "default" },
  valueTone: { type: String, default: "default" },
  borderTone: { type: String, default: "default" },
  bgTone: { type: String, default: "default" },

  iconTone: { type: String, default: "primary" },
  iconShape: { type: String, default: "rounded" },
  iconSize: { type: String, default: "md" },

  bg: { type: String, default: "#fff" },
  color: { type: String, default: "rgb(85 80 80)" },

  iconBg: { type: String, default: "#fde7e7" },
  iconColor: { type: String, default: "#d32f2f" },
});

const TONES = new Set([
  "default",
  "primary",
  "success",
  "warning",
  "danger",
  "info",
  "muted",
]);

const toneOrDefault = (v) => (TONES.has(v) ? v : "default");

const kpiClass = computed(() => [
  `kpi--border-${toneOrDefault(props.borderTone)}`,
  `kpi--bg-${toneOrDefault(props.bgTone)}`,
  `kpi--title-${toneOrDefault(props.titleTone)}`,
  `kpi--value-${toneOrDefault(props.valueTone)}`,
  `kpi--icon-${toneOrDefault(props.iconTone)}`,
  `kpi--icon-shape-${
    ["circle", "rounded", "square"].includes(props.iconShape)
      ? props.iconShape
      : "rounded"
  }`,
  `kpi--icon-${
    ["sm", "md", "lg"].includes(props.iconSize) ? props.iconSize : "md"
  }`,
  { "is-compact": props.compact },
]);

const iconId = computed(() => {
  if (!props.icon) return null;
  if (props.icon.includes(":")) return props.icon;
  if (props.icon.startsWith("tabler-")) return "tabler:" + props.icon.slice(7);
  if (props.icon.startsWith("mdi-")) return "mdi:" + props.icon.slice(4);
  return props.icon;
});

const wrapperStyle = computed(() => ({
  backgroundColor: props.bg,
  color: props.color,
}));

const iconWrapperStyle = computed(() => ({
  backgroundColor: props.iconBg,
  color: props.iconColor,
}));
</script>

<template>
  <VCard
    class="kpi"
    :class="kpiClass"
    :style="{
      ...wrapperStyle,
      '--trendColor': props.iconColor,
      '--trendColorText': props.color,
    }"
  >
    <div class="row">
      <div class="left" :style="iconWrapperStyle">
        <span v-if="iconId" class="ico">
          <Icon :icon="iconId" width="18" height="18" />
        </span>
      </div>

      <slot name="actions" />
    </div>

    <div class="title">{{ title }}</div>
    <div class="val">{{ value }}</div>

    <div class="note-percent" v-if="notePercent">
      <strong>{{ notePercent }}</strong>
    </div>

    <div class="foot" v-if="note || trend !== null">
      <div
        class="trend"
        :class="trend >= 0 ? 'up' : 'down'"
        v-if="trend !== null"
      >
        <span class="ico">
          <Icon
            :icon="trend >= 0 ? 'tabler:trending-up' : 'tabler:trending-down'"
            width="16"
            height="16"
          />
        </span>
        <span>{{ Math.abs(trend).toFixed(1) }}%</span>
        <span class="note">{{ note }}</span>
      </div>
    </div>
  </VCard>
</template>

<style scoped>
.kpi {
  --kpi-bd: color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  --kpi-bg: var(--v-theme-surface);
  --kpi-title: var(--v-theme-on-surface);
  --kpi-value: var(--v-theme-on-surface);

  padding: 12px;
  border: 1px solid var(--kpi-bd);
  border-radius: 12px;
  background: var(--kpi-bg);

  transition: transform 0.22s cubic-bezier(0.2, 0.8, 0.2, 1),
    box-shadow 0.22s cubic-bezier(0.2, 0.8, 0.2, 1);
  will-change: transform;
}

.kpi:hover {
  transform: translateY(-4px) scale(1.015);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.06);
}

.row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-block-end: 6px;
}

.left {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 8px;
  border-radius: 6px;

  transition: transform 0.22s cubic-bezier(0.2, 0.8, 0.2, 1);
  will-change: transform;
}

.kpi:hover .left {
  transform: scale(1.08) rotate(-2deg);
}

.title {
  color: var(--kpi-title);
  font-size: 20px;
  opacity: 0.8;

  transition: transform 0.22s ease;
}

.val {
  color: var(--kpi-value);
  font-size: 28px;
  font-weight: 700;
  line-height: 1.2;

  transition: transform 0.22s ease;
}

.kpi:hover .title,
.kpi:hover .val {
  transform: translateY(-1px);
}

.kpi.is-compact .val {
  font-size: 22px;
}

.foot {
  display: flex;
  align-items: center;
  font-size: 12px;
  gap: 10px;
  margin-block-start: 6px;
}

.trend {
  display: flex;
  align-items: center;
  color: var(--trendColor);
}

.trend .ico svg,
.trend span {
  color: inherit;
  fill: currentColor;
  stroke: currentColor;
}

.note {
  padding-left: 4px;
  opacity: 0.7;
}

.ico {
  display: grid;
  border: 1px solid
    var(--ico-bd, color-mix(in oklab, var(--v-theme-primary) 16%, transparent));
  border-radius: var(--ico-radius, 10px);
  background: var(
    --ico-bg,
    color-mix(in oklab, var(--v-theme-primary) 8%, transparent)
  );
  block-size: var(--ico-size, 28px);
  inline-size: var(--ico-size, 28px);
  place-items: center;
}

.kpi--icon-sm {
  --ico-size: 24px;
}
.kpi--icon-md {
  --ico-size: 28px;
}
.kpi--icon-lg {
  --ico-size: 34px;
}

.kpi--icon-shape-circle {
  --ico-radius: 999px;
}
.kpi--icon-shape-rounded {
  --ico-radius: 10px;
}
.kpi--icon-shape-square {
  --ico-radius: 6px;
}

.kpi--border-default {
  --kpi-bd: color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
}
.kpi--border-primary {
  --kpi-bd: color-mix(in oklab, var(--v-theme-primary) 35%, transparent);
}
.kpi--border-success {
  --kpi-bd: color-mix(in oklab, var(--v-theme-success) 35%, transparent);
}
.kpi--border-warning {
  --kpi-bd: color-mix(in oklab, var(--v-theme-warning) 35%, transparent);
}
.kpi--border-danger {
  --kpi-bd: color-mix(in oklab, var(--v-theme-error) 35%, transparent);
}
.kpi--border-info {
  --kpi-bd: color-mix(in oklab, var(--v-theme-info) 35%, transparent);
}
.kpi--border-muted {
  --kpi-bd: color-mix(in oklab, var(--v-theme-on-surface) 18%, transparent);
}
.kpi--bg-default {
  --kpi-bg: var(--v-theme-surface);
}
.kpi--bg-primary {
  --kpi-bg: color-mix(
    in oklab,
    var(--v-theme-primary) 6%,
    var(--v-theme-surface)
  );
}
.kpi--bg-success {
  --kpi-bg: color-mix(
    in oklab,
    var(--v-theme-success) 6%,
    var(--v-theme-surface)
  );
}
.kpi--bg-warning {
  --kpi-bg: color-mix(
    in oklab,
    var(--v-theme-warning) 6%,
    var(--v-theme-surface)
  );
}
.kpi--bg-danger {
  --kpi-bg: color-mix(
    in oklab,
    var(--v-theme-error) 6%,
    var(--v-theme-surface)
  );
}
.kpi--bg-info {
  --kpi-bg: color-mix(in oklab, var(--v-theme-info) 6%, var(--v-theme-surface));
}
.kpi--bg-muted {
  --kpi-bg: color-mix(
    in oklab,
    var(--v-theme-on-surface) 3%,
    var(--v-theme-surface)
  );
}

.kpi--title-default {
  --kpi-title: var(--v-theme-on-surface);
}
.kpi--title-primary {
  --kpi-title: var(--v-theme-primary);
}
.kpi--title-success {
  --kpi-title: var(--v-theme-success);
}
.kpi--title-warning {
  --kpi-title: var(--v-theme-warning);
}
.kpi--title-danger {
  --kpi-title: var(--v-theme-error);
}
.kpi--title-info {
  --kpi-title: var(--v-theme-info);
}
.kpi--title-muted {
  --kpi-title: color-mix(in oklab, var(--v-theme-on-surface) 70%, transparent);
}

.kpi--value-default {
  --kpi-value: var(--v-theme-on-surface);
}
.kpi--value-primary {
  --kpi-value: var(--v-theme-primary);
}
.kpi--value-success {
  --kpi-value: var(--v-theme-success);
}
.kpi--value-warning {
  --kpi-value: var(--v-theme-warning);
}
.kpi--value-danger {
  --kpi-value: var(--v-theme-error);
}
.kpi--value-info {
  --kpi-value: var(--v-theme-info);
}
.kpi--value-muted {
  --kpi-value: color-mix(in oklab, var(--v-theme-on-surface) 70%, transparent);
}

@media (prefers-reduced-motion: reduce) {
  .kpi,
  .left,
  .title,
  .val {
    transition: none;
  }
}
</style>
