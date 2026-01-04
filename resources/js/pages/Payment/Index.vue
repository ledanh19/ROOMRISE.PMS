<template>
  <Head title="Phiếu thu - Hotel Collect | Room Rise" />
  <Layout>
    <!-- Header Card với gradient background -->
    <VCard class="mb-6 payment-header-card">
      <VCardItem class="payment-header-content">
        <div class="d-flex justify-space-between align-center flex-wrap">
          <div class="header-text-section">
            <VCardTitle class="payment-title">
              <VIcon
                size="32"
                color="white"
                icon="tabler-receipt"
                class="mr-3"
              />
              Phiếu thu Hotel Collect
            </VCardTitle>
            <p class="payment-subtitle">
              Quản lý các khoản thanh toán trực tiếp tại khách sạn
            </p>
          </div>
          <VBtn
            color="white"
            variant="elevated"
            size="large"
            class="export-btn"
            @click="isDialogVisible = true"
          >
            <VIcon size="20" icon="tabler-download" class="mr-2" />
            Xuất dữ liệu
          </VBtn>
        </div>
      </VCardItem>
    </VCard>

    <!-- Filter Card với improved styling -->
    <VCard class="mb-6 filter-card">
      <VCardItem class="filter-header">
        <div class="d-flex align-center justify-space-between">
          <div class="filter-title-section">
            <VIcon
              size="24"
              color="primary"
              icon="tabler-filter"
              class="mr-2"
            />
            <span class="text-h6 font-weight-bold">Bộ lọc</span>
          </div>
          <VBtn
            variant="text"
            color="error"
            @click="resetFilter"
            class="reset-btn"
          >
            <VIcon size="18" icon="tabler-refresh" class="mr-1" />
            Đặt lại
          </VBtn>
        </div>

        <VRow class="mt-4">
          <VCol cols="12" sm="6" md="4" lg="3">
            <AppSelect
              item-value="id"
              item-title="title"
              label="Loại ngày"
              v-model="date_type"
              :items="typeOptions"
              chips
              closable-chips
              class="filter-select"
            />
          </VCol>

          <VCol cols="12" sm="6" md="4" lg="3">
            <AppDateTimePicker
              v-model="range_date"
              placeholder="Chọn ngày"
              label="Khoảng thời gian"
              :config="{ mode: 'range' }"
              class="filter-date-picker"
            />
          </VCol>
          <VCol cols="12" sm="6" md="4" lg="2">
            <AppSelect
              v-model="created_by"
              :items="users"
              item-title="name"
              item-value="name"
              label="Nhân viên"
              chips
              closable-chips
              class="filter-select"
            />
          </VCol>
          <VCol cols="12" sm="6" md="4" lg="2">
            <AppSelect
              v-model="ota_name"
              :items="otaOptions"
              label="Nguồn đặt phòng"
              chips
              closable-chips
              class="filter-select"
            />
          </VCol>

          <VCol cols="12" sm="6" md="4" lg="2">
            <AppSelect
              label="Phương thức thanh toán"
              v-model="payment_method"
              :items="paymentOptions"
              chips
              closable-chips
              class="filter-select"
            />
          </VCol>
        </VRow>
      </VCardItem>
    </VCard>

    <!-- Data Table Card với enhanced styling -->
    <VCard class="data-table-card">
      <VCardText class="table-header">
        <div class="d-flex align-center justify-space-between flex-wrap gap-4">
          <div class="d-flex gap-2 align-center">
            <VIcon size="20" color="primary" icon="tabler-list" class="mr-2" />
            <p class="text-body-1 mb-0 font-weight-medium">Hiển thị</p>
            <AppSelect
              :model-value="itemsPerPage"
              :items="PAGINATION_OPTIONS"
              style="inline-size: 5.5rem"
              @update:model-value="itemsPerPage = parseInt($event, 10)"
              class="pagination-select"
            />
            <span class="text-body-2 text-medium-emphasis ml-2">bản ghi</span>
          </div>

          <div class="d-flex align-center justify-lg-end flex-wrap gap-3">
            <AppTextField
              v-model="search"
              placeholder="Tìm kiếm đặt phòng..."
              style="inline-size: 18rem"
              class="search-field"
              prepend-inner-icon="tabler-search"
            />

            <VBtn
              color="primary"
              variant="outlined"
              @click="showColumnDialog = true"
              class="column-btn"
            >
              <VIcon size="18" icon="tabler-columns" class="mr-2" />
              Tùy chỉnh cột
            </VBtn>
            <VBtn
              color="success"
              variant="elevated"
              @click="exportSelected"
              :loading="exportingSelected"
              v-if="selected_bookings.length > 0"
              class="export-selected-btn"
            >
              <VIcon size="18" icon="tabler-download" class="mr-2" />
              Xuất {{ selected_bookings.length }} bản ghi
            </VBtn>
          </div>
        </div>
      </VCardText>

      <!-- Selection Summary -->
      <VCardText v-if="selected_bookings.length > 0" class="selection-summary">
        <div class="d-flex align-center justify-space-between flex-wrap gap-3">
          <div class="selection-info">
            <VIcon
              size="20"
              color="success"
              icon="tabler-check-circle"
              class="mr-2"
            />
            <span class="font-weight-medium"
              >{{ selected_bookings.length }} bản ghi đã chọn</span
            >
          </div>
          <div class="d-flex gap-2">
            <VBtn
              variant="text"
              color="info"
              @click="selectAllBookings"
              :loading="isSelectingAll"
              :disabled="hasSelectedAll || incomeIds.length === 0"
              class="select-all-btn"
            >
              <VIcon size="18" icon="tabler-select-all" class="mr-1" />
              Chọn tất cả ({{ incomeIds.length }})
            </VBtn>

            <VBtn
              variant="text"
              color="error"
              @click="clearAllSelection"
              class="clear-selection-btn"
            >
              <VIcon size="18" icon="tabler-x" class="mr-1" />
              Bỏ chọn tất cả
            </VBtn>
          </div>
        </div>
      </VCardText>

      <!-- Enhanced Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items-length="data.total"
        :headers="visibleHeaders"
        :items="data.data"
        :search="search"
        item-value="id"
        class="payment-data-table"
        @update:options="updateOptions"
        show-select
        v-model="selected_bookings"
        hover
      >
        <template #item.rooms="{ item }">
          <div class="room-info">
            <template v-if="item.booking?.booking_rooms?.length">
              <div
                v-for="room in item.booking.booking_rooms"
                :key="room.id"
                class="room-item"
              >
                <VIcon
                  size="16"
                  color="primary"
                  icon="tabler-bed"
                  class="mr-1"
                />
                {{ room?.room_unit?.name }}
              </div>
            </template>
            <span v-else class="text-medium-emphasis">--</span>
          </div>
        </template>

        <template #item.income_expense_id="{ item }">
          <div class="payment-id">
            <VChip size="small" color="primary" variant="tonal">
              #{{ item.id }}
            </VChip>
          </div>
        </template>

        <template #item.booking_code="{ item }">
          <div class="booking-code">
            <VChip size="small" color="secondary" variant="tonal">
              {{ item.booking.booking_code }}
            </VChip>
          </div>
        </template>

        <template #item.customer_id="{ item }">
          <div class="customer-info">
            <VIcon size="16" color="info" icon="tabler-user" class="mr-1" />
            {{ item?.booking?.customer?.full_name }}
          </div>
        </template>

        <template #item.amount="{ item }">
          <div class="amount-display">
            <span class="font-weight-bold text-success">
              {{ formatCurrency(item.amount) }}
            </span>
          </div>
        </template>

        <template #item.ota_name="{ item }">
          <div class="ota-info">
            <VChip
              size="small"
              :color="getOtaColor(item?.booking?.ota_name)"
              variant="tonal"
            >
              {{ item?.booking?.ota_name }}
            </VChip>
          </div>
        </template>

        <template #item.check_in_date="{ item }">
          <div class="date-info">
            <VIcon
              size="16"
              color="success"
              icon="tabler-plane-arrival"
              class="mr-1"
            />
            {{ formatDate(item?.booking?.check_in_date) }}
          </div>
        </template>

        <template #item.check_out_date="{ item }">
          <div class="date-info">
            <VIcon
              size="16"
              color="warning"
              icon="tabler-plane-departure"
              class="mr-1"
            />
            {{ formatDate(item?.booking?.check_out_date) }}
          </div>
        </template>
        <template #item.date="{ item }">
          <div class="date-info">
            <VIcon
              size="16"
              color="success"
              icon="tabler-credit-card"
              class="mr-1"
            />
            {{ formatDate(item?.date) }}
          </div>
        </template>

        <template #item.actions="{ item }">
          <Link
            class="view-link"
            :href="route('payment.detailById', item?.booking?.id)"
          >
            <VBtn
              size="small"
              color="primary"
              variant="outlined"
              class="view-btn"
            >
              <VIcon size="16" icon="tabler-eye" class="mr-1" />
              Xem
            </VBtn>
          </Link>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="data.total"
          />
        </template>

        <template #body.append>
          <tr class="summary-row">
            <td class="pa-4"></td>
            <td v-for="header in visibleHeaders" :key="header.key" class="pa-4">
              <template v-if="header.key === 'id'">
                <strong class="summary-label">Tóm tắt</strong>
              </template>

              <template v-else-if="header.key === 'amount'">
                <div class="summary-amount">
                  <div class="text-h6 text-success font-weight-bold">
                    Tổng tiền
                  </div>
                  <div class="text-h5 text-success">
                    {{ formatCurrency(totalIncome) }}
                  </div>
                </div>
              </template>
            </td>
          </tr>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Dialogs -->
    <PaymentHistories
      v-model:is-dialog-visible="isFormDialogVisible"
      :history-TargetId="historyTargetId"
    />
    <PayDialog
      v-model:is-pay-dialog-visible="isFormPayDialogVisible"
      :payment-TargetId="paymentTargetId"
      @update:payment="loadData"
    />
    <ColumnSettings
      v-model="showColumnDialog"
      :headers="defaultHeaders"
      @update:headers="visibleHeaders = $event"
    />
  </Layout>

  <!-- Export Confirmation Dialog -->
  <VDialog v-model="isDialogVisible" width="500" persistent>
    <DialogCloseBtn @click="isDialogVisible = false" />

    <VCard class="export-dialog">
      <VCardTitle class="export-dialog-title">
        <VIcon size="24" color="primary" icon="tabler-download" class="mr-2" />
        Xác nhận xuất dữ liệu
      </VCardTitle>
      <VCardText class="export-dialog-content">
        <p>
          Xuất dữ liệu sẽ mất khoảng 10 giây để hoàn tất. Sau khi hoàn tất, bạn
          sẽ nhận được thông báo để tải file. Vui lòng nhấp vào "Xác nhận" để
          bắt đầu!
        </p>
      </VCardText>
      <VCardText class="d-flex flex-wrap gap-3">
        <VBtn
          color="success"
          @click="confirmSelectedExport"
          v-if="selected_bookings.length > 0"
          class="confirm-btn"
        >
          <VIcon size="18" icon="tabler-check" class="mr-2" />
          Xác nhận
        </VBtn>
        <VBtn color="success" @click="confirmExport" v-else class="confirm-btn">
          <VIcon size="18" icon="tabler-check" class="mr-2" />
          Xác nhận
        </VBtn>

        <VBtn
          variant="tonal"
          color="secondary"
          @click="isDialogVisible = false"
          class="cancel-btn"
        >
          <VIcon size="18" icon="tabler-x" class="mr-2" />
          Hủy bỏ
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import Layout from "@/layouts/blank.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { PAGINATION_OPTIONS } from "@/utils/constants";
import { formatCurrency, formatDate } from "@/utils/formatters";
import { Head, Link, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { onMounted, ref, watch } from "vue";
import ColumnSettings from "./ColumnSettings.vue";
import PayDialog from "./PayDialog.vue";
import PaymentHistories from "./PaymentHistories.vue";

const propertyStore = usePropertyStore();
const props = defineProps({
  data: Object,
  filters: Object,
  incomeIds: Array,
  totalIncome: Array,
  users: Array,
});

const search = ref("");
const filter = ref(false);
const isFormDialogVisible = ref(false);
const isFormPayDialogVisible = ref(false);
const selectedData = ref();
const range_date = ref("");
const showColumnDialog = ref(false);
const date_type = ref(null);
const payment_method = ref(null);
const created_by = ref(null);

const isDialogVisible = ref(false);
const selected_bookings = ref([]);
const isSelectedExportDialog = ref(false);
const exportingSelected = ref(false);
const property_id = ref(
  propertyStore.selectedProperty || Number(props.filters?.property_id) || null
);
const payment_status = ref("Tất cả");
const status = ref("Tất cả trạng thái");
const paymentTargetId = ref();
const historyTargetId = ref();
const ota_name = ref(null);
const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);

const typeOptions = [
  { id: "check_in_date", title: "Ngày nhận phòng" },
  { id: "check_out_date", title: "Ngày trả phòng" },
  { id: "created_at", title: "Ngày đặt phòng" },
  { id: "date", title: "Ngày thanh toán" },
];
const otaOptions = ref([
  "Walk-in",
  "Từ đối tác",
  "BookingCom",
  "Agoda",
  "Airbnb",
  "Expedia",
  "Traveloka",
]);
const statusOptions = [
  "Tất cả trạng thái",
  "Hủy",
  "Mới",
  "Xác nhận",
  "Yêu cầu",
];
const paymentOptions = [
  "Tiền mặt",
  "Chuyển khoản",
  "QR Code",
  "9Pay",
  "Momo",
  "VNPay",
  "Thẻ tín dụng",
];
const paymentTypeOptions = [
  { id: "OTA Collect", title: "OTA Collect" },
  { id: "Hotel Collect", title: "Hotel Collect" },
];
const fetchData = () => {
  router.get(
    route("payment.index"),
    {
      page: page.value,
      range_date: range_date.value,
      ota_name: ota_name.value,
      payment_method: payment_method.value,
      search: search.value,
      created_by: created_by.value,
      date_type: date_type.value,
      paginate: itemsPerPage.value,
      property_id: propertyStore.selectedProperty || null,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

watch(
  () => propertyStore.selectedProperty,
  (val) => {
    property_id.value = val;
    fetchData();
  }
);
onMounted(() => {
  if (property_id.value) {
    fetchData();
  }
});
const updateOptions = (options) => {
  page.value = options.page;
  itemsPerPage.value = options.itemsPerPage;
};
const toggleFilter = () => {
  filter.value = !filter.value;
};
const showHistory = (id) => {
  isFormDialogVisible.value = true;
  historyTargetId.value = id;
};
const addNewPay = (id) => {
  isFormPayDialogVisible.value = true;
  paymentTargetId.value = id;
};
const resetFilter = () => {
  range_date.value = "";
  date_type.value = null;
  ota_name.value = null;
  payment_method.value = null;
  fetchData();
};
const loadData = () => {
  fetchData();
};
watch([page, itemsPerPage], fetchData);
watch(range_date, debounce(fetchData, 300));
watch(ota_name, debounce(fetchData, 300));
watch(date_type, debounce(fetchData, 300));
watch(created_by, debounce(fetchData, 300));
watch(payment_method, debounce(fetchData, 300));
watch(search, debounce(fetchData, 300));

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
function getOtaColor(otaName) {
  switch (otaName) {
    case "Walk-in":
      return "primary";
    case "Từ đối tác":
      return "info";
    case "BookingCom":
      return "success";
    case "Agoda":
      return "warning";
    case "Airbnb":
      return "error";
    case "Expedia":
      return "info";
    case "Traveloka":
      return "secondary";
    default:
      return "grey";
  }
}
const defaultHeaders = [
  { title: "Mã TT", key: "id", sortable: false },
  { title: "Mã đặt phòng", key: "booking_code", sortable: false },
  { title: "Nguồn", key: "ota_name", sortable: false },
  { title: "Khách hàng", key: "customer_id", sortable: false },
  { title: "Số tiền", key: "amount", sortable: false },
  { title: "Phòng", key: "rooms", sortable: false },
  { title: "Phương thức", key: "payment_method", sortable: false },
  { title: "Nhận phòng", key: "check_in_date", sortable: false },
  { title: "Trả phòng", key: "check_out_date", sortable: false },
  { title: "Ngày thanh toán", key: "date", sortable: false },
  { title: "Nhân viên", key: "created_by", sortable: false },
  { title: "Nội dung", key: "note", sortable: false },
  { title: "", key: "actions", sortable: false },
];

const visibleHeaders = ref([...defaultHeaders]);

const confirmExport = () => {
  const exportUrl = route("payment.exports", {
    range_date: range_date.value,
    date_type: date_type.value,
    ota_name: ota_name.value,
    payment_method: payment_method.value,
    created_by: created_by.value,
  });
  isDialogVisible.value = false;
  window.location.href = exportUrl;
};

const isSelectingAll = ref(false);
const hasSelectedAll = ref(false);

const selectAllBookings = () => {
  isSelectingAll.value = true;

  selected_bookings.value = [...props.incomeIds];
  hasSelectedAll.value = true;

  isSelectingAll.value = false;
};

const clearAllSelection = () => {
  selected_bookings.value = [];
  hasSelectedAll.value = false;
};

watch(
  [range_date, ota_name, payment_method, search, () => props.incomeIds],
  () => {
    selected_bookings.value = [];
    hasSelectedAll.value = false;
  }
);

const exportSelected = () => {
  if (selected_bookings.value.length === 0) {
    return;
  }
  isDialogVisible.value = true;
};

const confirmSelectedExport = async () => {
  if (selected_bookings.value.length === 0) return;

  exportingSelected.value = true;

  try {
    const exportUrl = route("payment.exports.selected", {
      selected_bookings: selected_bookings.value,
    });

    selected_bookings.value = [];
    hasSelectedAll.value = false;
    isDialogVisible.value = false;
    window.location.href = exportUrl;
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    exportingSelected.value = false;
  }
};
</script>
<style scoped>
/* Header Card Styles */
.payment-header-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
  border-radius: 16px;
  overflow: hidden;
}

.payment-header-content {
  padding: 2rem;
}

.payment-title {
  color: white !important;
  font-size: 2rem !important;
  font-weight: 700 !important;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
}

.payment-subtitle {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.1rem;
  margin: 0;
}

.export-btn {
  background: rgba(255, 255, 255, 0.2) !important;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  font-weight: 600;
  transition: all 0.3s ease;
  color: #fff;
}

.export-btn:hover {
  background: rgba(255, 255, 255, 0.3) !important;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

/* Filter Card Styles */
.filter-card {
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  background-color: rgb(var(--v-theme-surface));
}

.filter-header {
  padding: 1.5rem;
}

.filter-title-section {
  display: flex;
  align-items: center;
}

.reset-btn {
  font-weight: 600;
  transition: all 0.3s ease;
}

.reset-btn:hover {
  transform: scale(1.05);
}

.filter-select,
.filter-date-picker {
  transition: all 0.3s ease;
}

.filter-select:hover,
.filter-date-picker:hover {
  transform: translateY(-2px);
}

/* Form Controls Dark Mode */
.filter-select :deep(.v-field),
.filter-date-picker :deep(.v-field),
.search-field :deep(.v-field),
.pagination-select :deep(.v-field) {
  background-color: rgb(var(--v-theme-surface));
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  border-radius: 8px;
}

/* Data Table Card Styles */
.data-table-card {
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  overflow: hidden;
  width: 100%; /* Đảm bảo card sử dụng toàn bộ chiều rộng */
  min-width: 1400px; /* Chiều rộng tối thiểu để hiển thị đủ các cột */
  background-color: rgb(var(--v-theme-surface));
}

.table-header {
  border-bottom: 1px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  padding: 1.5rem;
}

.search-field {
  transition: all 0.3s ease;
}

.search-field:hover {
  transform: translateY(-1px);
}

.column-btn {
  font-weight: 600;
  transition: all 0.3s ease;
}

.column-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.export-selected-btn {
  font-weight: 600;
  transition: all 0.3s ease;
}

.export-selected-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

/* Selection Summary Styles */
.selection-summary {
  background: rgba(var(--v-theme-success), 0.1);
  border-bottom: 1px solid rgba(var(--v-theme-success), 0.3);
  padding: 1rem 1.5rem;
}

.selection-info {
  display: flex;
  align-items: center;
  font-size: 1.1rem;
}

.select-all-btn,
.clear-selection-btn {
  font-weight: 600;
  transition: all 0.3s ease;
}

.select-all-btn:hover,
.clear-selection-btn:hover {
  transform: scale(1.05);
}

/* Data Table Styles */
.payment-data-table {
  border-radius: 0;
  width: 100%; /* Đảm bảo datatable sử dụng toàn bộ chiều rộng */
  min-width: 1400px; /* Chiều rộng tối thiểu */
}

.payment-data-table :deep(.v-data-table__th) {
  color: rgba(
    var(--v-theme-on-surface),
    var(--v-high-emphasis-opacity)
  ) !important;
  font-weight: 600 !important;
  font-size: 0.75rem !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  border-bottom: 1px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity)) !important;
}

.payment-data-table :deep(.v-data-table__td) {
  color: rgba(
    var(--v-theme-on-surface),
    var(--v-high-emphasis-opacity)
  ) !important;
  padding: 12px 8px !important;
}

.payment-data-table :deep(.v-data-table__wrapper) {
  border-radius: 0;
  width: 100%;
  overflow-x: auto; /* Cho phép scroll ngang nếu cần */
}

.payment-data-table :deep(.v-data-table) {
  width: 100%;
  min-width: 1400px; /* Đảm bảo bảng có đủ chiều rộng */
}

.payment-data-table :deep(.v-data-table__thead) {
  width: 100%;
}

.payment-data-table :deep(.v-data-table__tbody) {
  width: 100%;
}

.payment-data-table :deep(.v-data-table__tr) {
  width: 100%;
}

.payment-data-table :deep(.v-data-table__tr:hover) {
  background: rgba(var(--v-theme-primary), 0.04) !important;
  transition: all 0.3s ease;
}

/* Định nghĩa chiều rộng cho từng cột */
.payment-data-table :deep(.v-data-table__th),
.payment-data-table :deep(.v-data-table__td) {
  white-space: nowrap; /* Ngăn text bị xuống dòng */
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 12px 8px !important;
  min-width: 100px; /* Chiều rộng tối thiểu cho mỗi cột */
}

/* Chiều rộng cụ thể cho từng loại cột */
.payment-data-table :deep(.v-data-table__th:nth-child(1)), /* Mã TT */
.payment-data-table :deep(.v-data-table__td:nth-child(1)) {
  min-width: 80px;
  max-width: 80px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(2)), /* Mã đặt phòng */
.payment-data-table :deep(.v-data-table__td:nth-child(2)) {
  min-width: 150px;
  max-width: 150px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(3)), /* Nguồn */
.payment-data-table :deep(.v-data-table__td:nth-child(3)) {
  min-width: 150px;
  max-width: 150px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(4)), /* Khách hàng */
.payment-data-table :deep(.v-data-table__td:nth-child(4)) {
  min-width: 150px;
  max-width: 150px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(5)), /* Số tiền */
.payment-data-table :deep(.v-data-table__td:nth-child(5)) {
  min-width: 150px;
  max-width: 150px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(6)), /* Phòng */
.payment-data-table :deep(.v-data-table__td:nth-child(6)) {
  min-width: 100px;
  max-width: 100px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(7)), /* Phương thức */
.payment-data-table :deep(.v-data-table__td:nth-child(7)) {
  min-width: 150px;
  max-width: 150px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(8)), /* Nhận phòng */
.payment-data-table :deep(.v-data-table__td:nth-child(8)) {
  min-width: 150px;
  max-width: 150px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(9)), /* Trả phòng */
.payment-data-table :deep(.v-data-table__td:nth-child(9)) {
  min-width: 150px;
  max-width: 150px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(10)), /* Ngày thanh toán */
.payment-data-table :deep(.v-data-table__td:nth-child(10)) {
  min-width: 130px;
  max-width: 130px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(11)), /* Nhân viên */
.payment-data-table :deep(.v-data-table__td:nth-child(11)) {
  min-width: 150px;
  max-width: 150px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(12)), /* Nội dung */
.payment-data-table :deep(.v-data-table__td:nth-child(12)) {
  min-width: 150px;
  max-width: 150px;
}

.payment-data-table :deep(.v-data-table__th:nth-child(13)), /* Actions */
.payment-data-table :deep(.v-data-table__td:nth-child(13)) {
  min-width: 100px;
  max-width: 100px;
}

/* Cải thiện hiển thị cho các cell có nội dung dài */
.payment-data-table :deep(.v-data-table__td) {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  vertical-align: middle;
}

/* Responsive cho màn hình nhỏ */
@media (max-width: 1400px) {
  .data-table-card {
    min-width: 100%;
    overflow-x: auto;
  }

  .payment-data-table {
    min-width: 1400px;
  }
}

/* Custom scrollbar cho horizontal scroll */
.payment-data-table :deep(.v-data-table__wrapper)::-webkit-scrollbar {
  height: 8px;
}

.payment-data-table :deep(.v-data-table__wrapper)::-webkit-scrollbar-track {
  background: rgba(var(--v-theme-on-surface), 0.1);
  border-radius: 4px;
}

.payment-data-table :deep(.v-data-table__wrapper)::-webkit-scrollbar-thumb {
  background: rgb(var(--v-theme-primary));
  border-radius: 4px;
}

.payment-data-table
  :deep(.v-data-table__wrapper)::-webkit-scrollbar-thumb:hover {
  background: rgb(var(--v-theme-primary-darken-1));
}

/* Table Cell Styles */
.room-info {
  padding: 0.75rem;
}

.room-item {
  display: flex;
  align-items: center;
  margin-bottom: 0.25rem;
  padding: 0.25rem 0.5rem;
  background: rgba(25, 118, 210, 0.1);
  border-radius: 6px;
  font-size: 0.9rem;
}

.payment-id,
.booking-code {
  display: flex;
  justify-content: start;
}

.customer-info {
  display: flex;
  align-items: center;

  font-weight: 500;
}

.amount-display {
  text-align: center;
  font-size: 1.1rem;
}

.ota-info {
  display: flex;
  justify-content: start;
}

.date-info {
  display: flex;
  align-items: center;
  font-weight: 500;
}

.view-link {
  text-decoration: none;
}

.view-btn {
  font-weight: 600;
  transition: all 0.3s ease;
}

.view-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(25, 118, 210, 0.3);
}

/* Summary Row Styles */
.summary-row {
  background: rgba(var(--v-theme-success), 0.1) !important;
  border-top: 2px solid rgb(var(--v-theme-success));
}

.summary-label {
  color: rgb(var(--v-theme-success));
  font-size: 1.2rem;
}

.summary-amount {
  text-align: center;
  padding: 1rem;
}

/* Dialog Styles */
.export-dialog {
  border-radius: 16px;
  overflow: hidden;
}

.export-dialog-title {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1.5rem;
  display: flex;
  align-items: center;
}

.export-dialog-content {
  padding: 1.5rem;
  font-size: 1.1rem;
  line-height: 1.6;
}

.confirm-btn {
  font-weight: 600;
  transition: all 0.3s ease;
}

.confirm-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.cancel-btn {
  font-weight: 600;
  transition: all 0.3s ease;
}

.cancel-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
  .payment-title {
    font-size: 1.5rem !important;
  }

  .payment-subtitle {
    font-size: 1rem;
  }

  .header-text-section {
    margin-bottom: 1rem;
  }

  .export-btn {
    width: 100%;
  }
}

/* Animation Effects */
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

.payment-header-card,
.filter-card,
.data-table-card {
  animation: fadeInUp 0.6s ease-out;
}

/* Hover Effects */
.filter-card:hover,
.data-table-card:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
  transition: all 0.3s ease;
}

/* Custom Scrollbar */
.payment-data-table :deep(.v-data-table__wrapper)::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.payment-data-table :deep(.v-data-table__wrapper)::-webkit-scrollbar-track {
  background: rgba(var(--v-theme-on-surface), 0.1);
  border-radius: 4px;
}

.payment-data-table :deep(.v-data-table__wrapper)::-webkit-scrollbar-thumb {
  background: rgb(var(--v-theme-primary));
  border-radius: 4px;
}

.payment-data-table
  :deep(.v-data-table__wrapper)::-webkit-scrollbar-thumb:hover {
  background: rgb(var(--v-theme-primary-darken-1));
}
</style>
