<template>
  <VDialog
    v-model="isVisiableBookingDetailDialog"
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    persistent
  >
    <DialogCloseBtn @click="isVisiableBookingDetailDialog = false" />
    <VCard class="pa-6">
      <VCardText>
        <div v-if="loading" class="d-flex justify-center align-center py-8">
          <VProgressCircular indeterminate color="primary" size="48" />
        </div>
        <div v-else-if="error" class="d-flex justify-center align-center py-8">
          <VAlert type="error" variant="tonal" class="text-center">
            <VIcon icon="tabler-alert-circle" class="me-2" />
            Không thể tải thông tin chi tiết booking
          </VAlert>
        </div>
        <div v-else>
          <!-- Header -->
          <div class="d-flex align-center justify-space-between mb-6">
            <div class="d-flex align-center">
              <h3 class="text-h4 font-weight-bold text-primary mb-1 me-4">
                Thông tin đặt phòng
              </h3>
              <VBtn
                :href="route('bookings.show', props.bookingId)"
                target="_blank"
                variant="outlined"
                color="primary"
                size="small"
                prepend-icon="tabler-external-link"
                class="text-none"
              >
                Xem chi tiết
              </VBtn>
            </div>
            <VChip
              :color="statusColor(bookingDetail.status)"
              variant="flat"
              class="text-white status-chip"
              size="small"
            >
              <VIcon
                :icon="getStatusIcon(bookingDetail.status)"
                size="14"
                class="mr-1"
              ></VIcon>
              {{ bookingDetail.status }}
            </VChip>
          </div>

          <!-- General Information -->
          <VCard variant="outlined" class="mb-6">
            <VCardText class="pa-4">
              <div class="d-flex align-center mb-3">
                <VIcon
                  icon="tabler-info-circle"
                  color="primary"
                  class="me-3"
                  size="24"
                />
                <h6 class="text-h6 mb-0">Thông tin chung</h6>
              </div>
              <VRow>
                <VCol cols="12" md="6">
                  <div class="d-flex align-center mb-3">
                    <VIcon
                      icon="tabler-user"
                      color="info"
                      class="me-2"
                      size="20"
                    />
                    <span class="text-body-2 text-medium-emphasis"
                      >Khách hàng:</span
                    >
                  </div>
                  <div class="text-body-1 font-weight-medium">
                    {{ bookingDetail.customer_name || "Không có thông tin" }}
                  </div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="d-flex align-center mb-3">
                    <VIcon
                      icon="tabler-home"
                      color="success"
                      class="me-2"
                      size="20"
                    />
                    <span class="text-body-2 text-medium-emphasis"
                      >Chỗ nghỉ:</span
                    >
                  </div>
                  <div class="text-body-1 font-weight-medium">
                    {{ bookingDetail.property_name || "-" }}
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <!-- Booking Details -->
          <VRow class="mb-6">
            <VCol cols="12" md="4">
              <VCard variant="outlined" class="h-100">
                <VCardText class="pa-4 text-center">
                  <VIcon
                    icon="tabler-calendar-time"
                    color="success"
                    size="32"
                    class="mb-2"
                  />
                  <div class="text-caption text-medium-emphasis">Check-in</div>
                  <div class="text-h6 font-weight-bold">
                    {{ formatDate(bookingDetail.check_in_datetime) || "-" }}
                  </div>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard variant="outlined" class="h-100">
                <VCardText class="pa-4 text-center">
                  <VIcon
                    icon="tabler-calendar-off"
                    color="error"
                    size="32"
                    class="mb-2"
                  />
                  <div class="text-caption text-medium-emphasis">Check-out</div>
                  <div class="text-h6 font-weight-bold">
                    {{ formatDate(bookingDetail.check_out_datetime) || "-" }}
                  </div>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard variant="outlined" class="h-100">
                <VCardText class="pa-4 text-center">
                  <VIcon
                    icon="tabler-currency-dong"
                    color="warning"
                    size="32"
                    class="mb-2"
                  />
                  <div class="text-caption text-medium-emphasis">Tổng tiền</div>
                  <div class="text-h6 font-weight-bold text-warning">
                    {{ formatCurrency(bookingDetail.total_amount) || "-" }}
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <!-- Room Details Table -->
          <div class="mb-4">
            <div class="d-flex align-center mb-3">
              <VIcon icon="tabler-bed" color="primary" class="me-3" size="24" />
              <h6 class="text-h6 mb-0">Chi tiết phòng</h6>
            </div>
          </div>

          <VCard variant="outlined" class="overflow-hidden">
            <VTable class="text-no-wrap">
              <thead>
                <tr class="bg-grey-darken-3">
                  <th class="text-white font-weight-medium pa-4">
                    <div class="d-flex align-center">
                      <VIcon icon="tabler-bed" class="me-2" size="18" />
                      Loại phòng
                    </div>
                  </th>
                  <th class="text-white font-weight-medium pa-4">
                    <div class="d-flex align-center">
                      <VIcon icon="tabler-door" class="me-2" size="18" />
                      Phòng
                    </div>
                  </th>
                  <th class="text-white font-weight-medium pa-4 text-right">
                    <div class="d-flex align-center justify-end">
                      <VIcon
                        icon="tabler-currency-dong"
                        class="me-2"
                        size="18"
                      />
                      Giá
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(room, index) in bookingDetail.rooms"
                  :key="room.id"
                  :class="index % 2 === 0 ? 'bg-grey-lighten-5' : ''"
                >
                  <td class="pa-4">
                    <div class="d-flex align-center">
                      <VChip
                        variant="tonal"
                        color="primary"
                        size="small"
                        class="me-3"
                      >
                        {{ room.room?.name || "-" }}
                      </VChip>
                    </div>
                  </td>
                  <td class="pa-4">
                    <div class="d-flex align-center">
                      <VIcon
                        icon="tabler-door"
                        color="info"
                        class="me-2"
                        size="18"
                      />
                      {{ room.room_unit?.name || "-" }}
                    </div>
                  </td>
                  <td class="pa-4 text-right">
                    <VChip
                      variant="elevated"
                      color="success"
                      size="small"
                      class="font-weight-bold"
                    >
                      {{ formatCurrency(room.room_price_at_booking) || "-" }}
                    </VChip>
                  </td>
                </tr>
                <tr
                  v-if="
                    !bookingDetail.rooms || bookingDetail.rooms.length === 0
                  "
                >
                  <td colspan="3" class="pa-8 text-center text-medium-emphasis">
                    <VIcon icon="tabler-info-circle" size="24" class="mb-2" />
                    <div>Không có thông tin phòng</div>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { isVisiableBookingDetailDialog } from "@/composables/booking";
import { formatCurrency, formatDate } from "@/utils/formatters";
import axios from "axios";
import { ref, watch } from "vue";

const props = defineProps({
  bookingId: Number,
});

const bookingDetail = ref({});
const loading = ref(false);
const error = ref(false);

const statusColor = (status) => {
  switch (status) {
    case "Mới":
      return "primary";
    case "Xác nhận":
      return "success";
    case "Yêu cầu":
      return "warning";
    case "Hủy":
      return "error";
    case "Hoàn thành":
      return "success";
    default:
      return "grey";
  }
};

const getStatusIcon = (status) => {
  switch (status) {
    case "Mới":
      return "tabler-circle";
    case "Xác nhận":
      return "tabler-check";
    case "Yêu cầu":
      return "tabler-alert-triangle";
    case "Hủy":
      return "tabler-x";
    case "Hoàn thành":
      return "tabler-check";
    default:
      return "tabler-circle";
  }
};

watch(isVisiableBookingDetailDialog, async (isVisible) => {
  if (isVisible && props.bookingId) {
    loading.value = true;
    error.value = false;
    try {
      const res = await axios.get(
        route("bookings.get-detail", props.bookingId)
      );
      bookingDetail.value = res.data;
    } catch (e) {
      error.value = true;
    } finally {
      loading.value = false;
    }
  }
});
</script>

<style scoped>
.v-table {
  border-radius: 8px;
}

.v-table th {
  font-weight: 600 !important;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.875rem;
}

.v-table td {
  font-size: 0.875rem;
}

.v-card {
  border-radius: 12px;
  transition: all 0.3s ease;
}

.v-card:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.v-chip {
  border-radius: 8px;
}
</style>
