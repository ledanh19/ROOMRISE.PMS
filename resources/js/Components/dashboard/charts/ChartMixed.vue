<script setup>
import { useUiTone } from "@/utils/useUiTone";
import { computed } from "vue";
import ApexChart from "vue3-apexcharts";

const { isDark, textColor } = useUiTone();

const props = defineProps({
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 320 },

  stacked: { type: Boolean, default: false },
  colors: { type: Array, default: () => [] },

  smooth: { type: Boolean, default: true },
  strokeWidth: { type: Number, default: 3 },
  strokeCap: { type: String, default: "round" },

  markersSize: { type: Number, default: 5 },
  showToolbar: { type: Boolean, default: false },

  areaOpacity: { type: Number, default: 0.15 },

  gridColor: { type: String, default: "" },
  foreColor: { type: String, default: "var(--v-theme-on-surface)" },
  yLabelFormatter: { type: Function, default: (v) => v },
  tooltipYFormatter: { type: Function, default: (v) => v },

  tooltipTheme: { type: String, default: "light" },
  unit: { type: String, default: "Đặt phòng" },
  options: { type: Object, default: () => ({}) },
});

const labelColor = computed(() =>
  isDark.value ? "rgba(255,255,255,0.78)" : "rgba(0,0,0,0.68)"
);

const gridColorAuto = computed(() =>
  isDark.value ? "rgba(255,255,255,0.12)" : "rgba(0,0,0,0.06)"
);

const fillOpacityArray = computed(() =>
  (props.series || []).map((s) => (s.type === "area" ? props.areaOpacity : 1))
);

const merged = computed(() => {
  return {
    chart: {
      type: "line",
      height: props.height,
      stacked: props.stacked,
      toolbar: { show: props.showToolbar },
      zoom: { enabled: false },
      foreColor: labelColor.value,
    },

    colors: props.colors.length ? props.colors : undefined,

    stroke: {
      curve: props.smooth ? "smooth" : "straight",
      width: Array.isArray(props.series)
        ? props.series.map((s) =>
            s.type === "line" || s.type === "area" ? props.strokeWidth : 0
          )
        : props.strokeWidth,
      lineCap: props.strokeCap,
    },

    markers: {
      size: props.markersSize,
      strokeWidth: 2,
      hover: { size: props.markersSize + 2 },
    },

    fill: { type: "solid", opacity: fillOpacityArray.value },

    plotOptions: {
      bar: {
        columnWidth: "50%",
        borderRadius: 2,
        endingShape: "flat",
        rangeBarOverlap: false,
        rangeBarGroupRows: false,
      },
    },

    xaxis: {
      categories: props.categories,
      tickPlacement: "between",
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: {
        style: {
          colors: props.categories.map(() => labelColor.value),
          fontWeight: 600,
        },
      },
    },

    yaxis: {
      min: 0,
      labels: {
        formatter: (v) => new Intl.NumberFormat("vi-VN").format(v),
        style: {
          colors: [labelColor.value],
          fontWeight: 500,
        },
      },
      title: { text: props.unit, style: { color: textColor.value } },
    },

    grid: {
      borderColor: props.gridColor || gridColorAuto.value,
      strokeDashArray: 4,
      xaxis: { lines: { show: false } },
      padding: { left: 16, right: 16 },
    },

    tooltip: {
      shared: true,
      intersect: false,
      theme: props.tooltipTheme,
      y: {
        formatter: (val) => props.tooltipYFormatter(val),
      },
    },

    legend: {
      show: true,
      position: "bottom",
      fontSize: "12px",
      markers: { radius: 12 },
      itemMargin: { horizontal: 10, vertical: 4 },
      labels: {
        colors: labelColor.value,
      },
    },

    dataLabels: { enabled: false },

    ...props.options,
  };
});
</script>

<template>
  <ApexChart type="line" :series="series" :options="merged" :height="height" />
</template>

<style scoped>
.apexcharts-tooltip {
  padding: 8px !important;
  border-radius: 10px !important;
  box-shadow: 0 8px 20px rgba(2, 6, 23, 8%);
  font-size: 13px !important;
}

.apexcharts-series-markers .apexcharts-marker {
  transition: transform 120ms ease;
}

.apexcharts-series-markers .apexcharts-marker:hover {
  transform: scale(1.15);
}
</style>
