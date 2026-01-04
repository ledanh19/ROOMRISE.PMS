<template>
  <VCard>
    <VCardTitle class="d-flex justify-space-between align-center">
      <span>Booking theo khu vực (Khách nội địa)</span>
      <VBtn variant="text" size="small" color="primary"> Xem chi tiết </VBtn>
    </VCardTitle>
    <VCardText>
      <div style="position: relative; height: 300px; width: 100%">
        <Bar :data="chartData" :options="chartOptions" />
      </div>
    </VCardText>
  </VCard>
</template>

<script setup>
import { Bar } from "vue-chartjs";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from "chart.js";
import { ref, onMounted } from "vue";
import axios from "axios";

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
);

const chartData = ref({
  labels: [],
  datasets: [],
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: "y", // Horizontal bar
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      callbacks: {
        label: function (context) {
          return `${context.raw} booking`;
        },
      },
    },
  },
  scales: {
    x: {
      beginAtZero: true,
      grid: {
        display: true,
        color: "rgba(0,0,0,0.1)",
      },
    },
    y: {
      grid: {
        display: false,
      },
    },
  },
};

const fetchBookingByAreaData = async () => {
  try {
    const response = await axios.get(route("dashboard.booking-by-area"));

    chartData.value = {
      labels: response.data.labels,
      datasets: [
        {
          data: response.data.data,
          backgroundColor: [
            "#FF6B6B", // TP.HCM
            "#4ECDC4", // Hà Nội
            "#45B7D1", // Đà Nẵng
            "#96CEB4", // Cần Thơ
            "#FFEAA7", // Khác
          ],
          borderRadius: 4,
          borderSkipped: false,
        },
      ],
    };
  } catch (error) {
    console.error("Lỗi khi lấy dữ liệu booking theo khu vực:", error);
    // Mock data
    chartData.value = {
      labels: ["TP.HCM", "Hà Nội", "Đà Nẵng", "Cần Thơ", "Khác"],
      datasets: [
        {
          data: [120, 85, 65, 45, 25],
          backgroundColor: [
            "#FF6B6B",
            "#4ECDC4",
            "#45B7D1",
            "#96CEB4",
            "#FFEAA7",
          ],
          borderRadius: 4,
          borderSkipped: false,
        },
      ],
    };
  }
};

onMounted(() => {
  fetchBookingByAreaData();
});
</script>
