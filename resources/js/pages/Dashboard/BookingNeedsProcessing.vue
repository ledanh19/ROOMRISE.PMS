<template>
  <VCard>
    <VCardTitle class="d-flex justify-space-between align-center">
      <div class="text-h5">Booking cần xử lý</div>
    </VCardTitle>

    <VCardText>
      <div v-if="loading" class="text-center py-4">
        <VProgressCircular indeterminate color="primary" />
      </div>

      <div v-else-if="bookings.length === 0" class="text-center py-4">
        <VIcon size="48" color="success" class="mb-2">mdi-check-circle</VIcon>
        <div class="text-body-1">Không có booking cần xử lý</div>
      </div>

      <div v-else>
        <VDataTable :headers="headers" :items="bookings" :items-per-page="10">
          <template #item.customer="{ item }">
            {{ item.customer?.full_name }}
          </template>

          <template #item.dates="{ item }">
            {{ formatDate(item.check_in_date) }} -
            {{ formatDate(item.check_out_date) }} <br />
            ({{ getDaysDifference(item.check_in_date, item.check_out_date) }}
            đêm)
          </template>

          <template #item.amount="{ item }">
            {{ formatCurrency(item.customer_payment_amount) }}
          </template>

          <template #item.issue_type="{ item }">
            <VChip
              :color="getStatusColor(item.issue_type)"
              size="small"
              variant="flat"
            >
              {{ item.issue_type }}
            </VChip>
          </template>

          <template #item.actions="{ item }">
            <VBtn
              size="small"
              color="primary"
              variant="outlined"
              class="mr-1"
              @click="viewDetails(item)"
            >
              Chi tiết
            </VBtn>
          </template>
        </VDataTable>
      </div>
    </VCardText>
  </VCard>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import { formatCurrency, formatDate } from "@/utils/formatters";
// Props
const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const loading = ref(false);
const bookings = ref([]);

// Fetch bookings cần xử lý
const fetchBookingsNeedProcessing = async () => {
  loading.value = true;
  try {
    const response = await axios.get(
      route("dashboard.bookings-need-processing"),
      {
        params: props.filters,
      }
    );
    bookings.value = response.data.data;
  } catch (error) {
    console.error("Lỗi khi lấy booking cần xử lý:", error);
  } finally {
    loading.value = false;
  }
};

// Watch filters
watch(
  () => props.filters,
  () => {
    fetchBookingsNeedProcessing();
  },
  { deep: true, immediate: true }
);

const headers = [
  { title: "Khách hàng", key: "customer", sortable: false },
  { title: "Ngày", key: "dates", sortable: false },
  { title: "Số tiền", key: "amount", sortable: true },
  { title: "Vấn đề", key: "issue_type", sortable: false },
  { title: "Hành động", key: "actions", sortable: false },
];

// Actions
const viewDetails = (booking) => {
  // Navigate to booking detail page
  window.open(route("bookings.show", booking.id), "_blank");
};

// Utility functions

const getDaysDifference = (checkin, checkout) => {
  const diff = new Date(checkout) - new Date(checkin);
  return Math.ceil(diff / (1000 * 60 * 60 * 24));
};

const getSourceColor = (source) => {
  const colors = {
    "Booking.com": "primary",
    Agoda: "success",
    Expedia: "warning",
    Airbnb: "error",
  };
  return colors[source] || "default";
};

const getStatusColor = (issueType) => {
  const colors = {
    "Chưa gán phòng": "error",
    "Chưa thu tiền": "warning",
    "Cần xác nhận": "info",
  };
  return colors[issueType] || "default";
};
</script>
