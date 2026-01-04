<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import { useTheme } from "vuetify";
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
} from "chart.js";
import { Bar } from "vue-chartjs";

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
  colors: {
    type: Object,
    required: true,
  },
});

const vuetifyTheme = useTheme();

const chartLabels = ref([]);
const incomeData = ref([]);
const expenseData = ref([]);

const chartData = computed(() => ({
  labels: chartLabels.value,
  datasets: [
    {
      label: "Thu",
      data: incomeData.value,
      backgroundColor: props.colors.success || "#28C76F",
      stack: "stack1",
    },
    {
      label: "Chi",
      data: expenseData.value,
      backgroundColor: props.colors.error || "#EA5455",
      stack: "stack1",
    },
  ],
}));

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: true },
    tooltip: {
      callbacks: {
        title: (context) => `Ngày ${context[0].label}`,
        label: (context) =>
          `${context.dataset.label}: ${context.parsed.y}k VNĐ`,
      },
    },
  },
  scales: {
    x: {
      stacked: true,
      ticks: { color: "#555" },
      grid: { display: false },
    },
    y: {
      stacked: true,
      ticks: {
        callback: (value) => `${value}k`,
        color: "#555",
      },
      grid: { color: "#eee" },
    },
  },
}));
const fetchData = async () => {
  const res = await axios.get(route("income-and-expense.getChartData"));
  chartLabels.value = res.data.labels;
  incomeData.value = res.data.income;
  expenseData.value = res.data.expense;
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
  <div style="height: 400px">
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>
