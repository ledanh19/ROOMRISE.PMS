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
          @click="onClear"
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

    <div class="filter-one-line">
      <div class="range-grid">
        <!-- Date cell: label above, selects below (like currency) -->
        <div class="date-cell">
          <div class="group-label">Phạm vi ngày</div>
          <div class="date-controls">
            <AppSelect
              v-model="form.monthFrom"
              :items="monthOptions"
              item-title="title"
              item-value="value"
              placeholder="Tháng"
              class="ctrl ctrl-big"
              clearable
            />
            <AppSelect
              v-model="form.yearFrom"
              :items="yearOptions"
              item-title="title"
              item-value="value"
              placeholder="Năm"
              class="ctrl ctrl-big"
              clearable
            />
            <div class="sep">đến</div>
            <AppSelect
              v-model="form.monthTo"
              :items="monthOptions"
              item-title="title"
              item-value="value"
              placeholder="Tháng"
              class="ctrl ctrl-big"
              clearable
            />
            <AppSelect
              v-model="form.yearTo"
              :items="yearOptions"
              item-title="title"
              item-value="value"
              placeholder="Năm"
              class="ctrl ctrl-big"
              clearable
            />
          </div>
        </div>

        <!-- Currency cell: label above single select -->
        <div class="currency-cell">
          <div class="group-label">Đơn vị tiền tệ</div>
          <AppSelect
            v-model="form.currency"
            :items="currencyOptions"
            item-title="label"
            item-value="value"
            placeholder="Chọn đơn vị"
            clearable
            class="ctrl ctrl-big"
          />
        </div>
      </div>
    </div>

    <TableReport
      :headers="headerRevenueBooking"
      :rows="rowsDetail"
      :totals="bookingTotals"
      :loading="loading"
      table-height="70vh"
      :page="active.page"
      :total="active.total"
      :size="active.size"
      @update:page="onUpdatePage"
      @update:size="onUpdateSize"
    />
  </VCard>
</template>

<script setup>
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import RefreshIcon from "@/Components/control-panel/RefreshIcon.vue";
import TableReport from "../Item/tableReport.vue";

import { useRevenueReport } from "@/stores/useRevenueReport";
import { currencyOptions } from "@/utils/constants";
import { headerRevenueBooking } from "@/utils/headerReport";

import { storeToRefs } from "pinia";
import { computed, onMounted, ref } from "vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo doanh thu theo đặt phòng" },
});

const report = useRevenueReport();
const { active } = storeToRefs(report);
const TAB = "revenue-report-booking";

const form = ref({
  monthFrom: null,
  yearFrom: null,
  monthTo: null,
  yearTo: null,
  currency: null,
});

const monthOptions = Array.from({ length: 12 }, (_, i) => ({
  title: `Tháng ${i + 1}`,
  value: i + 1,
}));

const yearOptions = (() => {
  const now = new Date();
  const y = now.getFullYear();
  const arr = [];
  for (let k = y + 1; k >= y - 6; k--) arr.push({ title: String(k), value: k });
  return arr;
})();

const rowsDetail = computed(() => active.value?.rows ?? []);
const bookingTotals = computed(() => active.value?.bookingTotal ?? {});
const loading = computed(() => !!active.value?.loading);

function getSelectedPropertyIdFromLocalStorage() {
  try {
    const raw = localStorage.getItem("selectedProperty");
    if (!raw || raw === "null") return "";
    const v = JSON.parse(raw);
    const id = v?.id ?? v?.value ?? v;
    const n = Number(id);
    return Number.isFinite(n) ? n : id || null;
  } catch {
    return null;
  }
}

function buildPayload() {
  return {
    monthFrom: form.value.monthFrom ?? "",
    monthTo: form.value.monthTo ?? "",
    yearFrom: form.value.yearFrom ?? "",
    yearTo: form.value.yearTo ?? "",
    currency: form.value.currency ?? "",
    property: getSelectedPropertyIdFromLocalStorage(),
  };
}

let debounceTimer;
let lastSig = "";
async function runSearch() {
  const payload = buildPayload();
  const sig = JSON.stringify(payload);
  if (sig === lastSig) return;
  lastSig = sig;
  await report.switchTab(TAB, { reset: false, fetchNow: false });
  report.replaceFilters(payload, TAB);
  await report.fetch(TAB);
}

function onSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(runSearch, 120);
}

onMounted(async () => {
  await report.switchTab(TAB, { reset: false, fetchNow: false });
  const f = active.value?.filters || {};
  form.value.monthFrom = f.monthFrom ?? form.value.monthFrom;
  form.value.yearFrom = f.yearFrom ?? form.value.yearFrom;
  form.value.monthTo = f.monthTo ?? form.value.monthTo;
  form.value.yearTo = f.yearTo ?? form.value.yearTo;
  form.value.currency = f.currency ?? null;
});

function onUpdatePage(p) {
  report.setPage(p, TAB);
  report.fetch(TAB);
}
function onUpdateSize(s) {
  report.setSize(s, TAB);
  report.fetch(TAB);
}

function resetUIToDefaults() {
  form.value.monthFrom = null;
  form.value.yearFrom = null;
  form.value.monthTo = null;
  form.value.yearTo = null;
  form.value.currency = null;
}

async function onClear() {
  clearTimeout(debounceTimer);
  resetUIToDefaults();
  lastSig = "";
  try {
    report.setPage(1, TAB);
  } catch (e) {}
  const emptyPayload = buildPayload();
  await report.switchTab(TAB, { reset: false, fetchNow: false });
  report.replaceFilters(emptyPayload, TAB);
  await report.fetch(TAB);
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
  align-items: center;
  justify-content: space-between;
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  padding-block: 14px;
  padding-inline: 16px;
}

.title {
  margin: 0;
  font-weight: 700;
}

.filter-one-line {
  background: color-mix(in oklab, var(--v-theme-on-surface) 4%, transparent);
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  padding-block: 12px;
  padding-inline: 18px;
}

/* Main grid: date-cell (left) | currency-cell (right) */
.range-grid {
  display: grid;
  align-items: start;
  gap: 12px 16px;
  grid-template-columns: minmax(320px, 1fr) minmax(200px, 360px);
  min-inline-size: 0;
}

/* Date cell: label above, controls below */
.date-cell {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.date-cell > .group-label {
  margin: 0;
  color: color-mix(in oklab, var(--v-theme-on-surface) 70%, transparent);
  font-size: 14px;
  font-weight: 600;
  white-space: nowrap;
}

/* Inner grid for date selects: MonthFrom | YearFrom | sep | MonthTo | YearTo */
.date-controls {
  display: grid;
  align-items: center;
  gap: 8px 12px;
  grid-template-columns:
    minmax(120px, 160px)
    minmax(100px, 120px)
    minmax(38px, 56px)
    minmax(120px, 160px)
    minmax(100px, 120px);
}

/* separator 'đến' styling */
.sep {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
  color: color-mix(in oklab, var(--v-theme-on-surface) 60%, transparent);
  hyphens: none;
  min-inline-size: 38px;
  overflow-wrap: normal;
  text-align: center;
  white-space: nowrap;
  word-break: keep-all;
}

/* Currency cell: label above, select below */
.currency-cell {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-inline-size: 0;
}

.currency-cell > .group-label {
  margin: 0;
  color: color-mix(in oklab, var(--v-theme-on-surface) 70%, transparent);
  font-size: 14px;
  font-weight: 600;
}

.currency-cell :deep(.v-field) {
  overflow: hidden;
  inline-size: 100%;
  max-inline-size: 100%;
}

.currency-cell :deep(.v-field__input) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Controls compact */
.ctrl,
.ctrl-big {
  inline-size: 100%;
  max-inline-size: 100%;
  min-inline-size: 0;
}

/* compact height */
.ctrl-big :deep(.v-field) {
  border-radius: 10px;
  min-block-size: 36px;
}

.ctrl-big :deep(.v-field__input) {
  font-size: 14px;
  line-height: 20px;
  padding-block: 6px;
  padding-inline: 10px;
}

/* ensure select text compact */
.ctrl-big :deep(.v-select__selection-text),
.ctrl-big :deep(.v-field__input input) {
  overflow: hidden;
  font-size: 14px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Responsive: stack on smaller screens */
@media (max-width: 1000px) {
  .range-grid {
    grid-template-columns: 1fr;
  }

  .date-controls {
    grid-template-columns: repeat(2, 1fr);
    row-gap: 8px;
  }

  .sep {
    grid-column: 1 / -1;
    justify-self: center;
  }
}

@media (max-width: 480px) {
  .date-controls {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
