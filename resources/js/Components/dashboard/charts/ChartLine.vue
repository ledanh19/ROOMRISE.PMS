<script setup>
import { useUiTone } from "@/utils/useUiTone";
import { computed } from "vue";
import ApexChart from "vue3-apexcharts";

const { isDark, textColor } = useUiTone();

const props = defineProps({
  series: { type: Array, required: true },
  categories: { type: Array, default: () => [] },
  height: { type: [Number, String], default: 260 },
  smooth: { type: Boolean, default: true },
  strokeWidth: { type: Number, default: 3 },
  strokeCap: { type: String, default: "round" },
  markersSize: { type: Number, default: 0 },
  showToolbar: { type: Boolean, default: false },
  showToolbar: { type: Boolean, default: false },
  unit: { type: String, default: "VND" },
  gridColor: { type: String, default: "" },

  yLabelFormatter: { type: Function, default: (v) => v },
  tooltipYFormatter: { type: Function, default: (v) => v },
  showDataLabels: { type: Boolean, default: false },

  colors: { type: Array, default: () => [] },

  dashArray: { type: Array, default: () => [] },

  fillType: { type: String, default: "solid" },
  fillOpacity: { type: Number, default: 0.2 },

  tooltipTheme: { type: String, default: "light" },

  options: { type: Object, default: () => ({}) },
});

const labelColor = computed(() =>
  isDark.value ? "rgba(255,255,255,0.78)" : "rgba(0,0,0,0.68)"
);

const gridColorAuto = computed(() =>
  isDark.value ? "rgba(255,255,255,0.12)" : "rgba(0,0,0,0.06)"
);

function fmtValue(val) {
  if (val == null) return "";
  return new Intl.NumberFormat("vi-VN").format(val) + "đ";
}

const tooltipYFormatterComputed = computed(() => {
  return (v) => {
    if (props.unit === "VND") return fmtValue(v);
    return v;
  };
});

const merged = computed(() => ({
  tooltipYFormatterComputed() {
    return (v) => {
      if (this.unit === "VND") {
        return fmtValue(v);
      }
      return v;
    };
  },
  chart: {
    type: "line",
    height: props.height,
    toolbar: { show: props.showToolbar },
    zoom: { enabled: false },
  },

  colors: props.colors.length ? props.colors : undefined,

  dataLabels: { enabled: props.showDataLabels },

  stroke: {
    curve: props.smooth ? "smooth" : "straight",
    width: props.strokeWidth,
    dashArray: props.dashArray.length ? props.dashArray : undefined,
    lineCap: props.strokeCap,
  },

  markers: {
    size: 5,
    strokeWidth: 2,
    hover: { size: 5 },
  },

  xaxis: {
    categories: props.categories,
    tickPlacement: "on",
    labels: {
      style: {
        colors: labelColor.value,
        fontSize: "11px",
      },
    },
  },

  yaxis: {
    min: 0,
    max: undefined,
    tickAmount: 5,
    forceNiceScale: true,
    labels: {
      formatter: (v) => new Intl.NumberFormat("vi-VN").format(v),
      style: { colors: labelColor.value, fontSize: "11px" },
    },
    title: { text: props.unit, style: { color: labelColor.value } },
  },

  legend: {
    show: true,
    labels: {
      colors: labelColor.value,
    },
    showForSingleSeries: true,
  },

  grid: {
    borderColor: props.gridColor || gridColorAuto.value,
  },

  tooltip: {
    shared: true,
    intersect: false,
    theme: props.tooltipTheme,
    y: { formatter: tooltipYFormatterComputed.value },
  },

  fill:
    props.fillType === "gradient"
      ? {
          type: "gradient",
          gradient: {
            shadeIntensity: 0.3,
            opacityFrom: props.fillOpacity,
            opacityTo: 0,
            stops: [0, 90, 100],
          },
        }
      : { type: "solid", opacity: 1 },

  ...props.options,
}));
</script>

<template>
  <ApexChart type="line" :options="merged" :series="series" :height="height" />
</template>
