<script setup>
import { useUiTone } from "@/utils/useUiTone";
import { computed } from "vue";
import ApexChart from "vue3-apexcharts";

const props = defineProps({
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 280 },
  color: { type: String, default: "#FB8C00" },
  smooth: { type: Boolean, default: true },
  strokeWidth: { type: Number, default: 3 },
  opacityFrom: { type: Number, default: 0.35 },
  opacityTo: { type: Number, default: 0.05 },
});

const { isDark, textColor } = useUiTone();

const gridColor = computed(() =>
  isDark.value ? "rgba(255,255,255,.10)" : "rgba(0,0,0,.08)"
);

function fmtNight(v) {
  if (v == null || isNaN(v)) return "";
  return `${v} đêm`;
}

const opts = computed(() => ({
  chart: {
    type: "area",
    toolbar: { show: false },
    background: "transparent",
    foreColor: textColor.value,
    zoom: { enabled: false },
  },
  markers: {
    size: 5,
    strokeWidth: 2,
    hover: { size: 6 },
  },
  theme: { mode: isDark.value ? "dark" : "light" },
  colors: [props.color],
  stroke: {
    curve: props.smooth ? "smooth" : "straight",
    width: props.strokeWidth,
  },
  fill: {
    type: "gradient",
    gradient: { opacityFrom: props.opacityFrom, opacityTo: props.opacityTo },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.categories,
    axisBorder: { show: false },
    labels: { style: { fontSize: "12px", colors: textColor.value } },
  },
  yaxis: {
    decimalsInFloat: 1,
    min: 0,
    labels: {
      style: { colors: textColor.value },
      formatter: (v) => new Intl.NumberFormat("vi-VN").format(v),
    },
    title: { text: "Đêm", style: { color: textColor.value } },
  },
  grid: { borderColor: gridColor.value },
  tooltip: {
    shared: false,
    intersect: false,
    custom: ({ series, seriesIndex, dataPointIndex, w }) => {
      const dateLabel =
        (w.globals.categoryLabels &&
          w.globals.categoryLabels[dataPointIndex]) ||
        "";
      const name = w.globals.seriesNames?.[seriesIndex] ?? "LOS";
      const val = series?.[seriesIndex]?.[dataPointIndex];

      const bg = isDark.value
        ? "rgba(20,20,20,0.95)"
        : "rgba(255,255,255,0.98)";
      const fg = isDark.value ? "rgba(255,255,255,0.94)" : "rgba(0,0,0,0.88)";
      const sub = isDark.value ? "rgba(255,255,255,0.78)" : "rgba(0,0,0,0.6)";
      const headerBg = isDark.value
        ? "rgba(255,255,255,0.02)"
        : "rgba(0,0,0,0.02)";
      const headerBorder = isDark.value
        ? "rgba(255,255,255,0.03)"
        : "rgba(0,0,0,0.04)";
      const bd = isDark.value
        ? "1px solid rgba(255,255,255,0.04)"
        : "1px solid rgba(0,0,0,0.08)";
      const shadow = isDark.value
        ? "0 8px 28px rgba(0,0,0,0.6)"
        : "0 8px 28px rgba(0,0,0,0.12)";

      // safe format for value
      const displayVal = fmtNight(val || 0);

      // dot color fallback to first color prop
      const dotColor =
        (w.globals.colors && w.globals.colors[seriesIndex]) ||
        props.color ||
        "#FB8C00";

      return `
      <div style="
        min-width:160px;
        border-radius:10px;
        overflow:hidden;
        border:${bd};
        background:${bg};
        box-shadow:${shadow};
        font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
      ">
        <div style="
          background:${headerBg};
          color:${fg};
          padding:8px 12px;
          font-size:12px;
          font-weight:700;
          border-bottom:1px solid ${headerBorder};
        ">
          ${dateLabel}
        </div>
        <div style="padding:10px 12px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
          <div style="display:flex;align-items:center;gap:10px;min-width:0;">
            <span style="
              width:10px;height:10px;border-radius:50%;
              background:${dotColor};
              display:inline-block;flex:0 0 10px;
              box-sizing:border-box;
            "></span>
            <div style="font-size:13px;color:${sub};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              ${name}
            </div>
          </div>
          <div style="font-size:13px;color:${fg};font-variant-numeric:tabular-nums;font-weight:700;white-space:nowrap;">
            ${displayVal}
          </div>
        </div>
      </div>
    `;
    },
  },
}));
</script>

<template>
  <ApexChart :series="series" :options="opts" type="area" :height="height" />
</template>
