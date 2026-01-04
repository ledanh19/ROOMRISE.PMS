<template>
  <Head title="Tổng Quan | Room Rise" />
  <Layout>
    <!-- Filter Section -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12" md="3">
            <AppDateTimePicker
              v-model="filters.timeRange"
              placeholder="Chọn ngày"
              :config="{ mode: 'range' }"
            />
          </VCol>
          <!-- <VCol cols="12" md="3">
            <AppSelect
              v-model="filters.area"
              :items="areaOptions"
              placeholder="Chọn khu vực"
            />
          </VCol> -->
          <VCol cols="12" md="3">
            <VBtn color="primary" @click="applyFilters" :loading="loading">
              Áp dụng
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Dashboard Stats Cards Component -->
    <DashboardStatsCards :stats="dashboardStats" />

    <!-- Line Chart section -->
    <VCard class="mt-6">
      <VCardText>
        <div class="chart-container">
          <div class="d-flex justify-space-between align-center mb-4">
            <h3 class="text-h5 font-weight-medium">
              Hoạt động & Doanh thu 7 ngày gần nhất
            </h3>
            <VBtn
              color="primary"
              variant="outlined"
              @click="showDetailDialog = true"
            >
              Xem chi tiết
            </VBtn>
          </div>

          <RevenueLineChart
            v-if="chartData.labels.length > 0"
            :chart-data="chartData"
            :show-title="false"
          />
        </div>
      </VCardText>
    </VCard>
    <!-- Analytics Charts Row -->
    <VRow class="mt-6">
      <VCol cols="12" md="6">
        <BookingSourceChart :filters="filters" />
      </VCol>
      <VCol cols="12" md="6">
        <CustomerLocationChart :filters="filters" />
      </VCol>
      <!-- <VCol cols="12" md="12">
        <BookingByAreaChart :filters="filters" />
      </VCol> -->
    </VRow>

    <!-- Management Widgets Row -->
    <VRow class="mt-6">
      <VCol cols="12" md="8">
        <BookingNeedsProcessing :filters="filters" />
        <CurrentGuests :filters="filters" />
        <RoomStatusByType :filters="filters" />
      </VCol>
      <VCol cols="12" md="4"> <QuickAction /> </VCol>
    </VRow>

    <!-- Dialog chi tiết booking -->
    <VDialog v-model="showDetailDialog" max-width="1200px" scrollable>
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center">
          <span class="text-h5">Chi tiết booking 7 ngày gần nhất</span>
          <VBtn icon variant="text" @click="showDetailDialog = false">
            <VIcon>mdi-close</VIcon>
          </VBtn>
        </VCardTitle>

        <VCardText>
          <VDataTable
            :headers="bookingHeaders"
            :items="bookingDetails"
            :loading="loadingDetails"
            item-value="id"
          >
            <template #item.check_in_date="{ item }">
              {{ formatDate(item.check_in_date) }}
            </template>

            <template #item.customer_name="{ item }">
              {{ item.customer?.full_name || "N/A" }}
            </template>

            <template #item.room_info="{ item }">
              <div v-if="item.booking_rooms && item.booking_rooms.length > 0">
                <div
                  v-for="room in item.booking_rooms"
                  :key="room.id"
                  class="d-flex flex-column mb-2"
                >
                  {{ room.room.name }} ( {{ room.room_unit.name }})
                </div>
              </div>
              <span v-else class="text-grey-500">Chưa gán phòng</span>
            </template>

            <template #item.customer_payment_amount="{ item }">
              {{ formatCurrency(item.customer_payment_amount) }}
            </template>

            <template #item.source="{ item }">
              <VChip :color="getSourceColor(item.ota_name)" size="small">
                {{ item.ota_name || "Trực tiếp" }}
              </VChip>
            </template>
          </VDataTable>
        </VCardText>
      </VCard>
    </VDialog>
  </Layout>
</template>

<script setup>
import Layout from "../../layouts/blank.vue";
import { Link, Head } from "@inertiajs/vue3";
import { ref, onMounted, watch } from "vue";
import RevenueLineChart from "./RevenueLineChart.vue";
import BookingSourceChart from "./BookingSourceChart.vue";
import CustomerLocationChart from "./CustomerLocationChart.vue";
import BookingByAreaChart from "./BookingByAreaChart.vue";
import DashboardStatsCards from "./DashboardStatsCards.vue";
import axios from "axios";
import BookingNeedsProcessing from "./BookingNeedsProcessing.vue";
import CurrentGuests from "./CurrentGuests.vue";
import RoomStatusByType from "./RoomStatusByType.vue";
import QuickAction from "./QuickAction.vue";
import { formatCurrency, formatDate } from "@/utils/formatters";
import dayjs from "dayjs";
import { usePropertyStore } from "@/stores/usePropertyStore";
const propertyStore = usePropertyStore();
// Filter state

const filters = ref({
  timeRange: null,
  property: propertyStore.selectedProperty,
  area: null,
});
const loading = ref(false);

// Filter options
const timeRangeOptions = [
  { title: "Hôm nay", value: "today" },
  { title: "Tuần này", value: "this_week" },
  { title: "Tháng này", value: "this_month" },
  { title: "7 ngày qua", value: "last_7_days" },
  { title: "30 ngày qua", value: "last_30_days" },
];

const propertyOptions = ref([{ title: "Tất cả Property", value: null }]);

const areaOptions = ref([{ title: "Tất cả khu vực", value: null }]);

// Other state variables...
const chartData = ref({ labels: [], datasets: [] });
const dashboardStats = ref({});
const showDetailDialog = ref(false);
const bookingDetails = ref([]);

// Headers cho bảng booking
const bookingHeaders = [
  { title: "Ngày", key: "check_in_date", width: "120px" },
  { title: "Khách", key: "customer_name", width: "200px" },
  { title: "Loại & Phòng", key: "room_info", width: "200px" },
  { title: "Số tiền", key: "customer_payment_amount", width: "150px" },
  { title: "Nguồn booking", key: "source", width: "120px" },
];

// Lấy dữ liệu dashboard stats
const fetchDashboardStats = async () => {
  try {
    loading.value = true;
    const response = await axios.get(route("dashboard.stats"), {
      params: filters.value,
    });
    dashboardStats.value = response.data;
  } catch (error) {
    console.error("Lỗi khi lấy dữ liệu dashboard stats:", error);
  } finally {
    loading.value = false;
  }
};

const fetchChartData = async () => {
  try {
    const response = await axios.get(route("dashboard.chart-data"), {
      params: filters.value,
    });
    chartData.value = response.data;
  } catch (error) {
    console.error("Lỗi khi lấy dữ liệu biểu đồ:", error);
  }
};

// Apply filters - gọi lại tất cả API
const applyFilters = async () => {
  await Promise.all([fetchDashboardStats(), fetchChartData()]);
  // Charts sẽ tự động reload qua watch filters
};

// Auto apply filters khi thay đổi
watch(
  filters,
  () => {
    applyFilters();
  },
  { deep: true }
);

watch(
  () => filters.value.property,
  (newVal) => {
    propertyStore.setProperty(newVal);
  }
);

// Nếu giá trị trong store thay đổi từ Navbar → update vào filters
watch(
  () => propertyStore.selectedProperty,
  (val) => {
    filters.value.property = val;
  }
);

// Lấy chi tiết booking khi mở dialog
const loadingDetails = ref(false);
const fetchBookingDetails = async (propertyId = null) => {
  loadingDetails.value = true;
  try {
    const params = { ...filters.value, property: propertyId };
    const response = await axios.get(route("dashboard.booking-details"), {
      params,
    });
    bookingDetails.value = response.data.bookings;
  } catch (error) {
    console.error("Lỗi khi lấy chi tiết booking:", error);
  } finally {
    loadingDetails.value = false;
  }
};

// Watch dialog để fetch data khi mở
watch(showDetailDialog, (newValue) => {
  if (newValue) {
    console.log("Property khi mở dialog:", propertyStore.selectedProperty);
    fetchBookingDetails(propertyStore.selectedProperty);
  }
});

const getSourceColor = (source) => {
  const colors = {
    "Booking.com": "primary",
    Agoda: "success",
    Expedia: "warning",
    Airbnb: "error",
  };
  return colors[source] || "default";
};

// Load filter options
const loadFilterOptions = async () => {
  try {
    // Load properties
    const propertiesResponse = await axios.get(route("dashboard.properties"));
    propertyOptions.value = [
      { title: "Tất cả Property", value: null },
      ...propertiesResponse.data.map((p) => ({
        title: p.name,
        value: p.id,
      })),
    ];

    // Load areas nếu cần
    // const areasResponse = await axios.get(route("dashboard.areas"));
    // areaOptions.value = [
    //   { title: "Tất cả khu vực", value: null },
    //   ...areasResponse.data.map((a) => ({
    //     title: a.name,
    //     value: a.id,
    //   })),
    // ];
  } catch (error) {
    console.error("Lỗi khi load filter options:", error);
  }
};

onMounted(async () => {
  await loadFilterOptions();
  await applyFilters();
});
</script>

<style scoped>
.chart-container {
  height: 500px;
  position: relative;
}
</style>
