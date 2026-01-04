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
      <div class="range-cluster">
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
      </div>

      <div class="group">
        <div class="group-label">Đơn vị tiền tệ</div>
        <AppSelect
          v-model="form.currency"
          :items="currencyOptions"
          item-title="label"
          item-value="value"
          placeholder="Chọn đơn vị"
          clearable
          class="ctrl"
        />
      </div>

      <div class="group">
        <div class="group-label">OTA</div>
        <AppSelect
          v-model="form.otaName"
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
      :headers="headerPerformanceByRoomType"
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
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import TableReport from "../Item/tableReport.vue";

import { useRevenueReport } from "@/stores/useRevenueReport";
import { currencyOptions, otaChannels } from "@/utils/constants";
import { headerPerformanceByRoomType } from "@/utils/headerReport";

import { storeToRefs } from "pinia";
import { computed, onMounted, ref } from "vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo doanh thu theo đặt phòng" },
});

const report = useRevenueReport();
const { active } = storeToRefs(report);
const TAB = "activity-report";

const form = ref({
  currency: null,
  otaName: null,
});

const date = ref({
  from: null,
  to: null,
});

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

function toDateStr(v) {
  if (!v) return "";
  return String(v).slice(0, 10);
}

function buildTimeRange(from, to) {
  const f = toDateStr(from);
  const t = toDateStr(to);
  if (!f && !t) return "";
  if (f && t) return `${f} to ${t}`;

  const d = f || t;
  return `${d} to ${d}`;
}

function parseTimeRange(s) {
  const str = String(s || "");
  const parts = str.split(/\s*to\s*/i);
  if (parts.length === 2) {
    const from = parts[0] || null;
    const to = parts[1] || null;
    return { from, to };
  }
  return { from: null, to: null };
}

function buildPayload() {
  return {
    timeRange: buildTimeRange(date.value.from, date.value.to),
    currency: form.value.currency ?? "",
    otaName: form.value.otaName ?? "",
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
  form.value.currency = f.currency ?? null;
  form.value.otaName = f.otaName ?? null;

  if (f.timeRange) {
    const { from, to } = parseTimeRange(f.timeRange);
    date.value.from = from;
    date.value.to = to;
  } else {
    date.value.from = f.dateFrom ?? null;
    date.value.to = f.dateTo ?? null;
  }
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
  form.value.currency = null;
  form.value.otaName = null;
  date.value.from = null;
  date.value.to = null;
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
  display: grid;
  align-items: end;
  background: color-mix(in oklab, var(--v-theme-on-surface) 4%, transparent);
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  gap: 12px 14px;
  grid-template-columns: repeat(6, minmax(180px, 1fr));
  padding-block: 12px;
  padding-inline: 18px;
}

.range-cluster {
  display: flex;
  flex-wrap: nowrap;
  align-items: end;
  column-gap: 10px;
  grid-column: span 3;
  min-inline-size: 0;
}

.range-cluster .group {
  inline-size: clamp(180px, 22vw, 260px);
  min-inline-size: 0;
}

.range-cluster .sep {
  align-self: end;
  color: color-mix(in oklab, var(--v-theme-on-surface) 60%, transparent);
  margin-block-end: 6px;
  padding-inline: 2px;
  white-space: nowrap;
}

.group-label {
  color: color-mix(in oklab, var(--v-theme-on-surface) 70%, transparent);
  font-size: 14px;
  font-weight: 600;
  margin-block-end: 4px;
}

.group .ctrl {
  inline-size: 100%;
  min-inline-size: 0;
}

.picker :deep(.v-field) {
  min-block-size: 40px;
}

.picker :deep(.v-field__input) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (max-width: 1280px) {
  .filter-one-line {
    grid-template-columns: repeat(4, minmax(180px, 1fr));
  }

  .range-cluster {
    grid-column: span 4;
  }
}

@media (max-width: 920px) {
  .filter-one-line {
    gap: 10px 12px;
    grid-template-columns: repeat(2, minmax(160px, 1fr));
  }

  .range-cluster {
    flex-wrap: wrap;
    grid-column: span 2;
    row-gap: 8px;
  }

  .range-cluster .group {
    inline-size: 100%;
  }

  .range-cluster .sep {
    order: -1;
    margin: 0;
  }
}
</style>
