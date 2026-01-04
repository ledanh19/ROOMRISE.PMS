<script setup>
import { computed } from "vue";
import ApexChart from "vue3-apexcharts";

const props = defineProps({
  values: { type: Array, required: true },
  labels: { type: Array, required: true },
  percents: { type: Array, default: null },
  unit: { type: String, default: "bookings" },
  height: { type: [Number, String], default: 260 },
  colors: { type: Array, default: () => ["#4C6EF5"] },
  foreColor: { type: String, default: "var(--v-theme-on-surface)" },
  barRadius: { type: Number, default: 8 },
  barHeight: { type: String, default: "36%" },
  trackColor: {
    type: String,
    default: "color-mix(in oklab, var(--v-theme-primary) 6%, transparent)",
  },
  total: { type: Number, default: 0 },
  showTooltip: { type: Boolean, default: false },
  gridColor: { type: String, default: "transparent" },
  options: { type: Object, default: () => ({}) },
});

const autoTotal = computed(() =>
  props.total > 0
    ? props.total
    : props.values.reduce((a, b) => a + (Number(b) || 0), 0)
);

const series = computed(() => [{ name: props.unit, data: props.values }]);

const percArray = computed(() => {
  if (
    Array.isArray(props.percents) &&
    props.percents.length === props.values.length
  )
    return props.percents.map((n) => Math.round(Number(n) || 0));
  const t = autoTotal.value || 1;
  return props.values.map((v) => Math.round(((Number(v) || 0) * 100) / t));
});

const annoPoints = computed(() =>
  props.labels.map((lab, i) => {
    const val = Number(props.values[i]) || 0;
    const pct = percArray.value[i] ?? 0;
    const text = `${val.toLocaleString("vi-VN")} ${props.unit}  ${pct}%`;
    return {
      x: autoTotal.value || val,
      y: lab,
      marker: { size: 0 },
      label: {
        text,
        borderWidth: 0,
        offsetX: -8,
        style: {
          background: "transparent",
          color: props.foreColor,
          fontSize: "12px",
          fontWeight: 700,
        },
      },
    };
  })
);

const opts = computed(() => ({
  chart: {
    type: "bar",
    height: props.height,
    toolbar: { show: false },
    animations: { speed: 250 },
    foreColor: props.foreColor,
  },
  series: series.value,
  colors: props.colors,
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: props.barRadius,
      barHeight: props.barHeight,
      distributed: false,
      dataLabels: { position: "top" },
      colors: {
        backgroundBarColors: [props.trackColor],
        backgroundBarOpacity: 0.35,
      },
    },
  },

  dataLabels: { enabled: false },

  xaxis: {
    categories: props.labels,
    max: autoTotal.value || undefined,
    labels: { show: false },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: { style: { fontWeight: 600, colors: [props.foreColor] } },
  },
  grid: {
    borderColor: props.gridColor,
    xaxis: { lines: { show: false } },
    yaxis: { lines: { show: true } },
    padding: { left: 8, right: 8 },
  },
  tooltip: { enabled: props.showTooltip },
  legend: { show: false },
  fill: { opacity: 1 },
  states: {
    hover: { filter: { type: "none" } },
    active: { filter: { type: "none" } },
  },

  annotations: { points: annoPoints.value },

  ...props.options,
}));
</script>

<template>
  <div class="leadtime-progress">
    <ApexChart type="bar" :options="opts" :series="series" :height="height" />
  </div>
</template>

<style scoped>
.leadtime-progress {
  inline-size: 100%;
}
</style>
