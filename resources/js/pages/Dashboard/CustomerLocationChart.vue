<template>
  <VCard class="customer-location-card" elevation="3">
    <VCardTitle class="card-header">
      <VAvatar size="40" color="success" variant="tonal" class="me-3">
        <VIcon icon="tabler-map-pin" />
      </VAvatar>
      <div class="flex-grow-1">
        <div class="text-h5 font-weight-bold">Thành phố khách hàng</div>
        <div class="text-body-2 text-medium-emphasis">
          Phân bố khách hàng theo địa lý
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

        <!-- Enhanced Custom Legend -->
        <div class="custom-legend">
          <div class="legend-header">
            <VIcon icon="tabler-map" size="16" class="me-2" />
            <span class="text-subtitle-2 font-weight-bold">Phân bố địa lý</span>
            <VChip size="x-small" color="grey" variant="tonal" class="ms-2">
              {{ legendData.length }} thành phố
            </VChip>
          </div>

          <!-- Enhanced Legend Items với Scrollable Container -->
          <div class="legend-items-container">
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
                <div class="legend-content">
                  <div class="legend-label">
                    <VIcon icon="tabler-map-pin" size="12" class="me-1" />
                    <span class="legend-text">{{ item.label }}</span>
                  </div>
                  <div class="legend-stats">
                    <span class="legend-count">
                      <strong>{{ item.value }}</strong>
                    </span>
                    <VChip
                      size="x-small"
                      :color="getLocationChipColor(item.percentage)"
                      variant="tonal"
                      class="legend-percentage"
                    >
                      {{ item.percentage }}%
                    </VChip>
                  </div>
                </div>
              </div>
            </div>

            <!-- Scroll Indicator -->
            <div v-if="legendData.length > 8" class="scroll-indicator">
              <VIcon
                icon="tabler-chevron-down"
                size="12"
                class="text-medium-emphasis"
              />
              <span class="text-caption text-medium-emphasis"
                >Cuộn để xem thêm</span
              >
            </div>
          </div>

          <div class="legend-summary-compact">
            <div class="summary-row">
              <VIcon icon="tabler-users" size="14" class="me-1 text-success" />
              <span class="text-caption">
                <strong>{{ totalCustomers }}</strong> khách hàng
              </span>
            </div>
            <div class="summary-row">
              <VIcon icon="tabler-map-pin" size="14" class="me-1 text-info" />
              <span class="text-caption">
                <strong>{{ legendData.length }}</strong> thành phố
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <VAvatar size="80" color="grey-lighten-3" class="mb-4">
          <VIcon icon="tabler-map-off" size="40" />
        </VAvatar>
        <div class="text-h6 text-disabled mb-2">Chưa có dữ liệu</div>
        <div class="text-body-2 text-medium-emphasis">
          Chưa có thông tin thành phố khách hàng
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
          <VAvatar size="40" color="success" variant="tonal" class="me-3">
            <VIcon icon="tabler-users" />
          </VAvatar>
          <div class="flex-grow-1">
            <span class="text-h5 font-weight-bold"
              >Chi tiết khách hàng theo thành phố</span
            >
            <div class="text-body-2 text-medium-emphasis">
              {{ customerDetails.length }} khách hàng được tìm thấy
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

        <VCardText class="pa-0">
          <div v-if="loadingDetails" class="loading-container">
            <VSkeletonLoader type="table-row@5" />
          </div>

          <VDataTable
            v-else
            :headers="enhancedCustomerHeaders"
            :items="filteredCustomerDetails"
            :loading="loadingDetails"
            item-value="id"
            class="modern-table"
            :items-per-page="15"
            :search="searchQuery"
          >
            <template #item.full_name="{ item }">
              <div class="d-flex align-center">
                <VAvatar size="32" color="primary" variant="tonal" class="me-3">
                  <VIcon icon="tabler-user" size="16" />
                </VAvatar>
                <div>
                  <div class="font-weight-bold">{{ item.full_name }}</div>
                  <div class="text-caption text-medium-emphasis">
                    ID: {{ item.id }}
                  </div>
                </div>
              </div>
            </template>

            <template #item.location="{ item }">
              <div class="d-flex align-center">
                <VIcon
                  icon="tabler-map-pin"
                  size="16"
                  class="me-2 text-success"
                />
                <VChip
                  :color="getCityChipColor(item.city)"
                  size="small"
                  variant="tonal"
                >
                  {{ item.city || "Chưa xác định" }}
                </VChip>
              </div>
            </template>

            <template #item.bookings_count="{ item }">
              <div class="d-flex align-center">
                <VAvatar size="24" color="info" variant="tonal" class="me-2">
                  <VIcon icon="tabler-calendar-check" size="12" />
                </VAvatar>
                <span class="font-weight-bold text-info">
                  {{ item.bookings_count }} booking
                </span>
              </div>
            </template>

            <template #item.total_spent="{ item }">
              <div class="d-flex align-center justify-end">
                <VIcon
                  icon="tabler-currency-dollar"
                  size="16"
                  class="me-2 text-success"
                />
                <span class="font-weight-bold text-success">
                  {{ formatCurrency(item.total_spent) }}
                </span>
              </div>
            </template>

            <template #item.last_booking="{ item }">
              <div class="d-flex align-center">
                <VIcon
                  icon="tabler-calendar"
                  size="16"
                  class="me-2 text-medium-emphasis"
                />
                <span>{{ formatDate(item.last_booking_date) }}</span>
              </div>
            </template>

            <template #item.actions="{ item }">
              <VBtn
                size="small"
                color="primary"
                variant="tonal"
                prepend-icon="tabler-eye"
                @click="viewCustomerDetails(item)"
                class="action-btn"
              >
                Xem
              </VBtn>
            </template>

            <template #no-data>
              <div class="no-data-container">
                <VAvatar size="64" color="grey-lighten-3" class="mb-4">
                  <VIcon icon="tabler-user-off" size="32" />
                </VAvatar>
                <div class="text-h6 text-disabled mb-2">Không có dữ liệu</div>
                <div class="text-body-2 text-medium-emphasis">
                  Không tìm thấy khách hàng nào
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
import { Pie } from "vue-chartjs";
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from "chart.js";
import { ref, watch, computed } from "vue";
import axios from "axios";

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
const customerDetails = ref([]);
const isLoading = ref(false);
const hiddenSegments = ref([]);

// Filter states
const searchQuery = ref("");
const selectedCity = ref(null);
const sortBy = ref("name");

// Enhanced headers
const enhancedCustomerHeaders = [
  {
    title: "Khách hàng",
    key: "full_name",
    width: "250px",
    sortable: true,
  },
  {
    title: "Thành phố",
    key: "location",
    width: "180px",
    sortable: true,
  },
  {
    title: "Số booking",
    key: "bookings_count",
    width: "140px",
    align: "center",
    sortable: true,
  },
];

// Generate random colors
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
  ];

  for (let i = 0; i < count; i++) {
    if (i < baseColors.length) {
      colors.push(baseColors[i]);
    } else {
      const hue = Math.floor(Math.random() * 360);
      const saturation = 65 + Math.floor(Math.random() * 20);
      const lightness = 55 + Math.floor(Math.random() * 20);
      colors.push(`hsl(${hue}, ${saturation}%, ${lightness}%)`);
    }
  }
  return colors;
};

// Computed properties
const hasData = computed(() => {
  return chartData.value.labels && chartData.value.labels.length > 0;
});

const totalCustomers = computed(() => {
  if (!hasData.value || !chartData.value.datasets[0]?.data) return 0;
  return chartData.value.datasets[0].data.reduce((a, b) => a + b, 0);
});

const legendData = computed(() => {
  if (!hasData.value) return [];

  const labels = chartData.value.labels || [];
  const data = chartData.value.datasets[0]?.data || [];
  const colors = chartData.value.datasets[0]?.backgroundColor || [];

  if (labels.length === 0 || data.length === 0) return [];

  const total = data.reduce((a, b) => a + b, 0);
  if (total === 0) return [];

  return labels.map((label, index) => {
    const value = data[index] || 0;
    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;

    return {
      label: label || `Thành phố ${index + 1}`,
      value: value,
      percentage: percentage,
      color: colors[index] || "#cccccc",
    };
  });
});

const cityOptions = computed(() => {
  const cities = [
    ...new Set(customerDetails.value.map((c) => c.city).filter(Boolean)),
  ];
  return cities.map((city) => ({ title: city, value: city }));
});

const sortOptions = [
  { title: "Tên A-Z", value: "name" },
  { title: "Số booking nhiều nhất", value: "bookings_desc" },
  { title: "Chi tiêu nhiều nhất", value: "spent_desc" },
  { title: "Đặt gần nhất", value: "recent" },
];

const filteredCustomerDetails = computed(() => {
  let filtered = [...customerDetails.value];

  // Filter by city
  if (selectedCity.value) {
    filtered = filtered.filter((c) => c.city === selectedCity.value);
  }

  // Sort
  switch (sortBy.value) {
    case "bookings_desc":
      filtered.sort(
        (a, b) => (b.bookings_count || 0) - (a.bookings_count || 0)
      );
      break;
    case "spent_desc":
      filtered.sort((a, b) => (b.total_spent || 0) - (a.total_spent || 0));
      break;
    case "recent":
      filtered.sort(
        (a, b) =>
          new Date(b.last_booking_date || 0) -
          new Date(a.last_booking_date || 0)
      );
      break;
    default:
      filtered.sort((a, b) =>
        (a.full_name || "").localeCompare(b.full_name || "")
      );
  }

  return filtered;
});

const summaryStats = computed(() => {
  if (!customerDetails.value.length) {
    return [
      {
        label: "Tổng khách hàng",
        value: 0,
        color: "primary",
        icon: "tabler-users",
      },
      {
        label: "Tổng booking",
        value: 0,
        color: "success",
        icon: "tabler-calendar-check",
      },
      {
        label: "Tổng doanh thu",
        value: formatCurrency(0),
        color: "info",
        icon: "tabler-currency-dollar",
      },
      {
        label: "Số thành phố",
        value: 0,
        color: "warning",
        icon: "tabler-map-pin",
      },
    ];
  }

  const totalBookings = customerDetails.value.reduce(
    (sum, customer) => sum + (customer.bookings_count || 0),
    0
  );
  const totalRevenue = customerDetails.value.reduce(
    (sum, customer) => sum + (customer.total_spent || 0),
    0
  );
  const uniqueCities = [
    ...new Set(customerDetails.value.map((c) => c.city).filter(Boolean)),
  ];

  return [
    {
      label: "Tổng khách hàng",
      value: customerDetails.value.length,
      color: "primary",
      icon: "tabler-users",
    },
    {
      label: "Tổng booking",
      value: totalBookings,
      color: "success",
      icon: "tabler-calendar-check",
    },
    {
      label: "Tổng doanh thu",
      value: formatCurrency(totalRevenue),
      color: "info",
      icon: "tabler-currency-dollar",
    },
    {
      label: "Số thành phố",
      value: uniqueCities.length,
      color: "warning",
      icon: "tabler-map-pin",
    },
  ];
});

// Chart options
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
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
          const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
          return `${label}: ${value} khách hàng (${percentage}%)`;
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
};

const getLocationChipColor = (percentage) => {
  if (percentage >= 30) return "success";
  if (percentage >= 15) return "warning";
  return "error";
};

const getCityChipColor = (city) => {
  const colors = ["primary", "success", "warning", "info", "secondary"];
  const index = city ? city.charCodeAt(0) % colors.length : 0;
  return colors[index];
};

// Fetch chart data
const fetchCustomerLocationData = async () => {
  isLoading.value = true;
  try {
    const response = await axios.get(route("dashboard.customer-locations"), {
      params: props.filters,
    });

    if (!response.data || !response.data.labels || !response.data.data) {
      chartData.value = { labels: [], datasets: [] };
      return;
    }

    const labels = response.data.labels;
    const data = response.data.data;
    const dataLength = Math.max(labels.length, data.length);
    const randomColors = generateRandomColors(dataLength);

    chartData.value = {
      labels: labels,
      datasets: [
        {
          data: data,
          backgroundColor: randomColors,
          borderWidth: 2,
          borderColor: "#fff",
          hoverBorderWidth: 4,
          hoverBorderColor: "#fff",
        },
      ],
    };
  } catch (error) {
    console.error("Lỗi khi lấy dữ liệu vị trí khách hàng:", error);
    chartData.value = { labels: [], datasets: [] };
  } finally {
    isLoading.value = false;
  }
};

// Fetch customer details
const fetchCustomerDetails = async () => {
  if (customerDetails.value.length > 0) return;

  loadingDetails.value = true;
  try {
    const response = await axios.get(route("dashboard.customer-details"), {
      params: props.filters,
    });

    if (response.data && Array.isArray(response.data.data)) {
      customerDetails.value = response.data.data;
    } else {
      customerDetails.value = [];
    }
  } catch (error) {
    console.error("Lỗi khi lấy chi tiết khách hàng:", error);
    customerDetails.value = [];
  } finally {
    loadingDetails.value = false;
  }
};

const viewCustomerDetails = (customer) => {
  // Navigate to customer detail page
  console.log("View customer:", customer);
};

const exportLocationData = async () => {
  try {
    const response = await axios.get(
      route("dashboard.export-customer-locations"),
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
      `customer-locations-${new Date().toISOString().split("T")[0]}.xlsx`
    );
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (error) {
    console.error("Lỗi khi xuất Excel:", error);
  }
};

// Watch filters
watch(
  () => props.filters,
  () => {
    fetchCustomerLocationData();
    customerDetails.value = [];
    hiddenSegments.value = [];
  },
  { deep: true, immediate: true }
);

// Watch dialog
watch(showDetailDialog, (newValue) => {
  if (newValue) {
    fetchCustomerDetails();
  }
});

// Thêm reactive state cho legend view
const isCompactView = ref(false);

// Thêm method toggle legend view
const toggleLegendView = () => {
  isCompactView.value = !isCompactView.value;
};

// Utility functions
const formatDate = (date) => {
  if (!date) return "Chưa có";
  return new Date(date).toLocaleDateString("vi-VN");
};

const formatCurrency = (value) =>
  new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(value || 0);
</script>

<style scoped>
.customer-location-card {
  border-radius: 16px;
  border: 1px solid rgba(var(--v-border-color), 0.1);
  transition: all 0.3s ease;
  overflow: hidden;
  height: 100%;
}

.customer-location-card:hover {
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.card-header {
  padding: 20px 24px 16px;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.1);
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-success), 0.05) 0%,
    rgba(var(--v-theme-success), 0.02) 100%
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
}

.chart-content {
  flex: 1;
  position: relative;
  min-height: 300px;
}

.custom-legend {
  width: 300px;
  background: rgba(var(--v-theme-surface), 0.5);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(var(--v-border-color), 0.1);
}

.legend-header {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.1);
}

.legend-items-container {
  max-height: 250px;
  overflow-y: auto;
  padding-right: 8px;
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
  background: rgba(var(--v-theme-success), 0.05);
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
  display: flex;
  align-items: center;
}

.legend-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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

.legend-count strong {
  color: rgb(var(--v-theme-success));
}

.legend-summary {
  text-align: center;
  padding: 8px;
  background: rgba(var(--v-theme-grey-lighten-4), 0.5);
  border-radius: 6px;
}

.legend-summary-compact {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-top: 1px solid rgba(var(--v-border-color), 0.1);
}

.summary-row {
  display: flex;
  align-items: center;
  gap: 8px;
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
    rgba(var(--v-theme-success), 0.05) 0%,
    rgba(var(--v-theme-success), 0.02) 100%
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

.filter-section {
  background: rgba(var(--v-theme-grey-lighten-5), 0.3);
  padding: 20px 24px;
}

.search-input,
.city-filter,
.sort-select {
  transition: all 0.3s ease;
}

.search-input:hover,
.city-filter:hover,
.sort-select:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
  background: rgba(var(--v-theme-success), 0.05);
  font-weight: 600;
  color: rgb(var(--v-theme-success));
  padding: 16px;
}

.modern-table :deep(tbody tr:hover) {
  background: rgba(var(--v-theme-success), 0.05);
}

.action-btn {
  transition: all 0.3s ease;
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(var(--v-theme-primary), 0.3);
}

.no-data-container {
  text-align: center;
  padding: 48px 24px;
}

/* Responsive */
@media (max-width: 1024px) {
  .chart-wrapper {
    flex-direction: column;
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

  .filter-section {
    padding: 16px 20px;
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

.customer-location-card {
  animation: fadeInScale 0.5s ease-out;
}
</style>
