<template>
  <div class="chart-container">
    <h4>Phân bổ vai trò</h4>
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

// Tính số lượng theo loại vai trò
const partnerTypeCount = computed(() => {
  const typeCount = { Sale: 0, "Sale TA": 0 };
  if (props.data && Array.isArray(props.data)) {
    props.data.forEach((item) => {
      if (item.type === "Sale" || item.type === "Sale TA") {
        typeCount[item.type] = (typeCount[item.type] || 0) + 1;
      }
    });
  }
  return typeCount;
});

onMounted(() => {
  const ctx = chart.value.getContext("2d");
  chartInstance = new Chart(ctx, {
    type: "pie",
    data: {
      labels: ["Sale", "Sale TA"],
      datasets: [
        {
          data: [
            partnerTypeCount.value["Sale"],
            partnerTypeCount.value["Sale TA"],
          ],
          backgroundColor: ["#2196F3", "#9C27B0"],
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
        labels: ["Sale", "Sale TA"],
        datasets: [
          {
            data: [
              partnerTypeCount.value["Sale"],
              partnerTypeCount.value["Sale TA"],
            ],
            backgroundColor: ["#2196F3", "#9C27B0"],
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
