<template>
  <Head title="Quản lý thu chi | Room Rise" />
  <Layout>
    <VCard elevation="0" class="dashboard-container">
      <!-- Header Section -->
      <VCardTitle
        class="d-flex align-center justify-space-between pa-6 bg-gradient-primary text-white header-section"
      >
        <div class="d-flex align-center">
          <div class="icon-container me-3">
            <VIcon icon="tabler-chart-pie" size="32" class="header-icon" />
          </div>
          <div>
            <div class="text-h4 font-weight-bold text-white">
              Quản lý thu chi
            </div>
            <div class="text-body-1 opacity-90 text-white mt-1">
              Theo dõi và quản lý toàn bộ giao dịch thu chi
            </div>
          </div>
        </div>
        <div class="d-flex gap-3">
          <VBtn
            variant="elevated"
            color="white"
            class="text-primary action-btn"
            @click="handleExport"
            prepend-icon="tabler-download"
          >
            Xuất Excel
          </VBtn>
          <VBtn
            variant="elevated"
            color="warning"
            class="action-btn"
            @click="addNewItem"
            prepend-icon="tabler-plus"
          >
            Tạo phiếu chi
          </VBtn>
        </div>
      </VCardTitle>

      <!-- Legend Section -->
      <div class="pa-4 bg-gradient-light border-b legend-section">
        <div class="d-flex gap-4 align-center flex-wrap">
          <div class="d-flex align-center legend-item">
            <VIcon
              icon="tabler-circle-filled"
              color="success"
              size="16"
              class="me-2"
            />
            <span class="text-body-2">Phiếu thu tự sinh</span>
          </div>
          <div class="d-flex align-center legend-item">
            <VIcon
              icon="tabler-circle-filled"
              color="error"
              size="16"
              class="me-2"
            />
            <span class="text-body-2">Phiếu chi tạo tay</span>
          </div>
          <div class="d-flex align-center legend-item">
            <VIcon
              icon="tabler-chart-bar"
              color="info"
              size="16"
              class="me-2"
            />
            <span class="text-body-2">Báo cáo tổng hợp</span>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="pa-6 stats-section">
        <VRow>
          <VCol cols="12" md="3">
            <VCard
              class="stats-card success-card"
              color="success"
              variant="tonal"
              elevation="2"
            >
              <VCardText class="text-center pa-6">
                <div class="icon-wrapper success mb-3">
                  <VIcon
                    icon="tabler-trending-up"
                    size="48"
                    class="stats-icon"
                    color="success"
                  />
                </div>
                <div class="text-h4 font-weight-bold text-success mb-1">
                  {{ formatCurrency(totalIncome) }}
                </div>
                <div class="text-subtitle-1">Tổng thu</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" md="3">
            <VCard
              class="stats-card error-card"
              color="error"
              variant="tonal"
              elevation="2"
            >
              <VCardText class="text-center pa-6">
                <div class="icon-wrapper error mb-3">
                  <VIcon
                    icon="tabler-trending-down"
                    size="48"
                    class="stats-icon"
                    color="error"
                  />
                </div>
                <div class="text-h4 font-weight-bold text-error mb-1">
                  {{ formatCurrency(totalExpense) }}
                </div>
                <div class="text-subtitle-1">Tổng chi</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" md="3">
            <VCard
              class="stats-card warning-card"
              color="warning"
              variant="tonal"
              elevation="2"
            >
              <VCardText class="text-center pa-6">
                <div class="icon-wrapper warning mb-3">
                  <VIcon
                    icon="tabler-calculator"
                    size="48"
                    class="stats-icon"
                    color="warning"
                  />
                </div>
                <div class="text-h4 font-weight-bold text-warning mb-1">
                  {{ formatCurrency(netAmount) }}
                </div>
                <div class="text-subtitle-1">Số dư ròng</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" md="3">
            <VCard
              class="stats-card info-card"
              color="info"
              variant="tonal"
              elevation="2"
            >
              <VCardText class="text-center pa-6">
                <div class="icon-wrapper info mb-3">
                  <VIcon
                    icon="tabler-files"
                    size="48"
                    class="stats-icon"
                    color="info"
                  />
                </div>
                <div class="text-h4 font-weight-bold text-info mb-1">
                  {{ count }}
                </div>
                <div class="text-subtitle-1">Tổng phiếu</div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </div>

      <!-- Filter and Actions Bar -->
      <VCardText class="pa-6 bg-gradient-light filter-section">
        <!-- Type Filter Buttons -->
        <div class="d-flex gap-2 mb-4 flex-wrap">
          <VBtn
            @click="selectedType = 'Tất cả'"
            :variant="selectedType === 'Tất cả' ? 'flat' : 'outlined'"
            :color="selectedType === 'Tất cả' ? 'info' : 'secondary'"
            prepend-icon="tabler-list"
            class="filter-btn"
          >
            Tất cả
          </VBtn>
          <VBtn
            @click="selectedType = 'income'"
            :variant="selectedType === 'income' ? 'flat' : 'outlined'"
            :color="selectedType === 'income' ? 'success' : 'secondary'"
            prepend-icon="tabler-plus"
            class="filter-btn"
          >
            Phiếu thu
          </VBtn>
          <VBtn
            @click="selectedType = 'expense'"
            :variant="selectedType === 'expense' ? 'flat' : 'outlined'"
            :color="selectedType === 'expense' ? 'error' : 'secondary'"
            prepend-icon="tabler-minus"
            class="filter-btn"
          >
            Phiếu chi
          </VBtn>
          <VSpacer />
          <VBtn
            :variant="filter ? 'flat' : 'outlined'"
            :color="filter ? 'primary' : 'secondary'"
            @click="toggleFilter"
            prepend-icon="tabler-adjustments"
            class="filter-btn"
          >
            {{ filter ? "Ẩn bộ lọc" : "Hiện bộ lọc" }}
          </VBtn>
          <VBtn
            v-if="filter"
            variant="outlined"
            color="secondary"
            @click="resetFilter"
            prepend-icon="tabler-refresh"
            class="filter-btn"
          >
            Reset
          </VBtn>
        </div>

        <!-- Advanced Filters -->
        <VExpandTransition>
          <VCard v-show="filter" variant="outlined" class="pa-4 filter-card">
            <VCardTitle class="d-flex align-center pa-0 pb-4">
              <div class="filter-icon-wrapper me-2">
                <VIcon icon="tabler-filter" color="primary" />
              </div>
              <span>Bộ lọc nâng cao</span>
            </VCardTitle>
            <VRow>
              <VCol cols="12" md="3">
                <AppDateTimePicker
                  v-model="range_date"
                  label="Khoảng thời gian"
                  placeholder="Chọn ngày"
                  prepend-inner-icon="tabler-calendar"
                  :config="{ mode: 'range' }"
                />
              </VCol>
              <VCol cols="12" md="3">
                <AppSelect
                  v-model="selectedCategory"
                  :items="categoriesOptions"
                  label="Danh mục"
                  prepend-inner-icon="tabler-category"
                />
              </VCol>
              <VCol cols="12" md="3">
                <AppSelect
                  v-model="selectedPayment"
                  :items="paymentOptions"
                  label="Phương thức thanh toán"
                  prepend-inner-icon="tabler-credit-card"
                />
              </VCol>
              <VCol cols="12" md="3">
                <AppTextField
                  v-model="selectedCreatedBy"
                  label="Người tạo"
                  prepend-inner-icon="tabler-user"
                />
              </VCol>
            </VRow>
          </VCard>
        </VExpandTransition>
      </VCardText>

      <!-- Data Table Section -->
      <VCardText class="pa-0 table-section">
        <VCard variant="outlined" class="ma-6 table-container">
          <!-- Table Header -->
          <div
            class="d-flex align-center justify-space-between pa-4 border-b table-header"
          >
            <div class="d-flex align-center gap-2">
              <div class="table-icon-wrapper">
                <VIcon icon="tabler-table" color="primary" />
              </div>
              <span class="text-h6">Danh sách giao dịch</span>
              <VChip
                color="primary"
                size="small"
                variant="tonal"
                class="count-chip"
              >
                {{ data.total }} giao dịch
              </VChip>
            </div>
            <div class="d-flex align-center gap-2">
              <span class="text-body-2">Hiển thị</span>
              <AppSelect
                :model-value="itemsPerPage"
                :items="[
                  { value: 10, title: '10' },
                  { value: 25, title: '25' },
                  { value: 50, title: '50' },
                  { value: 100, title: '100' },
                  { value: -1, title: 'Tất cả' },
                ]"
                style="inline-size: 100px"
                variant="outlined"
                density="compact"
                @update:model-value="itemsPerPage = parseInt($event, 10)"
              />
            </div>
          </div>

          <!-- Enhanced Data Table -->
          <VDataTableServer
            v-model:items-per-page="itemsPerPage"
            v-model:page="page"
            v-model="selectedIds"
            :items-length="data.total"
            :headers="enhancedHeaders"
            :items="data.data"
            item-value="id"
            class="modern-table enhanced-table"
            @update:options="updateOptions"
            show-select
            :loading="false"
            loading-text="Đang tải dữ liệu..."
          >
            <template #item.id="{ item }">
              <div class="d-flex align-center">
                <span class="font-weight-medium">{{ item.id }}</span>
              </div>
            </template>

            <template #item.date="{ item }">
              <div class="date-cell">
                <VIcon
                  icon="tabler-calendar"
                  size="14"
                  class="me-1 text-medium-emphasis"
                />
                <span class="date-text">{{ formatDate(item.date) }}</span>
              </div>
            </template>

            <template #item.type="{ item }">
              <VChip
                :color="item.type === 'income' ? 'success' : 'error'"
                :prepend-icon="
                  item.type === 'income' ? 'tabler-plus' : 'tabler-minus'
                "
                variant="flat"
                size="small"
                class="type-chip"
              >
                {{ item.type === "income" ? "Thu (Auto)" : "Chi (Tay)" }}
              </VChip>
            </template>

            <template #item.category="{ item }">
              <div class="py-2">
                <div class="font-weight-medium">{{ item.category }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ item.subcategory }}
                </div>
              </div>
            </template>

            <template #item.amount="{ item }">
              <VChip
                :color="item.type === 'income' ? 'success' : 'error'"
                :prepend-icon="
                  item.type === 'income' ? 'tabler-plus' : 'tabler-minus'
                "
                variant="tonal"
                size="small"
                class="font-weight-bold amount-chip"
              >
                {{ formatCurrency(item.amount) }}
              </VChip>
            </template>

            <template #item.payment_status="{ item }">
              <VChip
                :color="
                  item.payment_status === 'Đã thanh toán'
                    ? 'success'
                    : 'warning'
                "
                :prepend-icon="
                  item.payment_status === 'Đã thanh toán'
                    ? 'tabler-check'
                    : 'tabler-clock'
                "
                variant="flat"
                size="small"
                class="status-chip"
              >
                {{ item.payment_status }}
              </VChip>
            </template>

            <template #item.booking_id="{ item }">
              <div class="d-flex gap-1 flex-wrap">
                <template v-if="item.partner_bookings?.length">
                  <VChip
                    v-for="(booking, index) in item.partner_bookings.slice(
                      0,
                      2
                    )"
                    :key="booking.id"
                    color="info"
                    size="x-small"
                    variant="outlined"
                    class="booking-chip"
                  >
                    {{ booking.id }}
                  </VChip>
                  <VChip
                    v-if="item.partner_bookings.length > 2"
                    color="secondary"
                    size="x-small"
                    variant="tonal"
                  >
                    +{{ item.partner_bookings.length - 2 }}
                  </VChip>
                </template>
                <template v-else-if="item.booking_id">
                  <VChip
                    color="info"
                    size="x-small"
                    variant="outlined"
                    class="booking-chip"
                  >
                    {{ item.booking_id }}
                  </VChip>
                </template>
                <template v-else>
                  <VChip color="secondary" size="x-small" variant="text">
                    --
                  </VChip>
                </template>
              </div>
            </template>

            <template #item.business_type="{ item }">
              <VChip
                v-if="item.business_type"
                :color="getBusinessTypeColor(item.business_type)"
                size="small"
                variant="tonal"
                class="business-chip"
              >
                {{ item.business_type }}
              </VChip>
            </template>

            <template #item.room_payment_method="{ item }">
              <VTooltip location="top" v-if="item.room_payment_method">
                <template #activator="{ props }">
                  <VChip
                    v-bind="props"
                    color="info"
                    size="small"
                    variant="outlined"
                    class="payment-method-chip"
                  >
                    {{ item.room_payment_method }}
                  </VChip>
                </template>
                <div class="text-sm space-y-1">
                  <template
                    v-if="item.partner_bookings && item.partner_bookings.length"
                  >
                    <div>Chi tiết PTTT:</div>
                    <div
                      v-for="booking in item.partner_bookings"
                      :key="'partner-' + booking.id"
                    >
                      {{ booking.id }}: {{ booking.payment_type }}
                    </div>
                  </template>
                  <template v-else-if="item.booking">
                    <div>Chi tiết PTTT:</div>
                    <div>
                      {{ item.booking.id }}: {{ item.booking.payment_type }}
                    </div>
                  </template>
                  <template v-else>
                    <div class="text-gray-500 italic">Không có booking</div>
                  </template>
                </div>
              </VTooltip>
            </template>

            <template #item.actions="{ item }">
              <div class="d-flex gap-1">
                <VBtn
                  icon="tabler-eye"
                  size="small"
                  color="info"
                  variant="tonal"
                  @click="showDetail(item)"
                  class="action-button"
                >
                </VBtn>
                <template v-if="item.type === 'expense'">
                  <VBtn
                    icon="tabler-edit"
                    size="small"
                    color="warning"
                    variant="tonal"
                    @click="editDetail(item)"
                    class="action-button"
                  >
                  </VBtn>
                  <VBtn
                    icon="tabler-trash"
                    size="small"
                    color="error"
                    variant="tonal"
                    @click="deleteItem(item)"
                    class="action-button"
                  >
                  </VBtn>
                </template>
                <template v-else>
                  <VBtn
                    icon="tabler-lock"
                    size="small"
                    color="grey"
                    disabled
                    variant="tonal"
                  >
                  </VBtn>
                  <VBtn
                    icon="tabler-lock"
                    size="small"
                    color="grey"
                    disabled
                    variant="tonal"
                  >
                  </VBtn>
                </template>
              </div>
            </template>

            <template #bottom>
              <div
                class="d-flex align-center justify-space-between pa-4 border-t table-footer"
              >
                <div class="text-body-2 text-medium-emphasis">
                  Hiển thị {{ data.data.length }} trong tổng số
                  {{ data.total }} giao dịch
                </div>
                <TablePagination
                  v-model:page="page"
                  :items-per-page="itemsPerPage"
                  :total-items="data.total"
                />
              </div>
            </template>
          </VDataTableServer>
        </VCard>
      </VCardText>
    </VCard>

    <!-- Dialogs -->
    <IncomeExpenseFormDialog
      v-model:is-dialog-visible="isFormDialogVisible"
      :partner-group="partnerGroup"
      @update-data="handleUpdateData"
    />
    <Detail
      v-model:is-detail-dialog-visible="isDetailDialogVisible"
      :data="selectedData"
      @update-data="handleUpdateData"
    />
    <IncomeExpenseFormDialog
      v-model:is-dialog-visible="isFormEditDialogVisible"
      :data="dataExpense"
      @update-data="handleUpdateData"
    />
    <AppConfirmDialog @on-submit="handleDelete" />
  </Layout>
</template>

<script setup>
import IncomeExpenseFormDialog from "@/Components/incomeexpense/IncomeExpenseFormDialog.vue";
import AppConfirmDialog from "@/Components/shared/AppConfirmDialog.vue";
import {
  isOpenAppConfirmDialog,
  loadingAppConfirmDialog,
} from "@/composables/dialog";
import Layout from "@/layouts/blank.vue";
import { formatCurrency, formatDate } from "@/utils/formatters";
import { Head, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { onMounted, ref, watch } from "vue";
import Detail from "./Detail.vue";

const props = defineProps({
  data: Object,
  filters: Object,
  totalIncome: Number,
  totalExpense: String,
  netAmount: Number,
  count: Number,
  partnerGroup: Object,
});

// Enhanced headers
const enhancedHeaders = [
  { title: "Mã", key: "id", sortable: false, width: "80px" },
  { title: "Ngày", key: "date", sortable: false, width: "120px" },
  { title: "Loại", key: "type", sortable: false, width: "120px" },
  { title: "Danh mục", key: "category", sortable: false, width: "150px" },
  {
    title: "PTTT Phòng",
    key: "room_payment_method",
    sortable: false,
    width: "120px",
  },
  {
    title: "Phương thức",
    key: "payment_method",
    sortable: false,
    width: "120px",
  },
  { title: "Nguồn", key: "payment_source", sortable: false, width: "100px" },
  {
    title: "Đối tượng",
    key: "payment_object",
    sortable: false,
    width: "120px",
  },
  { title: "Booking", key: "booking_id", sortable: false, width: "100px" },
  { title: "Nghiệp vụ", key: "business_type", sortable: false, width: "120px" },
  { title: "Số tiền", key: "amount", sortable: false, width: "120px" },
  {
    title: "Trạng thái",
    key: "payment_status",
    sortable: false,
    width: "120px",
  },
  { title: "Người tạo", key: "created_by", sortable: false, width: "120px" },
  { title: "Thao tác", key: "actions", sortable: false, width: "120px" },
];

// Reactive variables
const isFormDialogVisible = ref(false);
const isFormEditDialogVisible = ref(false);
const isDetailDialogVisible = ref(false);
const range_date = ref("");
const selectedData = ref();
const filter = ref(false);
const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);
const selectedType = ref("Tất cả");
const selectedIds = ref([]);
const selectedCategory = ref("Tất cả danh mục");
const selectedPayment = ref("Tất cả hình thức");
const selectedCreatedBy = ref("");
const dataExpense = ref();

// Options
const categoriesOptions = [
  "Tất cả danh mục",
  "Doanh thu",
  "Thu nhập khác",
  "Chi phí vận hành",
  "Chi phí nhân sự",
  "Hoa hồng & Marketing",
  "Chi phí cố định",
];

const paymentOptions = [
  "Tất cả hình thức",
  "Tiền mặt",
  "Chuyển khoản",
  "QR Code",
  "9Pay",
  "Momo",
  "VNPay",
  "Thẻ tín dụng",
];

// Methods
// const formatDate = (date) => {
//   if (!date) return "--";
//   return new Date(date).toLocaleDateString("vi-VN");
// };

const fetchData = () => {
  router.get(
    route("income-and-expense.index"),
    {
      page: page.value,
      range_date: range_date.value,
      category:
        selectedCategory.value !== "Tất cả danh mục"
          ? selectedCategory.value
          : null,
      payment:
        selectedPayment.value !== "Tất cả hình thức"
          ? selectedPayment.value
          : null,
      staff: selectedCreatedBy.value || null,
      type: selectedType.value !== "Tất cả" ? selectedType.value : null,
      paginate: itemsPerPage.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

const handleUpdateData = () => {
  fetchData();
};

const handleExport = () => {
  const exportUrl = route("income-and-expense.export", {
    selectedType: selectedType.value !== "Tất cả" ? selectedType.value : null,
    range_date: range_date.value,
    selectedCategory:
      selectedCategory.value === "Tất cả danh mục"
        ? null
        : selectedCategory.value,
    selectedPayment:
      selectedPayment.value === "Tất cả hình thức"
        ? null
        : selectedPayment.value,
    selectedCreatedBy: selectedCreatedBy.value,
    selectedIds: selectedIds.value.length ? selectedIds.value : null,
  });
  window.location.href = exportUrl;
};

const updateOptions = (options) => {
  page.value = options.page;
  itemsPerPage.value = options.itemsPerPage;
};

const addNewItem = () => {
  isFormDialogVisible.value = true;
};

const showDetail = (item) => {
  selectedData.value = item;
  isDetailDialogVisible.value = true;
};

const editDetail = (item) => {
  isFormEditDialogVisible.value = true;
  dataExpense.value = item;
};

const deleteItem = async (item) => {
  selectedData.value = item;
  isOpenAppConfirmDialog.value = true;
};

const handleDelete = () => {
  router.delete(
    route("income-and-expense.destroy", { id: selectedData.value.id }),
    {
      onStart: () => {
        loadingAppConfirmDialog.value = true;
      },
      onFinish: () => {
        loadingAppConfirmDialog.value = false;
        isOpenAppConfirmDialog.value = false;
      },
    }
  );
};

const resetFilter = () => {
  selectedType.value = "Tất cả";
  range_date.value = "";
  selectedCategory.value = "Tất cả danh mục";
  selectedPayment.value = "Tất cả hình thức";
  selectedCreatedBy.value = "";
  fetchData();
};

const toggleFilter = () => {
  filter.value = !filter.value;
};

const getBusinessTypeColor = (type) => {
  switch (type) {
    case "Đặt phòng":
      return "info";
    case "Đối soát đối tác":
      return "warning";
    case "Nhập tay":
      return "secondary";
    case "Quyết toán OTA":
      return "primary";
    case "Hủy đặt phòng":
      return "error";
    default:
      return "secondary";
  }
};

// Watchers
onMounted(fetchData);
watch(selectedType, debounce(fetchData, 300));
watch(range_date, debounce(fetchData, 300));
watch(selectedCategory, debounce(fetchData, 300));
watch(selectedPayment, debounce(fetchData, 300));
watch(selectedCreatedBy, debounce(fetchData, 300));
watch([page, itemsPerPage], fetchData);
</script>

<style scoped>
/* Header Section */
.header-section {
  background: linear-gradient(
    135deg,
    rgb(var(--v-theme-primary)) 0%,
    rgb(var(--v-theme-primary-darken-1)) 50%,
    rgb(var(--v-theme-primary-darken-2)) 100%
  );
  position: relative;
  overflow: hidden;
}

.header-section::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  pointer-events: none;
}

.icon-container {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  padding: 8px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  transition: all 0.3s ease;
}

.icon-container:hover {
  transform: scale(1.1);
  background: rgba(255, 255, 255, 0.3);
}

.header-icon {
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.action-btn {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 8px;
  font-weight: 600;
  text-transform: none;
  letter-spacing: 0.5px;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

/* Legend Section */
.legend-section {
  background-color: rgb(var(--v-theme-surface));
  border-bottom: 2px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}

.legend-item {
  transition: all 0.3s ease;
  padding: 6px 12px;
  border-radius: 6px;
  background: rgba(var(--v-theme-on-surface), 0.05);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

.legend-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
  background: rgba(var(--v-theme-on-surface), 0.1);
}

/* Stats Section */
.stats-section {
  background-color: rgb(var(--v-theme-surface));
}

.stats-card {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 16px;
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  overflow: hidden;
  position: relative;
  background-color: rgb(var(--v-theme-surface));
}

.stats-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(
    90deg,
    transparent 0%,
    currentColor 50%,
    transparent 100%
  );
  opacity: 0;
  transition: opacity 0.3s ease;
}

.stats-card:hover::before {
  opacity: 1;
}

.stats-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
}

.success-card:hover {
  box-shadow: 0 15px 35px rgba(var(--v-theme-success), 0.15);
}

.error-card:hover {
  box-shadow: 0 15px 35px rgba(var(--v-theme-error), 0.15);
}

.warning-card:hover {
  box-shadow: 0 15px 35px rgba(var(--v-theme-warning), 0.15);
}

.info-card:hover {
  box-shadow: 0 15px 35px rgba(var(--v-theme-info), 0.15);
}

.icon-wrapper {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  transition: all 0.3s ease;
}

.icon-wrapper.success {
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-success), 0.1) 0%,
    rgba(var(--v-theme-success), 0.2) 100%
  );
}

.icon-wrapper.error {
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-error), 0.1) 0%,
    rgba(var(--v-theme-error), 0.2) 100%
  );
}

.icon-wrapper.warning {
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-warning), 0.1) 0%,
    rgba(var(--v-theme-warning), 0.2) 100%
  );
}

.icon-wrapper.info {
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-info), 0.1) 0%,
    rgba(var(--v-theme-info), 0.2) 100%
  );
}

.stats-card:hover .icon-wrapper {
  transform: scale(1.1);
}

.stats-icon {
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

/* Filter Section */
.filter-section {
  background-color: rgb(var(--v-theme-surface));
}

.filter-btn {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 8px;
  font-weight: 600;
  text-transform: none;
  letter-spacing: 0.5px;
}

.filter-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.filter-card {
  border-radius: 12px;
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface));
  backdrop-filter: blur(10px);
}

.filter-icon-wrapper {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-primary), 0.1) 0%,
    rgba(var(--v-theme-primary), 0.2) 100%
  );
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Table Section */
.table-section {
  /* background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); */
}

.table-container {
  border-radius: 16px;
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  background-color: rgb(var(--v-theme-surface));
}

.table-header {
  border-bottom: 2px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}

.table-icon-wrapper {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  /* background: linear-gradient(
    135deg,
    rgba(var(--v-theme-primary), 0.1) 0%,
    rgba(var(--v-theme-primary), 0.2) 100%
  ); */
  display: flex;
  align-items: center;
  justify-content: center;
}

.count-chip {
  font-weight: 600;
  border-radius: 16px;
}

.table-footer {
  border-top: 2px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}

/* Enhanced Table Styles */
.enhanced-table :deep(.v-data-table__tr) {
  transition: all 0.3s ease;
  border-radius: 6px;
  margin: 1px 0;
}

.enhanced-table :deep(.v-data-table__tr:hover) {
  background: rgba(var(--v-theme-primary), 0.04) !important;
  transform: translateX(3px);
  box-shadow: 0 2px 8px rgba(var(--v-theme-primary), 0.1);
}

.enhanced-table :deep(th) {
  font-weight: 700;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  border-bottom: 2px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.875rem;
  white-space: nowrap;
}

.enhanced-table :deep(td) {
  border-bottom: 1px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  padding: 12px 8px;
  white-space: nowrap;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

/* Date column specific styles */
.date-cell {
  display: flex;
  align-items: center;
  white-space: nowrap;
  min-width: 120px;
}

/* Chip Styles */
.type-chip,
.status-chip,
.amount-chip,
.booking-chip,
.business-chip,
.payment-method-chip {
  transition: all 0.3s ease;
  border-radius: 6px;
  font-weight: 600;
}

.type-chip:hover,
.status-chip:hover,
.amount-chip:hover,
.booking-chip:hover,
.business-chip:hover,
.payment-method-chip:hover {
  transform: scale(1.05);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Action Buttons */
.action-button {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 6px;
}

.action-button:hover {
  transform: scale(1.1);
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
}

/* Utility Classes */
.bg-gradient-primary {
  background: linear-gradient(
    135deg,
    rgb(var(--v-theme-primary)) 0%,
    rgb(var(--v-theme-primary-darken-1)) 100%
  );
}

.bg-gradient-light {
  background-color: rgb(var(--v-theme-surface));
}

.border-b {
  border-bottom: 1px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}

.border-t {
  border-top: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}

/* Responsive Design */
@media (max-width: 768px) {
  .stats-card:hover {
    transform: translateY(-3px) scale(1.01);
  }

  .action-btn:hover {
    transform: translateY(-1px);
  }

  .filter-btn:hover {
    transform: translateY(-1px);
  }

  .enhanced-table :deep(.v-data-table__tr:hover) {
    transform: translateX(2px);
  }
}

/* Animation Keyframes */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.02);
  }
}

/* Apply animations */
.stats-card {
  animation: fadeInUp 0.5s ease-out;
}

.stats-card:nth-child(1) {
  animation-delay: 0.1s;
}
.stats-card:nth-child(2) {
  animation-delay: 0.2s;
}
.stats-card:nth-child(3) {
  animation-delay: 0.3s;
}
.stats-card:nth-child(4) {
  animation-delay: 0.4s;
}

/* Loading states */
.enhanced-table :deep(.v-data-table__tr.loading) {
  animation: pulse 1.5s infinite;
}

/* Focus states for accessibility */
.action-btn:focus,
.filter-btn:focus,
.action-button:focus {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

/* Form controls dark mode support */
.enhanced-table :deep(.v-field) {
  background-color: rgb(var(--v-theme-surface));
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

.filter-card :deep(.v-field) {
  background-color: rgb(var(--v-theme-surface));
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}
</style>
