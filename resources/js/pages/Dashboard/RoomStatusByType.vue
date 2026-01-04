<template>
  <VCard class="mt-5">
    <VCardTitle class="d-flex justify-space-between align-center">
      <span>Tình trạng phòng theo loại</span>
    </VCardTitle>

    <VCardText>
      <div v-if="loading" class="text-center py-4">
        <VProgressCircular indeterminate color="primary" />
      </div>

      <div v-else>
        <VDataTable
          :headers="headers"
          :items="roomTypes"
          :items-per-page="10"
          :loading="loading"
          loading-text="Đang tải dữ liệu..."
        >
          <!-- Room Name -->
          <template #item.room_name="{ item }">
            <strong>{{ item.room_name }}</strong>
          </template>

          <!-- Occupancy Info -->
          <template #item.occupancy="{ item }">
            <div class="text-caption">
              {{ item.booked }}/{{ item.total }} ({{ item.percentage }}%)
            </div>
            <VProgressLinear
              :model-value="item.percentage"
              :color="getOccupancyColor(item.percentage)"
              height="6"
              rounded
            />
          </template>

          <!-- Available -->
          <template #item.available="{ item }">
            <span>{{ item.available }}</span>
          </template>

          <!-- Action -->
          <template #item.actions="{ item }">
            <VBtn
              color="primary"
              size="small"
              variant="outlined"
              @click="viewRoomTypeDetails(item)"
            >
              Chi tiết
            </VBtn>
          </template>
        </VDataTable>
      </div>
    </VCardText>

    <!-- Dialog chi tiết phòng -->
    <VDialog v-model="showDetailDialog" max-width="600px">
      <VCard class="pa-2">
        <VCardTitle class="d-flex justify-space-between align-center">
          <span>Chi tiết phòng - {{ selectedRoomType?.room_name }}</span>
        </VCardTitle>

        <VCardText>
          <div v-if="loadingDetails" class="text-center py-4">
            <VProgressCircular indeterminate color="primary" />
          </div>

          <div v-else-if="selectedRoomType">
            <!-- Thống kê tổng quan -->
            <VRow class="mb-4">
              <VCol cols="6">
                <div class="text-center">
                  <div class="text-h4 font-weight-bold">
                    {{ selectedRoomType.total }}
                  </div>
                  <div class="text-caption text-grey-600">Tổng số phòng</div>
                </div>
              </VCol>
              <VCol cols="6">
                <div class="text-center">
                  <div class="text-h4 font-weight-bold text-success">
                    {{ selectedRoomType.booked }}
                  </div>
                  <div class="text-caption text-grey-600">Đang sử dụng</div>
                </div>
              </VCol>
            </VRow>

            <VRow class="mb-4">
              <VCol cols="6">
                <div class="text-center">
                  <div class="text-h4 font-weight-bold text-info">
                    {{ selectedRoomType.available }}
                  </div>
                  <div class="text-caption text-grey-600">Còn trống</div>
                </div>
              </VCol>
              <VCol cols="6">
                <div class="text-center">
                  <div class="text-h4 font-weight-bold text-warning">
                    {{ selectedRoomType.percentage }}%
                  </div>
                  <div class="text-caption text-grey-600">Tỷ lệ lấp đầy</div>
                </div>
              </VCol>
            </VRow>

            <VDivider class="my-4" />

            <!-- Danh sách phòng còn trống -->
            <div class="mb-3">
              <h4 class="text-h6 font-weight-medium">
                Danh sách phòng còn trống
              </h4>
            </div>

            <div v-if="roomDetails.available === 0" class="text-center py-4">
              <div class="text-body-1">Không có phòng trống</div>
              <div class="text-caption text-grey-600">
                Tất cả phòng đều đã được đặt
              </div>
            </div>

            <VRow v-else>
              <VCol
                v-for="room in roomDetails.empty_rooms"
                :key="room.id"
                cols="6"
                sm="4"
                md="3"
              >
                <VCard
                  color="default"
                  variant="outlined"
                  class="text-center pa-3 room-card"
                  elevation="1"
                >
                  <div class="font-weight-medium">{{ room.name }}</div>
                </VCard>
              </VCol>
            </VRow>
          </div>
        </VCardText>
      </VCard>
    </VDialog>
  </VCard>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import axios from "axios";

// Props
const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const loading = ref(false);
const loadingDetails = ref(false);
const roomTypes = ref([]);
const showDetailDialog = ref(false);
const selectedRoomType = ref(null);
const roomDetails = ref([]);

// Fetch room status by type
const fetchRoomStatusByType = async () => {
  loading.value = true;
  try {
    const response = await axios.get(route("dashboard.room-status-by-type"), {
      params: props.filters,
    });
    roomTypes.value = response.data.data;
  } catch (error) {
    console.error("Lỗi khi lấy tình trạng phòng:", error);
  } finally {
    loading.value = false;
  }
};

// Fetch room details - chỉ lấy phòng còn trống
const fetchRoomDetails = async (roomId) => {
  loadingDetails.value = true;
  try {
    const response = await axios.get(route("dashboard.room-details"), {
      params: {
        room_id: roomId,
        ...props.filters,
      },
    });
    roomDetails.value = response.data.data;
    console.log("Room details:", roomDetails.value);
  } catch (error) {
    console.error("Lỗi khi lấy chi tiết phòng:", error);
    // Mock data chỉ phòng trống
  } finally {
    loadingDetails.value = false;
  }
};
const headers = [
  { title: "Loại phòng", key: "room_name" },
  { title: "Tình trạng", key: "occupancy", sortable: false },
  { title: "Còn trống", key: "available" },
  { title: "Hành động", key: "actions", sortable: false },
];

// Watch filters
watch(
  () => props.filters,
  () => {
    fetchRoomStatusByType();
  },
  { deep: true, immediate: true }
);

// Actions
const viewRoomTypeDetails = async (roomType) => {
  selectedRoomType.value = roomType;
  showDetailDialog.value = true;
  await fetchRoomDetails(roomType.id);
};

// Utility functions
const getOccupancyColor = (rate) => {
  if (rate >= 90) return "error";
  if (rate >= 70) return "warning";
  if (rate >= 50) return "success";
  return "info";
};

const getRoomStatusColor = (status) => {
  switch (status) {
    case "occupied":
      return "success";
    case "available":
      return "default";
    case "maintenance":
      return "warning";
    case "cleaning":
      return "info";
    default:
      return "default";
  }
};

const getRoomStatusText = (status) => {
  switch (status) {
    case "occupied":
      return "Đang lưu trú";
    case "available":
      return "Trống";
    case "maintenance":
      return "Bảo trì";
    case "cleaning":
      return "Dọn phòng";
    default:
      return "Không xác định";
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString("vi-VN");
};
</script>

<style scoped>
.v-card {
  transition: transform 0.2s ease-in-out;
}

.v-card:hover {
  transform: translateY(-2px);
}

.room-card {
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}

.room-card:hover {
  border-color: rgb(var(--v-theme-primary));
  transform: translateY(-1px);
}
</style>
