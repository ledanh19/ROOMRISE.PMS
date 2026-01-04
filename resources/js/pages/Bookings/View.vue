<template>
  <Head title="Xem đặt phòng | Room Rise" />
  <Layout>
    <!-- Header với gradient background -->
    <VCard class="mb-6 overflow-hidden" elevation="0">
      <div class="bg-gradient-primary pa-6 text-white">
        <VRow class="align-center">
          <VCol cols="12" md="8">
            <div class="d-flex align-center mb-2">
              <div>
                <h1 class="text-h4 text-white font-weight-bold mb-1">
                  Chi tiết đặt phòng
                </h1>
                <div class="text-h6 text-white opacity-90">
                  #{{ booking.booking_code }}
                </div>
              </div>
            </div>
            <div class="d-flex gap-3 flex-wrap">
              <VChip
                :color="getStatusChipColor(booking.status)"
                variant="elevated"
                class="text-white font-weight-bold"
                prepend-icon="tabler-info-circle"
              >
                {{ booking.status }}
              </VChip>
              <VChip
                :color="getOTAChipColor(booking.ota_name)"
                variant="elevated"
                class="text-white"
                prepend-icon="tabler-world"
              >
                {{ booking.ota_name }}
              </VChip>
              <VChip
                :color="
                  booking.payment_status === 'Đã thanh toán'
                    ? 'success'
                    : 'warning'
                "
                variant="elevated"
                class="text-white"
                prepend-icon="tabler-credit-card"
              >
                {{ booking.payment_status }}
              </VChip>
            </div>
          </VCol>
          <VCol cols="12" md="4" class="text-md-end">
            <!-- Action buttons for desktop -->
            <div class="d-none d-md-flex gap-2 justify-end flex-wrap">
              <VBtn
                variant="elevated"
                color="white"
                class="text-primary"
                @click="openInvoice(booking.id)"
              >
                <VIcon icon="tabler-file-dollar" class="me-2" />
                Hóa đơn
              </VBtn>
              <VBtn
                variant="elevated"
                color="warning"
                @click="isEditDialogVisible = true"
              >
                <VIcon icon="tabler-edit" class="me-2" />
                Chỉnh sửa
              </VBtn>
              <VBtn
                v-if="booking.payment_status !== 'Đã thanh toán'"
                variant="elevated"
                color="success"
                @click="isPayDialogVisible = true"
              >
                <VIcon icon="tabler-coin" class="me-2" />
                Thanh toán
              </VBtn>
              <VBtn variant="outlined" color="white">
                <VIcon icon="tabler-dots-vertical" />
                <VMenu activator="parent">
                  <VList>
                    <VListItem @click="isAddDialogVisible = true">
                      <template #prepend>
                        <VIcon icon="tabler-circle-plus" />
                      </template>
                      <VListItemTitle>Thêm phòng</VListItemTitle>
                    </VListItem>
                    <VListItem @click="openConfirmInvoice(booking.id)">
                      <template #prepend>
                        <VIcon icon="tabler-rosette-discount-check" />
                      </template>
                      <VListItemTitle>Xác nhận</VListItemTitle>
                    </VListItem>
                    <VListItem
                      v-if="
                        booking.status !== 'Hủy' &&
                        (booking.ota_name === 'Walk-in' ||
                          booking.ota_name === 'Từ đối tác')
                      "
                      @click="isCancelDialogVisible = true"
                    >
                      <template #prepend>
                        <VIcon icon="tabler-circle-x" />
                      </template>
                      <VListItemTitle>Hủy đặt phòng</VListItemTitle>
                    </VListItem>
                    <VListItem
                      v-if="
                        booking.status !== 'Hủy' &&
                        booking.status !== 'Không đến' &&
                        (booking.ota_name === 'BookingCom' ||
                          booking.ota_name === 'Expedia')
                      "
                      @click="noshowOta(booking.id)"
                    >
                      <template #prepend>
                        <VIcon icon="tabler-circle-x" />
                      </template>
                      <VListItemTitle>Báo không đến OTA</VListItemTitle>
                    </VListItem>
                    <VListItem
                      v-if="
                        booking.status !== 'Hủy' &&
                        booking.status !== 'Không đến'
                      "
                      @click="noshowExternal(booking.id)"
                    >
                      <template #prepend>
                        <VIcon icon="tabler-cancel" />
                      </template>
                      <VListItemTitle>Báo không đến nội bộ</VListItemTitle>
                    </VListItem>
                  </VList>
                </VMenu>
              </VBtn>
            </div>

            <!-- Mobile action button -->
            <div class="d-md-none">
              <VBtn
                variant="elevated"
                color="white"
                class="text-primary me-2"
                @click="openInvoice(booking.id)"
              >
                <VIcon icon="tabler-file-dollar" />
              </VBtn>
              <VBtn variant="outlined" color="white">
                <VIcon icon="tabler-dots-vertical" />
                <VMenu activator="parent">
                  <VList>
                    <VListItem @click="isEditDialogVisible = true">
                      <template #prepend>
                        <VIcon icon="tabler-edit" />
                      </template>
                      <VListItemTitle>Chỉnh sửa</VListItemTitle>
                    </VListItem>
                    <VListItem
                      v-if="booking.payment_status !== 'Đã thanh toán'"
                      @click="isPayDialogVisible = true"
                    >
                      <template #prepend>
                        <VIcon icon="tabler-coin" />
                      </template>
                      <VListItemTitle>Thanh toán</VListItemTitle>
                    </VListItem>
                    <!-- Other menu items... -->
                  </VList>
                </VMenu>
              </VBtn>
            </div>
          </VCol>
        </VRow>
      </div>
    </VCard>

    <VRow>
      <!-- Main Content -->
      <VCol cols="12" lg="8">
        <!-- Booking Info Card with modern design -->
        <VCard class="modern-card mb-6" elevation="2">
          <VCardItem>
            <VCardTitle class="d-flex align-center pb-4">
              <VAvatar size="40" color="primary" variant="tonal" class="me-3">
                <VIcon icon="tabler-info-circle" />
              </VAvatar>
              <div>
                <div class="text-h5 font-weight-bold">Thông tin đặt phòng</div>
                <div class="text-body-2 text-medium-emphasis">
                  Chi tiết và trạng thái booking
                </div>
              </div>
            </VCardTitle>

            <VRow>
              <VCol cols="12" sm="6" lg="4">
                <div class="info-item">
                  <VIcon icon="tabler-hash" class="info-icon" color="primary" />
                  <div class="info-content">
                    <div class="">Mã đặt phòng</div>
                    <div class="info-value text-primary">
                      {{ booking.booking_code }}
                    </div>
                  </div>
                </div>
              </VCol>

              <VCol cols="12" sm="6" lg="4">
                <div class="info-item">
                  <VIcon
                    icon="tabler-calendar"
                    class="info-icon"
                    color="success"
                  />
                  <div class="info-content">
                    <div class="">Ngày đặt</div>
                    <div class="info-value">
                      {{ formatDate(booking.created_at) }}
                    </div>
                  </div>
                </div>
              </VCol>

              <VCol cols="12" sm="6" lg="4">
                <div class="info-item">
                  <VIcon
                    icon="tabler-credit-card"
                    class="info-icon"
                    color="info"
                  />
                  <div class="info-content">
                    <div class="">Phương thức thanh toán</div>
                    <div class="info-value">
                      {{ booking.room_payment_method }}
                    </div>
                  </div>
                </div>
              </VCol>

              <VCol cols="12" sm="6" lg="4">
                <div class="info-item">
                  <VIcon
                    icon="tabler-percentage"
                    class="info-icon"
                    color="warning"
                  />
                  <div class="info-content">
                    <div class="">Phí hoa hồng</div>
                    <div class="info-value">
                      {{ formatCurrency(booking.commission_fee) }}
                    </div>
                  </div>
                </div>
              </VCol>

              <VCol cols="12" sm="6" lg="4">
                <div class="info-item">
                  <VIcon
                    icon="tabler-notes"
                    class="info-icon"
                    color="secondary"
                  />
                  <div class="info-content">
                    <div class="">Ghi chú</div>
                    <div class="info-value">{{ booking.note || "–" }}</div>
                  </div>
                </div>
              </VCol>
              <VCol cols="12" sm="6" lg="4">
                <div class="info-item">
                  <VIcon
                    icon="tabler-users"
                    class="info-icon"
                    color="secondary"
                  />
                  <div class="info-content">
                    <div class="">Đối tác</div>
                    <div class="info-value">
                      {{ booking?.customer?.partner?.name || "–" }}
                    </div>
                  </div>
                </div>
              </VCol>
            </VRow>
          </VCardItem>
        </VCard>

        <!-- Rooms Card with enhanced design -->
        <VCard class="modern-card mb-6" elevation="2">
          <VCardItem>
            <VCardTitle class="d-flex align-center justify-space-between pb-4">
              <div class="d-flex align-center">
                <VAvatar size="40" color="success" variant="tonal" class="me-3">
                  <VIcon icon="tabler-bed" />
                </VAvatar>
                <div>
                  <div class="text-h5 font-weight-bold">Danh sách phòng</div>
                  <div class="text-body-2 text-medium-emphasis">
                    {{ booking.booking_rooms?.length || 0 }} phòng được đặt
                  </div>
                </div>
              </div>
              <VChip color="primary" variant="tonal" size="small">
                {{ booking.booking_rooms?.length || 0 }} phòng
              </VChip>
            </VCardTitle>

            <!-- Mobile Room Cards -->
            <div v-if="$vuetify.display.smAndDown" class="room-cards-mobile">
              <VCard
                v-for="room in booking.booking_rooms"
                :key="room.id"
                variant="outlined"
                class="mb-4 room-card-mobile"
              >
                <VCardText>
                  <div class="d-flex justify-space-between align-start mb-3">
                    <div class="flex-grow-1">
                      <div class="text-subtitle-1 font-weight-bold mb-1">
                        <VIcon icon="tabler-building" size="16" class="me-2" />
                        {{ room?.property?.name }}
                      </div>
                      <div class="text-body-2 mb-1">
                        <VIcon icon="tabler-door" size="16" class="me-2" />
                        {{ room?.room?.name }} - {{ room?.room_unit?.name }}
                      </div>
                      <div class="text-body-2">
                        <VIcon
                          icon="tabler-calendar-event"
                          size="16"
                          class="me-2"
                        />
                        {{ formatDate(room?.check_in_date) }} →
                        {{ formatDate(room?.check_out_date) }}
                        <VChip color="info" size="x-small" class="ms-2">
                          {{
                            dayjs(room?.check_out_date).diff(
                              dayjs(room?.check_in_date),
                              "day"
                            )
                          }}
                          đêm
                        </VChip>
                      </div>
                    </div>
                    <VBtn
                      icon
                      size="small"
                      variant="text"
                      @click="showDetailBooking(room)"
                    >
                      <VIcon icon="tabler-edit" />
                    </VBtn>
                  </div>

                  <VRow>
                    <VCol cols="6">
                      <div class="text-caption text-medium-emphasis">
                        Trạng thái
                      </div>
                      <VChip
                        v-if="room.room_status"
                        :color="statusColor(room.room_status)"
                        size="small"
                        variant="flat"
                        class="text-white"
                      >
                        {{ room.room_status }}
                      </VChip>
                    </VCol>
                    <VCol cols="6" class="text-end">
                      <div class="text-caption text-medium-emphasis">
                        Tiền phòng
                      </div>
                      <div class="text-h6 font-weight-bold text-success">
                        {{ formatCurrency(room.total) }}
                      </div>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </div>

            <!-- Desktop Table -->
            <VTable v-else class="modern-table">
              <thead>
                <tr>
                  <th class="table-header">Chỗ nghỉ</th>
                  <th class="table-header">Loại phòng</th>
                  <th class="table-header">Phòng</th>
                  <th class="table-header">Thời gian</th>
                  <th class="table-header">Trạng thái</th>
                  <th class="table-header">Tiền phòng</th>
                  <th class="table-header">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="room in booking.booking_rooms"
                  :key="room.id"
                  class="table-row"
                >
                  <td class="font-weight-medium">{{ room?.property?.name }}</td>
                  <td>{{ room?.room?.name }}</td>
                  <td>{{ room?.room_unit?.name }}</td>
                  <td>
                    <div class="d-flex align-center">
                      <VIcon
                        icon="tabler-calendar"
                        size="16"
                        class="me-2 text-medium-emphasis"
                      />
                      <div>
                        <div>{{ formatDate(room?.check_in_date) }} →</div>
                        <div>{{ formatDate(room?.check_out_date) }}</div>
                        <VChip color="info" size="x-small" variant="tonal">
                          {{
                            dayjs(room?.check_out_date).diff(
                              dayjs(room?.check_in_date),
                              "day"
                            )
                          }}
                          đêm
                        </VChip>
                      </div>
                    </div>
                  </td>
                  <td>
                    <VChip
                      v-if="room.room_status"
                      :color="statusColor(room.room_status)"
                      variant="flat"
                      class="text-white"
                      size="small"
                    >
                      {{ room.room_status }}
                    </VChip>
                    <span v-else class="text-medium-emphasis">–</span>
                  </td>
                  <td>
                    <div class="text-h6 font-weight-bold text-success">
                      {{ formatCurrency(room.total) }}
                    </div>
                  </td>
                  <td>
                    <VBtn
                      icon
                      size="small"
                      color="primary"
                      variant="text"
                      @click="showDetailBooking(room)"
                    >
                      <VIcon icon="tabler-edit" />
                      <VTooltip activator="parent" text="Chỉnh sửa phòng" />
                    </VBtn>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardItem>
        </VCard>
      </VCol>

      <!-- Sidebar -->
      <VCol cols="12" lg="4">
        <!-- Customer Info Card -->
        <VCard class="modern-card mb-6" elevation="2">
          <VCardItem>
            <VCardTitle class="d-flex align-center pb-4">
              <VAvatar size="40" color="info" variant="tonal" class="me-3">
                <VIcon icon="tabler-user" />
              </VAvatar>
              <div>
                <div class="text-h5 font-weight-bold">Thông tin khách hàng</div>
                <div class="text-body-2 text-medium-emphasis">
                  Chi tiết khách đặt phòng
                </div>
              </div>
            </VCardTitle>

            <VList lines="two" class="pa-0">
              <VListItem>
                <template #prepend>
                  <VAvatar size="32" color="primary" variant="tonal">
                    <VIcon icon="tabler-user-circle" size="18" />
                  </VAvatar>
                </template>
                <VListItemTitle class="font-weight-medium">
                  {{ customer?.full_name || "–" }}
                </VListItemTitle>
                <VListItemSubtitle>Tên đầy đủ</VListItemSubtitle>
              </VListItem>

              <VListItem>
                <template #prepend>
                  <VAvatar size="32" color="success" variant="tonal">
                    <VIcon icon="tabler-phone" size="18" />
                  </VAvatar>
                </template>
                <VListItemTitle class="font-weight-medium">
                  {{ customer?.phone || "–" }}
                </VListItemTitle>
                <VListItemSubtitle>Số điện thoại</VListItemSubtitle>
              </VListItem>

              <VListItem>
                <template #prepend>
                  <VAvatar size="32" color="warning" variant="tonal">
                    <VIcon icon="tabler-mail" size="18" />
                  </VAvatar>
                </template>
                <VListItemTitle class="font-weight-medium">
                  {{ customer?.email || "–" }}
                </VListItemTitle>
                <VListItemSubtitle>Email</VListItemSubtitle>
              </VListItem>

              <VListItem>
                <template #prepend>
                  <VAvatar size="32" color="info" variant="tonal">
                    <VIcon icon="tabler-world" size="18" />
                  </VAvatar>
                </template>
                <VListItemTitle class="font-weight-medium">
                  {{ customer?.country || "–" }}
                </VListItemTitle>
                <VListItemSubtitle>Quốc gia</VListItemSubtitle>
              </VListItem>
            </VList>
          </VCardItem>
        </VCard>

        <!-- Payment Summary Card -->
        <VCard class="modern-card" elevation="2">
          <VCardItem>
            <VCardTitle class="d-flex align-center pb-4">
              <VAvatar size="40" color="success" variant="tonal" class="me-3">
                <VIcon icon="tabler-currency-dollar" />
              </VAvatar>
              <div>
                <div class="text-h5 font-weight-bold">Tóm tắt thanh toán</div>
                <div class="text-body-2 text-medium-emphasis">
                  Thông tin tài chính
                </div>
              </div>
            </VCardTitle>

            <VList lines="two" class="pa-0">
              <VListItem>
                <VListItemTitle class="d-flex justify-space-between">
                  <span>Tiền phòng</span>
                  <span class="font-weight-bold">{{
                    formatCurrency(booking.total_amount)
                  }}</span>
                </VListItemTitle>
              </VListItem>

              <VListItem>
                <VListItemTitle class="d-flex justify-space-between">
                  <span>Phí hoa hồng</span>
                  <span class="font-weight-bold text-warning">{{
                    formatCurrency(booking.commission_fee)
                  }}</span>
                </VListItemTitle>
              </VListItem>

              <VListItem>
                <VListItemTitle class="d-flex justify-space-between">
                  <span>Giảm giá</span>
                  <span class="font-weight-bold text-error"
                    >-{{ formatCurrency(totalDiscount) }}</span
                  >
                </VListItemTitle>
              </VListItem>

              <VDivider class="my-2" />

              <VListItem>
                <VListItemTitle class="d-flex justify-space-between text-h6">
                  <span>Tổng thanh toán</span>
                  <span class="font-weight-bold text-success">{{
                    formatCurrency(booking.customer_payment_amount)
                  }}</span>
                </VListItemTitle>
              </VListItem>

              <VListItem>
                <VListItemTitle class="d-flex justify-space-between">
                  <span>Đã thanh toán</span>
                  <span class="font-weight-bold text-info">{{
                    formatCurrency(booking.paid)
                  }}</span>
                </VListItemTitle>
              </VListItem>

              <VListItem>
                <VListItemTitle class="d-flex justify-space-between">
                  <span>Còn lại</span>
                  <span class="font-weight-bold text-primary">{{
                    formatCurrency(booking.remaining)
                  }}</span>
                </VListItemTitle>
              </VListItem>
            </VList>
          </VCardItem>
        </VCard>
      </VCol>

      <!-- Payment History Sections -->
      <VCol cols="12">
        <!-- Payment History -->
        <VCard class="modern-card mb-6" elevation="2">
          <VCardItem>
            <VCardTitle class="d-flex align-center justify-space-between pb-4">
              <div class="d-flex align-center">
                <VAvatar size="40" color="success" variant="tonal" class="me-3">
                  <VIcon icon="tabler-cash" />
                </VAvatar>
                <div>
                  <div class="text-h5 font-weight-bold">Lịch sử thanh toán</div>
                  <div class="text-body-2 text-medium-emphasis">
                    {{ paymentHistories?.length || 0 }} giao dịch
                  </div>
                </div>
              </div>
              <VChip color="success" variant="tonal" size="small">
                {{ paymentHistories?.length || 0 }} giao dịch
              </VChip>
            </VCardTitle>

            <PaymentHistoryTable
              :payments="paymentHistories"
              :is-mobile="$vuetify.display.smAndDown"
            />
          </VCardItem>
        </VCard>

        <!-- Partner Payment History -->
        <VCard class="modern-card mb-6" elevation="2">
          <VCardItem>
            <VCardTitle class="d-flex align-center justify-space-between pb-4">
              <div class="d-flex align-center">
                <VAvatar size="40" color="warning" variant="tonal" class="me-3">
                  <VIcon icon="tabler-users" />
                </VAvatar>
                <div>
                  <div class="text-h5 font-weight-bold">Công nợ đối tác</div>
                  <div class="text-body-2 text-medium-emphasis">
                    {{ paymentHistoriesPartner?.length || 0 }} giao dịch
                  </div>
                </div>
              </div>
              <VChip color="warning" variant="tonal" size="small">
                {{ paymentHistoriesPartner?.length || 0 }} giao dịch
              </VChip>
            </VCardTitle>

            <PaymentHistoryTable
              :payments="paymentHistoriesPartner"
              :is-mobile="$vuetify.display.smAndDown"
              :is-partner="true"
            />
          </VCardItem>
        </VCard>

        <!-- OTA History -->
        <VCard class="modern-card" elevation="2">
          <VCardItem>
            <VCardTitle class="d-flex align-center justify-space-between pb-4">
              <div class="d-flex align-center">
                <VAvatar size="40" color="primary" variant="tonal" class="me-3">
                  <VIcon icon="tabler-world" />
                </VAvatar>
                <div>
                  <div class="text-h5 font-weight-bold">Lịch sử OTA</div>
                  <div class="text-body-2 text-medium-emphasis">
                    {{ otaHistories?.length || 0 }} giao dịch
                  </div>
                </div>
              </div>
              <VChip color="primary" variant="tonal" size="small">
                {{ otaHistories?.length || 0 }} giao dịch
              </VChip>
            </VCardTitle>

            <PaymentHistoryTable
              :payments="otaHistories"
              :is-mobile="$vuetify.display.smAndDown"
              :is-ota="true"
            />
          </VCardItem>
        </VCard>
      </VCol>
    </VRow>

    <!-- Dialogs -->
    <UpdatePayment
      v-model:is-pay-dialog-visible="isPayDialogVisible"
      :booking="props.booking"
    />
    <FormEditDialog
      v-model:is-edit-dialog-visible="isEditDialogVisible"
      :data="props.booking"
    />
    <FormAddDialog
      v-model:is-add-dialog-visible="isAddDialogVisible"
      :data="props.booking"
    />
    <AddNewBookingDrawer
      v-model:is-drawer-open="isAddNewBookingDrawerVisible"
      :booking="booking"
      :customer="customer"
      :paymentHistories="paymentHistories"
      :paymentHistoriesPartner="paymentHistoriesPartner"
      :bookingCustomers="bookingCustomers"
      :otaHistories="otaHistories"
      :room="detailRoom"
      @update:undoCheck="reloadData"
    />
    <CancelBooking
      v-model:is-cancel-dialog-visible="isCancelDialogVisible"
      :booking="props.booking"
    />
  </Layout>
</template>

<script setup>
import Layout from "@/layouts/blank.vue";
import { formatCurrency, formatDate } from "@/utils/formatters";
import { Head, router } from "@inertiajs/vue3";
import dayjs from "dayjs";
import { computed, ref } from "vue";
import { useToast } from "vue-toastification";
import { apiPost } from "../../utils/api";
import AddNewBookingDrawer from "./AddNewBookingDrawer.vue";
import CancelBooking from "./CancelBooking.vue";
import FormAddDialog from "./FormAddDialog.vue";
import FormEditDialog from "./FormEditDialog.vue";
import PaymentHistoryTable from "./PaymentHistoryTable.vue"; // Component riêng
import UpdatePayment from "./UpdatePayment.vue";

const props = defineProps({
  booking: Object,
  customer: Object,
  paymentHistories: Object,
  paymentHistoriesPartner: Object,
  bookingCustomers: Object,
  otaHistories: Object,
});

// Reactive variables
const isAddNewBookingDrawerVisible = ref(false);
const isDialogVisible = ref(false);
const isEditDialogVisible = ref(false);
const isPayDialogVisible = ref(false);
const isAddDialogVisible = ref(false);
const isCancelDialogVisible = ref(false);
const detailRoom = ref({});
const toast = useToast();

// Computed
const totalDiscount = computed(() => {
  return (props.booking.booking_rooms || []).reduce((total, item) => {
    return total + (Number(item.discount) || 0);
  }, 0);
});

// Methods
const showDetailBooking = (room) => {
  isAddNewBookingDrawerVisible.value = true;
  detailRoom.value = room;
};

const statusColor = (status) => {
  const colorMap = {
    Mới: "primary",
    "Xác nhận": "success",
    "Đã thanh toán": "success",
    "Đã nhận phòng": "success",
    "Yêu cầu": "warning",
    "Đã cọc": "warning",
    "Chưa nhận phòng": "warning",
    "Hoàn thành": "secondary",
    "Chờ thanh toán": "secondary",
    "Chưa thanh toán": "error",
    Hủy: "error",
    "Đã trả phòng": "info",
    "Đã xác nhận": "info",
  };
  return colorMap[status] || "grey";
};

const getStatusChipColor = (status) => {
  if (["Xác nhận", "Đã thanh toán", "Đã nhận phòng"].includes(status))
    return "success";
  if (["Yêu cầu", "Đã cọc", "Chưa nhận phòng"].includes(status))
    return "warning";
  if (["Chưa thanh toán", "Hủy"].includes(status)) return "error";
  return "info";
};

const getOTAChipColor = (otaName) => {
  const colorMap = {
    "walk-in": "warning",
    "booking.com": "primary",
    agoda: "error",
    expedia: "info",
    airbnb: "pink",
    "từ đối tác": "info",
  };
  return colorMap[otaName?.toLowerCase()] || "secondary";
};

const reloadData = () => {
  router.reload({
    only: ["booking", "bookingCustomers", "paymentHistories"],
  });
};

const openInvoice = () => {
  const url = route("bookings.exportInvoicePdf", props.booking.id);
  window.open(url, "_blank");
};

const openConfirmInvoice = () => {
  const url = route("bookings.exportConfirmInvoicePdf", props.booking.id);
  window.open(url, "_blank");
};

const noshowOta = async (id) => {
  try {
    await apiPost(`/api/admin/bookings/no-show-ota/${id}`, {
      method: "POST",
    });
    toast.success("Cập nhật trạng thái không đến OTA thành công");
    reloadData();
  } catch (error) {
    toast.error("Có lỗi xảy ra, vui lòng thử lại.");
  }
};

const noshowExternal = async (id) => {
  try {
    await apiPost(`/api/admin/bookings/no-show-external/${id}`, {
      method: "POST",
    });
    toast.success("Cập nhật trạng thái không đến nội bộ thành công");
    reloadData();
  } catch (error) {
    toast.error("Có lỗi xảy ra, vui lòng thử lại.");
  }
};
</script>

<style scoped>
.bg-gradient-primary {
  background: linear-gradient(
    135deg,
    rgb(var(--v-theme-primary)) 0%,
    rgb(var(--v-theme-primary-darken-1)) 100%
  );
}

.modern-card {
  border-radius: 16px;
  transition: all 0.3s ease;
}

.modern-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.info-item {
  display: flex;
  align-items: center;
  padding: 16px 0;
  border-bottom: 1px solid rgb(var(--v-theme-grey-lighten-3));
}

.info-item:last-child {
  border-bottom: none;
}

.info-icon {
  margin-right: 12px;
  flex-shrink: 0;
}

.info-content {
  flex-grow: 1;
}

.info-label {
  font-size: 0.875rem;
  color: rgb(var(--v-theme-on-surface-variant));
  margin-bottom: 4px;
}

.info-value {
  font-weight: 500;
  font-size: 1rem;
}

.modern-table {
  border-radius: 12px;
  overflow: hidden;
}

.table-header {
  background-color: rgb(var(--v-theme-grey-lighten-4));
  font-weight: 600;
  color: rgb(var(--v-theme-primary));
  padding: 16px;
}

.table-row:hover {
  background-color: rgb(var(--v-theme-primary-lighten-5));
}

.room-card-mobile {
  border-radius: 12px;
  transition: all 0.2s ease;
}

.room-card-mobile:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.room-cards-mobile {
  margin-top: 16px;
}
</style>
