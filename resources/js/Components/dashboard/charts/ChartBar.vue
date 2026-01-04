<script setup>
import { computed } from "vue";
import ApexChart from "vue3-apexcharts";

const props = defineProps({
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },

  height: { type: [Number, String], default: 260 },
  columnWidth: { type: String, default: "28%" },
  borderRadius: { type: Number, default: 6 },
  horizontal: { type: Boolean, default: false },

  showToolbar: { type: Boolean, default: true },
  colors: { type: Array, default: () => [] },

  tooltipTheme: { type: String, default: "light" },

  gridColor: { type: String, default: "rgba(0,0,0,.08)" },
  yLabelFormatter: { type: Function, default: (v) => v },
  tooltipYFormatter: { type: Function, default: (v) => v },
  dataLabelFormatter: { type: Function, default: (v) => v },
  unit: { type: String, default: "VND" },
  options: { type: Object, default: () => ({}) },
});

const safeCategories = computed(() =>
  Array.isArray(props.categories) ? props.categories : []
);

const textColor = computed(() =>
  props.tooltipTheme === "dark" ? "#ffffff" : "#111827"
);

const xLabelColors = computed(() =>
  safeCategories.value.map(() => textColor.value)
);

const merged = computed(() => ({
  chart: {
    type: "bar",
    height: props.height,
    toolbar: {
      show: props.showToolbar,
      tools: {
        download: false,
        selection: true,
        zoom: true,
        pan: true,
        reset: true,
      },
    },
    foreColor: textColor.value,
    zoom: { enabled: true, type: "x", autoScaleYaxis: true },
    animations: { enabled: false },
  },

  colors: props.colors.length ? props.colors : undefined,

  plotOptions: {
    bar: {
      borderRadius: props.borderRadius,
      columnWidth: props.columnWidth,
      horizontal: props.horizontal,
      rangeBarOverlap: false,
      rangeBarGroupRows: false,
    },
  },

  dataLabels: {
    enabled: false,
    formatter: props.dataLabelFormatter,
  },

  xaxis: {
    categories: safeCategories.value,
    tickPlacement: "between",
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: {
      show: true,
      rotate: -45,
      rotateAlways: false,
      hideOverlappingLabels: true,
      trim: true,
      maxHeight: 120,
      style: {
        colors: xLabelColors.value,
        fontSize: "11px",
        fontWeight: 600,
      },
    },
  },

  yaxis: {
    labels: {
      formatter: (v) => new Intl.NumberFormat("vi-VN").format(v),
      style: {
        colors: [textColor.value],
        fontWeight: 500,
      },
    },
    title: { text: props.unit, style: { color: textColor.value } },
  },

  grid: {
    borderColor: props.gridColor,
    strokeDashArray: 4,
    xaxis: { lines: { show: false } },
    padding: { left: 12, right: 12 },
  },

  tooltip: {
    theme: props.tooltipTheme,
    shared: true,
    intersect: false,
    y: {
      formatter: props.tooltipYFormatter,
      title: {
        formatter: (seriesName) => {
          if (seriesName === "Total") return "Số booking";
          return seriesName;
        },
      },
    },
  },

  legend: {
    show: Array.isArray(props.series) && props.series.length > 1,
    position: "bottom",
    fontSize: "12px",
    labels: { colors: textColor.value },
    markers: { radius: 10 },
    itemMargin: { horizontal: 10, vertical: 6 },
  },

  responsive: [
    {
      breakpoint: 1200,
      options: {
        plotOptions: { bar: { columnWidth: "32%" } },
        xaxis: { labels: { rotate: -40, style: { fontSize: "10px" } } },
      },
    },
    {
      breakpoint: 800,
      options: {
        plotOptions: { bar: { columnWidth: "40%" } },
        xaxis: { labels: { rotate: -60, style: { fontSize: "10px" } } },
      },
    },
    {
      breakpoint: 480,
      options: {
        plotOptions: { bar: { columnWidth: "60%" } },
        xaxis: {
          labels: {
            rotate: -90,
            style: { fontSize: "10px" },
            hideOverlappingLabels: false,
          },
        },
      },
    },
  ],

  ...props.options,
}));
</script>

<template>
  <ApexChart type="bar" :options="merged" :series="series" :height="height" />
</template>
