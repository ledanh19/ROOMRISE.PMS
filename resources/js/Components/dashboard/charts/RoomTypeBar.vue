<script setup>
import { useUiTone } from "@/utils/useUiTone";
import { computed } from "vue";
import ApexChart from "vue3-apexcharts";

const props = defineProps({
  categories: { type: Array, required: true },
  data: { type: Array, required: true },
  color: { type: String, default: "#1E88E5" },
  height: { type: [Number, String], default: 260 },
});

const { isDark, textColor } = useUiTone();

const gridColor = computed(() =>
  isDark.value ? "rgba(255,255,255,0.08)" : "rgba(0,0,0,0.06)"
);

const options = computed(() => ({
  chart: {
    type: "bar",
    toolbar: { show: false },
    background: "transparent",
    foreColor: textColor.value,
  },
  theme: { mode: isDark.value ? "dark" : "light" },
  colors: [props.color],
  plotOptions: { bar: { columnWidth: "38%", borderRadius: 8 } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.categories,
    axisBorder: { show: false },
    labels: { style: { colors: textColor.value } },
  },
  yaxis: {
    labels: {
      style: { colors: textColor.value },
      formatter: (v) => new Intl.NumberFormat("vi-VN").format(v),
    },
    title: { text: "VND", style: { color: textColor.value } },
    min: 0,
    max: Math.max(...props.data) + 10,
  },
  grid: { borderColor: gridColor.value },
  tooltip: {
    y: {
      formatter: (value) => {
        if (value == null) return "";
        return new Intl.NumberFormat("vi-VN").format(value) + "đ";
      },
    },
  },
}));

const series = computed(() => [{ name: "Doanh thu", data: props.data }]);
</script>

<template>
  <ApexChart :series="series" :options="options" type="bar" :height="height" />
</template>
