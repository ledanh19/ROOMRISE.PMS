<script setup>
import { useUiTone } from "@/utils/useUiTone";
import { computed } from "vue";
import ApexChart from "vue3-apexcharts";

const props = defineProps({
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  colors: { type: Array, default: () => ["#1E88E5", "#43A047", "#FB8C00"] },
  height: { type: [Number, String], default: 360 },
});

const { isDark, textColor } = useUiTone();

const gridColor = computed(() =>
  isDark.value ? "rgba(255,255,255,0.1)" : "rgba(0,0,0,0.1)"
);

function fmtValue(name, val) {
  if (val == null) return "";
  if (name.toLowerCase().includes("occ") || name.includes("Occupancy"))
    return `${val}%`;
  return (
    new Intl.NumberFormat("vi-VN", {
      maximumFractionDigits: 0,
    }).format(val) + "đ"
  );
}

const opts = computed(() => ({
  chart: {
    type: "line",
    toolbar: { show: false },
    background: "transparent",
    foreColor: textColor.value,
    zoom: { enabled: false },
  },
  theme: { mode: isDark.value ? "dark" : "light" },
  colors: props.colors,
  stroke: { curve: "smooth", width: 3 },
  dataLabels: { enabled: false },
  markers: {
    size: 5,
    strokeWidth: 2,
    hover: { size: 6 },
  },

  yaxis: [
    {
      title: { text: "VND", style: { color: textColor.value } },
      labels: {
        style: { colors: textColor.value },
        formatter: (v) => new Intl.NumberFormat("vi-VN").format(v),
      },
      axisTicks: { show: true },
    },
    {
      opposite: true,
      seriesName: "Occupancy",
      title: { text: "%", style: { color: textColor.value } },
      labels: {
        style: { colors: textColor.value },
        formatter: (v) => `${v.toFixed(0)}%`,
      },
      tickAmount: 5,
    },
  ],

  xaxis: {
    categories: props.categories,
    axisBorder: { show: false },
    labels: { style: { fontSize: "12px", colors: textColor.value } },
  },

  grid: { borderColor: gridColor.value },
  legend: {
    show: true,
    labels: { colors: textColor.value },
    position: "bottom",
  },

  tooltip: {
    shared: true,
    intersect: false,
    custom: ({ series, seriesIndex, dataPointIndex, w }) => {
      const bg = isDark.value
        ? "rgba(20,20,20,0.95)"
        : "rgba(255,255,255,0.98)";
      const fg = isDark.value ? "#fff" : "#111";
      const sub = isDark.value ? "rgba(255,255,255,0.78)" : "rgba(0,0,0,0.6)";
      const headerBg = isDark.value
        ? "rgba(255,255,255,0.02)"
        : "rgba(0,0,0,0.02)";
      const headerBorder = isDark.value
        ? "rgba(255,255,255,0.03)"
        : "rgba(0,0,0,0.04)";
      const dateLabel = w.globals.categoryLabels?.[dataPointIndex] ?? "";

      const rowsHtml = (w.globals.seriesNames || [])
        .map((name, i) => {
          const v = series[i]?.[dataPointIndex];
          if (v === null || v === undefined) return "";
          const color =
            (w.globals.colors && w.globals.colors[i]) ||
            props.colors[i] ||
            "#999";
          return `
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:6px 0;">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;">
              <span style="
                width:10px;height:10px;border-radius:50%;
                background:${color};
                display:inline-block;
                box-sizing:border-box;
                flex:0 0 10px;
              "></span>
              <span style="font-size:13px;color:${sub};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                ${name}
              </span>
            </div>
            <div style="font-size:13px;color:${fg};font-weight:700;white-space:nowrap;">
              ${fmtValue(name, v)}
            </div>
          </div>
        `;
        })
        .filter(Boolean)
        .join("");

      return `
      <div style="
        min-width:220px;
        border-radius:10px;
        overflow:hidden;
        border:1px solid rgba(0,0,0,0.06);
        background:${bg};
        box-shadow:0 8px 28px rgba(0,0,0,0.12);
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
        <div style="padding:10px 12px;">
          ${rowsHtml}
        </div>
      </div>
    `;
    },
  },
}));
</script>

<template>
  <ApexChart
    :options="opts"
    :series="props.series"
    type="line"
    :height="props.height"
  />
</template>
