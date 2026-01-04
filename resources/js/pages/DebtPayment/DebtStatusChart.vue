<template>
  <div class="chart-container">
    <h4>Trạng thái công nợ</h4>
    <div class="d-flex justify-center align-center h-100">
      <div style="height: 300px; width: 300px; position: relative">
        <canvas ref="chart"></canvas>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from "vue";
import Chart from "chart.js/auto";

const props = defineProps({
  data: Array,
});

const chart = ref(null);
let chartInstance = null; // Biến để lưu instance của Chart

// Tính số lượng theo trạng thái công nợ
const debtStatusCount = computed(() => {
  const statusCount = {
    "Đã thanh toán": 0,
    "Còn nợ": 0,
    "Cần trả": 0,
    "Không có công nợ": 0,
  };
  props.data.forEach((item) => {
    const status = item.debt_status;
    statusCount[status] = (statusCount[status] || 0) + 1;
  });
  return statusCount;
});

onMounted(() => {
  const ctx = chart.value.getContext("2d");
  chartInstance = new Chart(ctx, {
    type: "pie",
    data: {
      labels: ["Đã thanh toán", "Còn nợ", "Cần trả", "Không có công nợ"],
      datasets: [
        {
          data: [
            debtStatusCount.value["Đã thanh toán"],
            debtStatusCount.value["Còn nợ"],
            debtStatusCount.value["Cần trả"],
            debtStatusCount.value["Không có công nợ"],
          ],
          backgroundColor: ["#4CAF50", "#F44336", "#FF9800", "#9E9E9E"],
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
    },
  });
});

watch(
  () => props.data,
  () => {
    if (chartInstance) {
      chartInstance.destroy(); // Phá hủy instance cũ
    }
    const ctx = chart.value.getContext("2d");
    chartInstance = new Chart(ctx, {
      type: "pie",
      data: {
        labels: ["Đã thanh toán", "Còn nợ", "Cần trả", "Không có công nợ"],
        datasets: [
          {
            data: [
              debtStatusCount.value["Đã thanh toán"],
              debtStatusCount.value["Còn nợ"],
              debtStatusCount.value["Cần trả"],
              debtStatusCount.value["Không có công nợ"],
            ],
            backgroundColor: ["#4CAF50", "#F44336", "#FF9800", "#9E9E9E"],
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
      },
    });
  }
);
</script>

<style scoped>
.chart-container {
  width: 100%;
  position: relative;
}

canvas {
  max-height: 100%; /* Đảm bảo canvas không vượt quá chiều cao container */
  width: 100% !important; /* Đảm bảo canvas chiếm toàn bộ chiều rộng */
  height: auto !important; /* Cho phép chiều cao tự động điều chỉnh */
}
</style>
