<script setup>
import { useUiTone } from "@/utils/useUiTone";
import { computed } from "vue";
import ApexChart from "vue3-apexcharts";

const props = defineProps({
  categories: { type: Array, required: true },
  adrData: { type: Array, required: true },
  revparData: { type: Array, required: true },
  height: { type: [Number, String], default: 300 },
  colors: { type: Array, default: () => ["#1E88E5", "#2E7D32"] },
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
  },
  theme: { mode: isDark.value ? "dark" : "light" },
  colors: props.colors,
  stroke: { curve: "smooth", width: 3 },
  dataLabels: { enabled: false },
  markers: {
    size: 5,
    strokeWidth: 2,
    hover: { size: 5 },
  },
  xaxis: {
    categories: props.categories,
    axisBorder: { show: false },
    labels: { style: { colors: textColor.value } },
  },
  yaxis: {
    title: { text: "VND", style: { color: textColor.value } },
    labels: {
      style: { colors: textColor.value },
      formatter: (v) => new Intl.NumberFormat("vi-VN").format(v),
    },
    min: 0,
    max: Math.max(...props.adrData, ...props.revparData) + 10,
  },
  grid: { borderColor: gridColor.value },
  legend: { show: true, labels: { colors: textColor.value } },
  tooltip: {
    shared: true,
    intersect: false,
    custom: ({ series, dataPointIndex, w }) => {
      const date = w.globals.categoryLabels?.[dataPointIndex] ?? "";
      const seriesMeta = (w.config && w.config.series) || [];
      const values = series.map((s) => s?.[dataPointIndex]);

      const bg = isDark.value
        ? "rgba(20,20,22,0.92)"
        : "rgba(255,255,255,0.96)";
      const fg = isDark.value ? "rgba(255,255,255,0.94)" : "rgba(0,0,0,0.88)";
      const sub = isDark.value ? "rgba(255,255,255,0.72)" : "rgba(0,0,0,0.6)";
      const bd = isDark.value
        ? "1px solid rgba(255,255,255,0.06)"
        : "1px solid rgba(0,0,0,0.06)";
      const shadow = isDark.value
        ? "0 8px 30px rgba(0,0,0,0.6)"
        : "0 8px 30px rgba(0,0,0,0.12)";

      const fmt = (v) => {
        if (v === undefined || v === null || v === "") return "-";
        const n = Number(v);
        if (Number.isNaN(n)) return String(v);
        return new Intl.NumberFormat("vi-VN").format(Math.round(n)) + "đ";
      };

      const getColor = (i) =>
        (props && props.colors && props.colors[i]) ||
        (w.globals.colors && w.globals.colors[i]) ||
        "#999";
      const rows = [
        {
          name: (seriesMeta[0] && seriesMeta[0].name) || "ADR (Net)",
          val: values[0],
          color: getColor(0),
        },
        {
          name: (seriesMeta[1] && seriesMeta[1].name) || "RevPAR (Net)",
          val: values[1],
          color: getColor(1),
        },
      ];

      const rowsHtml = rows
        .map(
          (r) => `
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:6px 0;">
        <div style="display:flex;align-items:center;gap:10px;min-width:0;">
          <span style="
            width:10px;
            height:10px;
            border-radius:50%;
            background:${r.color};
            display:inline-block;
            box-sizing:border-box;
            border:2px solid ${bg};
            flex: 0 0 10px;
          "></span>
          <span style="font-size:13px;color:${sub};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            ${r.name}
          </span>
        </div>
        <div style="font-size:13px;color:${fg};font-weight:700;white-space:nowrap;">
          ${fmt(r.val)}
        </div>
      </div>`
        )
        .join("");

      // Header separated
      const headerBg = isDark.value
        ? "rgba(255,255,255,0.02)"
        : "rgba(0,0,0,0.02)";
      const headerColor = isDark.value
        ? "rgba(255,255,255,0.95)"
        : "rgba(0,0,0,0.85)";
      const headerBorder = isDark.value
        ? "rgba(255,255,255,0.03)"
        : "rgba(0,0,0,0.04)";

      return `
      <div style="
        width:auto;
        min-width:200px;
        border-radius:10px;
        overflow:hidden;
        border:${bd};
        box-shadow:${shadow};
        background:${bg};
        font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
      ">
        <div style="
          background:${headerBg};
          color:${headerColor};
          padding:8px 12px;
          font-size:12px;
          font-weight:700;
          border-bottom:1px solid ${headerBorder};
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
  { name: "ADR (Net)", type: "line", data: props.adrData },
  { name: "RevPAR (Net)", type: "line", data: props.revparData },
]);
</script>

<template>
  <ApexChart :series="series" :options="options" type="line" :height="height" />
</template>
