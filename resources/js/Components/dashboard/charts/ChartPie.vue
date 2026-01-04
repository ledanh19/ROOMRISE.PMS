<script setup>
import { formatCurrencyVND } from "@/utils/helper";
import { useUiTone } from "@/utils/useUiTone";
import { computed } from "vue";
import ApexChart from "vue3-apexcharts";

const { isDark, textColor: uiTextColor } = useUiTone();

const props = defineProps({
  series: { type: Array, required: true },
  labels: { type: Array, required: true },
  hint: { type: Array, required: false },
  revenues: { type: Array, required: false },

  height: { type: [Number, String], default: 260 },
  legendPosition: { type: String, default: "bottom" },

  colors: { type: Array, default: () => [] },
  tooltipTheme: { type: String, default: "light" },
  dataLabelFormatter: { type: Function, default: (v) => `${v.toFixed(1)}%` },
  options: { type: Object, default: () => ({}) },
});

const count = computed(() =>
  Math.min(
    Array.isArray(props.series) ? props.series.length : 0,
    Array.isArray(props.labels) ? props.labels.length : 0
  )
);
const seriesSafe = computed(() => props.series.slice(0, count.value));
const labelsSafe = computed(() => props.labels.slice(0, count.value));
const colorsSafe = computed(() =>
  props.colors?.length ? props.colors.slice(0, count.value) : undefined
);

const bgColor = computed(() => (isDark.value ? "#0f1720" : "#fff"));
const headerBg = computed(() =>
  isDark.value ? "rgba(255,255,255,0.03)" : "#f3f4f6"
);
const borderColor = computed(() =>
  isDark.value ? "rgba(255,255,255,0.06)" : "rgba(0,0,0,0.08)"
);

function safeString(v) {
  if (v === undefined || v === null) return "";
  return String(v);
}

function renderCustomTooltip({ series, seriesIndex, dataPointIndex, w }) {
  try {
    const idx = Number.isFinite(dataPointIndex)
      ? dataPointIndex
      : Number.isFinite(seriesIndex)
      ? seriesIndex
      : 0;

    const label =
      (w && w.globals && w.globals.labels && w.globals.labels[idx]) ||
      labelsSafe.value?.[idx] ||
      "";

    const dotColor =
      (w && w.globals && w.globals.colors && w.globals.colors[idx]) ||
      (colorsSafe.value && colorsSafe.value[idx]) ||
      "#3b82f6";

    let bookings = "";
    if (Array.isArray(props.hint)) {
      const h = props.hint[idx];
      if (h !== undefined && h !== null) {
        bookings =
          typeof h === "object" && !Array.isArray(h)
            ? h.bookings ?? h.count ?? ""
            : h;
      }
    }

    let revenue = "";
    if (
      Array.isArray(props.revenues) &&
      props.revenues[idx] !== undefined &&
      props.revenues[idx] !== null
    ) {
      revenue = props.revenues[idx];
    } else if (Array.isArray(props.hint)) {
      const h = props.hint[idx];
      if (h && typeof h === "object" && !Array.isArray(h)) {
        revenue = h.revenue ?? h.revenues ?? h.amount ?? "";
      }
    }

    const hasBooking =
      bookings !== "" && bookings !== undefined && bookings !== null;
    const hasRevenue =
      revenue !== "" && revenue !== undefined && revenue !== null;

    const totals = (w && w.globals && w.globals.seriesTotals) || [];
    const total = totals.reduce((s, n) => s + (Number(n) || 0), 0) || 0;
    const value = Number(totals[idx] || 0);
    const pct = total === 0 ? 0 : (value / total) * 100;

    const html = `
      <div class="apx-tooltip" style="background:${bgColor.value}; color:${
      uiTextColor.value
    }; border:1px solid ${borderColor.value}">
        <div class="apx-header" style="background:${
          headerBg.value
        }; color:inherit">${safeString(label)}</div>
        <div class="apx-body">
          ${
            hasBooking
              ? `
                <div class="apx-row">
                  <span class="apx-dot" style="background:${dotColor}"></span>
                  <span class="apx-left">Đặt phòng:</span>
                  <span class="apx-right">${safeString(bookings)}</span>
                </div>`
              : ""
          }

          ${
            hasRevenue
              ? `
            <div class="apx-row">
              <span class="apx-dot" style="background:${dotColor}"></span>
              <span class="apx-left">Doanh thu:</span>
              <span class="apx-right">${safeString(
                formatCurrencyVND(revenue)
              )}</span>
            </div>`
              : ""
          }
          <div class="apx-row">
            <span class="apx-dot" style="background:${dotColor}"></span>
            <span class="apx-left">Phần trăm:</span>
            <span class="apx-right">${pct.toFixed(1)}%</span>
          </div>
        </div>
      </div>`.trim();

    return html;
  } catch (err) {
    console.error("renderCustomTooltip error:", err);
    return `<div class="apx-tooltip">${String(
      series && series[seriesIndex]
    )}</div>`;
  }
}

const merged = computed(() => ({
  chart: {
    type: "pie",
    height: props.height,
    foreColor: uiTextColor.value,
  },

  labels: labelsSafe.value,
  colors: colorsSafe.value,

  legend: {
    show: false,
  },

  dataLabels: {
    enabled: false,
  },

  tooltip: {
    enabled: true,
    shared: false,
    intersect: true,
    theme: props.tooltipTheme,
    custom: renderCustomTooltip,
  },

  stroke: { show: false, width: 0 },

  ...props.options,
}));
</script>

<template>
  <ApexChart
    type="pie"
    :options="merged"
    :series="seriesSafe"
    :height="height"
  />
</template>

<style>
.apx-tooltip {
  display: inline-block;
  overflow: hidden;
  border-radius: 6px;
  box-shadow: 0 6px 14px rgba(0, 0, 0, 6%);
  font-size: 12px;
  line-height: 1;
  min-inline-size: 150px;
  white-space: nowrap;
}

.apx-header {
  border-block-end: 1px solid rgba(0, 0, 0, 6%);
  font-size: 12px;
  font-weight: bold !important;
  padding-block: 6px;
  padding-inline: 8px;
}

.apx-body {
  padding-block: 6px;
  padding-inline: 8px;
}

.apx-row {
  display: flex;
  align-items: center;
  gap: 6px;
  padding-block: 2px;
  padding-inline: 0;
}

.apx-dot {
  flex: 0 0 7px;
  border-radius: 7px;
  block-size: 7px;
  inline-size: 7px;
  margin-inline-end: 6px;
}

.apx-left {
  flex: 1 1 auto;
  font-size: 12px;
  opacity: 0.95;
}

.apx-right {
  font-size: 12px;
  font-weight: 600;
  min-inline-size: 68px;
  text-align: end;
  white-space: nowrap;
}

.apexcharts-tooltip.dark .apx-header {
  border-block-end-color: rgba(255, 255, 255, 4%);
}

.apexcharts-tooltip.light .apx-header {
  border-block-end-color: rgba(0, 0, 0, 6%);
}
</style>
