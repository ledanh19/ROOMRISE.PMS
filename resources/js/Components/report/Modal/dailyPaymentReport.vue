<template>
  <VCard class="report-card">
    <div class="toolbar">
      <h2 class="title">{{ title }}</h2>

      <div class="actions">
        <VBtn
          variant="outlined"
          class="reset-btn mr-2"
          color="error"
          :loading="active.loading"
          :disabled="active.loading"
          @click="clearAllFilters"
        >
          <RefreshIcon :size="18" class="refresh-icon" />

          Xoá bộ lọc
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="tabler-search"
          @click="onSearch"
          :loading="loading"
          :disabled="loading"
        >
          Lọc
        </VBtn>
      </div>
    </div>

    <VRow class="filters" dense>
      <VCol cols="12" md="6">
        <div class="filter-group">
          <div class="filter-title">Ngày thanh toán</div>

          <div class="inline-controls">
            <div class="field">
              <div class="label">Từ ngày</div>
              <AppDateTimePicker
                v-model="date.from"
                class="picker picker--date"
                placeholder="YYYY-MM-DD"
                :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
                :clearable="true"
              />
            </div>

            <span class="sep">đến</span>

            <div class="field">
              <div class="label">Đến ngày</div>
              <AppDateTimePicker
                v-model="date.to"
                class="picker picker--date"
                placeholder="YYYY-MM-DD"
                :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
                :clearable="true"
              />
            </div>
          </div>
        </div>
      </VCol>

      <VCol cols="12" md="6">
        <div class="filter-group">
          <div class="filter-title">Thời gian thanh toán</div>

          <div class="inline-controls">
            <div class="field">
              <div class="label">Từ giờ</div>
              <AppDateTimePicker
                v-model="time.from"
                class="picker picker--time"
                placeholder="HH:mm"
                :config="{
                  enableTime: true,
                  noCalendar: true,
                  dateFormat: 'H:i',
                  time_24hr: true,
                }"
                :clearable="true"
              />
            </div>

            <span class="sep">đến</span>

            <div class="field">
              <div class="label">Đến giờ</div>
              <AppDateTimePicker
                v-model="time.to"
                class="picker picker--time"
                placeholder="HH:mm"
                :config="{
                  enableTime: true,
                  noCalendar: true,
                  dateFormat: 'H:i',
                  time_24hr: true,
                }"
                :clearable="true"
              />
            </div>
          </div>
        </div>
      </VCol>
    </VRow>

    <TableReport
      :headers="headerDailyPaymentReportTable1"
      :rows="rowsDetail"
      :totals="detailTotals"
      :loading="loading"
      table-height="50vh"
      :isPaginated="true"
      :total="total1"
      :page="page1"
      :size="size1"
      :per-page-options="perPageOptions"
      @update:page="onUpdatePage1"
      @update:size="onUpdateSize1"
    />

    <div class="my-5"></div>

    <TableReport
      :headers="headerDailyPaymentReportTable2"
      :rows="rowsTable2"
      :totals="rowsSummary"
      :loading="loading"
      table-height="30vh"
      table-width="100%"
      :isPaginated="true"
      :total="totalTable2"
      :page="pageTable2"
      :size="sizeTable2"
      :per-page-options="perPageOptions"
      @update:page="onUpdatePage2"
      @update:size="onUpdateSize2"
    /> </VCard
  >0
</template>

<script setup>
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import { useReport } from "@/stores/useReport";
import {
  headerDailyPaymentReportTable1,
  headerDailyPaymentReportTable2,
} from "@/utils/headerReport";
import { storeToRefs } from "pinia";
import { computed, onMounted, ref, watch } from "vue";
import TableReport from "../Item/tableReport.vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo thanh toán" },
});

const report = useReport();
const { active } = storeToRefs(report);

const DAILY_TAB = "daily-payment-report";
const REPLACE_FILTERS_AUTO_FETCH = false;

const date = ref({ from: "", to: "" });
const time = ref({ from: "", to: "" });

const rowsDetail = computed(() => active.value?.rows ?? []);
const page1 = computed(() => active.value?.page ?? 1);
const size1 = computed(() => active.value?.size ?? 10);
const total1 = computed(() => active.value?.total ?? 0);
const detailTotals = computed(() => {
  const rows = rowsDetail.value;
  const incomeTotalSum = rows.reduce(
    (s, r) => s + (Number(r?.incomeTotal) || 0),
    0
  );
  const countItems = rows.length;
  return { incomeTotal: incomeTotalSum, countItems };
});

const rowsSummary = computed(() => active.value?.paymentTotals ?? []);
const rowsTable2 = computed(() => active.value?.rowsTable2 ?? []);
const pageTable2 = computed(() => active.value?.pageTable2 ?? 1);
const sizeTable2 = computed(() => active.value?.sizeTable2 ?? 10);
const totalTable2 = computed(() => active.value?.totalTable2 ?? 0);

const perPageOptions = [1, 10, 20, 50, 100];
const loading = computed(() => !!active.value?.loading);

function onUpdatePage1(p) {
  report.setPage(p, DAILY_TAB);
  report.fetch(DAILY_TAB);
}
function onUpdateSize1(s) {
  report.setSize(s, DAILY_TAB);
  report.fetch(DAILY_TAB);
}

function onUpdatePage2(p) {
  report.setPageTable2(p, DAILY_TAB);
  report.fetch(DAILY_TAB);
}
function onUpdateSize2(s) {
  report.setSizeTable2(s, DAILY_TAB);
  report.fetch(DAILY_TAB);
}

function buildPayload() {
  return {
    dateFrom: date.value.from || "",
    dateTo: date.value.to || "",
    timeFrom: time.value.from || "",
    timeTo: time.value.to || "",
  };
}
function signature(p) {
  return `${p.dateFrom}|${p.dateTo}|${p.timeFrom}|${p.timeTo}`;
}
function wasCleared(next, prev) {
  const hadValue = prev !== "" && prev != null;
  const nowEmpty = next === "" || next == null;
  return hadValue && nowEmpty;
}

const isSearching = ref(false);
let debounceTimer;
let lastSig = "";

async function runSearch() {
  const payload = buildPayload();
  const sig = signature(payload);
  if (sig === lastSig) return;
  if (isSearching.value) return;

  isSearching.value = true;
  try {
    lastSig = sig;
    report.replaceFilters(payload, DAILY_TAB);
    if (!REPLACE_FILTERS_AUTO_FETCH && typeof report.fetch === "function") {
      await report.fetch(DAILY_TAB);
    }
  } finally {
    isSearching.value = false;
  }
}

function onSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(runSearch, 200);
}

watch(
  [
    () => date.value.from,
    () => date.value.to,
    () => time.value.from,
    () => time.value.to,
  ],
  ([nf, nt, tf, tt], [pf, pt, pft, ptt]) => {
    if (
      wasCleared(nf, pf) ||
      wasCleared(nt, pt) ||
      wasCleared(tf, pft) ||
      wasCleared(tt, ptt)
    ) {
      onSearch();
    }
  },
  { flush: "post" }
);

onMounted(async () => {
  await report.switchTab(DAILY_TAB, { reset: false, fetchNow: false });
});

async function syncAndFetch(patch) {
  report.replaceFilters(patch, DAILY_TAB);
  await report.fetch();
}

async function clearAllFilters() {
  date.value = { from: "", to: "" };
  time.value = { from: "", to: "" };
  await syncAndFetch({
    dateFrom: "",
    dateTo: "",
    timeFrom: "",
    timeTo: "",
  });
}
</script>

<style scoped>
.report-card {
  border: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 8%, transparent);
  border-radius: 14px;
  background: var(--v-theme-surface);
}

.toolbar {
  display: flex;
  justify-content: space-between;
  padding: 16px;
  gap: 12px;
}

.filters {
  padding: 12px;
}

.filter-group {
  display: grid;
  gap: 10px;
}

.filter-title {
  color: rgba(var(--v-theme-on-surface), 0.92);
  font-weight: 700;
}

.inline-controls {
  display: grid;
  align-items: end;
  gap: 12px;
  grid-template-columns: 1fr auto 1fr;
}

.sep {
  color: rgba(var(--v-theme-on-surface), 0.7);
}

.my-5 {
  margin-block: 24px;
}
</style>
