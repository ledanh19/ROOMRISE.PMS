<template>
  <VCard class="booking-source-card" elevation="3">
    <VCardTitle class="card-header">
      <VAvatar size="40" color="info" variant="tonal" class="me-3">
        <VIcon icon="tabler-chart-pie" />
      </VAvatar>
      <div class="flex-grow-1">
        <div class="text-h5 font-weight-bold">Nguồn đặt phòng</div>
        <div class="text-body-2 text-medium-emphasis">
          Phân tích theo kênh bán hàng
        </div>
      </div>
      <VBtn
        size="small"
        color="primary"
        variant="tonal"
        @click="showDetailDialog = true"
        prepend-icon="tabler-eye"
        class="detail-btn"
      >
        Chi tiết
      </VBtn>
    </VCardTitle>

    <VCardText class="chart-container">
      <div v-if="isLoading" class="loading-state">
        <VProgressCircular indeterminate color="primary" size="48" />
        <div class="text-h6 mt-4">Đang tải dữ liệu...</div>
      </div>

      <div v-else-if="hasData" class="chart-wrapper">
        <div class="chart-content">
          <Pie :data="chartData" :options="chartOptions" />
        </div>

        <!-- Enhanced Legend -->
        <div class="custom-legend">
          <div class="legend-items">
            <div
              v-for="(item, index) in legendData"
              :key="index"
              class="legend-item"
              @click="toggleSegment(index)"
              :class="{
                'legend-item--inactive': hiddenSegments.includes(index),
              }"
            >
              <div
                class="legend-color"
                :style="{ backgroundColor: item.color }"
              ></div>
              <div
                class="legend-content d-flex align-center justify-space-between"
              >
                <div class="legend-label d-flex align-center">
                  <VImg
                    v-if="getOtaLogo(item.label)"
                    :src="getOtaLogo(item.label)"
                    :alt="item.label"
                    width="20"
                    height="20"
                    class="me-2"
                    cover
                  />
                  {{ item.label }}
                </div>
                <div class="legend-stats">
                  <VChip
                    size="x-small"
                    :color="getSourceChipColor(item.percentage)"
                    variant="tonal"
                  >
                    {{ item.percentage }}%
                  </VChip>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <VAvatar size="80" color="grey-lighten-3" class="mb-4">
          <VIcon icon="tabler-chart-pie-off" size="40" />
        </VAvatar>
        <div class="text-h6 text-disabled mb-2">Chưa có dữ liệu</div>
        <div class="text-body-2 text-medium-emphasis">
          Không có booking nào trong khoảng thời gian này
        </div>
      </div>
    </VCardText>

    <!-- Enhanced Dialog -->
    <VDialog
      v-model="showDetailDialog"
      max-width="1400px"
      scrollable
      class="detail-dialog"
    >
      <VCard class="dialog-card">
        <VCardTitle class="dialog-header d-flex">
          <div class="flex-grow-1">
            <span class="text-h5 font-weight-bold"
              >Chi tiết nguồn đặt phòng</span
            >
            <div class="text-body-2 text-medium-emphasis">
              {{ bookingDetails.length }} booking được tìm thấy
            </div>
          </div>
          <VBtn
            icon
            variant="text"
            @click="showDetailDialog = false"
            color="secondary"
            class="close-btn"
          >
            <VIcon>tabler-x</VIcon>
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-0">
          <div v-if="loadingDetails" class="loading-container">
            <VSkeletonLoader type="table-row@5" />
          </div>

          <VDataTable
            v-else
            :headers="enhancedBookingHeaders"
            :items="bookingDetails"
            :loading="loadingDetails"
            item-value="id"
            class="modern-table"
            :items-per-page="15"
          >
            <template #item.source="{ item }">
              <div class="d-flex align-center">
                <VChip
                  :color="getRandomSourceColor(item.ota_name)"
                  size="small"
                  variant="flat"
                  :prepend-icon="getSourceIcon(item.ota_name)"
                  class="source-chip"
                >
                  {{ item.ota_name || "Trực tiếp" }}
                </VChip>
              </div>
            </template>

            <template #item.customer_name="{ item }">
              <div class="d-flex align-center">
                <VAvatar size="24" color="primary" variant="tonal" class="me-2">
                  <VIcon icon="tabler-user" size="12" />
                </VAvatar>
                <div class="font-weight-medium">
                  {{ item.customer?.full_name || "N/A" }}
                </div>
              </div>
            </template>

            <template #item.room_info="{ item }">
              <div v-if="item.booking_rooms && item.booking_rooms.length > 0">
                <VChip
                  v-for="room in item.booking_rooms.slice(0, 2)"
                  :key="room.id"
                  size="small"
                  variant="tonal"
                  color="info"
                  class="me-1 mb-1"
                >
                  {{ room?.room?.name }} ({{ room?.room_unit?.name }})
                </VChip>
                <VChip
                  v-if="item.booking_rooms.length > 2"
                  size="small"
                  variant="outlined"
                  class="me-1 mb-1"
                >
                  +{{ item.booking_rooms.length - 2 }} khác
                </VChip>
              </div>
              <VChip v-else size="small" color="warning" variant="tonal">
                Chưa gán phòng
              </VChip>
            </template>

            <template #item.check_in_date="{ item }">
              <div class="d-flex align-center">
                <VIcon
                  icon="tabler-calendar"
                  size="16"
                  class="me-2 text-medium-emphasis"
                />
                {{ formatDate(item.check_in_date) }}
              </div>
            </template>

            <template #item.customer_payment_amount="{ item }">
              <div class="d-flex align-center justify-end">
                <VIcon
                  icon="tabler-currency-dollar"
                  size="16"
                  class="me-2 text-success"
                />
                <span class="font-weight-bold text-success">
                  {{ formatCurrency(item?.customer_payment_amount) }}
                </span>
              </div>
            </template>

            <template #no-data>
              <div class="no-data-container">
                <VAvatar size="64" color="grey-lighten-3" class="mb-4">
                  <VIcon icon="tabler-database-off" size="32" />
                </VAvatar>
                <div class="text-h6 text-disabled mb-2">Không có dữ liệu</div>
                <div class="text-body-2 text-medium-emphasis">
                  Chưa có booking nào từ nguồn này
                </div>
              </div>
            </template>
          </VDataTable>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4">
          <VBtn
            color="secondary"
            variant="outlined"
            @click="showDetailDialog = false"
          >
            Đóng
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VCard>
</template>

<script setup>
import { formatCurrency, formatDate } from "@/utils/formatters";
import axios from "axios";
import { ArcElement, Chart as ChartJS, Legend, Tooltip } from "chart.js";
import { computed, ref, watch } from "vue";
import { Pie } from "vue-chartjs";

ChartJS.register(ArcElement, Tooltip, Legend);

// Props nhận filter từ Index
const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const chartData = ref({ labels: [], datasets: [] });
const showDetailDialog = ref(false);
const loadingDetails = ref(false);
const bookingDetails = ref([]);
const isLoading = ref(false);
const hiddenSegments = ref([]);

// Enhanced headers
const enhancedBookingHeaders = [
  {
    title: "Nguồn",
    key: "source",
    width: "140px",
    sortable: true,
  },
  {
    title: "Khách hàng",
    key: "customer_name",
    width: "200px",
    sortable: true,
  },
  {
    title: "Phòng",
    key: "room_info",
    width: "250px",
    sortable: false,
  },
  {
    title: "Ngày nhận",
    key: "check_in_date",
    width: "150px",
    sortable: true,
  },
  {
    title: "Số tiền",
    key: "customer_payment_amount",
    width: "150px",
    align: "end",
    sortable: true,
  },
];

const otaLogos = {
  bookingcom: "/images/bookingcom.png",
  ctrip: "/images/ctrip.png",
  expedia: "/images/expedia.png",
  airbnb: "/images/airbnb.png",
  agoda: "/images/agoda.png",
  // Thêm các OTA khác có thể có
  "booking.com": "/images/bookingcom.png",
  "trip.com": "/images/ctrip.png",
  tripadvisor: "/images/expedia.png",
  "hotels.com": "/images/expedia.png",
  trivago: "/images/expedia.png",
  direct: null, // Không có logo cho booking trực tiếp
  website: null,
  phone: null,
  "walk-in": null,
};

// Function to get OTA logo
const getOtaLogo = (sourceName) => {
  if (!sourceName) return null;

  // Chuẩn hóa tên nguồn
  const normalizedName = sourceName.toLowerCase().trim();

  // Kiểm tra trực tiếp
  if (otaLogos[normalizedName]) {
    return otaLogos[normalizedName];
  }

  // Kiểm tra các biến thể khác
  const variants = [
    normalizedName.replace(/\s+/g, ""),
    normalizedName.replace(/[^a-z0-9]/g, ""),
    normalizedName.replace(/\s+/g, "-"),
  ];

  for (const variant of variants) {
    if (otaLogos[variant]) {
      return otaLogos[variant];
    }
  }

  return null;
};

// Generate random colors function
const generateRandomColors = (count) => {
  const colors = [];
  const baseColors = [
    "#FF6B6B",
    "#4ECDC4",
    "#45B7D1",
    "#96CEB4",
    "#FFEAA7",
    "#DDA0DD",
    "#98D8C8",
    "#F7DC6F",
    "#BB8FCE",
    "#85C1E9",
    "#F8C471",
    "#82E0AA",
    "#F1948A",
    "#85929E",
    "#D5A6BD",
    "#A9CCE3",
    "#A3E4D7",
    "#D2B4DE",
    "#F9E79F",
    "#FADBD8",
  ];

  for (let i = 0; i < count; i++) {
    if (i < baseColors.length) {
      colors.push(baseColors[i]);
    } else {
      // Generate random color
      const hue = Math.floor(Math.random() * 360);
      const saturation = 65 + Math.floor(Math.random() * 20); // 65-85%
      const lightness = 55 + Math.floor(Math.random() * 20); // 55-75%
      colors.push(`hsl(${hue}, ${saturation}%, ${lightness}%)`);
    }
  }
  return colors;
};

// Color mapping for sources
const sourceColorMap = ref(new Map());

const getRandomSourceColor = (source) => {
  if (!sourceColorMap.value.has(source)) {
    const colors = [
      "primary",
      "success",
      "warning",
      "error",
      "info",
      "secondary",
    ];
    const randomColor = colors[Math.floor(Math.random() * colors.length)];
    sourceColorMap.value.set(source, randomColor);
  }
  return sourceColorMap.value.get(source);
};

// Computed properties
const hasData = computed(() => {
  return chartData.value.labels && chartData.value.labels.length > 0;
});

const totalBookings = computed(() => {
  if (!hasData.value) return 0;
  return chartData.value.datasets[0]?.data.reduce((a, b) => a + b, 0) || 0;
});

const legendData = computed(() => {
  if (!hasData.value) return [];

  const labels = chartData.value.labels;
  const data = chartData.value.datasets[0].data;
  const colors = chartData.value.datasets[0].backgroundColor;
  const total = data.reduce((a, b) => a + b, 0);

  return labels.map((label, index) => ({
    label,
    value: data[index],
    percentage: Math.round((data[index] / total) * 100),
    color: colors[index],
  }));
});

const summaryStats = computed(() => {
  if (!bookingDetails.value.length) return [];

  const totalRevenue = bookingDetails.value.reduce(
    (sum, booking) => sum + (booking.customer_payment_amount || 0),
    0
  );
  const avgBookingValue = totalRevenue / bookingDetails.value.length;
  const uniqueSources = [
    ...new Set(bookingDetails.value.map((b) => b.ota_name)),
  ];

  return [
    {
      label: "Tổng booking",
      value: bookingDetails.value.length,
      color: "primary",
      icon: "tabler-calendar-check",
    },
    {
      label: "Tổng doanh thu",
      value: formatCurrency(totalRevenue),
      color: "success",
      icon: "tabler-currency-dollar",
    },
    {
      label: "Trung bình/booking",
      value: formatCurrency(avgBookingValue),
      color: "info",
      icon: "tabler-calculator",
    },
    {
      label: "Số nguồn khác nhau",
      value: uniqueSources.length,
      color: "warning",
      icon: "tabler-world",
    },
  ];
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false, // Hide default legend
    },
    tooltip: {
      backgroundColor: "rgba(0, 0, 0, 0.8)",
      titleColor: "#fff",
      bodyColor: "#fff",
      borderColor: "rgba(255, 255, 255, 0.2)",
      borderWidth: 1,
      callbacks: {
        label: function (context) {
          const label = context.label || "";
          const value = context.raw;
          const total = context.dataset.data.reduce((a, b) => a + b, 0);
          const percentage = ((value / total) * 100).toFixed(1);
          return `${label}: ${value} booking (${percentage}%)`;
        },
      },
    },
  },
  animation: {
    animateRotate: true,
    animateScale: true,
    duration: 1000,
  },
  interaction: {
    intersect: false,
  },
  onHover: (event, elements) => {
    event.native.target.style.cursor = elements.length ? "pointer" : "default";
  },
};

// Methods
const toggleSegment = (index) => {
  const hiddenIndex = hiddenSegments.value.indexOf(index);
  if (hiddenIndex === -1) {
    hiddenSegments.value.push(index);
  } else {
    hiddenSegments.value.splice(hiddenIndex, 1);
  }

  // Update chart data visibility
  const meta = chartData.value._meta;
  if (meta) {
    Object.keys(meta).forEach((key) => {
      meta[key].data[index].hidden = hiddenSegments.value.includes(index);
    });
  }
};

const getSourceChipColor = (percentage) => {
  if (percentage >= 40) return "success";
  if (percentage >= 20) return "warning";
  return "error";
};

const getSourceIcon = (source) => {
  const icons = {
    "booking.com": "tabler-world",
    "Booking.com": "tabler-world",
    agoda: "tabler-plane",
    Agoda: "tabler-plane",
    expedia: "tabler-compass",
    Expedia: "tabler-compass",
    airbnb: "tabler-home",
    Airbnb: "tabler-home",
    "walk-in": "tabler-walk",
    "trực tiếp": "tabler-building-store",
  };
  return icons[source?.toLowerCase()] || "tabler-world";
};

// Fetch chart data với filter
const fetchBookingSourceData = async () => {
  isLoading.value = true;
  try {
    const response = await axios.get(route("dashboard.booking-sources"), {
      params: props.filters,
    });

    const dataLength = response.data.labels.length;
    const randomColors = generateRandomColors(dataLength);

    chartData.value = {
      labels: response.data.labels,
      datasets: [
        {
          data: response.data.data,
          backgroundColor: randomColors,
          borderWidth: 2,
          borderColor: "#fff",
          hoverBorderWidth: 4,
          hoverBorderColor: "#fff",
        },
      ],
    };
  } catch (error) {
    console.error("Lỗi khi lấy dữ liệu nguồn booking:", error);
    chartData.value = { labels: [], datasets: [] };
  } finally {
    isLoading.value = false;
  }
};

// Fetch booking details với filter
const fetchBookingBySource = async () => {
  if (bookingDetails.value.length > 0) return;

  loadingDetails.value = true;
  try {
    const response = await axios.get(route("dashboard.booking-by-source"), {
      params: props.filters,
    });
    bookingDetails.value = response.data.data;
  } catch (error) {
    console.error("Lỗi khi lấy booking theo nguồn:", error);
    bookingDetails.value = [];
  } finally {
    loadingDetails.value = false;
  }
};

const exportSourceData = async () => {
  try {
    const response = await axios.get(
      route("dashboard.export-booking-sources"),
      {
        params: props.filters,
        responseType: "blob",
      }
    );

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `booking-sources-${new Date().toISOString().split("T")[0]}.xlsx`
    );
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (error) {
    console.error("Lỗi khi xuất Excel:", error);
  }
};

// Watch filters từ Index để reload data
watch(
  () => props.filters,
  () => {
    fetchBookingSourceData();
    bookingDetails.value = [];
    hiddenSegments.value = [];
  },
  { deep: true, immediate: true }
);

// Watch dialog để fetch booking details
watch(showDetailDialog, (newValue) => {
  if (newValue) {
    fetchBookingBySource();
  }
});

// Utility functions
</script>

<style scoped>
.booking-source-card {
  border-radius: 16px;
  border: 1px solid rgba(var(--v-border-color), 0.1);
  transition: all 0.3s ease;
  overflow: hidden;
  height: 100%;
}

.booking-source-card:hover {
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.card-header {
  padding: 20px 24px 16px;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.1);
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-info), 0.05) 0%,
    rgba(var(--v-theme-info), 0.02) 100%
  );
  display: flex;
  align-items: center;
  gap: 12px;
}

.detail-btn {
  transition: all 0.3s ease;
}

.detail-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(var(--v-theme-primary), 0.3);
}

.chart-container {
  padding: 24px;
  height: 400px;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.chart-wrapper {
  display: flex;
  gap: 24px;
  height: 100%;
  align-content: center;
}

.chart-content {
  flex: 1;
  position: relative;
  min-height: 300px;
}

.custom-legend {
  width: 300px;
  background: rgba(var(--v-theme-surface), 0.5);
}

.legend-header {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.1);
}

.legend-items {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.legend-item:hover {
  background: rgba(var(--v-theme-primary), 0.05);
  transform: translateX(4px);
}

.legend-item--inactive {
  opacity: 0.5;
}

.legend-color {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  flex-shrink: 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.legend-content {
  flex: 1;
}

.legend-label {
  font-weight: 600;
  font-size: 0.875rem;
  margin-bottom: 4px;
}

.legend-stats {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.legend-count {
  font-size: 0.75rem;
  color: rgb(var(--v-theme-on-surface-variant));
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
}

/* Dialog Styles */
.detail-dialog :deep(.v-overlay__content) {
  margin: 24px;
  width: calc(100% - 48px);
  max-width: 1400px;
}

.dialog-card {
  border-radius: 16px;
  overflow: hidden;
}

.dialog-header {
  padding: 20px 24px;
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-primary), 0.05) 0%,
    rgba(var(--v-theme-primary), 0.02) 100%
  );
}

.close-btn {
  transition: all 0.2s ease;
}

.close-btn:hover {
  transform: rotate(90deg);
}

.summary-stats {
  background: rgba(var(--v-theme-grey-lighten-5), 0.5);
  padding: 24px;
}

.summary-stat-card {
  text-align: center;
  padding: 20px 16px;
  border-radius: 12px;
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-border-color), 0.1);
  transition: all 0.3s ease;
  height: 100%;
}

.summary-stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 8px;
}

.stat-label {
  font-size: 0.875rem;
  color: rgb(var(--v-theme-on-surface-variant));
  font-weight: 500;
}

.loading-container {
  padding: 48px 24px;
  text-align: center;
}

.modern-table {
  border-radius: 0;
}

.modern-table :deep(.v-data-table__td) {
  padding: 16px;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.1);
}

.modern-table :deep(.v-data-table__th) {
  background: rgba(var(--v-theme-primary), 0.05);
  font-weight: 600;
  color: rgb(var(--v-theme-primary));
  padding: 16px;
}

.modern-table :deep(tbody tr:hover) {
  background: rgba(var(--v-theme-primary), 0.05);
}

.source-chip {
  font-weight: 600;
  transition: all 0.3s ease;
}

.source-chip:hover {
  transform: scale(1.05);
}

/* OTA Logo styles */
.legend-label .v-img {
  border-radius: 4px;
  object-fit: cover;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.legend-label .v-img:hover {
  transform: scale(1.1);
  transition: transform 0.2s ease;
}

.no-data-container {
  text-align: center;
  padding: 48px 24px;
}

/* Responsive */
@media (max-width: 1024px) {
  .chart-wrapper {
    flex-direction: column;
    align-content: center;
  }

  .custom-legend {
    width: 100%;
  }

  .legend-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 8px;
  }
}

@media (max-width: 768px) {
  .card-header {
    flex-wrap: wrap;
    gap: 8px;
  }

  .chart-container {
    padding: 16px;
    height: auto;
  }

  .chart-content {
    min-height: 250px;
  }

  .custom-legend {
    padding: 12px;
  }

  .summary-stats {
    padding: 16px;
  }

  .summary-stat-card {
    padding: 16px 12px;
  }

  .detail-dialog :deep(.v-overlay__content) {
    margin: 12px;
    width: calc(100% - 24px);
  }
}

/* Animations */
@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.booking-source-card {
  animation: fadeInScale 0.5s ease-out;
}
</style>
