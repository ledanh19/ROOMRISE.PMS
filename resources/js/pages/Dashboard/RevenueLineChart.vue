<template>
  <div>
    <h3 v-if="showTitle" class="mb-4">Hoạt động & Doanh thu 7 ngày gần nhất</h3>
    <div style="position: relative; height: 400px; width: 100%">
      <Line :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>

<script setup>
import { Line } from "vue-chartjs";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from "chart.js";

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps({
  chartData: {
    type: Object,
    required: true,
  },
  showTitle: {
    type: Boolean,
    default: true,
  },
});

const formatCurrency = (value) => {
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + "M";
  } else if (value >= 1000) {
    return (value / 1000).toFixed(0) + "K";
  } else {
    return value.toFixed(0);
  }
};

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: "index",
    intersect: false,
  },
  scales: {
    x: {
      display: true,
      grid: {
        display: true,
        color: "rgba(0,0,0,0.1)",
      },
      ticks: {
        font: {
          size: 12,
        },
      },
    },
    y: {
      type: "linear",
      display: true,
      position: "left",
      title: {
        display: true,
        text: "Doanh thu (VNĐ)",
      },
      grid: {
        display: true,
        color: "rgba(0,0,0,0.1)",
      },
      ticks: {
        callback: function (value) {
          return formatCurrency(value);
        },
        font: {
          size: 12,
        },
      },
    },
    y1: {
      type: "linear",
      display: true,
      position: "right",
      title: {
        display: true,
        text: "Số lượng",
      },
      grid: {
        drawOnChartArea: false,
      },
      ticks: {
        font: {
          size: 12,
        },
      },
    },
  },
  plugins: {
    legend: {
      position: "top",
      labels: {
        usePointStyle: true,
        pointStyle: "circle",
        font: {
          size: 12,
        },
      },
    },
    tooltip: {
      backgroundColor: "rgba(255, 255, 255, 0.95)",
      titleColor: "#374151",
      bodyColor: "#374151",
      borderColor: "#E5E7EB",
      borderWidth: 1,
      cornerRadius: 8,
      displayColors: true,
      callbacks: {
        title: function (context) {
          return context[0].label;
        },
        label: function (context) {
          let label = context.dataset.label || "";
          if (label) {
            label += " : ";
          }
          if (context.datasetIndex === 0) {
            // Hiển thị số tiền với định dạng VNĐ
            const value = context.parsed.y;
            label += value.toLocaleString("vi-VN") + " VNĐ";
          } else {
            label += context.parsed.y;
          }
          return label;
        },
      },
    },
  },
};
</script>
