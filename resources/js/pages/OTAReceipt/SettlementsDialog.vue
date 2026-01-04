<template>
  <VDialog
    :model-value="props.isDialogVisible || props.isEditDialogVisible"
    @update:model-value="
      (val) => {
        if (!val) {
          emit('update:isDialogVisible', false);
          emit('update:isEditDialogVisible', false);
        }
      }
    "
    width="1100"
    persistent
    transition="dialog-bottom-transition"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="settlement-dialog-card">
      <!-- Header với gradient và animation -->
      <VCardTitle class="d-flex align-center pa-8 text-white settlement-header">
        <div class="header-icon-wrapper me-4">
          <VIcon icon="tabler-file-invoice" size="32" />
        </div>
        <div class="header-content">
          <div class="text-h4 font-weight-bold mb-2">Xác nhận quyết toán</div>
          <div class="text-body-1 opacity-90">
            Tạo phiếu quyết toán cho
            <span class="font-weight-bold text-warning-lighten-3">
              {{ props.selectedBookings?.length || 0 }} booking
            </span>
          </div>
        </div>
        <div class="header-decoration">
          <div class="decoration-circle"></div>
          <div class="decoration-circle"></div>
        </div>
      </VCardTitle>

      <VCardText class="pa-0">
        <!-- Summary Cards với animation -->
        <div class="pa-8 bg-grey-lighten-5">
          <div class="d-flex align-center mb-6">
            <VIcon
              icon="tabler-chart-pie"
              class="me-3"
              color="primary"
              size="24"
            />
            <span class="text-h5 font-weight-bold">Tổng quan tài chính</span>
          </div>
          <VRow>
            <VCol cols="4">
              <VCard
                class="summary-card text-center elevation-4 hover-elevation-8"
                color="primary"
                variant="tonal"
              >
                <VCardText class="py-6">
                  <div class="icon-wrapper mb-3">
                    <VIcon icon="tabler-coins" size="40" color="primary" />
                  </div>
                  <div class="text-subtitle-1 mb-2 font-weight-medium">
                    Tổng tiền
                  </div>
                  <div class="text-h5 font-weight-bold text-primary">
                    {{ formatCurrency(totalNetEstimate) }}
                  </div>
                </VCardText>
              </VCard>
            </VCol>
            <VCol cols="4">
              <VCard
                class="summary-card text-center elevation-4 hover-elevation-8"
                color="success"
                variant="tonal"
              >
                <VCardText class="py-6">
                  <div class="icon-wrapper mb-3">
                    <VIcon icon="tabler-cash" size="40" color="success" />
                  </div>
                  <div class="text-subtitle-1 mb-2 font-weight-medium">
                    Tổng đã nhận
                  </div>
                  <div class="text-h5 font-weight-bold text-success">
                    {{ formatCurrency(totalPayoutReceived) }}
                  </div>
                </VCardText>
              </VCard>
            </VCol>
            <VCol cols="4">
              <VCard
                class="summary-card text-center elevation-4 hover-elevation-8"
                :color="differenceColor"
                variant="tonal"
              >
                <VCardText class="py-6">
                  <div class="icon-wrapper mb-3">
                    <VIcon
                      :icon="differenceIcon"
                      size="40"
                      :color="differenceColor"
                    />
                  </div>
                  <div class="text-subtitle-1 mb-2 font-weight-medium">
                    Chênh lệch
                  </div>
                  <div
                    class="text-h5 font-weight-bold"
                    :class="`text-${differenceColor}`"
                  >
                    {{ formatCurrency(Math.abs(differenceAmount)) }}
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </div>

        <!-- Table Section với styling mới -->
        <div class="pa-8">
          <div class="d-flex align-center mb-6">
            <div class="section-icon-wrapper me-3">
              <VIcon icon="tabler-list-details" color="white" size="20" />
            </div>
            <span class="text-h5 font-weight-bold">Chi tiết booking</span>
          </div>
          <VCard
            variant="outlined"
            class="table-card overflow-hidden elevation-2"
          >
            <VTable class="booking-table">
              <thead>
                <tr class="table-header">
                  <th class="text-left font-weight-bold">
                    <VIcon icon="tabler-hash" size="18" class="me-2" />
                    Booking ID
                  </th>
                  <th class="text-center font-weight-bold">
                    <VIcon icon="tabler-chart-line" size="18" class="me-2" />
                    Tổng tiền
                  </th>
                  <th class="text-center font-weight-bold">
                    <VIcon icon="tabler-cash" size="18" class="me-2" />
                    OTA Payout
                  </th>
                  <th class="text-center font-weight-bold">
                    <VIcon icon="tabler-calculator" size="18" class="me-2" />
                    Chênh lệch
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, index) in props.selectedBookings"
                  :key="item.id"
                  :class="index % 2 === 0 ? 'bg-grey-lighten-6' : ''"
                  class="table-row hover-row"
                >
                  <td class="py-4">
                    <div class="d-flex align-center">
                      <div class="booking-id-badge me-3">
                        <VIcon
                          icon="tabler-receipt"
                          size="16"
                          color="primary"
                        />
                      </div>
                      <span class="font-weight-medium text-body-1">{{
                        item.booking_code
                      }}</span>
                    </div>
                  </td>
                  <td class="text-center py-4">
                    <VChip
                      color="primary"
                      variant="tonal"
                      size="large"
                      class="amount-chip"
                    >
                      {{ formatCurrency(item.net_estimate) }}
                    </VChip>
                  </td>
                  <td class="text-center py-4">
                    <VChip
                      color="success"
                      variant="tonal"
                      size="large"
                      class="amount-chip"
                    >
                      {{ formatCurrency(item.payout_received) }}
                    </VChip>
                  </td>
                  <td class="text-center py-4">
                    <VChip
                      :color="getBookingDifferenceColor(item)"
                      variant="tonal"
                      size="large"
                      class="amount-chip"
                    >
                      {{
                        formatCurrency(
                          Math.abs(
                            (item.payout_received || 0) -
                              (item.net_estimate || 0)
                          )
                        )
                      }}
                    </VChip>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="table-footer">
                  <td class="py-5 text-h6 font-weight-bold">
                    <VIcon icon="tabler-sum" class="me-3" size="24" />
                    Tổng cộng
                  </td>
                  <td class="text-center py-5">
                    <div
                      class="text-h6 font-weight-bold text-primary total-amount"
                    >
                      {{ formatCurrency(totalNetEstimate) }}
                    </div>
                  </td>
                  <td class="text-center py-5">
                    <div
                      class="text-h6 font-weight-bold text-success total-amount"
                    >
                      {{ formatCurrency(totalPayoutReceived) }}
                    </div>
                  </td>
                  <td class="text-center py-5">
                    <div
                      class="text-h6 font-weight-bold total-amount"
                      :class="`text-${differenceColor}`"
                    >
                      {{ formatCurrency(Math.abs(differenceAmount)) }}
                    </div>
                  </td>
                </tr>
              </tfoot>
            </VTable>
          </VCard>

          <!-- Date Input Section với styling mới -->
          <div class="mt-8">
            <VCard
              variant="outlined"
              class="pa-6 input-section-card elevation-2"
            >
              <div class="d-flex align-center mb-4">
                <div class="section-icon-wrapper me-3">
                  <VIcon icon="tabler-calendar-event" color="white" size="20" />
                </div>
                <span class="text-h5 font-weight-bold"
                  >Thông tin thanh toán</span
                >
              </div>
              <VRow>
                <VCol cols="6">
                  <AppTextField
                    v-model="expected_date"
                    type="date"
                    label="Ngày dự kiến thanh toán"
                    prepend-inner-icon="tabler-calendar"
                    required
                    variant="outlined"
                    class="date-input"
                  />
                </VCol>
                <VCol cols="6">
                  <VAlert
                    :color="differenceAmount >= 0 ? 'success' : 'error'"
                    variant="tonal"
                    class="mb-0 status-alert"
                    :class="
                      differenceAmount >= 0 ? 'success-alert' : 'error-alert'
                    "
                  >
                    <template #prepend>
                      <VIcon
                        :icon="
                          differenceAmount >= 0
                            ? 'tabler-check'
                            : 'tabler-alert-circle'
                        "
                        size="24"
                      />
                    </template>
                    <div class="d-flex align-center">
                      <span class="me-2"
                        >{{ differenceAmount >= 0 ? "Thừa" : "Thiếu" }}:</span
                      >
                      <strong class="text-h6">{{
                        formatCurrency(Math.abs(differenceAmount))
                      }}</strong>
                    </div>
                  </VAlert>
                </VCol>
              </VRow>
            </VCard>
          </div>
        </div>
      </VCardText>

      <!-- Action Buttons với styling mới -->
      <VCardActions class="pa-8 bg-grey-lighten-5 justify-end action-buttons">
        <VBtn
          variant="outlined"
          color="error"
          size="x-large"
          @click="onReset"
          prepend-icon="tabler-x"
          class="action-btn cancel-btn"
          elevation="2"
        >
          <span class="font-weight-medium">Hủy bỏ</span>
        </VBtn>
        <VBtn
          color="success"
          size="x-large"
          @click="onSubmit"
          prepend-icon="tabler-check"
          :loading="submitting"
          class="action-btn submit-btn"
          elevation="4"
        >
          <span class="font-weight-bold">Xác nhận quyết toán</span>
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  ota_name: String,
  selectedBookings: Array,
});

const emit = defineEmits(["update:isDialogVisible", "update:settlements"]);
const expected_date = ref(null);
const submitting = ref(false);

// Computeds
const totalNetEstimate = computed(() => {
  if (!props.selectedBookings?.length) return 0;
  return props.selectedBookings.reduce((sum, booking) => {
    const netEstimate = parseFloat(booking.net_estimate) || 0;
    return sum + netEstimate;
  }, 0);
});

const totalPayoutReceived = computed(() => {
  return (
    props.selectedBookings?.reduce((sum, booking) => {
      return sum + (parseFloat(booking.payout_received) || 0);
    }, 0) || 0
  );
});

const differenceAmount = computed(() => {
  return totalPayoutReceived.value - totalNetEstimate.value;
});

const differenceColor = computed(() => {
  return differenceAmount.value >= 0 ? "success" : "error";
});

const differenceIcon = computed(() => {
  return differenceAmount.value >= 0
    ? "tabler-trending-up"
    : "tabler-trending-down";
});

// Methods
const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return "0 ₫";
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(amount));
};

const getBookingDifferenceColor = (booking) => {
  const diff = (booking.payout_received || 0) - (booking.net_estimate || 0);
  return diff >= 0 ? "success" : "error";
};

const onReset = () => {
  emit("update:isDialogVisible", false);
};

const onSubmit = async () => {
  if (!expected_date.value) {
    alert("Vui lòng chọn ngày dự kiến thanh toán");
    return;
  }

  submitting.value = true;
  const selectedIds = props.selectedBookings.map((b) => b.id);

  try {
    const response = await axios.post(route("ota-receipt.storeSettlements"), {
      selected_bookings: selectedIds,
      expected_date: expected_date.value,
      total_net_estimate: totalNetEstimate.value,
      total_payout_received: totalPayoutReceived.value,
      difference_amount: differenceAmount.value,
      ota_name: props.ota_name,
    });

    if (response.data.success) {
      expected_date.value = null;
      onReset();
      emit("update:settlements");
    } else {
      alert("Không thể tạo phiếu: " + response.data.message);
    }
  } catch (error) {
    console.error("Lỗi tạo phiếu:", error);
    alert("Đã xảy ra lỗi khi tạo phiếu quyết toán.");
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
/* Dialog Card */
.settlement-dialog-card {
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

/* Header Styling */
.settlement-header {
  background: linear-gradient(135deg, #4970f5 0%, #675dd8 50%, #3b58d2 100%);
  position: relative;
  overflow: hidden;
}

.header-icon-wrapper {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 12px;
  backdrop-filter: blur(10px);
}

.header-content {
  flex: 1;
}

.header-decoration {
  position: absolute;
  right: -20px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.decoration-circle {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  animation: pulse 2s infinite;
}

.decoration-circle:nth-child(2) {
  animation-delay: 0.5s;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 0.3;
    transform: scale(1);
  }
  50% {
    opacity: 0.8;
    transform: scale(1.2);
  }
}

/* Background Gradients */
.bg-gradient-light {
  background: linear-gradient(135deg, #fff 0%, #f8f7fa 100%);
}

/* Summary Cards */
.summary-card {
  border-radius: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.summary-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.5),
    transparent
  );
  transform: translateX(-100%);
  transition: transform 0.6s ease;
}

.summary-card:hover::before {
  transform: translateX(100%);
}

.summary-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.icon-wrapper {
  background: rgba(73, 112, 245, 0.1);
  border-radius: 50%;
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  transition: all 0.3s ease;
}

.summary-card:hover .icon-wrapper {
  transform: scale(1.1);
  background: rgba(73, 112, 245, 0.15);
}

/* Section Headers */
.section-icon-wrapper {
  background: linear-gradient(135deg, #4970f5, #675dd8);
  border-radius: 8px;
  padding: 8px;
  box-shadow: 0 4px 12px rgba(73, 112, 245, 0.3);
}

/* Table Styling */
.table-card {
  border-radius: 12px;
  border: 1px solid rgb(var(--v-theme-outline-variant));
}

.table-header {
  background: linear-gradient(
    135deg,
    rgba(73, 112, 245, 0.05),
    rgba(73, 112, 245, 0.1)
  );
}

.table-header th {
  height: 60px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #4970f5;
  border-bottom: 2px solid rgba(73, 112, 245, 0.2);
}

.table-row {
  transition: all 0.3s ease;
}

.hover-row:hover {
  background-color: rgba(73, 112, 245, 0.05) !important;
}

.booking-table td {
  height: 72px;
  border-bottom: 1px solid rgb(var(--v-theme-outline-variant));
}

.booking-id-badge {
  background: rgba(73, 112, 245, 0.1);
  border-radius: 6px;
  padding: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.amount-chip {
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.amount-chip:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.table-footer {
  background: linear-gradient(
    135deg,
    rgba(73, 112, 245, 0.1),
    rgba(73, 112, 245, 0.15)
  );
  border-top: 2px solid rgba(73, 112, 245, 0.2);
}

.total-amount {
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Input Section */
.input-section-card {
  border-radius: 12px;
  border: 1px solid rgb(var(--v-theme-outline-variant));
}

.date-input {
  border-radius: 8px;
}

.status-alert {
  border-radius: 12px;
  border: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.success-alert {
  background: linear-gradient(
    135deg,
    rgba(40, 199, 111, 0.1),
    rgba(40, 199, 111, 0.15)
  );
  border-left: 4px solid #28c76f;
}

.error-alert {
  background: linear-gradient(
    135deg,
    rgba(255, 76, 81, 0.1),
    rgba(255, 76, 81, 0.15)
  );
  border-left: 4px solid #ff4c51;
}

/* Action Buttons */
.action-buttons {
  border-top: 1px solid rgb(var(--v-theme-outline-variant));
}

.action-btn {
  border-radius: 12px;
  font-weight: 600;
  text-transform: none;
  letter-spacing: 0.5px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.action-btn::before {
  content: "";
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.2),
    transparent
  );
  transition: left 0.5s ease;
}

.action-btn:hover::before {
  left: 100%;
}

.cancel-btn {
  border: 2px solid #ff4c51;
  color: #ff4c51;
  font-size: 1rem;
  min-width: 120px;
}

.cancel-btn:hover {
  background: #ff4c51;
  border-color: #ff4c51;
  color: white !important;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(255, 76, 81, 0.3);
}

.cancel-btn:active {
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(255, 76, 81, 0.3);
  color: white !important;
}

.submit-btn {
  background: linear-gradient(135deg, #28c76f, #24b364);
  box-shadow: 0 6px 20px rgba(40, 199, 111, 0.4);
  border: 2px solid #28c76f;
  font-size: 1.1rem;
  min-width: 200px;
  color: white !important;
}

.submit-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(40, 199, 111, 0.5);
  background: linear-gradient(135deg, #24b364, #1f9e5a);
  color: white !important;
}

.submit-btn:active {
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(40, 199, 111, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
  .settlement-dialog-card {
    margin: 16px;
  }

  .header-decoration {
    display: none;
  }

  .summary-card {
    margin-bottom: 16px;
  }
}

/* Animation Classes */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from {
  transform: translateY(20px);
  opacity: 0;
}

.slide-up-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}
</style>
