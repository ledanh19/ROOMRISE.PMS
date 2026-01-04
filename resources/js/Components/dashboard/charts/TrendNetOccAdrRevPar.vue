<script setup>
import { useUiTone } from "@/utils/useUiTone";
import { computed } from "vue";
import ApexChart from "vue3-apexcharts";

const props = defineProps({
  categories: { type: Array, required: true },
  netData: { type: Array, required: true },
  revpacData: { type: Array, required: true },
  adrData: { type: Array, required: true },
  revparData: { type: Array, required: true },
  height: { type: [Number, String], default: 360 },
  colors: {
    type: Array,
    default: () => ["#2E7D32", "#1E88E5", "#FB8C00", "#D81B60"],
  },
});

const { isDark, textColor } = useUiTone();

const gridColor = computed(() =>
  isDark.value ? "rgba(255,255,255,0.08)" : "rgba(0,0,0,0.06)"
);

const options = computed(() => ({
  chart: {
    type: "line",
    toolbar: { show: false },
    background: "transparent",
    foreColor: textColor.value,
    zoom: { enabled: false },
    selection: { enabled: false },
    animations: { enabled: true },
  },
  theme: { mode: isDark.value ? "dark" : "light" },
  colors: props.colors,
  stroke: {
    curve: "smooth",
    width: [3, 3, 3, 3],
  },

  markers: {
    size: 3,
    strokeWidth: 2,
    hover: { size: 5 },
  },

  dataLabels: { enabled: false },

  xaxis: {
    categories: props.categories,
    axisBorder: { show: false },
    labels: { style: { fontSize: "12px", colors: textColor.value } },
    crosshairs: {
      show: false,
      stroke: { width: 1 },
    },
  },

  yaxis: [
    {
      title: { text: "VND", style: { color: textColor.value } },
      labels: {
        style: { colors: textColor.value },
        formatter: (v) => new Intl.NumberFormat("vi-VN").format(v),
      },
      min:
        Math.min(...props.netData, ...props.adrData, ...props.revparData) - 10,
      max:
        Math.max(...props.netData, ...props.adrData, ...props.revparData) + 10,
    },
  ],

  grid: { borderColor: gridColor.value },

  legend: { show: false, labels: { colors: textColor.value } },

  markers: {
    size: 5,
    strokeWidth: 2,
    hover: { size: 7 },
  },
  tooltip: {
    shared: true,
    intersect: false,
    custom: ({ series, seriesIndex, dataPointIndex, w }) => {
      const date = w.globals.categoryLabels?.[dataPointIndex] ?? "";
      const seriesMeta = (w.config && w.config.series) || [];
      const values = series.map((s) => s?.[dataPointIndex]);

      const bg = isDark.value
        ? "rgba(20,20,22,0.92)"
        : "rgba(255,255,255,0.98)";
      const fg = isDark.value ? "rgba(255,255,255,0.92)" : "rgba(0,0,0,0.88)";
      const sub = isDark.value ? "rgba(255,255,255,0.72)" : "rgba(0,0,0,0.6)";
      const bd = isDark.value
        ? "1px solid rgba(255,255,255,0.06)"
        : "1px solid rgba(0,0,0,0.06)";
      const shadow = isDark.value
        ? "0 6px 20px rgba(0,0,0,0.6)"
        : "0 6px 20px rgba(0,0,0,0.12)";

      const fmt = (v) => {
        if (v === undefined || v === null || v === "") return "0";
        const n = Number(v);
        if (Number.isNaN(n)) return String(v);
        return new Intl.NumberFormat("vi-VN").format(Math.round(n)) + "đ";
      };

      const rowsHtml = (seriesMeta || [])
        .map((meta, i) => {
          const name = meta?.name ?? `Series ${i + 1}`;
          const color =
            (props && props.colors && props.colors[i]) ||
            (w.globals.colors && w.globals.colors[i]) ||
            "#999";
          const val = fmt(values[i]);

          return `
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:6px 0;">
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="
                width:10px;
                height:10px;
                border-radius:50%;
                background:${color};
                display:inline-block;
                box-sizing:border-box;
                border:2px solid ${bg};
              "></span>
              <span style="font-size:13px;color:${sub};">${name}</span>
            </div>
            <div style="font-size:13px;color:${fg};font-weight:700;">${val}</div>
          </div>
        `;
        })
        .join("");

      // Header (title) style separated
      const headerBg = isDark.value
        ? "rgba(255,255,255,0.02)"
        : "rgba(0,0,0,0.02)";
      const headerColor = isDark.value
        ? "rgba(255,255,255,0.95)"
        : "rgba(0,0,0,0.85)";

      return `
      <div style="
        width:auto;
        min-width:180px;
        border-radius:8px;
        overflow:hidden;
        border:${bd};
        box-shadow:${shadow};
        background:${bg};
        font-family:system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
      ">
        <div style="
          background:${headerBg};
          color:${headerColor};
          padding:8px 12px;
          font-size:12px;
          font-weight:700;
          border-bottom:1px solid ${
            isDark.value ? "rgba(255,255,255,0.03)" : "rgba(0,0,0,0.04)"
          };
        ">
          ${date}
        </div>
        <div style="padding:10px 12px;">
          ${rowsHtml}
        </div>
      </div>
    `;
    },
  },
}));

const series = computed(() => [
  { name: "Doanh thu (Net)", type: "line", data: props.netData, yAxisIndex: 0 },
  { name: "RevPAC", type: "line", data: props.revpacData, yAxisIndex: 0 },
  { name: "ADR", type: "line", data: props.adrData, yAxisIndex: 0 },
  { name: "RevPAR", type: "line", data: props.revparData, yAxisIndex: 0 },
]);
</script>

<template>
  <ApexChart :series="series" :options="options" :height="height" />
</template>
