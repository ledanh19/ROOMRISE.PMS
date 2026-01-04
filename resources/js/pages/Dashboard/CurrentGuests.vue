<template>
  <VCard class="mt-5">
    <VCardTitle>Khách đang lưu trú</VCardTitle>

    <VCardText>
      <div v-if="loading" class="text-center py-4">
        <VProgressCircular indeterminate color="primary" />
      </div>

      <div v-else-if="guests.length === 0" class="text-center py-4">
        <VIcon size="48" color="grey" class="mb-2">mdi-home-outline</VIcon>
        <div class="text-body-1">Không có khách lưu trú</div>
      </div>

      <div v-else>
        <VDataTable
          :headers="headers"
          :items="guests"
          :items-per-page="10"
        
        >
          <template #item.full_name="{ item }">
            {{ item.customer?.full_name || "Không có tên" }}
          </template>

          <template #item.room_info="{ item }">
            {{ item.room_info }}
          </template>

          <template #item.check_out_date="{ item }">
            {{ getDaysRemaining(item.check_out_date) }}
          </template>

          <template #item.status="{ item }">
            <VChip
              :color="getStatusColor(item.status)"
              size="small"
              variant="flat"
            >
              {{ item.status }}
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
import { ref, watch } from "vue";
import axios from "axios";

// Props
const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const loading = ref(false);
const guests = ref([]);

// Fetch current guests
const fetchCurrentGuests = async () => {
  loading.value = true;
  try {
    const response = await axios.get(route("dashboard.current-guests"), {
      params: props.filters,
    });
    guests.value = response.data.data;
  } catch (error) {
    console.error("Lỗi khi lấy khách đang lưu trú:", error);
  } finally {
    loading.value = false;
  }
};
const headers = [
  {
    title: "Khách hàng",
    key: "full_name",
    sortable: false,
  },
  {
    title: "Phòng",
    key: "room_info",
    sortable: false,
  },
  {
    title: "Ngày check-out",
    key: "check_out_date",
    sortable: false,
  },
  {
    title: "Trạng thái",
    key: "status",
    sortable: false,
  },
  {
    title: "Thao tác",
    key: "actions",
    sortable: false,
  },
];

// Watch filters
watch(
  () => props.filters,
  () => {
    fetchCurrentGuests();
  },
  { deep: true, immediate: true }
);

// Actions
const viewDetails = (guest) => {
  window.open(route("bookings.show", guest.id), "_blank");
};

// Utility functions
const getDaysRemaining = (checkoutDate) => {
  const today = new Date();
  const checkout = new Date(checkoutDate);
  const diffTime = checkout - today;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays === 0) return "Check-out hôm nay";
  if (diffDays === 1) return "Check-out ngày mai";
  if (diffDays > 1) return `Còn ${diffDays} ngày`;
  return "Quá hạn check-out";
};

const getStatusColor = (status) => {
  const colors = {
    "Đang lưu trú": "success",
    "Check-out hôm nay": "warning",
    "Đang lưu trú": "info",
  };
  return colors[status] || "default";
};
</script>
