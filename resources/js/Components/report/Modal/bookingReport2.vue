<template>
  <VCard v-if="ready" class="report-card">
    <div class="header-row">
      <div class="heading">
        <h2 class="title">{{ title }}</h2>
      </div>
      <div class="actions">
        <VBtn
          variant="outlined"
          class="reset-btn mr-2"
          :loading="active.loading"
          :disabled="active.loading"
          @click="clearAllFilters"
          color="error"
        >
          <RefreshIcon :size="18" class="refresh-icon" />
          Xoá bộ lọc
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="tabler-search"
          :loading="loading"
          :disabled="loading"
          @click="onSearch"
        >
          Xem
        </VBtn>
      </div>
    </div>

    <div class="filter-panel">
      <div class="filter-grid">
        <div class="group group--date">
          <div class="group-label">Khung ngày</div>
          <div class="group-row">
            <AppSelect
              v-model="filters.dateType"
              :items="dateTypeOptions"
              item-title="title"
              item-value="value"
              placeholder="Chọn loại ngày"
              clearable
              class="ctrl w-200"
            />
          </div>
        </div>

        <div class="group group--ota">
          <div class="group-label">Từ</div>
          <AppDateTimePicker
            v-model="dateFrom"
            :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
            placeholder="Từ"
            clearable
            class="ctrl input-date"
          />
        </div>

        <div class="group group--ota">
          <div class="group-label">Đến</div>
          <AppDateTimePicker
            v-model="dateTo"
            :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
            placeholder="Đến"
            clearable
            class="ctrl input-date"
          />
        </div>

        <div class="group group--ota">
          <div class="group-label">Nguồn đặt phòng</div>
          <AppSelect
            v-model="filters.otaName"
            :items="otaOptions"
            item-title="label"
            item-value="value"
            placeholder="Nguồn phòng"
            clearable
            class="ctrl w-300"
          />
        </div>

        <div class="group group--booking-status">
          <div class="group-label">Trạng thái đặt phòng</div>
          <AppSelect
            v-model="filters.bookingStatus"
            :items="bookingStatusOptions"
            placeholder="Tất cả"
            clearable
            class="ctrl w-200"
          />
        </div>

        <div class="group group--payment-status">
          <div class="group-label">Trạng thái thanh toán</div>
          <AppSelect
            v-model="filters.paymentStatus"
            :items="paymentStatusOptions"
            placeholder="Tất cả"
            clearable
            class="ctrl w-200"
          />
        </div>
      </div>
    </div>

    <div class="table-card">
      <TableReport
        :headers="headers"
        :rows="rows"
        :tableHeight="'70vh'"
        :tableWidth="'100%'"
        :totals="totalsForTable"
        :total="total"
        :page="page"
        :size="size"
        :loading="loading"
        :perPageOptions="[10, 20, 50, 100]"
        :isPaginated="true"
        @update:page="onPageUpdate"
        @update:size="onSizeUpdate"
      />
    </div>
  </VCard>
</template>

<script setup>
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import RefreshIcon from "@/Components/control-panel/RefreshIcon.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { useReportManager } from "@/stores/useReportManager";
import {
  bookingStatusOptions,
  dateTypeOptions,
  otaOptions,
  paymentStatusOptions,
} from "@/utils/constants";
import { headerBookingReport } from "@/utils/headerReport";
import { storeToRefs } from "pinia";
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue";
import TableReport from "../Item/tableReport.vue";

const TAB_KEY = "booking-report";
const PAGE_SIZE = 10;

const report = useReportManager();
const propertyStore = usePropertyStore();
const { active } = storeToRefs(report);

const title = "Báo cáo đặt phòng";
const ready = ref(false);

const dateFrom = ref("");
const dateTo = ref("");

const filters = reactive({
  dateType: "created_at",
  timeRange: "",
  bookingStatus: null,
  paymentStatus: null,
  otaName: null,
  property: "",
  pageSize: PAGE_SIZE,
});

const headers = computed(() => (headerBookingReport || []).filter(Boolean));
const loading = computed(
  () => !!(report.tabs[TAB_KEY]?.loading || active.value?.loading)
);
const rows = computed(() =>
  Array.isArray(report.tabs[TAB_KEY]?.rows) ? report.tabs[TAB_KEY].rows : []
);
const footer = computed(() => report.tabs[TAB_KEY]?.paymentTotalsFooter ?? {});
const total = computed(() => Number(report.tabs[TAB_KEY]?.total || 0));
const page = computed(() => Number(report.tabs[TAB_KEY]?.page || 1));
const size = computed(() => Number(report.tabs[TAB_KEY]?.size || PAGE_SIZE));

function buildOtaNameParam() {
  return String(filters.otaName || "");
}

function buildTimeRange() {
  const from = String(dateFrom.value || "").trim();
  const to = String(dateTo.value || "").trim();
  return from && to ? `${from} to ${to}` : filters.timeRange || "";
}

function normalizeOtaSend(n) {
  if (!n) return "";
  const s = n.trim();
  if (/booking/i.test(s)) return "bookingCom";
  if (/walk-?in/i.test(s)) return "walkIn";
  if (/agoda/i.test(s)) return "agoda";
  if (/expedia/i.test(s)) return "expedia";
  if (/airbnb/i.test(s)) return "airbnb";
  if (/ctrip/i.test(s)) return "ctrip";
  if (/widget/i.test(s)) return "widget";
  return s;
}

const numericKeyPattern =
  /price|total|amount|sum|fee|tax|discount|vnd|commission|bookingcom|walkin|commissionfee|otaFee|customerPaymentAmount|commission/i;

const totalsForTable = computed(() => {
  const f = footer.value || {};
  const result = { fullName: "Tổng cộng" };

  for (const h of headers.value || []) {
    const key = h?.key;
    if (!key) continue;

    const raw = f?.[key];
    if (raw !== undefined && raw !== null) {
      result[key] = raw;
      continue;
    }

    result[key] = numericKeyPattern.test(String(key)) ? 0 : "";
  }

  return result;
});

function onPageUpdate(p) {
  report.setPage(Number(p) || 1, TAB_KEY);
  report.fetch(TAB_KEY);
}

function onSizeUpdate(s) {
  const newSize = Number(s) || PAGE_SIZE;
  report.setSize(newSize, TAB_KEY);
  report.setPage(1, TAB_KEY);
  report.fetch(TAB_KEY);
}

async function onSearch() {
  await report.switchTab(TAB_KEY, { reset: false, fetchNow: false });
  report.ensureTab(TAB_KEY);

  const payload = {
    dateType: filters.dateType,
    timeRange: buildTimeRange(),
    bookingStatus: filters.bookingStatus,
    paymentStatus: filters.paymentStatus,
    otaName: normalizeOtaSend(buildOtaNameParam()),
    page: 1,
    size: Number(filters.pageSize),
    property: filters.property || propertyStore.selectedProperty?.value || "",
  };

  report.replaceFilters(payload, TAB_KEY);
  report.setPage(1, TAB_KEY);
  report.setSize(payload.size, TAB_KEY);
  report.resetForSearch(TAB_KEY);
  await report.fetch(TAB_KEY);
}

onMounted(async () => {
  const f = active.value?.filters || {};
  filters.dateType = f.dateType || "created_at";
  filters.bookingStatus = f.bookingStatus ?? null;
  filters.paymentStatus = f.paymentStatus ?? null;
  filters.otaName = f.otaName ?? null;
  filters.property = f.property ?? "";
  filters.pageSize = f.size || PAGE_SIZE;

  if (f.timeRange) {
    const [from, , to] = String(f.timeRange).split(" ");
    if (from && to) {
      dateFrom.value = from;
      dateTo.value = to;
    }
  }

  await report.switchTab(TAB_KEY, { reset: false, fetchNow: false });
  report.ensureTab(TAB_KEY);

  const t = report.tabs[TAB_KEY];
  if (!t?.rows?.length) {
    await report.fetch(TAB_KEY);
  }

  ready.value = true;
});

watch(
  () => report.currentTab,
  async (tab) => {
    if (tab === TAB_KEY) {
      await nextTick();
      await report.switchTab(TAB_KEY, { reset: false, fetchNow: false });
      report.ensureTab(TAB_KEY);
    }
  }
);

async function clearAllFilters() {
  filters.dateType = "created_at";
  filters.timeRange = "";
  filters.bookingStatus = null;
  filters.paymentStatus = null;
  filters.otaName = null;
  filters.property = "";
  filters.pageSize = PAGE_SIZE;

  dateFrom.value = "";
  dateTo.value = "";

  report.setPage(1, TAB_KEY);
  report.setSize(PAGE_SIZE, TAB_KEY);
  await onSearch();
}
</script>

<style>
.report-card {
  position: relative;
  display: block;
  padding: 16px;
  background: transparent;
}

.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-block-end: 12px;
  padding-block-start: 10px;
  padding-inline: 16px;
}

.heading .title {
  margin: 0;
  color: var(--v-theme-on-surface);
  font-size: 20px;
  font-weight: 700;
}

.filter-panel {
  position: relative;
  z-index: 70;
  padding: 18px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 2%);
  box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 3%);
  margin-block-end: 16px;
}

.filter-grid {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 16px;
}

.table-card {
  padding: 8px;
  border-radius: 12px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 1%), transparent);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 35%);
}

.input-date {
  box-sizing: border-box;
  inline-size: 260px;
  min-inline-size: 260px;
}

@media (max-width: 1200px) {
  .input-date {
    inline-size: 100%;
    max-inline-size: 260px;
    min-inline-size: 200px;
  }

  .filter-grid {
    gap: 10px;
  }
}

.text-end {
  text-align: end;
}

.text-center {
  text-align: center;
}

.text-start {
  text-align: start;
}
</style>
