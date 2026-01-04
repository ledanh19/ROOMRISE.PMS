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

    <div class="filter-bar filter-one-line">
      <div class="group">
        <div class="group-label">Khung ngày</div>
        <AppSelect
          v-model="ui.dateType"
          :items="dateTypeOptions"
          item-title="title"
          item-value="value"
          placeholder="Loại ngày"
          clearable
          class="ctrl"
        />
      </div>

      <div class="group">
        <div class="group-label">Từ ngày</div>
        <AppDateTimePicker
          v-model="date.from"
          class="ctrl picker"
          placeholder="YYYY-MM-DD"
          :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
          :clearable="true"
        />
      </div>

      <div class="sep">đến</div>

      <div class="group">
        <div class="group-label">Đến ngày</div>
        <AppDateTimePicker
          v-model="date.to"
          class="ctrl picker"
          placeholder="YYYY-MM-DD"
          :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
          :clearable="true"
        />
      </div>

      <div class="group">
        <div class="group-label">Đơn vị tiền tệ</div>
        <AppSelect
          v-model="ui.currency"
          :items="currencyOptions"
          item-title="label"
          placeholder="Chọn đơn vị"
          clearable
          class="ctrl"
        />
      </div>

      <div class="group">
        <div class="group-label">OTA</div>
        <AppSelect
          v-model="ui.otaName"
          :items="otaChannels"
          item-title="title"
          item-value="value"
          placeholder="Chọn OTA"
          clearable
          class="ctrl"
        />
      </div>
    </div>

    <TableReport
      :headers="headerPerformanceReport"
      :rows="rowsDetail"
      :totals="rowsTotal"
      :loading="loading"
      table-height="50vh"
      :page="active.page"
      :total="active.total"
      :size="active.size"
      @update:page="onUpdatePage"
      @update:size="onUpdateSize"
    />
  </VCard>
</template>

<script setup>
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import TableReport from "../Item/tableReport.vue";

import { useRevenueReport } from "@/stores/useRevenueReport";
import {
  currencyOptions,
  dateTypeOptions,
  otaChannels,
} from "@/utils/constants";
import { headerPerformanceReport } from "@/utils/headerReport";

import { storeToRefs } from "pinia";
import { computed, onMounted, ref } from "vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo thanh toán" },
});

const report = useRevenueReport();
const { active } = storeToRefs(report);
const DAILY_TAB = "performance-report";

const ui = ref({
  dateType: "created_at",
  currency: null,
  otaName: null,
});

const date = ref({
  from: null,
  to: null,
});

const rowsDetail = computed(() => active.value?.rows ?? []);
const rowsTotal = computed(() => active.value?.efficiencyTotal ?? []);
const loading = computed(() => !!active.value?.loading);

function getSelectedPropertyIdFromLocalStorage() {
  try {
    const raw = localStorage.getItem("selectedProperty");
    if (!raw || raw === "null") return "";
    const v = JSON.parse(raw);
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
  } catch {
    return null;
  }
}

function buildTimeRange() {
  const from = (date.value.from || "").toString().trim();
  const to = (date.value.to || "").toString().trim();
  return from && to ? `${from} to ${to}` : null;
}

function buildPayload() {
  return {
    dateType: ui.value.dateType ?? "created_at",
    timeRange: buildTimeRange(),
    currency: ui.value.currency ?? null,
    otaName: ui.value.otaName ?? null,
    property: getSelectedPropertyIdFromLocalStorage(),
  };
}

let debounceTimer;
let lastSig = "";

async function runSearch() {
  const payload = buildPayload();
  const s = JSON.stringify(payload);
  if (s === lastSig) return;
  lastSig = s;

  await report.switchTab(DAILY_TAB, { reset: false, fetchNow: false });
  report.replaceFilters(payload, DAILY_TAB);
  await report.fetch(DAILY_TAB);
}

function onSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(runSearch, 200);
}

onMounted(async () => {
  await report.switchTab(DAILY_TAB, { reset: false, fetchNow: false });
  const f = active.value?.filters || {};

  if (f.dateType) ui.value.dateType = f.dateType;

  ui.value.currency = f.currency ?? null;
  ui.value.otaName = f.otaName ?? null;

  if (f.timeRange) {
    const [from, , to] = String(f.timeRange).split(" ");
    date.value.from = from || null;
    date.value.to = to || null;
  }
});

function onUpdatePage(p) {
  report.setPage(p, DAILY_TAB);
  report.fetch(DAILY_TAB);
}
function onUpdateSize(s) {
  report.setSize(s, DAILY_TAB);
  report.fetch(DAILY_TAB);
}

function resetUIToDefaults() {
  ui.value.dateType = "created_at";
  ui.value.currency = "";
  ui.value.otaName = "";
  date.value.from = "";
  date.value.to = "";
}

async function onClear() {
  clearTimeout(debounceTimer);

  resetUIToDefaults();

  lastSig = "";

  try {
    report.setPage(1, DAILY_TAB);
  } catch (e) {}

  const emptyPayload = buildPayload();
  console.log(emptyPayload);
  await report.switchTab(DAILY_TAB, { reset: false, fetchNow: false });
  report.replaceFilters(emptyPayload, DAILY_TAB);
  await report.fetch(DAILY_TAB);
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
  display: grid;
  align-items: end;
  background: color-mix(in oklab, var(--v-theme-on-surface) 4%, transparent);
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  gap: 8px 16px;
  grid-template-columns: 180px 240px 40px 240px 220px 240px;
  padding-block: 8px;
  padding-inline: 16px;
}

.group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-inline-size: 0;
}

.group-label {
  color: color-mix(in oklab, var(--v-theme-on-surface) 70%, transparent);
  font-size: 13px;
  font-weight: 600;
}

.sep {
  align-self: end;
  color: color-mix(in oklab, var(--v-theme-on-surface) 60%, transparent);
  font-size: 13px;
  line-height: 40px;
  margin-block: 0 6px;
  margin-inline: 0;
  text-align: center;
}

.ctrl,
.picker {
  display: block;
  inline-size: 100%;
}

@media (max-width: 1279px) {
  .filter-one-line {
    grid-template-columns: 180px minmax(200px, 1fr) minmax(200px, 1fr) 220px 240px;
  }

  .sep {
    display: none;
  }
}

@media (max-width: 1023px) {
  .filter-one-line {
    grid-template-columns: 1fr 1fr;
    row-gap: 10px;
  }

  .sep {
    display: none;
  }
}

@media (max-width: 767px) {
  .filter-one-line {
    gap: 10px;
    grid-template-columns: 1fr;
    padding-block: 10px;
  }
}
</style>
