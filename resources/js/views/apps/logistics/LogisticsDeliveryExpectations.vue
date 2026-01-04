<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const roomStatus = ref({
  booked: 0,
  maintenance: 0,
  cleaning: 0,
  available: 0,
  total: 0,
});

const deliveryExceptionsChartSeries = ref([]);

const loadRoomStatusData = async () => {
  const res = await axios.get(route("bookings.getRoomStatusData"));
  roomStatus.value = res.data;

  deliveryExceptionsChartSeries.value = [
    roomStatus.value.booked,
    roomStatus.value.maintenance,
    roomStatus.value.cleaning,
    roomStatus.value.available,
  ];
};

onMounted(loadRoomStatusData);

const chartColors = {
  donut: {
    series1: "#4970F5",
    series2: "#ff4c51",
    series3: "#ff9f43",
    series4: "#28c76f",
  },
};

const headingColor =
  "rgba(var(--v-theme-on-background), var(--v-high-emphasis-opacity))";
const labelColor =
  "rgba(var(--v-theme-on-background), var(--v-medium-emphasis-opacity))";

const deliveryExceptionsChartConfig = ref({
  labels: ["Đã đặt", "Bảo trì", "Đang dọn", "Trống"],
  colors: [
    chartColors.donut.series1,
    chartColors.donut.series2,
    chartColors.donut.series3,
    chartColors.donut.series4,
  ],
  stroke: { width: 0 },
  dataLabels: {
    enabled: true,
    formatter(val, opts) {
      const actualValue = opts.w.globals.series[opts.seriesIndex];
      return `${actualValue}`;
    },
  },
  legend: {
    show: true,
    position: "bottom",
    offsetY: 10,
    markers: {
      width: 8,
      height: 8,
      offsetX: -3,
    },
    itemMargin: {
      horizontal: 15,
      vertical: 5,
    },
    fontSize: "13px",
    fontWeight: 400,
    labels: {
      colors: headingColor,
      useSeriesColors: false,
    },
  },
  tooltip: { theme: true },
  grid: { padding: { top: 15 } },
  plotOptions: {
    pie: {
      donut: {
        size: "75%",
        labels: {
          show: true,
          value: {
            fontSize: "24px",
            color: headingColor,
            fontWeight: 500,
            offsetY: -20,
            formatter(val) {
              return `${Number.parseInt(val)} Phòng`;
            },
          },
          name: { offsetY: 20 },
          total: {
            show: true,
            fontSize: "0.9375rem",
            fontWeight: 400,
            label: "Tổng Số Phòng",
            color: labelColor,
            formatter() {
              return `${roomStatus.value.total} Phòng`;
            },
          },
        },
      },
    },
  },
  responsive: [
    {
      breakpoint: 420,
      options: { chart: { height: 400 } },
    },
  ],
});
</script>

<template>
  <VCard>
    <VCardItem title="Tình Trạng Phòng">
      <template #append>
        <MoreBtn />
      </template>
    </VCardItem>
    <VCardText>
      <VueApexCharts
        type="donut"
        height="400"
        :options="deliveryExceptionsChartConfig"
        :series="deliveryExceptionsChartSeries"
      />
    </VCardText>
  </VCard>
</template>
