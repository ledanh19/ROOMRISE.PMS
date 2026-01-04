<template>
  <VCard>
    <VCardTitle class="pb-4">
      <div class="font-weight-medium">Hành động nhanh</div>
    </VCardTitle>

    <VCardText class="pt-0">
      <VList class="pa-0">
        <!-- Thêm Booking mới -->
        <VListItem class="px-0 mb-2" rounded @click="createNewBooking">
          <VBtn class="font-weight-medium bg_black w-100 text-left">
            Thêm Booking mới
          </VBtn>
        </VListItem>

        <!-- Tạo hóa đơn -->
        <!-- <VListItem class="px-0 mb-2" rounded @click="createInvoice">
          <VListItemTitle class="font-weight-medium">
            Tạo hóa đơn
          </VListItemTitle>
        </VListItem> -->

        <!-- Xem thông báo -->
        <!-- <VListItem class="px-0 mb-2" rounded @click="viewNotifications">
          <VListItemTitle class="font-weight-medium">
            Xem thông báo
            <VChip
              v-if="notificationCount > 0"
              size="small"
              color="error"
              class="ml-2"
            >
              {{ notificationCount }}
            </VChip>
          </VListItemTitle>
        </VListItem> -->

        <!-- Phân tích thị trường -->
        <!-- <VListItem class="px-0 mb-2" rounded @click="viewMarketAnalysis">
          <VListItemTitle class="font-weight-medium">
            Phân tích thị trường
          </VListItemTitle>
        </VListItem> -->
      </VList>
    </VCardText>
    <BookingFormDialog v-model:is-dialog-visible="isFormDialogVisible" />
  </VCard>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import BookingFormDialog from "@/Components/bookings/BookingFormDialog.vue";
const notificationCount = ref(4);

// Actions

const isFormDialogVisible = ref(false);
const createNewBooking = () => {
  isFormDialogVisible.value = true;
};

const createInvoice = () => {};

const viewNotifications = () => {};

const viewMarketAnalysis = () => {};

// Load notification count
const loadNotificationCount = async () => {
  //   try {
  //     // API call để lấy số thông báo chưa đọc
  //     const response = await axios.get(route("notifications.unread-count"));
  //     notificationCount.value = response.data.count;
  //   } catch (error) {
  //     console.error("Lỗi khi lấy số thông báo:", error);
  //   }
};

onMounted(() => {
  loadNotificationCount();
});
</script>

<style scoped>
.v-list-item {
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  border: 1px solid transparent;
}

/* .v-list-item:hover {
  background-color: rgba(var(--v-theme-primary), 0.04);
  border-color: rgba(var(--v-theme-primary), 0.12);
} */

.v-btn {
  box-shadow: none !important;
}
.bg_black {
  background-color: #000 !important;
  color: #fff !important;
}
</style>
