<template>
  <VCard>
    <VCardItem>
      <h3>Danh sách phiếu quyết toán</h3>
      <VDataTable
        :headers="headers"
        :items="settlements"
        item-value="id"
        return-object
        :items-per-page="-1"
        class="text-no-wrap"
        hide-default-footer
      >
        <template #item.created_at="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            <div>
              {{ formatDate(item.created_at) }}
            </div>
          </div>
        </template>
        <template #item.total_net_estimate="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            <div>
              {{ formatCurrency(item.total_net_estimate) }}
            </div>
          </div>
        </template>
        <template #item.total_payout="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3 text-success">
            <div>
              {{ formatCurrency(item.total_payout) }}
            </div>
          </div>
        </template>
        <template #item.total_difference="{ item }">
          <div class="text-high-emphasis text-body-1 text-error pa-3">
            <div>
              {{ formatCurrency(item.total_difference) }}
            </div>
          </div>
        </template>
        <template #item.status="{ item }">
          <VChip color="warning" v-if="item.status == 'Chờ thanh toán'">
            Chờ thanh toán
          </VChip>
          <VChip color="success" v-if="item.status == 'Đã quyết toán'">
            Đã quyết toán
          </VChip>
        </template>
        <template #item.settlement_date="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            <div>
              {{ formatDate(item.settlement_date) }}
            </div>
          </div>
        </template>
        <template #item.actions="{ item }">
          <VIcon @click="viewItem(item)" icon="tabler-eye" />
        </template>
      </VDataTable>
    </VCardItem>
  </VCard>
  <VDialog v-model="isDialogVisible" width="1000" persistent>
    <DialogCloseBtn @click="closeDialog" />

    <VCard>
      <!-- Header với gradient -->
      <VCardTitle
        class="d-flex align-center pa-6 bg-gradient-primary text-white"
      >
        <VIcon icon="tabler-file-invoice" size="24" class="me-3" />
        <div>
          <div class="text-h6 font-weight-bold">
            {{ selectedSettlements?.code }}
          </div>
          <div class="text-body-2 opacity-90">
            Chi tiết phiếu quyết toán -
            {{ selectedSettlements?.total_booking }} booking
          </div>
        </div>
        <VSpacer />
        <VChip
          :color="getStatusColor(selectedSettlements?.status)"
          variant="elevated"
          class="text-white"
          prepend-icon="tabler-clock"
        >
          {{ selectedSettlements?.status }}
        </VChip>
      </VCardTitle>

      <!-- Summary Section -->
      <div class="pa-6 bg-grey-lighten-5">
        <VRow>
          <VCol cols="3">
            <VCard color="primary" variant="tonal" class="text-center">
              <VCardText class="py-4">
                <VIcon
                  icon="tabler-coins"
                  size="32"
                  class="mb-2"
                  color="primary"
                />
                <div class="text-h6 font-weight-bold text-primary">
                  {{ formatCurrency(selectedSettlements?.total_net_estimate) }}
                </div>
                <div class="text-body-2">Tổng dự kiến</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="3">
            <VCard color="success" variant="tonal" class="text-center">
              <VCardText class="py-4">
                <VIcon
                  icon="tabler-cash"
                  size="32"
                  class="mb-2"
                  color="success"
                />
                <div class="text-h6 font-weight-bold text-success">
                  {{ formatCurrency(selectedSettlements?.total_payout) }}
                </div>
                <div class="text-body-2">Tổng nhận được</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="3">
            <VCard
              :color="getDifferenceColor(selectedSettlements?.total_difference)"
              variant="tonal"
              class="text-center"
            >
              <VCardText class="py-4">
                <VIcon
                  :icon="
                    getDifferenceIcon(selectedSettlements?.total_difference)
                  "
                  size="32"
                  class="mb-2"
                  :color="
                    getDifferenceColor(selectedSettlements?.total_difference)
                  "
                />
                <div
                  class="text-h6 font-weight-bold"
                  :class="`text-${getDifferenceColor(
                    selectedSettlements?.total_difference
                  )}`"
                >
                  {{
                    formatCurrency(
                      Math.abs(selectedSettlements?.total_difference || 0)
                    )
                  }}
                </div>
                <div class="text-body-2">Chênh lệch</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="3">
            <VCard color="info" variant="tonal" class="text-center">
              <VCardText class="py-4">
                <VIcon
                  icon="tabler-calendar-event"
                  size="32"
                  class="mb-2"
                  color="info"
                />
                <div class="text-h6 font-weight-bold text-info">
                  {{ formatDate(selectedSettlements?.settlement_date) }}
                </div>
                <div class="text-body-2">Ngày dự kiến</div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </div>

      <!-- Content -->
      <VCardText class="pa-6">
        <!-- Booking Details Table -->
        <div class="mb-6">
          <div class="d-flex align-center mb-4">
            <VIcon icon="tabler-list-details" class="me-2" color="primary" />
            <span class="text-h6">Chi tiết booking</span>
          </div>

          <VCard variant="outlined" class="overflow-hidden">
            <VDataTable
              v-if="selectedSettlements?.bookings"
              :headers="bookingHeaders"
              :items="selectedSettlements.bookings"
              item-value="id"
              :items-per-page="-1"
              class="booking-detail-table"
              hide-default-footer
            >
              <template #item.booking_code="{ item, index }">
                <div class="d-flex align-center py-2">
                  <span class="font-weight-medium">{{
                    item.booking_code
                  }}</span>
                </div>
              </template>

              <template #item.net_estimate="{ item }">
                <VChip color="primary" variant="tonal" size="small">
                  {{ formatCurrency(item.net_estimate) }}
                </VChip>
              </template>

              <template #item.payout_received="{ item }">
                <VChip color="success" variant="tonal" size="small">
                  {{ formatCurrency(item.payout_received) }}
                </VChip>
              </template>

              <template #item.difference_amount="{ item }">
                <VChip
                  :color="item.difference_amount >= 0 ? 'success' : 'error'"
                  variant="tonal"
                  size="small"
                >
                  {{ formatCurrency(Math.abs(item.difference_amount)) }}
                </VChip>
              </template>

              <template #item.reconciliation_status="{ item }">
                <VChip
                  :color="getReconciliationColor(item.reconciliation_status)"
                  :prepend-icon="
                    getReconciliationIcon(item.reconciliation_status)
                  "
                  variant="flat"
                  size="small"
                >
                  {{ item.reconciliation_status }}
                </VChip>
              </template>

              <!-- Thay thế #bottom bằng tfoot -->
              <template #tfoot>
                <tfoot class="bg-grey-lighten-4">
                  <tr>
                    <td class="text-subtitle-1 font-weight-bold pa-4">
                      <div class="d-flex align-center">
                        <VIcon icon="tabler-sum" class="me-2" />
                        Tổng cộng
                      </div>
                    </td>
                    <td class="">
                      <VChip color="primary" variant="flat" size="small">
                        {{
                          formatCurrency(
                            selectedSettlements?.total_net_estimate
                          )
                        }}
                      </VChip>
                    </td>
                    <td class="">
                      <VChip color="success" variant="flat" size="small">
                        {{ formatCurrency(selectedSettlements?.total_payout) }}
                      </VChip>
                    </td>
                    <td class="">
                      <VChip
                        :color="
                          getDifferenceColor(
                            selectedSettlements?.total_difference
                          )
                        "
                        variant="flat"
                        size="small"
                      >
                        {{
                          formatCurrency(
                            Math.abs(selectedSettlements?.total_difference || 0)
                          )
                        }}
                      </VChip>
                    </td>
                    <td class="">
                      <VChip
                        :color="
                          selectedSettlements?.reconciliation_status === 'Khớp'
                            ? 'success'
                            : 'warning'
                        "
                        variant="flat"
                        size="small"
                      >
                        {{
                          selectedSettlements?.reconciliation_status ||
                          "Tổng hợp"
                        }}
                      </VChip>
                    </td>
                  </tr>
                </tfoot>
              </template>
            </VDataTable>
          </VCard>
        </div>

        <!-- Payment Confirmation Form -->
        <template v-if="selectedSettlements?.status !== 'Đã quyết toán'">
          <div class="mb-4">
            <div class="d-flex align-center mb-3">
              <VIcon icon="tabler-cash" class="me-2" color="primary" />
              <span class="text-h6">Xác nhận thanh toán</span>
            </div>

            <VAlert
              :color="getDifferenceColor(selectedSettlements?.total_difference)"
              variant="tonal"
              class="mb-4"
            >
              <template #prepend>
                <VIcon
                  :icon="
                    getDifferenceIcon(selectedSettlements?.total_difference)
                  "
                />
              </template>
              <div class="d-flex justify-space-between align-center">
                <span>
                  {{
                    selectedSettlements?.total_difference >= 0
                      ? "Thừa"
                      : "Thiếu"
                  }}:
                  <strong>{{
                    formatCurrency(
                      Math.abs(selectedSettlements?.total_difference || 0)
                    )
                  }}</strong>
                </span>
                <VChip
                  :color="
                    selectedSettlements?.reconciliation_status === 'Khớp'
                      ? 'success'
                      : 'warning'
                  "
                  size="small"
                  variant="flat"
                >
                  {{
                    selectedSettlements?.reconciliation_status || "Chờ xử lý"
                  }}
                </VChip>
              </div>
            </VAlert>

            <VCard variant="outlined" class="pa-4">
              <VRow class="align-end">
                <VCol cols="6">
                  <AppTextField
                    v-model="total_payout"
                    label="Tổng số tiền nhận"
                    type="number"
                    prepend-inner-icon="tabler-currency-dong"
                    variant="outlined"
                    required
                  />
                </VCol>
                <VCol cols="6">
                  <AppSelect
                    v-model="payment_method"
                    label="Hình thức thanh toán"
                    :items="paymentOptions"
                    prepend-inner-icon="tabler-credit-card"
                    variant="outlined"
                    required
                  />
                </VCol>
              </VRow>
            </VCard>
          </div>
        </template>

        <!-- Status Information for Completed -->
        <template v-else>
          <VCard color="success" variant="tonal" class="pa-4">
            <div class="d-flex align-center">
              <VIcon
                icon="tabler-check-circle"
                size="32"
                class="me-3"
                color="success"
              />
              <div>
                <div class="text-h6 font-weight-bold text-success">
                  Đã hoàn thành quyết toán
                </div>
                <div class="text-body-2">
                  Phiếu này đã được xác nhận thanh toán vào
                  {{ formatDate(selectedSettlements?.updated_at) }}
                </div>
              </div>
            </div>
          </VCard>
        </template>
      </VCardText>

      <!-- Actions -->
      <VCardActions class="pa-6 bg-grey-lighten-5 justify-end">
        <VBtn
          variant="outlined"
          color="secondary"
          size="large"
          @click="closeDialog"
          prepend-icon="tabler-x"
        >
          Đóng
        </VBtn>
        <VBtn
          v-if="selectedSettlements?.status !== 'Đã quyết toán'"
          color="success"
          size="large"
          class="submit-btn"
          @click="submit(selectedSettlements.id)"
          prepend-icon="tabler-check"
          :loading="submitting"
          :disabled="!total_payout || !payment_method"
        >
          Xác nhận thanh toán
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
<script setup>
import { ref, toRefs, watch } from "vue";
import { formatCurrency, formatDate } from "@/utils/formatters";
const props = defineProps({
  currentTabSecond: Number,
});

const selectedSettlements = ref(null);
const isDialogVisible = ref(false);
const settlements = ref([]);
const { currentTabSecond } = toRefs(props);

const bookingHeaders = [
  { title: "Booking ID", key: "booking_code" },
  { title: "Tổng dự kiến", key: "net_estimate" },
  { title: "Tổng nhận được", key: "payout_received" },
  { title: "Chênh lệch", key: "difference_amount" },
  { title: "Trạng thái", key: "reconciliation_status" },
];

const headers = [
  { title: "Mã phiếu", key: "code", sortable: false },
  { title: "Ngày quyết toán", key: "created_at", sortable: false },
  { title: "Số booking", key: "total_booking", sortable: false },
  { title: "Tổng dự kiến", key: "total_net_estimate", sortable: false },
  { title: "Tổng nhận được", key: "total_payout", sortable: false },
  { title: "Chênh lệch", key: "total_difference", sortable: false },
  { title: "Trạng thái", key: "status", sortable: false },
  { title: "Ngày dự kiến", key: "settlement_date", sortable: false },
  { title: "Chi tiết", key: "actions", sortable: false },
];
const total_payout = ref(null);
const payment_method = ref("");

const onUpdatePayout = async (item) => {
  try {
    const response = await axios.put(route("ota-receipt.updatePayout"), {
      id: item.id,
      payout_received: item.payout_received,
    });

    await loadSettlements();

    if (selectedSettlements.value?.id) {
      const updated = settlements.value.find(
        (s) => s.id === selectedSettlements.value.id
      );
      if (updated) selectedSettlements.value = updated;
    }
  } catch (error) {
    console.error("❌ Lỗi khi cập nhật payout:", error);
  }
};

const loadSettlements = async () => {
  try {
    const response = await axios.get(route("ota-receipt.loadSettlements"));
    settlements.value = response.data;
  } catch (error) {
    console.error("Lỗi tải danh sách phiếu quyết toán:", error);
  }
};

const paymentOptions = [
  "Tiền mặt",
  "Chuyển khoản",
  "QR Code",
  "9Pay",
  "Momo",
  "VNPay",
  "Thẻ tín dụng",
];

onMounted(() => {
  if (currentTabSecond.value === 1) {
    loadSettlements();
  }
});

watch(currentTabSecond, (val) => {
  if (val === 1) {
    loadSettlements();
  }
});
const submitting = ref(false);

// Methods
const closeDialog = () => {
  isDialogVisible.value = false;
  selectedSettlements.value = null;
  total_payout.value = 0;
  payment_method.value = "";
};

const getStatusColor = (status) => {
  const colors = {
    "Chờ thanh toán": "warning",
    "Đã quyết toán": "success",
    Hủy: "error",
  };
  return colors[status] || "secondary";
};

const getDifferenceColor = (amount) => {
  if (!amount) return "secondary";
  return amount >= 0 ? "success" : "error";
};

const getDifferenceIcon = (amount) => {
  if (!amount) return "tabler-minus";
  return amount >= 0 ? "tabler-trending-up" : "tabler-trending-down";
};

const getReconciliationColor = (status) => {
  const colors = {
    Khớp: "success",
    Lệch: "warning",
    "Chờ Payout": "secondary",
  };
  return colors[status] || "secondary";
};

const getReconciliationIcon = (status) => {
  const icons = {
    Khớp: "tabler-check",
    Lệch: "tabler-alert-triangle",
    "Chờ Payout": "tabler-clock",
  };
  return icons[status] || "tabler-circle";
};

// Update viewItem method
const viewItem = (item) => {
  selectedSettlements.value = item;
  payment_method.value = "";
  isDialogVisible.value = true;
};

// Update submit method
const submit = async (id) => {
  if (!total_payout.value || !payment_method.value) {
    alert("Vui lòng nhập đầy đủ thông tin thanh toán");
    return;
  }

  submitting.value = true;
  try {
    const response = await axios.post(
      route("ota-receipt.confirmSettlements", {
        id: id,
        total_payout: total_payout.value,
        payment_method: payment_method.value,
      })
    );
    closeDialog();
    loadSettlements();
  } catch (error) {
    console.error("Xác nhận quyết toán thất bại:", error);
    alert("Có lỗi xảy ra khi xác nhận thanh toán");
  } finally {
    submitting.value = false;
  }
};
</script>
<style scoped>
.submit-btn {
  background: linear-gradient(135deg, #28c76f, #24b364);
  box-shadow: 0 6px 20px rgba(40, 199, 111, 0.4);
  border: 2px solid #28c76f;
  font-size: 1.1rem;
  min-width: 200px;
  color: white !important;
}
</style>
