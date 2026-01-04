<template>
  <div class="chart-container">
    <h4>Net công nợ</h4>
    <div style="height: 400px">
      <canvas ref="chart"></canvas>
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

// Tách dữ liệu theo đối tác và trạng thái
const debtData = computed(() => {
  const debtByPartner = {};
  props.data.forEach((item) => {
    if (!debtByPartner[item.name]) {
      debtByPartner[item.name] = {
        "Còn nợ": 0,
        "Cần trả": 0,
        "Đã thanh toán": 0,
        "Không có công nợ": 0,
      };
    }
    const net = item.total_net_debt / 1000000; // Chia cho 1 triệu
    const status = item.debt_status;
    if (status === "Còn nợ") debtByPartner[item.name]["Còn nợ"] = net;
    else if (status === "Cần trả") debtByPartner[item.name]["Cần trả"] = net;
    else if (status === "Đã thanh toán")
      debtByPartner[item.name]["Đã thanh toán"] = net;
    else if (status === "Không có công nợ")
      debtByPartner[item.name]["Không có công nợ"] = net;
  });
  return debtByPartner;
});

const labels = computed(() => props.data.map((item) => item.name));

onMounted(() => {
  const ctx = chart.value.getContext("2d");
  chartInstance = new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels.value,
      datasets: [
        {
          label: "Còn nợ",
          data: labels.value.map((name) => debtData.value[name]["Còn nợ"]),
          backgroundColor: "#F44336",
          borderWidth: 1,
        },
        {
          label: "Cần trả",
          data: labels.value.map((name) => debtData.value[name]["Cần trả"]),
          backgroundColor: "#FF9800",
          borderWidth: 1,
        },
        {
          label: "Đã thanh toán",
          data: labels.value.map(
            (name) => debtData.value[name]["Đã thanh toán"]
          ),
          backgroundColor: "#4CAF50",
          borderWidth: 1,
        },
        {
          label: "Không có công nợ",
          data: labels.value.map(
            (name) => debtData.value[name]["Không có công nợ"]
          ),
          backgroundColor: "#9E9E9E",
          borderWidth: 1,
        },
      ],
    },
    options: {
      indexAxis: "x", // Trục x là tên đối tác, trục y là số tiền
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: {
          beginAtZero: true,
          stacked: true, // Cộng dồn các cột theo chiều dọc
        },
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: "Số tiền (triệu VND)",
          },
          ticks: {
            stepSize: 1, // Bước tăng là 1 triệu
            callback: function (value) {
              return value + "M"; // Thêm "M" sau mỗi giá trị
            },
          },
          suggestedMax: 10, // Điều chỉnh dựa trên dữ liệu tối đa (ví dụ: 11.5M từ dữ liệu)
        },
      },
      plugins: {
        legend: {
          position: "top", // Hiển thị legend ở trên cùng
        },
      },
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
      type: "bar",
      data: {
        labels: labels.value,
        datasets: [
          {
            label: "Còn nợ",
            data: labels.value.map((name) => debtData.value[name]["Còn nợ"]),
            backgroundColor: "#F44336",
            borderWidth: 1,
          },
          {
            label: "Cần trả",
            data: labels.value.map((name) => debtData.value[name]["Cần trả"]),
            backgroundColor: "#FF9800",
            borderWidth: 1,
          },
          {
            label: "Đã thanh toán",
            data: labels.value.map(
              (name) => debtData.value[name]["Đã thanh toán"]
            ),
            backgroundColor: "#4CAF50",
            borderWidth: 1,
          },
          {
            label: "Không có công nợ",
            data: labels.value.map(
              (name) => debtData.value[name]["Không có công nợ"]
            ),
            backgroundColor: "#9E9E9E",
            borderWidth: 1,
          },
        ],
      },
      options: {
        indexAxis: "x",
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            beginAtZero: true,
            stacked: true,
          },
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: "Số tiền (triệu VND)",
            },
            ticks: {
              stepSize: 1,
              callback: function (value) {
                return value + "M";
              },
            },
            suggestedMax: 12, // Điều chỉnh lên 12M để bao gồm giá trị lớn nhất (11.5M)
          },
        },
        plugins: {
          legend: {
            position: "top",
          },
        },
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
  max-height: 100%;
  width: 100% !important;
  height: auto !important;
}
</style>
