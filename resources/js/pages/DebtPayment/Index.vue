<template>
  <Head title="Công nợ | Room Rise" />
  <Layout>
    <!-- Hero Section with Gradient Background -->
    <VCard class="hero-card mb-6" elevation="8">
      <div class="hero-gradient"></div>
      <VCardItem class="position-relative">
        <div class="d-flex justify-space-between align-center flex-wrap">
          <div class="hero-content">
            <div class="d-flex align-center mb-3">
              <VIcon icon="tabler-wallet" size="32" class="hero-icon mr-3" />
              <div>
                <h2 class="hero-title">Quản lý công nợ đối tác</h2>
                <p class="hero-subtitle">
                  Theo dõi các khoản công nợ từ đối tác Sale và Travel Agent
                </p>
              </div>
            </div>
          </div>
          <div class="stats-grid">
            <div class="stat-item stat-partners">
              <VIcon icon="tabler-users" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">Tổng số đối tác</div>
                <div class="stat-value">{{ totalPartners }}</div>
              </div>
            </div>
            <div class="stat-item stat-revenue">
              <VIcon icon="tabler-trending-up" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">Tổng doanh thu</div>
                <div class="stat-value">
                  {{ formatCurrency(totalRevenueSum) }}
                </div>
              </div>
            </div>
            <div class="stat-item stat-debt">
              <VIcon icon="tabler-receipt" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">Net công nợ</div>
                <div class="stat-value">{{ formatCurrency(netDebtSum) }}</div>
              </div>
            </div>
          </div>
        </div>
      </VCardItem>
    </VCard>

    <!-- Enhanced Filter Section -->
    <VCard class="filter-card mb-6" elevation="4">
      <div class="filter-header">
        <VIcon icon="tabler-filter" class="mr-2" />
        <span class="filter-title">Bộ lọc nâng cao</span>
      </div>
      <VCardItem>
        <VRow>
          <VCol cols="12" sm="12" md="12" lg="8">
            <VRow>
              <VCol cols="6" sm="6" md="4" lg="2">
                <div class="filter-group">
                  <AppDateTimePicker
                    label="Ngày đặt"
                    v-model="range_date"
                    placeholder="Ngày đặt"
                    :config="{ mode: 'range' }"
                    @update:model-value="fetchData"
                    class="enhanced-input"
                  />
                </div>
              </VCol>
              <VCol cols="6" sm="6" md="4" lg="2">
                <div class="filter-group">
                  <AppSelect
                    label="Đối tác"
                    v-model="partner_id"
                    :items="partnerOptions"
                    item-value="id"
                    item-title="name"
                    @update:model-value="fetchData"
                    class="enhanced-input"
                  />
                </div>
              </VCol>
              <VCol cols="6" sm="6" md="4" lg="2">
                <div class="filter-group">
                  <AppSelect
                    label="Vai trò"
                    v-model="type"
                    :items="typeOptions"
                    item-value="id"
                    item-title="name"
                    @update:model-value="fetchData"
                    class="enhanced-input"
                  />
                </div>
              </VCol>
              <VCol cols="6" sm="6" md="4" lg="2">
                <div class="filter-group">
                  <AppSelect
                    label="Trạng thái"
                    v-model="status"
                    :items="statusOptions"
                    @update:model-value="fetchData"
                    class="enhanced-input"
                  />
                </div>
              </VCol>
              <VCol cols="4" lg="4">
                <div class="d-flex h-100 flex-column justify-end">
                  <VCheckbox
                    @update:model-value="fetchData"
                    v-model="debtor_partner"
                    label="Chỉ hiển thị đối tác còn nợ"
                    class="enhanced-checkbox"
                  />
                </div>
              </VCol>
              <VCol cols="4" lg="4" class="hidden-lg-and-up">
                <div class="d-flex h-100 flex-column justify-end">
                  <VBtn
                    variant="outlined"
                    color="primary"
                    @click="resetFilters"
                    class="reset-btn"
                  >
                    <VIcon icon="tabler-refresh" class="mr-2" />
                    Làm mới
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VCol>
          <VCol
            cols="12"
            sm="12"
            md="12"
            lg="4"
            class="text-right justify-end flex-column hidden-md-and-down"
          >
            <div class="d-flex flex-column justify-end h-100 align-end">
              <VBtn
                variant="outlined"
                color="primary"
                @click="resetFilters"
                class="reset-btn-lg"
                prepend-icon="tabler-refresh"
              >
                Làm mới bộ lọc
              </VBtn>
            </div>
          </VCol>
        </VRow>
      </VCardItem>
    </VCard>

    <!-- Enhanced Charts Section -->
    <VRow class="mb-6">
      <VCol cols="12" md="6">
        <VCard class="chart-card h-100" elevation="6">
          <VCardItem>
            <div class="chart-header">
              <VIcon icon="tabler-chart-line" class="chart-icon mr-2" />
              <span class="chart-title">Biểu đồ công nợ</span>
            </div>
            <div class="chart-container">
              <NetDebtChart :data="filteredData" />
            </div>
          </VCardItem>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard class="chart-card mb-4" elevation="6">
          <VCardItem>
            <div class="chart-header">
              <VIcon icon="tabler-chart-pie" class="chart-icon mr-2" />
              <span class="chart-title">Phân bổ theo vai trò</span>
            </div>
            <div class="chart-container">
              <PartnerTypePieChart :data="filteredData" />
            </div>
          </VCardItem>
        </VCard>
        <VCard class="chart-card" elevation="6">
          <VCardItem>
            <div class="chart-header">
              <VIcon icon="tabler-chart-donut" class="chart-icon mr-2" />
              <span class="chart-title">Trạng thái công nợ</span>
            </div>
            <div class="chart-container">
              <DebtStatusChart :data="filteredData" />
            </div>
          </VCardItem>
        </VCard>
      </VCol>
    </VRow>
    <!-- Enhanced KPI Cards -->
    <VRow class="mb-6">
      <VCol cols="6" sm="6" md="6" lg="3">
        <VCard class="kpi-card kpi-partners" elevation="8">
          <div class="kpi-gradient kpi-gradient-blue"></div>
          <VCardItem class="position-relative">
            <div class="d-flex align-center">
              <div class="kpi-icon-wrapper kpi-icon-blue">
                <VIcon icon="tabler-users" size="24" />
              </div>
              <div class="ml-3">
                <div class="kpi-label">Tổng số đối tác</div>
                <div class="kpi-value kpi-value-blue">{{ totalPartners }}</div>
              </div>
            </div>
          </VCardItem>
        </VCard>
      </VCol>
      <VCol cols="6" sm="6" md="6" lg="3">
        <VCard class="kpi-card kpi-revenue" elevation="8">
          <div class="kpi-gradient kpi-gradient-success"></div>
          <VCardItem class="position-relative">
            <div class="d-flex align-center">
              <div class="kpi-icon-wrapper kpi-icon-success">
                <VIcon icon="tabler-trending-up" size="24" />
              </div>
              <div class="ml-3">
                <div class="kpi-label">Tổng doanh thu</div>
                <div class="kpi-value kpi-value-success">
                  {{ formatCurrency(totalRevenueSum) }}
                </div>
              </div>
            </div>
          </VCardItem>
        </VCard>
      </VCol>
      <VCol cols="6" sm="6" md="6" lg="3">
        <VCard class="kpi-card kpi-debtors" elevation="8">
          <div class="kpi-gradient kpi-gradient-error"></div>
          <VCardItem class="position-relative">
            <div class="d-flex align-center">
              <div class="kpi-icon-wrapper kpi-icon-error">
                <VIcon icon="tabler-alert-triangle" size="24" />
              </div>
              <div class="ml-3">
                <div class="kpi-label">Đối tác còn nợ</div>
                <div class="kpi-value kpi-value-error">{{ debtorsCount }}</div>
              </div>
            </div>
          </VCardItem>
        </VCard>
      </VCol>
      <VCol cols="6" sm="6" md="6" lg="3">
        <VCard class="kpi-card kpi-debt" elevation="8">
          <div class="kpi-gradient kpi-gradient-warning"></div>
          <VCardItem class="position-relative">
            <div class="d-flex align-center">
              <div class="kpi-icon-wrapper kpi-icon-warning">
                <VIcon icon="tabler-receipt" size="24" />
              </div>
              <div class="ml-3">
                <div class="kpi-label">Net công nợ</div>
                <div class="kpi-value kpi-value-warning">
                  {{ formatCurrency(netDebtSum) }}
                </div>
              </div>
            </div>
          </VCardItem>
        </VCard>
      </VCol>
    </VRow>

    <!-- Enhanced Data Table -->
    <VCard class="data-table-card" elevation="6">
      <div class="table-header">
        <div class="d-flex align-center">
          <VIcon icon="tabler-table" class="table-icon mr-3" />
          <div>
            <h4 class="table-title">Bảng tổng hợp công nợ đối tác</h4>
            <p class="table-subtitle">
              Chi tiết thông tin công nợ của từng đối tác
            </p>
          </div>
        </div>
      </div>
      <VCardItem>
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items-length="totalItems"
          :headers="headers"
          :items="items"
          item-value="id"
          class="enhanced-table text-no-wrap"
          @update:options="updateOptions"
        >
          <template #item.type="{ item }">
            <VChip color="info" v-if="item.type == 'Sale'">
              <VIcon icon="tabler-user" class="mr-1" /> {{ item.type }}
            </VChip>
            <VChip color="primary" v-else-if="item.type == 'Sale TA'">
              <VIcon icon="tabler-building" class="mr-1" /> {{ item.type }}
            </VChip>
          </template>
          <template #item.total_booking="{ item }">
            <div class="text-blue text-center text-h6">
              {{ item.filtered_bookings }}
            </div>
            <div>booking</div>
          </template>
          <!-- doanh thu -->
          <template #item.total_revenue="{ item }">
            <div class="text-success text-center text-h6">
              {{ formatCurrency(item.total_revenue) }}
            </div>
          </template>
          <!-- phải thu -->
          <template #item.total_receivable="{ item }">
            <div class="text-blue text-center text-h6">
              {{
                item.total_receivable >= 0
                  ? formatCurrency(item.total_receivable)
                  : "-"
              }}
            </div>
          </template>
          <!-- phải trả -->
          <template #item.total_payable="{ item }">
            <div class="text-warning text-center text-h6">
              {{
                item.total_receivable < 0
                  ? formatCurrency(Math.abs(item.total_receivable))
                  : "-"
              }}
            </div>
          </template>
          <!-- đã xử lý -->
          <template #item.processed="{ item }">
            <div class="text-center text-h6">
              {{ formatCurrency(item.total_processed) }}
            </div>
          </template>
          <!-- hoa hồng -->
          <template #item.total_commission="{ item }">
            <div class="text-center text-h6">
              {{ formatCurrency(item.total_commission) }}
            </div>
          </template>
          <!-- công nợ net -->
          <template #item.total_net_debt="{ item }">
            <div
              class="text-center text-h6"
              :class="{
                'text-error': item.total_net_debt > 0,
                'text-warning': item.total_net_debt < 0,
                'text-success': item.total_net_debt === 0,
              }"
            >
              {{ formatCurrency(item.total_net_debt) }}
            </div>
          </template>
          <!-- trạng thái công nợ -->
          <template #item.debt_status="{ item }">
            <VChip
              :color="getDebtStatusColor(item.debt_status)"
              variant="tonal"
              class="text-caption"
            >
              {{ item.debt_status }}
            </VChip>
          </template>

          <template #item.actions="{ item }">
            <VBtn
              variant="outlined"
              color="primary"
              @click="showDetailBooking(item)"
            >
              <VIcon size="22" icon="tabler-clipboard-list" class="mr-2" />
              Xem chi tiết
            </VBtn>
          </template>
          <template #bottom>
            <TablePagination
              v-model:page="page"
              :items-per-page="itemsPerPage"
              :total-items="data.total"
            />
          </template>
        </VDataTableServer>
      </VCardItem>
    </VCard>

    <DetailBooking
      v-model:is-detail-dialog-visible="isDetailBookingDialogVisible"
      :data="dataDetail"
    />
  </Layout>
</template>

<script setup>
import Layout from "@/layouts/blank.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { Head, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { computed, onMounted, ref, watch } from "vue";
import DebtStatusChart from "./DebtStatusChart.vue";
import DetailBooking from "./DetailBooking.vue";
import NetDebtChart from "./NetDebtChart.vue";
import PartnerTypePieChart from "./PartnerTypePieChart.vue";
const propertyStore = usePropertyStore();

const isDetailBookingDialogVisible = ref(false);
const dataDetail = ref();
const range_date = ref("");
const partner_id = ref(0);
const type = ref("all");
const debtor_partner = ref(false);
const reconciliation = ref("");
const status = ref("Tất cả trạng thái");
const partnerOptions = ref([]);
const props = defineProps({
  data: Object,
  filters: Object,
  totalPartners: Number,
  debtorsCount: Number,
});

const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);
const property_id = ref(
  propertyStore.selectedProperty || Number(props.filters?.property_id) || null
);

// Dữ liệu đã lọc từ server
const filteredData = computed(() => props.data.data || []);
// Tổng số bản ghi (đã lọc ở server)
const totalItems = computed(() => props.data.total || 0);
// Dữ liệu phân trang
const items = computed(() => filteredData.value);
// Tính tổng doanh thu và net công nợ cho dữ liệu đã lọc
const totalRevenueSum = computed(() =>
  filteredData.value.reduce(
    (sum, partner) => sum + (partner.total_revenue || 0),
    0
  )
);
const netDebtSum = computed(() =>
  filteredData.value.reduce(
    (sum, partner) => sum + (partner.total_net_debt || 0),
    0
  )
);

const fetchData = () => {
  router.get(
    route("debt-payment.index"),
    {
      page: page.value,
      range_date: range_date.value,
      debtor_partner: debtor_partner.value,
      type: type.value !== "all" ? type.value : null,
      partner_id: partner_id.value !== 0 ? partner_id.value : null,
      status: status.value !== "Tất cả trạng thái" ? status.value : null,
      property_id: property_id.value !== null ? property_id.value : null,
      paginate: itemsPerPage.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onSuccess: () => {
        page.value = 1;
      },
    }
  );
};

watch(
  [
    range_date,
    partner_id,
    type,
    debtor_partner,
    reconciliation,
    status,
    page,
    itemsPerPage,
  ],
  debounce(fetchData, 300)
);
watch(property_id, (val) => {
  propertyStore.setProperty(val);
  fetchData();
});

watch(
  () => propertyStore.selectedProperty,
  (val) => {
    property_id.value = val;
    fetchData();
  }
);

const updateOptions = (options) => {
  page.value = options.page;
  itemsPerPage.value = options.itemsPerPage;
};

const showDetailBooking = (item) => {
  dataDetail.value = item;
  isDetailBookingDialogVisible.value = true;
};

const headers = [
  { title: "Đối tác", key: "name", sortable: false },
  { title: "Vai trò", key: "type", sortable: false },
  { title: "Booking", key: "total_booking", sortable: false },
  { title: "Doanh thu", key: "total_revenue", sortable: false },
  { title: "Phải thu", key: "total_receivable", sortable: false },
  { title: "Phải trả", key: "total_payable", sortable: false },
  { title: "Đã xử lý", key: "processed", sortable: false },
  { title: "Hoa hồng", key: "total_commission", sortable: false },
  { title: "Net công nợ", key: "total_net_debt", sortable: false },
  { title: "Trạng thái", key: "debt_status", sortable: false },
  { title: "Thao tác", key: "actions", sortable: false },
];

const typeOptions = [
  { id: "all", name: "Tất cả vai trò" },
  { id: "Sale", name: "Sale" },
  { id: "Sale TA", name: "Sale TA" },
];

const statusOptions = [
  "Tất cả trạng thái",
  "Đã thanh toán",
  "Còn nợ",
  "Cần trả",
];
const reconciliationOptions = ["Tháng này"];

const loadPartnerData = async () => {
  const res = await axios.get(route("debtPayment.getPartners"));
  partnerOptions.value = [{ id: 0, name: "Tất cả đối tác" }, ...res.data];
};
const resetFilters = () => {
  range_date.value = "";
  partner_id.value = 0;
  type.value = "all";
  debtor_partner.value = false;
  reconciliation.value = "";
  status.value = "Tất cả trạng thái";
  page.value = 1;
  itemsPerPage.value = props.data.per_page;
  fetchData();
};
onMounted(() => {
  loadPartnerData();
  if (property_id.value) {
    fetchData();
  }
});

// Helper functions
const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return "0 ₫";
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(amount);
};

const getDebtStatusColor = (status) => {
  switch (status) {
    case "Đã thanh toán":
      return "success";
    case "Còn nợ":
      return "error";
    case "Cần trả":
      return "warning";
    default:
      return "primary";
  }
};
</script>

<style scoped>
/* Hero Section */
.hero-card {
  border-radius: 16px !important;
  overflow: hidden;
  position: relative;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.hero-gradient {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    135deg,
    rgba(102, 126, 234, 0.9) 0%,
    rgba(118, 75, 162, 0.9) 100%
  );
  z-index: 1;
}

.hero-content {
  z-index: 2;
  position: relative;
}

.hero-icon {
  background: rgba(255, 255, 255, 0.2);
  padding: 8px;
  border-radius: 12px;
  backdrop-filter: blur(10px);
}

.hero-title {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  color: #fff;
}

.hero-subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
  margin-bottom: 0;
}

.stats-grid {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  z-index: 2;
  position: relative;
}

.stat-item {
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.15);
  padding: 1rem 1.5rem;
  border-radius: 12px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
}

.stat-item:hover {
  transform: translateY(-2px);
  background: rgba(255, 255, 255, 0.2);
}

.stat-icon {
  margin-right: 0.75rem;
  opacity: 0.9;
}

.stat-label {
  font-size: 0.875rem;
  opacity: 0.8;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
}

/* Filter Section */
.filter-card {
  border-radius: 12px !important;
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  background-color: rgb(var(--v-theme-surface));
}

.filter-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  display: flex;
  align-items: center;
}

.filter-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

.filter-group {
  position: relative;
}

.enhanced-input {
  transition: all 0.3s ease;
}

.enhanced-input:hover {
  transform: translateY(-1px);
}

.enhanced-input :deep(.v-field) {
  background-color: rgb(var(--v-theme-surface));
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

.enhanced-checkbox {
  transition: all 0.3s ease;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

.reset-btn,
.reset-btn-lg {
  transition: all 0.3s ease;
  border-radius: 8px !important;
}

.reset-btn:hover,
.reset-btn-lg:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Charts Section */
.chart-card {
  border-radius: 12px !important;
  transition: all 0.3s ease;
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  background-color: rgb(var(--v-theme-surface));
}

.chart-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
}

.chart-header {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}

.chart-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 6px;
  border-radius: 8px;
}

.chart-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

.chart-container {
  min-height: 300px;
  padding: 1rem 0;
}

/* KPI Cards */
.kpi-card {
  border-radius: 16px !important;
  overflow: hidden;
  position: relative;
  transition: all 0.3s ease;
  cursor: pointer;
}

.kpi-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 16px 32px rgba(0, 0, 0, 0.2) !important;
}

.kpi-gradient {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  opacity: 0.1;
  z-index: 1;
}

.kpi-gradient-blue {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.kpi-gradient-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.kpi-gradient-error {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.kpi-gradient-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.kpi-icon-wrapper {
  padding: 12px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.kpi-icon-blue {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
}

.kpi-icon-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.kpi-icon-error {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.kpi-icon-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.kpi-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  margin-bottom: 0.25rem;
}

.kpi-value {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
}

.kpi-value-blue {
  color: #1d4ed8;
}

.kpi-value-success {
  color: #059669;
}

.kpi-value-error {
  color: #dc2626;
}

.kpi-value-warning {
  color: #d97706;
}

/* Data Table */
.data-table-card {
  border-radius: 12px !important;
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  background-color: rgb(var(--v-theme-surface));
}

.table-header {
  padding: 1.5rem;
  border-bottom: 1px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}

.table-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 8px;
  border-radius: 10px;
}

.table-title {
  font-size: 1.3rem;
  font-weight: 700;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  margin-bottom: 0.25rem;
}

.table-subtitle {
  font-size: 0.95rem;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  margin-bottom: 0;
}

.enhanced-table {
  background: transparent;
}

.enhanced-table :deep(.v-data-table__wrapper) {
  border-radius: 0 0 12px 12px;
}

.enhanced-table :deep(thead th) {
  color: rgba(
    var(--v-theme-on-surface),
    var(--v-high-emphasis-opacity)
  ) !important;
  font-weight: 600 !important;
  border-bottom: 2px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity)) !important;
}

.enhanced-table :deep(tbody tr:hover) {
  background: rgba(var(--v-theme-primary), 0.04) !important;
}

.enhanced-table :deep(tbody td) {
  padding: 16px 12px !important;
  color: rgba(
    var(--v-theme-on-surface),
    var(--v-high-emphasis-opacity)
  ) !important;
}

/* Animation */
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

.hero-card,
.filter-card,
.chart-card,
.kpi-card,
.data-table-card {
  animation: fadeInUp 0.6s ease-out;
}

.hero-card {
  animation-delay: 0.1s;
}

.filter-card {
  animation-delay: 0.2s;
}

.chart-card {
  animation-delay: 0.3s;
}

.kpi-card {
  animation-delay: 0.4s;
}

.data-table-card {
  animation-delay: 0.5s;
}

/* Responsive */
@media (max-width: 768px) {
  .hero-title {
    font-size: 1.5rem;
  }

  .stats-grid {
    flex-direction: column;
    gap: 1rem;
  }

  .stat-item {
    padding: 0.75rem 1rem;
  }

  .kpi-value {
    font-size: 1.25rem;
  }
}

/* Legacy styles for compatibility */
.bg_blue {
  border-radius: 0.5rem;
  background-color: rgba(var(--v-theme-primary), 0.1);
}
.bg_success {
  border-radius: 0.5rem;
  background-color: rgba(var(--v-theme-success), 0.1);
}
.bg_error {
  border-radius: 0.5rem;
  background-color: rgba(var(--v-theme-error), 0.1);
}
.bg_warning {
  border-radius: 0.5rem;
  background-color: rgba(var(--v-theme-warning), 0.1);
}
.text-blue {
  color: rgb(var(--v-theme-primary));
}
.text-success {
  color: rgb(var(--v-theme-success));
}
.text-error {
  color: rgb(var(--v-theme-error));
}
.item {
  padding: 1rem;
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: rgb(var(--v-theme-surface));
}
.grid {
  width: 100%;
  min-height: 300px;
  display: grid;
}
</style>
