<template>
  <Head title="Xem thanh toán | Room Rise" />
  <Layout>
    <!-- Header Section with Subtle Background -->
    <div class="payment-header mb-6">
      <div class="d-flex align-center justify-space-between flex-wrap">
        <div class="header-content">
          <h1 class="text-h3 font-weight-bold text-primary mb-2">
            <VIcon
              icon="tabler-receipt"
              class="me-3"
              size="32"
              color="primary"
            ></VIcon>
            Chi tiết thanh toán
          </h1>
          <p class="text-medium-emphasis text-subtitle-1">
            Quản lý và theo dõi thông tin thanh toán đặt phòng
          </p>
        </div>
        <VBtn
          @click="addNewPay()"
          v-if="booking.payment_status != 'Đã thanh toán'"
          size="large"
          color="primary"
          variant="elevated"
          class="px-6"
          prepend-icon="tabler-plus"
        >
          Thanh toán mới
        </VBtn>
      </div>
    </div>

    <VRow>
      <!-- Main Content Column -->
      <VCol cols="12" sm="12" md="8" lg="8">
        <!-- Payment History Cards -->
        <div v-for="item in histories" :key="item.id" class="mb-6">
          <VCard elevation="2" class="payment-card border-radius-xl">
            <VCardTitle class="d-flex align-center mb-4 border-b pb-4">
              <div class="icon-wrapper me-3">
                <VIcon icon="tabler-coin" size="28" color="primary"></VIcon>
              </div>
              <div>
                <h3 class="text-h5 text-primary font-weight-bold mb-1">
                  Thanh toán
                </h3>
                <p class="text-medium-emphasis text-subtitle-2 mb-0">
                  Thông tin giao dịch
                </p>
              </div>
            </VCardTitle>
            <VRow class="pa-6">
              <VCol cols="12" md="6" class="mb-4">
                <div class="info-item">
                  <label class="info-label">Mã thanh toán</label>
                  <div class="info-value text-primary">
                    <VIcon
                      icon="tabler-hash"
                      size="16"
                      class="me-2"
                      color="primary"
                    ></VIcon>
                    {{ item.id || "–" }}
                  </div>
                </div>
              </VCol>
              <VCol cols="12" md="6" class="mb-4">
                <div class="info-item">
                  <label class="info-label">Số tiền</label>
                  <div class="info-value amount-value">
                    <VIcon
                      icon="tabler-currency-dollar"
                      size="16"
                      class="me-2"
                      color="success"
                    ></VIcon>
                    {{ formatCurrency(item.amount) }}
                  </div>
                </div>
              </VCol>
              <VCol cols="12" md="6" class="mb-4">
                <div class="info-item">
                  <label class="info-label">Nhân viên</label>
                  <div class="info-value">
                    <VIcon
                      icon="tabler-user"
                      size="16"
                      class="me-2"
                      color="info"
                    ></VIcon>
                    {{ item.created_by }}
                  </div>
                </div>
              </VCol>
              <VCol cols="12" md="6" class="mb-4">
                <div class="info-item">
                  <label class="info-label">Phương thức</label>
                  <div class="info-value">
                    <VIcon
                      icon="tabler-credit-card"
                      size="16"
                      class="me-2"
                      color="warning"
                    ></VIcon>
                    {{ item.payment_method || "–" }}
                  </div>
                </div>
              </VCol>
              <VCol cols="12" md="6" class="mb-4">
                <div class="info-item">
                  <label class="info-label">Ngày thanh toán</label>
                  <div class="info-value">
                    <VIcon
                      icon="tabler-calendar"
                      size="16"
                      class="me-2"
                      color="secondary"
                    ></VIcon>
                    {{ formatDate(item.date) || "–" }}
                  </div>
                </div>
              </VCol>
            </VRow>
          </VCard>
        </div>

        <!-- Partner Payment History Cards -->
        <div
          v-for="item in paymentHistoriesPartner"
          :key="item.id"
          class="mb-6"
        >
          <VCard elevation="2" class="payment-card border-radius-xl">
            <VCardTitle class="d-flex align-center mb-4 border-b pb-4">
              <div class="icon-wrapper me-3">
                <VIcon
                  icon="tabler-handshake"
                  size="28"
                  color="warning"
                ></VIcon>
              </div>
              <div>
                <h3 class="text-h5 text-warning font-weight-bold mb-1">
                  Công nợ đối tác
                </h3>
                <p class="text-medium-emphasis text-subtitle-2 mb-0">
                  Giao dịch với đối tác
                </p>
              </div>
            </VCardTitle>
            <VRow class="pa-6">
              <VCol cols="12" md="6" class="mb-4">
                <div class="info-item">
                  <label class="info-label">Mã thanh toán</label>
                  <div class="info-value text-primary">
                    <VIcon
                      icon="tabler-hash"
                      size="16"
                      class="me-2"
                      color="primary"
                    ></VIcon>
                    {{ item.id || "–" }}
                  </div>
                </div>
              </VCol>
              <VCol cols="6" class="mb-4">
                <div class="info-item">
                  <label class="info-label">Số tiền</label>
                  <div class="info-value amount-value">
                    <VIcon
                      icon="tabler-currency-dollar"
                      size="16"
                      class="me-2"
                      color="success"
                    ></VIcon>
                    {{ formatCurrency(item.pivot.amount) }}
                  </div>
                </div>
              </VCol>
              <VCol cols="6" class="mb-4">
                <div class="info-item">
                  <label class="info-label">Nhân viên</label>
                  <div class="info-value">
                    <VIcon
                      icon="tabler-user"
                      size="16"
                      class="me-2"
                      color="info"
                    ></VIcon>
                    {{ item.created_by }}
                  </div>
                </div>
              </VCol>
              <VCol cols="6" class="mb-4">
                <div class="info-item">
                  <label class="info-label">Phương thức</label>
                  <div class="info-value">
                    <VIcon
                      icon="tabler-credit-card"
                      size="16"
                      class="me-2"
                      color="warning"
                    ></VIcon>
                    {{ item.payment_method || "–" }}
                  </div>
                </div>
              </VCol>
              <VCol cols="6" class="mb-4">
                <div class="info-item">
                  <label class="info-label">Ngày thanh toán</label>
                  <div class="info-value">
                    <VIcon
                      icon="tabler-calendar"
                      size="16"
                      class="me-2"
                      color="secondary"
                    ></VIcon>
                    {{ formatDate(item.date) || "–" }}
                  </div>
                </div>
              </VCol>
            </VRow>
          </VCard>
        </div>

        <!-- Booking Information Card -->
        <VCard elevation="2" class="payment-card border-radius-xl">
          <VCardTitle class="d-flex align-center mb-4 border-b pb-4">
            <div class="icon-wrapper me-3">
              <VIcon icon="tabler-book" size="28" color="info"></VIcon>
            </div>
            <div>
              <h3 class="text-h5 text-info font-weight-bold mb-1">
                Thông tin đặt phòng
              </h3>
              <p class="text-medium-emphasis text-subtitle-2 mb-0">
                Chi tiết booking
              </p>
            </div>
          </VCardTitle>
          <VRow class="pa-6">
            <VCol cols="12" md="6" class="mb-4">
              <div class="info-item">
                <label class="info-label">Trạng thái</label>
                <div class="info-value">
                  <VChip
                    :color="getStatusColor(booking.status)"
                    size="small"
                    class="font-weight-medium"
                  >
                    {{ booking.status || "–" }}
                  </VChip>
                </div>
              </div>
            </VCol>
            <VCol cols="12" md="6" class="mb-4">
              <div class="info-item">
                <label class="info-label">Mã đặt phòng</label>
                <div class="info-value text-primary">
                  <VIcon
                    icon="tabler-hash"
                    size="16"
                    class="me-2"
                    color="primary"
                  ></VIcon>
                  {{ booking.id || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" md="6" class="mb-4">
              <div class="info-item">
                <label class="info-label">Tổng số tiền</label>
                <div class="info-value amount-value">
                  <VIcon
                    icon="tabler-currency-dollar"
                    size="16"
                    class="me-2"
                    color="success"
                  ></VIcon>
                  {{ formatCurrency(booking.total_amount) }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" md="6" class="mb-4">
              <div class="info-item">
                <label class="info-label">Đã thanh toán</label>
                <div class="info-value amount-value">
                  <VIcon
                    icon="tabler-currency-dollar"
                    size="16"
                    class="me-2"
                    color="success"
                  ></VIcon>
                  {{ formatCurrency(booking.paid) }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" md="6" class="mb-4">
              <div class="info-item">
                <label class="info-label">Còn lại</label>
                <div class="info-value amount-value">
                  <VIcon
                    icon="tabler-currency-dollar"
                    size="16"
                    class="me-2"
                    color="success"
                  ></VIcon>
                  {{ formatCurrency(booking.remaining) }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" md="6" class="mb-4">
              <div class="info-item">
                <label class="info-label">Trạng thái thanh toán</label>
                <div class="info-value">
                  <VChip
                    :color="getPaymentColor(booking.payment_status)"
                    size="small"
                    class="font-weight-medium"
                    v-if="booking.payment_status"
                  >
                    {{ booking.payment_status }}
                  </VChip>
                </div>
              </div>
            </VCol>
            <VCol cols="12" md="6" class="mb-4">
              <div class="info-item">
                <label class="info-label">Nguồn</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-source"
                    size="16"
                    class="me-2"
                    color="secondary"
                  ></VIcon>
                  {{ booking.ota_name ?? "-" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" md="6" class="mb-4">
              <div class="info-item">
                <label class="info-label">Đối tác</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-source"
                    size="16"
                    class="me-2"
                    color="secondary"
                  ></VIcon>
                  {{ booking?.customer?.partner?.name ?? "-" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" md="6" class="mb-4">
              <div class="info-item">
                <label class="info-label">Nhận phòng</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-calendar-time"
                    size="16"
                    class="me-2"
                    color="info"
                  ></VIcon>
                  {{ formatDate(booking.check_in_date) || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" md="6" class="mb-4">
              <div class="info-item">
                <label class="info-label">Trả phòng</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-calendar-time"
                    size="16"
                    class="me-2"
                    color="info"
                  ></VIcon>
                  {{ formatDate(booking.check_out_date) || "–" }}
                </div>
              </div>
            </VCol>
          </VRow>
        </VCard>
      </VCol>

      <!-- Sidebar Column -->
      <VCol cols="12" sm="12" md="4" lg="4">
        <!-- Customer Information Card -->
        <VCard
          elevation="2"
          class="payment-card border-radius-xl sticky-sidebar"
        >
          <VCardTitle class="d-flex align-center mb-4 border-b pb-4">
            <div class="icon-wrapper me-3">
              <VIcon icon="tabler-users" size="28" color="success"></VIcon>
            </div>
            <div>
              <h3 class="text-h5 text-success font-weight-bold mb-1">
                Thông tin khách hàng
              </h3>
              <p class="text-medium-emphasis text-subtitle-2 mb-0">
                Chi tiết khách hàng
              </p>
            </div>
          </VCardTitle>
          <VRow class="pa-6">
            <VCol cols="12" class="mb-4">
              <div class="info-item">
                <label class="info-label">Tên đầy đủ</label>
                <div class="info-value text-primary">
                  <VIcon
                    icon="tabler-user"
                    size="16"
                    class="me-2"
                    color="primary"
                  ></VIcon>
                  {{ user.full_name || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-4">
              <div class="info-item">
                <label class="info-label">Số điện thoại</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-phone"
                    size="16"
                    class="me-2"
                    color="info"
                  ></VIcon>
                  {{ user.phone || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-4">
              <div class="info-item">
                <label class="info-label">Email</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-mail"
                    size="16"
                    class="me-2"
                    color="secondary"
                  ></VIcon>
                  {{ user.email || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-4">
              <div class="info-item">
                <label class="info-label">Quốc gia</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-flag"
                    size="16"
                    class="me-2"
                    color="warning"
                  ></VIcon>
                  {{ user.country || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-4">
              <div class="info-item">
                <label class="info-label">Loại giấy tờ</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-id"
                    size="16"
                    class="me-2"
                    color="info"
                  ></VIcon>
                  {{ user.id_type || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-4">
              <div class="info-item">
                <label class="info-label">Số định danh</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-hash"
                    size="16"
                    class="me-2"
                    color="primary"
                  ></VIcon>
                  {{ user.id_number || "–" }}
                </div>
              </div>
            </VCol>
          </VRow>
        </VCard>
      </VCol>
    </VRow>

    <PayDialog
      v-model:is-pay-dialog-visible="isFormPayDialogVisible"
      :payment-TargetId="booking.id"
      @update:payment="loadData"
    />
  </Layout>
</template>

<script setup>
import Layout from "@/layouts/blank.vue";
import { Head, router, Link } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { ref, watch } from "vue";
import PayDialog from "./PayDialog.vue";
import dayjs from "dayjs";
import { formatCurrency, formatDate } from "@/utils/formatters";
const props = defineProps({
  booking: Object,
  user: Object,
  histories: Object,
  paymentHistoriesPartner: Object,
});

const isFormPayDialogVisible = ref(false);

const addNewPay = () => {
  isFormPayDialogVisible.value = true;
};

function getPaymentColor(status) {
  switch (status) {
    case "Đã cọc":
      return "warning";
    case "Đã thanh toán":
      return "success";
    case "Chưa thanh toán":
      return "error";
    default:
      return "grey";
  }
}

function getStatusColor(status) {
  switch (status) {
    case "Đã xác nhận":
      return "success";
    case "Đang chờ":
      return "warning";
    case "Đã hủy":
    case "Hủy":
      return "error";
    case "Đã hoàn thành":
      return "primary";
    default:
      return "secondary";
  }
}

function loadData() {
  // Reload data after payment
  //window.location.reload();
}
</script>

<style scoped>
.payment-header {
  background: #f8f9fa;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
  border: 1px solid #e9ecef;
}

.payment-card {
  transition: all 0.3s ease;
  border: 1px solid #e9ecef;
  overflow: hidden;
}

.payment-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
}

.icon-wrapper {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 12px;
  border: 1px solid #e9ecef;
}

.info-item {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #e9ecef;
  transition: all 0.2s ease;
}

.info-item:hover {
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.info-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: #6c757d;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  display: flex;
  align-items: center;
  font-size: 1rem;
  font-weight: 500;
  color: #212529;
  margin: 0;
}

.amount-value {
  font-weight: 700;
  font-size: 1.1rem;
  color: #198754 !important;
}

.text-primary {
  color: #0d6efd !important;
  font-weight: 600;
}

.sticky-sidebar {
  position: sticky;
  top: 2rem;
}

.border-radius-xl {
  border-radius: 20px !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .payment-header {
    padding: 1.5rem;
  }

  .info-item {
    padding: 12px;
  }

  .sticky-sidebar {
    position: static;
  }
}

/* Animation for cards */
.payment-card {
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Custom chip styles */
.v-chip {
  font-weight: 600;
  letter-spacing: 0.5px;
}
</style>
