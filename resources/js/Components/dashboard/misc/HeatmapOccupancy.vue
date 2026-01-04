<script setup>
import { computed } from "vue";

const props = defineProps({
  weekdays: { type: Array, required: true },
  roomTypes: { type: Array, required: true },
  matrix: { type: Array, required: true },
  colors: {
    type: Object,
    default: () => ({
      gte95: "#2ECC71",
      b8594: "#1E88E5",
      o7584: "#FB8C00",
      lt75: "#8E44AD",
      head: "color-mix(in oklab,var(--v-theme-on-surface) 6%,transparent)",
    }),
  },
  cellRadius: { type: Number, default: 12 },
  cellPadding: { type: Number, default: 12 },
  labelColWidth: { type: String, default: "72px" },

  // NEW: width cho mỗi cột room type (vd: "110px")
  colWidth: { type: String, default: "110px" },
});

function colorFor(pct, c) {
  if (pct >= 95) return c.gte95;
  if (pct >= 85) return c.b8594;
  if (pct >= 75) return c.o7584;
  return c.lt75;
}

/*
  build grid template using fixed col widths so the whole grid can overflow horizontally.
  e.g. "72px repeat(8, 110px)"
*/
const gridTemplate = computed(() => {
  const count = Array.isArray(props.roomTypes) ? props.roomTypes.length : 0;
  // fallback if colWidth includes non-px values; we pass string through
  return `${props.labelColWidth} repeat(${count}, ${props.colWidth})`;
});
</script>

<template>
  <div class="heatmap">
    <div class="hm-head" :style="{ gridTemplateColumns: gridTemplate }">
      <div class="cell empty" />
      <div
        v-for="rt in roomTypes"
        :key="rt"
        class="cell head room-type-header"
        :title="rt"
      >
        <span class="truncate-text" tabindex="0" aria-label="Room type">
          <span class="text">{{ rt }}</span>
          <span class="full-tooltip" role="tooltip" aria-hidden="true">{{
            rt
          }}</span>
        </span>
      </div>
    </div>

    <div
      v-for="(wd, rIdx) in weekdays"
      :key="wd"
      class="hm-row"
      :style="{ gridTemplateColumns: gridTemplate }"
    >
      <div class="cell head">{{ wd }}</div>
      <div
        v-for="(rt, cIdx) in roomTypes"
        :key="rt + '-' + rIdx"
        class="cell val"
        :style="{
          background: colorFor(matrix[rIdx][cIdx], colors),
          borderRadius: cellRadius + 'px',
          padding: cellPadding + 'px',
        }"
      >
        {{ matrix[rIdx][cIdx] }}%
      </div>
    </div>

    <div class="legend">
      <span><i class="lg" :style="{ background: colors.gte95 }"></i> ≥95%</span>
      <span
        ><i class="lg" :style="{ background: colors.b8594 }"></i> 85–94%</span
      >
      <span
        ><i class="lg" :style="{ background: colors.o7584 }"></i> 75–84%</span
      >
      <span
        ><i class="lg" :style="{ background: colors.lt75 }"></i> &lt;75%</span
      >
    </div>
  </div>
</template>

<style scoped>
.heatmap {
  display: grid;

  /* allow horizontal scroll when grid is wider than container */
  overflow: auto;
  box-sizing: border-box;
  gap: 12px;
  -webkit-overflow-scrolling: touch;
}

/* ensure inner grid uses its intrinsic width (so overflow triggers) */
.hm-head,
.hm-row {
  display: grid;
  align-items: start;
  gap: 12px;
  min-inline-size: fit-content; /* important: don't let parent squeeze columns */
}

/* keep cells from collapsing */
.cell {
  box-sizing: border-box;
  padding: 12px;
  border-radius: 12px;
  font-weight: 800;
  min-inline-size: 0;
  text-align: center;
}

/* head style */
.cell.head {
  overflow: hidden;
  border-radius: 12px;
  background: color-mix(in oklab, var(--v-theme-on-surface) 6%, transparent);
  font-weight: 700;
}

/* value cell style */
.cell.val {
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 8%);
  color: #fff;
  min-inline-size: 0;
}

/* legend */
.legend {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  font-size: 12px;
  gap: 14px;
  opacity: 0.9;
}

.legend .lg {
  display: inline-block;
  border-radius: 6px;
  block-size: 14px;
  box-shadow: 0 0 0 1px rgba(0, 0, 0, 6%) inset;
  inline-size: 14px;
  margin-inline-end: 6px;
}

/* room type header truncation + tooltip */
.room-type-header {
  position: relative;
  overflow: visible;
  min-inline-size: 0;
}

.room-type-header .truncate-text {
  position: relative;
  display: block;
  box-sizing: border-box;
  inline-size: 100%;
  max-inline-size: 160px;
  padding-block: 2px;
  vertical-align: top;
}

.room-type-header .truncate-text .text {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  color: var(--v-theme-on-surface, #111);
  font-size: clamp(11px, 2vw, 13px);
  font-weight: 700;
  -webkit-line-clamp: 2;
  line-height: 1.25;
  max-block-size: calc(1.25em * 2);
  text-overflow: ellipsis;
  white-space: normal;
}

.cell.head {
  overflow: hidden;
  max-inline-size: 180px;
  min-inline-size: 80px;
}

.room-type-header .truncate-text .full-tooltip {
  position: absolute;
  z-index: 120;
  display: none;
  box-sizing: border-box;
  border: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 6%, transparent);
  border-radius: 8px;
  background: var(--v-theme-surface, #fff);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 12%);
  color: var(--v-theme-on-surface, #111);
  font-size: 13px;
  font-weight: 600;
  inset-block-start: calc(100% + 8px);
  inset-inline-start: 0;
  line-height: 1.3;
  max-inline-size: 420px;
  min-inline-size: 160px;
  opacity: 0;
  padding-block: 8px;
  padding-inline: 10px;
  pointer-events: none;
  transform: translateY(-6px) scale(0.98);
  transform-origin: top left;
  transition: opacity 0.14s ease, transform 0.14s ease;
  white-space: normal;
  word-break: break-word;
}

.room-type-header .truncate-text:hover .full-tooltip,
.room-type-header .truncate-text:focus-within .full-tooltip {
  display: block;
  opacity: 1;
  pointer-events: auto;
  transform: translateY(0) scale(1);
}

@media (max-height: 420px) {
  .room-type-header .truncate-text .full-tooltip {
    inset-block: auto calc(100% + 8px);
  }
}

@media (max-width: 480px) {
  .room-type-header .truncate-text .full-tooltip {
    font-size: 12px;
    max-inline-size: 260px;
    min-inline-size: 140px;
  }
}

.cell.head .truncate-text,
.cell.head .truncate-text .text {
  display: block;
  overflow: hidden;
}

.room-type-header .truncate-text .full-tooltip,
.room-type-header .truncate-text .full-tooltip * {
  user-select: text;
}

.room-type-header .truncate-text:focus-within {
  border-radius: 8px;
  outline: 2px solid
    color-mix(in oklab, var(--v-theme-primary, #1e88e5) 20%, transparent);
  outline-offset: 3px;
}

/* keep defaults for head sizes */
.cell.head {
  max-inline-size: 180px;
  min-inline-size: 80px;
}
</style>
