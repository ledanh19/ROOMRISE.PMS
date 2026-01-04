<script setup>
import { Pie } from "vue-chartjs";
import { ref, onMounted } from "vue";
import { Chart as ChartJS, Tooltip, Legend, ArcElement } from "chart.js";
import axios from "axios";

ChartJS.register(ArcElement, Tooltip, Legend);

const chartLabels = ref([]);
const chartValues = ref([]);

const chartData = computed(() => ({
  labels: chartLabels.value,
  datasets: [
    {
      data: chartValues.value,
      backgroundColor: getColorPalette(chartValues.value.length),
      borderWidth: 1,
    },
  ],
}));

const chartOptions = {
  responsive: true,
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      callbacks: {
        label: (ctx) => {
          return `${ctx.label}: ${ctx.raw}%`;
        },
      },
    },
  },
};
const presetColors = [
  "#FEC260",
  "#FF7300",
  "#9B8AFB",
  "#00C48C",
  "#FF4D6D",
  "#5FAD56",
];

function getColorPalette(count) {
  const colors = [];
  for (let i = 0; i < count; i++) {
    colors.push(presetColors[i % presetColors.length]);
  }
  return colors;
}
const fetchData = async () => {
  const res = await axios.get(route("income-and-expense.getPieData"));
  chartLabels.value = res.data.map((item) => item.label);
  chartValues.value = res.data.map((item) => item.value);
};
onMounted(fetchData);
const updateData = () => {
  fetchData();
};
defineExpose({
  updateData,
});
</script>

<template>
  <div class="d-flex justify-center align-center h-100">
    <div style="height: 300px; width: 300px; position: relative">
      <Pie :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
