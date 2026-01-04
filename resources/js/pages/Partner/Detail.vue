<template>
  <Head title="Chi tiết đối tác | Room Rise" />
  <Layout>
    <!-- Header Section with Subtle Background -->
    <div class="payment-header mb-6">
      <div class="d-flex align-center justify-space-between flex-wrap">
        <div class="header-content">
          <h1 class="text-h3 font-weight-bold text-primary mb-2">
            <VIcon
              icon="tabler-handshake"
              class="me-3"
              size="32"
              color="primary"
            ></VIcon>
            Chi tiết đối tác
          </h1>
          <p class="text-medium-emphasis text-subtitle-1">
            Quản lý và theo dõi thông tin đối tác và các booking liên quan
          </p>
        </div>
      </div>
    </div>

    <VRow>
      <!-- Main Column -->
      <VCol cols="12" sm="12" md="8" lg="8">
        <!-- Bookings Accordion -->
        <VCard elevation="2" class="payment-card border-radius-xl mb-6">
          <VCardTitle class="d-flex align-center mb-3 border-b pb-4">
            <div class="icon-wrapper me-3">
              <VIcon
                icon="tabler-calendar-event"
                size="28"
                color="primary"
              ></VIcon>
            </div>
            <div>
              <h3 class="text-h5 text-primary font-weight-bold mb-1">
                Lịch sử đặt phòng
              </h3>
              <p class="text-medium-emphasis text-subtitle-2 mb-0">
                {{ bookings?.length || 0 }} đặt phòng
              </p>
            </div>
          </VCardTitle>

          <VCardText class="pa-6">
            <VExpansionPanels
              v-if="bookings && bookings.length > 0"
              variant="accordion"
              class="booking-accordion"
            >
              <VExpansionPanel
                v-for="booking in bookings"
                :key="booking.id"
                class="booking-panel"
              >
                <VExpansionPanelTitle class="booking-panel-title">
                  <div class="d-flex align-center justify-space-between w-100">
                    <div class="d-flex align-center">
                      <VIcon
                        icon="tabler-bed"
                        size="20"
                        color="primary"
                        class="me-3"
                      ></VIcon>
                      <div>
                        <h4 class="text-h6 font-weight-bold mb-1">
                          Booking #{{ booking.id }}
                        </h4>
                        <p class="text-caption text-medium-emphasis mb-0">
                          {{ formatDate(booking.created_at) }}
                        </p>
                      </div>
                    </div>
                    <div class="text-end">
                      <VChip
                        :color="getBookingStatusColor(booking.status)"
                        size="small"
                        variant="elevated"
                      >
                        {{ booking.status }}
                      </VChip>
                    </div>
                  </div>
                </VExpansionPanelTitle>

                <VExpansionPanelText class="booking-panel-content">
                  <VRow>
                    <!-- Booking Information -->
                    <VCol cols="12" md="6">
                      <VCard
                        elevation="1"
                        class="booking-info-card h-100"
                        color="primary"
                        variant="tonal"
                      >
                        <VCardTitle class="d-flex align-center mb-3">
                          <VIcon
                            icon="tabler-info-circle"
                            size="20"
                            class="me-2"
                          ></VIcon>
                          Thông tin đặt phòng
                        </VCardTitle>
                        <VCardText>
                          <div class="booking-details">
                            <div class="detail-item mb-3">
                              <label class="detail-label">Ngày tạo:</label>
                              <span class="detail-value">{{
                                formatDate(booking.created_at)
                              }}</span>
                            </div>
                            <div class="detail-item mb-3">
                              <label class="detail-label">Mã booking:</label>
                              <span class="detail-value">
                                {{ booking.booking_code || booking.id }}
                              </span>
                            </div>
                            <div class="detail-item mb-3">
                              <label class="detail-label">Property:</label>
                              <span class="detail-value">
                                {{ booking.property?.name || "–" }}
                              </span>
                            </div>
                            <div class="detail-item mb-3">
                              <label class="detail-label">Nguồn:</label>
                              <span class="detail-value">
                                {{ booking.ota_name || "–" }}
                              </span>
                            </div>
                            <div class="detail-item mb-3">
                              <label class="detail-label">Check-in:</label>
                              <span class="detail-value">{{
                                booking.check_in_date
                                  ? formatDate(booking.check_in_date)
                                  : "Chưa có thông tin"
                              }}</span>
                            </div>
                            <div class="detail-item mb-3">
                              <label class="detail-label">Check-out:</label>
                              <span class="detail-value">{{
                                booking.check_out_date
                                  ? formatDate(booking.check_out_date)
                                  : "Chưa có thông tin"
                              }}</span>
                            </div>
                            <div class="detail-item mb-3">
                              <label class="detail-label">Phòng:</label>
                              <span class="detail-value">
                                <VChip
                                  v-for="room in booking.booking_rooms"
                                  :key="room.id"
                                  size="small"
                                  color="info"
                                  class="me-1 mb-1"
                                >
                                  {{ room.room?.name }} -
                                  {{ room.room_unit?.name }}
                                </VChip>
                              </span>
                            </div>
                            <div class="detail-item mb-3">
                              <label class="detail-label"
                                >Loại thanh toán:</label
                              >
                              <span class="detail-value">
                                <VChip
                                  :color="
                                    getPaymentTypeColor(booking.payment_type)
                                  "
                                  size="small"
                                  variant="tonal"
                                >
                                  {{ booking.payment_type || "–" }}
                                </VChip>
                              </span>
                            </div>
                            <div class="detail-item mb-3">
                              <label class="detail-label"
                                >Trạng thái thanh toán:</label
                              >
                              <span class="detail-value">
                                <VChip
                                  :color="
                                    getPaymentStatusColor(
                                      booking.payment_status
                                    )
                                  "
                                  size="small"
                                  variant="tonal"
                                >
                                  {{ booking.payment_status || "–" }}
                                </VChip>
                              </span>
                            </div>
                            <div
                              class="detail-item"
                              v-if="booking.total_amount"
                            >
                              <label class="detail-label">Tổng tiền:</label>
                              <span class="detail-value amount-highlight">
                                {{ formatCurrency(booking.total_amount) }}
                              </span>
                            </div>
                            <div
                              class="detail-item"
                              v-if="booking.commission_fee"
                            >
                              <label class="detail-label">Hoa hồng:</label>
                              <span class="detail-value amount-highlight">
                                <VIcon
                                  icon="tabler-percentage"
                                  size="14"
                                  class="me-1"
                                  color="success"
                                ></VIcon>
                                {{ formatCurrency(booking.commission_fee) }}
                              </span>
                            </div>
                          </div>
                        </VCardText>
                      </VCard>
                    </VCol>

                    <!-- Payment Information -->
                    <VCol cols="12" md="6">
                      <VCard
                        elevation="1"
                        class="payment-info-card h-100"
                        color="success"
                        variant="tonal"
                      >
                        <VCardTitle class="d-flex align-center mb-3">
                          <VIcon
                            icon="tabler-handshake"
                            size="20"
                            class="me-2"
                          ></VIcon>
                          Đối soát đối tác
                          <VSpacer></VSpacer>
                          <VChip
                            size="small"
                            color="warning"
                            variant="elevated"
                          >
                            {{ booking.partner_income_expenses?.length || 0 }}
                            giao dịch
                          </VChip>
                        </VCardTitle>
                        <VCardText>
                          <div
                            v-if="
                              booking.partner_income_expenses &&
                              booking.partner_income_expenses.length > 0
                            "
                            class="payment-list"
                          >
                            <div
                              v-for="payment in booking.partner_income_expenses"
                              :key="payment.id"
                              class="payment-item mb-3 p-3"
                            >
                              <div
                                class="d-flex align-center justify-space-between"
                              >
                                <div class="d-flex align-center">
                                  <VIcon
                                    :icon="
                                      payment.type === 'income'
                                        ? 'tabler-trending-up'
                                        : 'tabler-trending-down'
                                    "
                                    :color="
                                      payment.type === 'income'
                                        ? 'success'
                                        : 'error'
                                    "
                                    size="16"
                                    class="me-2"
                                  ></VIcon>
                                  <div>
                                    <p
                                      class="text-body-2 font-weight-medium mb-0"
                                    >
                                      {{
                                        payment.business_type ||
                                        "Giao dịch đối tác"
                                      }}
                                    </p>
                                    <p
                                      class="text-caption text-medium-emphasis mb-0"
                                    >
                                      Mã: {{ payment.source_business_code }} •
                                      {{ formatDate(payment.created_at) }}
                                    </p>
                                    <p
                                      class="text-caption text-medium-emphasis mb-0"
                                    >
                                      PT: {{ payment.payment_method }} •
                                      {{ payment.payment_source }}
                                    </p>
                                  </div>
                                </div>
                                <div class="text-end">
                                  <p
                                    class="text-body-1 font-weight-bold mb-0"
                                    :class="{
                                      'text-success': payment.type === 'income',
                                      'text-error': payment.type === 'expense',
                                    }"
                                  >
                                    {{ payment.type === "income" ? "+" : "-"
                                    }}{{ formatCurrency(payment.amount) }}
                                  </p>
                                  <VChip
                                    size="x-small"
                                    :color="
                                      payment.payment_status === 'Đã thanh toán'
                                        ? 'success'
                                        : 'warning'
                                    "
                                    variant="tonal"
                                  >
                                    {{ payment.payment_status }}
                                  </VChip>
                                  <div
                                    class="text-caption text-medium-emphasis mt-1"
                                  >
                                    Booking:
                                    {{
                                      formatCurrency(payment.pivot?.amount || 0)
                                    }}
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div v-else class="text-center py-4">
                            <VIcon
                              icon="tabler-receipt-off"
                              size="48"
                              color="medium-emphasis"
                              class="mb-2"
                            ></VIcon>
                            <p class="text-medium-emphasis">
                              Chưa có giao dịch đối soát
                            </p>
                          </div>
                        </VCardText>
                      </VCard>
                    </VCol>
                  </VRow>
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>

            <!-- No bookings state -->
            <div v-else class="text-center py-8">
              <VIcon
                icon="tabler-calendar-off"
                size="64"
                color="medium-emphasis"
                class="mb-3"
              ></VIcon>
              <h3 class="text-h5 text-medium-emphasis mb-2">
                Chưa có đặt phòng
              </h3>
              <p class="text-body-1 text-medium-emphasis">
                Đối tác này chưa có lịch sử đặt phòng nào.
              </p>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Sidebar Column -->
      <VCol cols="12" sm="12" md="4" lg="4">
        <!-- Customer Information Card -->
        <VCard
          elevation="2"
          class="payment-card border-radius-xl sticky-sidebar"
        >
          <VCardTitle class="d-flex align-center mb-3 border-b pb-4">
            <div class="icon-wrapper me-3">
              <VIcon icon="tabler-users" size="28" color="success"></VIcon>
            </div>
            <div>
              <h3 class="text-h5 text-success font-weight-bold mb-1">
                Thông tin đối tác
              </h3>
              <p class="text-medium-emphasis text-subtitle-2 mb-0">
                Chi tiết đối tác
              </p>
            </div>
          </VCardTitle>
          <VRow class="pa-6">
            <VCol cols="12" class="mb-3">
              <div class="info-item">
                <label class="info-label">Tên đối tác</label>
                <div class="info-value text-primary">
                  <VIcon
                    icon="tabler-building"
                    size="16"
                    class="me-2"
                    color="primary"
                  ></VIcon>
                  {{ partner.name || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-3">
              <div class="info-item">
                <label class="info-label">Loại đối tác</label>
                <div class="info-value">
                  <VChip
                    :color="getPartnerTypeColor(partner.type)"
                    size="small"
                    variant="tonal"
                  >
                    {{ partner.type || "–" }}
                  </VChip>
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-3">
              <div class="info-item">
                <label class="info-label">Trạng thái</label>
                <div class="info-value">
                  <VChip
                    :color="
                      partner.status === 'Hoạt động' ? 'success' : 'error'
                    "
                    size="small"
                    variant="tonal"
                  >
                    {{ partner.status || "–" }}
                  </VChip>
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-3">
              <div class="info-item">
                <label class="info-label">Số điện thoại</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-phone"
                    size="16"
                    class="me-2"
                    color="info"
                  ></VIcon>
                  {{ partner.phone || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-3">
              <div class="info-item">
                <label class="info-label">Email</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-mail"
                    size="16"
                    class="me-2"
                    color="secondary"
                  ></VIcon>
                  {{ partner.email || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-3">
              <div class="info-item">
                <label class="info-label">Hoa hồng</label>
                <div class="info-value amount-value">
                  <VIcon
                    icon="tabler-percentage"
                    size="16"
                    class="me-2"
                    color="success"
                  ></VIcon>
                  {{ partner.commission }}%
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-3">
              <div class="info-item">
                <label class="info-label">Phương thức thanh toán</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-credit-card"
                    size="16"
                    class="me-2"
                    color="warning"
                  ></VIcon>
                  {{ partner.payment_method || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-3">
              <div class="info-item">
                <label class="info-label">Mã nội bộ</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-hash"
                    size="16"
                    class="me-2"
                    color="primary"
                  ></VIcon>
                  {{ partner.internal_code || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-3">
              <div class="info-item">
                <label class="info-label">Nhóm đối tác</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-users-group"
                    size="16"
                    class="me-2"
                    color="info"
                  ></VIcon>
                  {{ partner.partner_group?.name || "–" }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-3" v-if="partner.address">
              <div class="info-item">
                <label class="info-label">Địa chỉ</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-map-pin"
                    size="16"
                    class="me-2"
                    color="error"
                  ></VIcon>
                  {{ partner.address }}
                </div>
              </div>
            </VCol>
            <VCol cols="12" class="mb-3" v-if="partner.internal_note">
              <div class="info-item">
                <label class="info-label">Ghi chú nội bộ</label>
                <div class="info-value">
                  <VIcon
                    icon="tabler-note"
                    size="16"
                    class="me-2"
                    color="secondary"
                  ></VIcon>
                  {{ partner.internal_note }}
                </div>
              </div>
            </VCol>
          </VRow>
        </VCard>
      </VCol>
    </VRow>
  </Layout>
</template>

<script setup>
import Layout from "@/layouts/blank.vue";
import { Head, router, Link } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { ref, watch } from "vue";

import dayjs from "dayjs";
import { formatCurrency, formatDate } from "@/utils/formatters";
const props = defineProps({
  partner: Object,
  bookings: {
    type: Array,
    default: () => [],
  },
});
console.log("partner: ", props.partner);
console.log("bookings: ", props.bookings);

// Helper function to get booking status color
const getBookingStatusColor = (status) => {
  const statusColors = {
    // Vietnamese statuses
    mới: "info",
    hủy: "error",
    "xác nhận": "success",
    "yêu cầu": "warning",
    "đã hoàn thành": "primary",

    // English statuses (fallback)
    new: "info",
    cancelled: "error",
    confirmed: "success",
    request: "warning",
    completed: "primary",
  };

  // Convert to lowercase for case-insensitive matching
  const normalizedStatus = status?.toLowerCase?.() || "";
  return statusColors[normalizedStatus] || statusColors[status] || "secondary";
};

// Helper function to get partner type color
const getPartnerTypeColor = (type) => {
  const typeColors = {
    Sale: "primary",
    "Sale TA": "success",
    OTA: "warning",
  };
  return typeColors[type] || "secondary";
};

// Helper function to get payment type color
const getPaymentTypeColor = (paymentType) => {
  const typeColors = {
    "Thanh toán trước": "success",
    "Thanh toán sau": "warning",
    "Thanh toán tại chỗ": "info",
    "Cọc trước": "primary",
    "Hoàn tiền": "error",
    // English fallbacks
    prepaid: "success",
    postpaid: "warning",
    onsite: "info",
    deposit: "primary",
    refund: "error",
  };

  const normalizedType = paymentType?.toLowerCase?.() || "";
  return typeColors[normalizedType] || typeColors[paymentType] || "secondary";
};

// Helper function to get payment status color
const getPaymentStatusColor = (paymentStatus) => {
  const statusColors = {
    // Vietnamese statuses
    "Đã thanh toán": "success",
    "Chưa thanh toán": "warning",
    "Đang xử lý": "info",
    "Thất bại": "error",
    Hủy: "error",
    "Đã hoàn thành": "primary",
    "Hoàn tiền": "secondary",

    // English fallbacks
    paid: "success",
    unpaid: "warning",
    processing: "info",
    failed: "error",
    cancelled: "error",
    completed: "primary",
    refunded: "secondary",
  };

  const normalizedStatus = paymentStatus?.toLowerCase?.() || "";
  return (
    statusColors[normalizedStatus] || statusColors[paymentStatus] || "secondary"
  );
};
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

/* Booking Accordion Styles */
.booking-accordion {
  border-radius: 12px;
  overflow: hidden;
}

.booking-panel {
  border: 1px solid #e9ecef;
  border-radius: 12px !important;
  margin-bottom: 16px;
  overflow: hidden;
}

.booking-panel:last-child {
  margin-bottom: 0;
}

.booking-panel-title {
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  padding: 20px !important;
}

.booking-panel-title:hover {
  background: #ffffff;
}

.booking-panel-content {
  padding: 24px !important;
  background: #ffffff;
}

.booking-info-card,
.payment-info-card {
  border-radius: 12px !important;
}

.booking-details .detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.booking-details .detail-item:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: 600;
  color: #6c757d;
  font-size: 0.875rem;
  min-width: 100px;
}

.detail-value {
  font-weight: 500;
  color: #212529;
  text-align: right;
}

.amount-highlight {
  color: #198754 !important;
  font-weight: 700 !important;
  font-size: 1.1rem !important;
}

.payment-list {
  max-height: 300px;
  overflow-y: auto;
}

.payment-item {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  background: #ffffff;
  transition: all 0.2s ease;
}

.payment-item:hover {
  border-color: #28a745;
  box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);
}

/* Responsive adjustments for accordion */
@media (max-width: 768px) {
  .booking-panel-title {
    padding: 16px !important;
  }

  .booking-panel-content {
    padding: 16px !important;
  }

  .detail-label {
    min-width: 80px;
    font-size: 0.8rem;
  }

  .detail-value {
    font-size: 0.875rem;
  }
}
</style>
