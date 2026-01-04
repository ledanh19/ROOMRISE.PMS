<template>
  <Head title="Phiếu thu - OTA Collect | Room Rise" />
  <Layout>
    <!-- Header Card với gradient background -->
    <VCard class="header-card mb-6">
      <VCardItem class="header-content">
        <div class="d-flex justify-space-between align-center">
          <div class="header-text">
            <VCardTitle class="text-white text-h4 font-weight-bold mb-2">
              <VIcon icon="tabler-receipt" class="me-3" size="32" />
              Phiếu thu OTA Collect
            </VCardTitle>
            <p class="text-white text-body-1 opacity-90 mb-0">
              Quản lý các khoản thu từ OTA và đối tác một cách hiệu quả
            </p>
          </div>
          <div class="header-stats">
            <div class="stat-item">
              <div class="stat-number">{{ props.totalBookings }}</div>
              <div class="stat-label">Tổng Booking</div>
            </div>
          </div>
        </div>
      </VCardItem>
    </VCard>

    <!-- Revenue Summary với animation -->
    <div class="revenue-summary-wrapper mb-6">
      <RevenueSummary
        :total-Bookings="props.totalBookings"
        :total-Difference="props.totalDifference"
        :total-Net="props.totalNet"
        :total-Gross="props.totalGross"
        :total-SettledBookings="props.totalSettledBookings"
        :total-PendingBookings="props.totalPendingBookings"
      />
    </div>

    <!-- Tabs với styling mới -->
    <VCard class="tabs-card mb-6">
      <VCardItem class="tabs-header">
        <VTabs v-model="currentTab" class="v-tabs-pill custom-tabs">
          <VTab class="w-50 tab-item">
            <VIcon icon="tabler-file-description" class="me-2" />
            <span class="tab-text">Đối soát doanh thu</span>
          </VTab>
          <VTab class="w-50 tab-item">
            <VIcon icon="tabler-circle-check" class="me-2" />
            <span class="tab-text">Đã quyết toán</span>
          </VTab>
        </VTabs>
      </VCardItem>
    </VCard>

    <VWindow v-model="currentTab" class="mt-5">
      <VWindowItem>
        <!-- Filter Card với styling mới -->
        <VCard class="filter-card mb-6">
          <VCardItem class="filter-header">
            <div class="d-flex align-center mb-4">
              <VIcon icon="tabler-filter" class="me-2 text-primary" />
              <h4 class="text-h6 mb-0">Bộ lọc tìm kiếm</h4>
            </div>
            <VRow>
              <VCol cols="12" sm="12" md="4" lg="4">
                <AppDateTimePicker
                  v-model="range_date"
                  label="Ngày đặt"
                  placeholder="Ngày đặt"
                  :config="{ mode: 'range' }"
                  class="custom-input"
                />
              </VCol>
              <VCol cols="12" sm="12" md="4" lg="4">
                <AppSelect
                  v-model="ota_name"
                  :items="otaOptions"
                  label="Kênh OTA"
                  class="custom-input"
                />
              </VCol>
              <VCol cols="12" sm="12" md="4" lg="4">
                <AppSelect
                  v-model="property_id"
                  :items="properties"
                  item-title="name"
                  item-value="id"
                  label="Chỗ nghỉ"
                  class="custom-input"
                />
              </VCol>
              <VCol cols="12" sm="12" md="4" lg="4">
                <AppSelect
                  v-model="customer_ids"
                  :items="customerOptions"
                  item-title="name"
                  item-value="id"
                  label="Khách hàng"
                  chips
                  multiple
                  closable-chips
                  class="custom-input"
                />
              </VCol>
              <VCol cols="12" sm="12" md="4" lg="4">
                <AppSelect
                  v-model="status_booking"
                  :items="statusOptions"
                  label="Trạng thái"
                  class="custom-input"
                />
              </VCol>
              <VCol cols="12" sm="12" md="4" lg="4">
                <div class="d-flex align-end h-100">
                  <VBtn
                    variant="outlined"
                    color="primary"
                    @click="handleReset"
                    class="reset-btn"
                  >
                    <VIcon icon="tabler-refresh" class="me-2" />
                    Reset
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VCardItem>
        </VCard>

        <!-- Data Table Card -->
        <VCard class="data-table-card">
          <VCardItem class="table-header">
            <div class="d-flex align-center justify-space-between">
              <div class="d-flex align-center">
                <VIcon icon="tabler-list" class="me-3 text-primary" size="24" />
                <VCardTitle class="text-h6 mb-0">
                  Danh sách booking
                  <VChip color="primary" class="ms-2">
                    {{ props.totalPendingBookings }}
                  </VChip>
                </VCardTitle>
              </div>
              <VBtn
                @click="handleSettlements"
                color="success"
                class="settlement-btn"
                :disabled="!selected_bookings.length"
              >
                <VIcon icon="tabler-calculator" class="me-2" />
                Quyết toán
              </VBtn>
            </div>
          </VCardItem>
          <VCardItem class="table-content">
            <VDataTable
              :headers="headers"
              :items="data"
              item-value="id"
              return-object
              :items-per-page="-1"
              class="text-no-wrap custom-data-table"
              hide-default-footer
              show-select
              v-model="selected_bookings"
            >
              <template #item.property_id="{ item }">
                <div class="text-high-emphasis text-body-1 pa-3">
                  <div class="property-name">
                    {{ item?.property ? item.property.name : "" }}
                  </div>
                </div>
              </template>
              <template #item.customer_id="{ item }">
                <div class="text-high-emphasis text-body-1 pa-3">
                  <div class="customer-name">
                    {{ item?.customer ? item.customer?.full_name : "" }}
                  </div>
                </div>
              </template>
              <template #item.total_amount="{ item }">
                <div class="text-high-emphasis text-body-1 pa-3">
                  <div class="amount-highlight">
                    <strong>
                      {{ formatCurrency(item.total_amount) }}
                    </strong>
                  </div>
                </div>
              </template>
              <template #item.ota_fee="{ item }">
                <div class="text-high-emphasis text-body-1 pa-3">
                  <span class="fee-amount text-error">
                    {{ formatCurrency(item.ota_fee) }}
                  </span>
                  <br />
                  <span class="fee-percent"
                    >({{ item.ota_fee_percent }} %)</span
                  >
                </div>
              </template>
              <template #item.check_in_out="{ item }">
                <div class="text-high-emphasis text-body-1 pa-3">
                  <div class="date-info">
                    <div class="check-in">
                      <VIcon
                        icon="tabler-plane-arrival"
                        size="16"
                        class="me-1"
                      />
                      {{ formatDate(item.check_in_date) }}
                    </div>
                    <div class="check-out">
                      <VIcon
                        icon="tabler-plane-departure"
                        size="16"
                        class="me-1"
                      />
                      {{ formatDate(item.check_out_date) }}
                    </div>
                  </div>
                </div>
              </template>
              <template #item.net_estimate="{ item }">
                <div class="text-high-emphasis text-body-1 pa-3">
                  <div class="net-estimate">
                    {{ formatCurrency(item.net_estimate) }}
                  </div>
                </div>
              </template>
              <template #item.payout_received="{ item }">
                <VTextField
                  v-model="item.payout_received"
                  variant="outlined"
                  type="number"
                  @blur="onUpdatePayout(item)"
                  class="payout-input"
                  density="compact"
                />
              </template>
              <template #item.note="{ item }">
                <div>
                  <AppTextarea
                    v-model="item.note"
                    @blur="onUpdatePayoutNote(item)"
                    auto-grow
                    rows="2"
                    row-height="20"
                    placeholder="Ghi chú"
                    class="note-textarea"
                    variant="outlined"
                    density="compact"
                  />
                </div>
              </template>

              <template #item.difference_amount="{ item }">
                <div class="text-high-emphasis text-body-1 pa-3">
                  <div
                    v-if="item.difference_amount < 0"
                    class="difference-amount negative"
                  >
                    {{ formatCurrency(item.difference_amount) }}
                  </div>
                  <div
                    v-else-if="item.difference_amount > 0"
                    class="difference-amount positive"
                  >
                    {{ formatCurrency(item.difference_amount) }}
                  </div>
                  <div v-else class="difference-amount neutral">
                    {{ formatCurrency(item.difference_amount) }}
                  </div>
                </div>
              </template>

              <template #item.reconciliation_status="{ item }">
                <div class="text-high-emphasis text-body-1 pa-3">
                  <div>
                    <VChip
                      color="success"
                      variant="elevated"
                      v-if="item.reconciliation_status == 'Khớp'"
                      class="status-chip"
                    >
                      <VIcon icon="tabler-check" size="16" class="me-1" />
                      Khớp
                    </VChip>
                    <VChip
                      color="warning"
                      variant="elevated"
                      v-if="item.reconciliation_status == 'Lệch'"
                      class="status-chip"
                    >
                      <VIcon
                        icon="tabler-alert-triangle"
                        size="16"
                        class="me-1"
                      />
                      Lệch
                    </VChip>
                    <VChip
                      color="secondary"
                      variant="elevated"
                      v-if="item.reconciliation_status == 'Chờ payout'"
                      class="status-chip"
                    >
                      <VIcon icon="tabler-clock" size="16" class="me-1" />
                      Chờ Payout
                    </VChip>
                  </div>
                </div>
              </template>
            </VDataTable>
          </VCardItem>
        </VCard>
      </VWindowItem>
      <VWindowItem>
        <!-- Settlement Filter Card -->
        <VCard class="filter-card mb-6">
          <VCardItem class="filter-header">
            <div class="d-flex align-center mb-4">
              <VIcon icon="tabler-filter" class="me-2 text-primary" />
              <h4 class="text-h6 mb-0">Bộ lọc quyết toán</h4>
            </div>
            <VRow>
              <VCol cols="12" sm="6" md="4" lg="4">
                <AppTextField
                  label="Booking"
                  v-model="booking_id_settlement"
                  type="text"
                  placeholder="Tìm kiếm booking"
                  class="custom-input"
                />
              </VCol>
              <VCol cols="12" sm="6" md="4" lg="4">
                <AppSelect
                  v-model="ota_channel_settlement"
                  :items="otaOptions"
                  label="Kênh OTA"
                  class="custom-input"
                />
              </VCol>
              <VCol cols="12" sm="6" md="4" lg="4">
                <AppSelect
                  v-model="partner_id_settlement"
                  :items="partnerOptions"
                  item-title="name"
                  item-value="id"
                  label="Tên đối tác"
                  class="custom-input"
                />
              </VCol>
              <VCol cols="12" sm="6" md="4" lg="4">
                <AppDateTimePicker
                  v-model="range_date_settlement"
                  label="Ngày quyết toán"
                  placeholder="Ngày quyết toán"
                  :config="{ mode: 'range' }"
                  class="custom-input"
                />
              </VCol>
              <VCol cols="12" sm="6" md="4" lg="4">
                <div class="d-flex align-end h-100">
                  <VBtn
                    variant="outlined"
                    color="primary"
                    @click="handleResetSettlement"
                    class="reset-btn"
                  >
                    <VIcon icon="tabler-refresh" class="me-2" />
                    Reset
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VCardItem>
        </VCard>

        <!-- Settlement Tabs -->
        <VCard class="tabs-card mb-6">
          <VCardItem class="tabs-header">
            <VTabs v-model="currentTabSecond" class="v-tabs-pill custom-tabs">
              <VTab class="w-50 tab-item">
                <VIcon icon="tabler-circle-check" class="me-2" />
                <span class="tab-text">Booking đã quyết toán</span>
              </VTab>
              <VTab class="w-50 tab-item">
                <VIcon icon="tabler-file-description" class="me-2" />
                <span class="tab-text">Phiếu quyết toán</span>
              </VTab>
            </VTabs>
          </VCardItem>
        </VCard>

        <VCard class="settlement-content-card">
          <VWindow v-model="currentTabSecond" class="mt-5">
            <VWindowItem>
              <SettlementBookings
                :currentTabSecond="currentTabSecond"
                :bookingIdSettlement="booking_id_settlement"
                :otaChannelSettlement="ota_channel_settlement"
                :partnerIdSettlement="partner_id_settlement"
                :rangeDateSettlement="range_date_settlement"
              />
            </VWindowItem>
            <VWindowItem>
              <Settlements :currentTabSecond="currentTabSecond" />
            </VWindowItem>
          </VWindow>
        </VCard>
      </VWindowItem>
    </VWindow>
    <SettlementsDialog
      v-model:is-dialog-visible="isDialogSettlementVisible"
      :selectedBookings="selected_bookings"
      :ota_name="ota_name"
      @update:settlements="loadData"
    />
  </Layout>
</template>

<script setup>
import Layout from "@/layouts/blank.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { formatCurrency, formatDate } from "@/utils/formatters";
import { Head, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { onMounted, ref, watch } from "vue";
import RevenueSummary from "./RevenueSummary.vue";
import SettlementBookings from "./SettlementBookings.vue";
import Settlements from "./Settlements.vue";
import SettlementsDialog from "./SettlementsDialog.vue";

const propertyStore = usePropertyStore();

const props = defineProps({
  data: Array,
  filters: Object,
  properties: Object,
  totalBookings: String,
  totalDifference: String,
  totalNet: String,
  totalGross: String,
  totalSettledBookings: String,
  totalPendingBookings: String,
});

const currentTab = ref(0);
const currentTabSecond = ref(0);
const filter = ref(false);
const range_date = ref("");
const range_date_settlement = ref("");
const ota_name = ref("Tất cả các kênh");
const ota_channel_settlement = ref("Tất cả các kênh");

const property_id = ref(
  propertyStore.selectedProperty || Number(props.filters?.property_id) || ""
);

const customer_ids = ref([]);
const customerOptions = ref([]);
const status_booking = ref("Tất cả trạng thái");
const selected_bookings = ref([]);
const partnerOptions = ref([]);
const partner_id_settlement = ref(0);
const booking_id_settlement = ref("");

const headers = [
  { title: "Booking ID", key: "booking_code", sortable: false },
  { title: "Chỗ nghỉ", key: "property_id", sortable: false },
  { title: "Kênh OTA", key: "ota_name", sortable: false },
  { title: "Khách hàng", key: "customer_id", sortable: false },
  { title: "Check-in/Check-out", key: "check_in_out", sortable: false },
  { title: "Tổng tiền", key: "total_amount", sortable: false },
  // { title: "Phí OTA", key: "ota_fee", sortable: false },
  // { title: "Net dự kiến", key: "net_estimate", sortable: false },
  { title: "OTA Payout", key: "payout_received", sortable: false },
  { title: "Chênh lệch", key: "difference_amount", sortable: false },
  { title: "Trạng thái", key: "reconciliation_status", sortable: false },
  { title: "Ghi chú", key: "note", sortable: false, width: "200" },
];

const statusOptions = ref(["Tất cả trạng thái", "Khớp", "Lệch", "Chờ payout"]);
const otaOptions = ref([
  "Tất cả các kênh",
  "BookingCom",
  "Agoda",
  "Airbnb",
  "Expedia",
  "Traveloka",
]);

const toggleFilter = () => {
  filter.value = !filter.value;
};

const fetchData = () => {
  selected_bookings.value = [];
  router.get(
    route("ota-receipt.index"),
    {
      range_date: range_date.value,
      customer_ids: customer_ids.value,
      property_id: property_id.value,
      ota_name: ota_name.value !== "Tất cả các kênh" ? ota_name.value : null,
      status_booking:
        status_booking.value !== "Tất cả trạng thái"
          ? status_booking.value
          : null,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

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

const handleReset = () => {
  range_date.value = "";
  ota_name.value = "Tất cả các kênh";
  status_booking.value = "Tất cả trạng thái";
  property_id.value = "";
  customer_ids.value = [];
  selected_bookings.value = [];
  fetchData();
};

const handleResetSettlement = () => {
  range_date_settlement.value = "";
  ota_channel_settlement.value = "Tất cả các kênh";
  partner_id_settlement.value = 0;
  booking_id_settlement.value = "";
};

const loadPartnerData = async () => {
  const res = await axios.get(route("debtPayment.getPartners"));
  partnerOptions.value = [{ id: 0, name: "Tất cả đối tác" }, ...res.data];
};
const onUpdatePayout = async (item) => {
  try {
    const response = await axios.put(route("ota-receipt.updatePayout"), {
      id: item.id,
      payout_received: item.payout_received,
    });
    fetchData();
    selected_bookings.value = [];
  } catch (error) {
    console.error("Lỗi khi cập nhật payout:", error);
  }
};
const onUpdatePayoutNote = async (item) => {
  try {
    const response = await axios.put(route("ota-receipt.updatePayoutNote"), {
      id: item.id,
      note: item.note,
    });
    console.log("Ghi chú cập nhật thành công", response.data);
  } catch (error) {
    console.error("Lỗi khi cập nhật ghi chú:", error);
  }
};
const isDialogSettlementVisible = ref(false);

const handleSettlements = async () => {
  if (!ota_name.value || ota_name.value === "Tất cả các kênh") {
    selected_bookings.value = [];
    alert("Vui lòng chọn một kênh OTA cụ thể trước khi tạo phiếu quyết toán.");
    return;
  }

  if (!selected_bookings.value.length) {
    // selected_bookings.value = [];
    alert("Vui lòng chọn ít nhất 1 booking để tạo phiếu quyết toán.");
    return;
  }

  isDialogSettlementVisible.value = true;
};
const loadData = () => {
  fetchData();
};
onMounted(() => {
  loadPartnerData();
  if (property_id.value) {
    fetchData();
  }
});

watch(range_date, debounce(fetchData, 300));
watch(ota_name, debounce(fetchData, 300));
watch(status_booking, debounce(fetchData, 300));
watch(customer_ids, debounce(fetchData, 300));

watch(
  () => property_id.value,
  async (id) => {
    if (!id) {
      customerOptions.value = [];
      customer_ids.value = [];
      return;
    }
    const res = await axios.get(route("customers.by-property", { id }));
    customerOptions.value = res.data;
    customer_ids.value = [];
  }
);
</script>

<style lang="scss">
.v-slide-group__prev,
.v-slide-group__next {
  display: none;
}

// Header Card với gradient background
.header-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
  border-radius: 16px;
  overflow: hidden;
  position: relative;

  &::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    pointer-events: none;
  }

  .header-content {
    position: relative;
    z-index: 1;
  }

  .header-text {
    .text-h4 {
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
  }

  .header-stats {
    .stat-item {
      text-align: center;
      padding: 16px 24px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);

      .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: white;
        margin-bottom: 4px;
      }

      .stat-label {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.875rem;
      }
    }
  }
}

// Revenue Summary wrapper
.revenue-summary-wrapper {
  animation: fadeInUp 0.6s ease-out;
}

// Tabs styling
.tabs-card {
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  background-color: rgb(var(--v-theme-surface));

  .tabs-header {
    border-radius: 16px 16px 0 0;
  }

  .custom-tabs {
    .tab-item {
      border-radius: 12px;
      margin: 0 4px;
      transition: all 0.3s ease;
      font-weight: 500;

      &:hover {
        background: rgba(102, 126, 234, 0.1);
        transform: translateY(-2px);
      }

      &.v-tab--selected {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
      }
    }

    .tab-text {
      font-weight: 500;
    }
  }
}

// Filter Card styling
.filter-card {
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  background-color: rgb(var(--v-theme-surface));

  .filter-header {
    border-radius: 16px 16px 0 0;
    border-bottom: 1px solid
      rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  }

  .custom-input {
    .v-field {
      border-radius: 12px;
      transition: all 0.3s ease;
      background-color: rgb(var(--v-theme-surface));
      color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));

      &:hover {
        box-shadow: 0 4px 12px rgba(var(--v-theme-primary), 0.15);
      }

      &.v-field--focused {
        box-shadow: 0 4px 12px rgba(var(--v-theme-primary), 0.25);
      }
    }
  }

  .reset-btn {
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
    }
  }
}

// Data Table Card styling
.data-table-card {
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  overflow: hidden;
  background-color: rgb(var(--v-theme-surface));

  .table-header {
    border-bottom: 1px solid
      rgba(var(--v-theme-on-surface), var(--v-border-opacity));

    .settlement-btn {
      border-radius: 12px;
      font-weight: 500;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25);

      &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.35);
      }

      &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
    }
  }

  .table-content {
    .custom-data-table {
      .v-data-table__wrapper {
        border-radius: 0 0 16px 16px;
      }

      .v-data-table-header {
        font-weight: 600;
      }

      .v-data-table__tr {
        transition: all 0.3s ease;

        &:hover {
          background: rgba(var(--v-theme-primary), 0.04);
        }
      }

      .v-data-table__td {
        border-bottom: 1px solid
          rgba(var(--v-theme-on-surface), var(--v-border-opacity));
        color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
      }

      .v-data-table__th {
        color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
        font-weight: 600;
      }
    }
  }
}

// Settlement Content Card
.settlement-content-card {
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  overflow: hidden;
  background-color: rgb(var(--v-theme-surface));
}

// Table cell styling
.property-name {
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

.customer-name {
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

.amount-highlight {
  font-size: 1.1rem;
  color: rgb(var(--v-theme-success));
  font-weight: 700;
}

.fee-amount {
  font-weight: 600;
  font-size: 1rem;
}

.fee-percent {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 0.875rem;
}

.date-info {
  .check-in,
  .check-out {
    display: flex;
    align-items: center;
    margin-bottom: 4px;
    font-size: 0.875rem;
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  }

  .check-in {
    color: rgb(var(--v-theme-success));
  }

  .check-out {
    color: rgb(var(--v-theme-error));
  }
}

.net-estimate {
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
  font-size: 1rem;
}

.payout-input {
  .v-field {
    border-radius: 8px;
    transition: all 0.3s ease;
    background-color: rgb(var(--v-theme-surface));
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));

    &:hover {
      box-shadow: 0 2px 8px rgba(var(--v-theme-primary), 0.15);
    }

    &.v-field--focused {
      box-shadow: 0 2px 8px rgba(var(--v-theme-primary), 0.25);
    }
  }
}

.note-textarea {
  .v-field {
    border-radius: 8px;
    transition: all 0.3s ease;
    background-color: rgb(var(--v-theme-surface));
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));

    &:hover {
      box-shadow: 0 2px 8px rgba(var(--v-theme-primary), 0.15);
    }

    &.v-field--focused {
      box-shadow: 0 2px 8px rgba(var(--v-theme-primary), 0.25);
    }
  }
}

.difference-amount {
  font-weight: 600;
  font-size: 1rem;
  padding: 4px 8px;
  border-radius: 6px;
  display: inline-block;

  &.negative {
    color: rgb(var(--v-theme-error));
    background: rgba(var(--v-theme-error), 0.1);
  }

  &.positive {
    color: rgb(var(--v-theme-success));
    background: rgba(var(--v-theme-success), 0.1);
  }

  &.neutral {
    color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
    background: rgba(var(--v-theme-on-surface), 0.1);
  }
}

.status-chip {
  font-weight: 500;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;

  &:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
}

// Animations
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

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

// Responsive design
@media (max-width: 768px) {
  .header-card {
    .header-stats {
      display: none;
    }
  }

  .filter-card {
    .v-col {
      margin-bottom: 16px;
    }
  }

  .data-table-card {
    .table-header {
      flex-direction: column;
      gap: 16px;
      align-items: flex-start;
    }
  }
}

// Custom scrollbar
.custom-data-table {
  .v-data-table__wrapper {
    &::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    &::-webkit-scrollbar-track {
      background: rgba(var(--v-theme-on-surface), 0.1);
      border-radius: 4px;
    }

    &::-webkit-scrollbar-thumb {
      background: rgb(var(--v-theme-primary));
      border-radius: 4px;

      &:hover {
        background: rgb(var(--v-theme-primary-darken-1));
      }
    }
  }
}

// Loading states
.v-data-table__loading {
  background: rgba(var(--v-theme-surface), 0.8);
  backdrop-filter: blur(4px);
}

// Focus states
.v-tab:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

.v-btn:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}
</style>
