<template>
  <div class="stat-mini" :class="toneClass">
    <!-- Icon -->
    <div class="icon-wrap">
      <VIcon :icon="icon" size="22" />
    </div>

    <!-- Content -->
    <div class="content">
      <div class="label">{{ title }}</div>

      <div class="value-row">
        <div class="value">{{ value }}</div>

        <!-- Trend -->
        <div
          v-if="trend"
          class="pill"
          :class="trend === 'up' ? 'pill-up' : 'pill-down'"
        >
          <VIcon :icon="trendIcon" size="14" />
          <span>{{ trendText || 0 }}</span>
        </div>

        <!-- Currency -->
        <div v-if="currency" class="pill" :class="currencyTone">
          <span>{{ currency }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  title: String,
  value: [String, Number],

  icon: {
    type: String,
    default: "tabler-chart-bar",
  },

  trend: {
    type: String, // up | down
    default: null,
  },
  trendText: {
    type: [String, Number],
    default: "",
  },

  currency: {
    type: String,
    default: null,
  },

  tone: {
    type: String, // success | error | info | null
    default: null,
  },
});

const trendIcon = computed(() =>
  props.trend === "up" ? "tabler-trending-up" : "tabler-trending-down"
);

const currencyTone = computed(() =>
  props.tone === "error" ? "pill-down" : "pill-up"
);
</script>

<style scoped>
.stat-mini {
  display: flex;
  align-items: center;
  gap: 0.75rem;

  padding: 0.7rem 0.8rem;
  border-radius: 10px;
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-border-color), 0.25);
}

/* Icon */
.icon-wrap {
  inline-size: 38px;
  block-size: 38px;
  border-radius: 10px;

  display: flex;
  align-items: center;
  justify-content: center;

  background: rgba(var(--v-theme-primary), 0.08);
  color: rgb(var(--v-theme-primary));
}

/* Content */
.content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.label {
  font-size: 0.78rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

.value-row {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.value {
  font-size: 1.05rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.95);
}

/* Pills */
.pill {
  display: inline-flex;
  align-items: center;
  gap: 2px;

  padding: 2px 6px;
  font-size: 0.68rem;
  font-weight: 500;
  border-radius: 999px;
  border: 1px solid transparent;
}

.pill-up {
  color: rgb(var(--v-theme-success));
  background: rgba(var(--v-theme-success), 0.08);
  border-color: rgba(var(--v-theme-success), 0.25);
}

.pill-down {
  color: rgb(var(--v-theme-error));
  background: rgba(var(--v-theme-error), 0.08);
  border-color: rgba(var(--v-theme-error), 0.25);
}

/* Tones */
.tone-success .icon-wrap {
  background: rgba(var(--v-theme-success), 0.12);
  color: rgb(var(--v-theme-success));
}

.tone-error .icon-wrap {
  background: rgba(var(--v-theme-error), 0.12);
  color: rgb(var(--v-theme-error));
}

.tone-info .icon-wrap {
  background: rgba(var(--v-theme-info), 0.12);
  color: rgb(var(--v-theme-info));
}
</style>
