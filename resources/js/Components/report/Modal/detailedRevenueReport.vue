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

    <!-- Filters -->
    <VRow class="filters" dense>
      <VCol cols="12" md="6">
        <div class="filter-group">
          <div class="filter-title">Ngày trả phòng</div>

          <div class="inline-controls">
            <div class="field">
              <div class="label">Từ ngày</div>
              <AppDateTimePicker
                v-model="date.toFrom"
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
                v-model="date.toTo"
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
          <div class="filter-title">Giờ trả phòng</div>

          <div class="inline-controls">
            <div class="field">
              <div class="label">Từ giờ</div>
              <AppDateTimePicker
                v-model="time.toFrom"
                class="picker picker--time"
                placeholder="HH:mm"
                :config="timeCfg"
                :clearable="true"
              />
            </div>

            <span class="sep">đến</span>

            <div class="field">
              <div class="label">Đến giờ</div>
              <AppDateTimePicker
                v-model="time.toTo"
                class="picker picker--time"
                placeholder="HH:mm"
                :config="timeCfg"
                :clearable="true"
              />
            </div>
          </div>
        </div>
      </VCol>

      <VCol cols="12" md="6">
        <div class="filter-group">
          <div class="filter-title">Nguồn đặt phòng</div>
          <AppSelect
            v-model="ui.otaName"
            :items="otaChannels"
            item-title="title"
            item-value="value"
            placeholder="Chọn nguồn..."
            clearable
            class="ctrl"
          />
        </div>
      </VCol>
    </VRow>

    <TableReport
      :headers="headerRevenueDetail"
      :rows="rowsDetail"
      :loading="loading"
      :page="active.page"
      :total="active.total"
      :size="active.size"
      :per-page-options="[10, 20, 50, 100]"
      :isPaginated="true"
      table-height="60vh"
      @update:page="onUpdatePage"
      @update:size="onUpdateSize"
    />

    <div class="my-5"></div>

    <VerticalTotals
      :headers="headerRevenueDetailTotal"
      :rows="rowsTotal"
      :loading="loading"
      :isPaginated="false"
      table-height="28vh"
      table-width="100%"
    />
  </VCard>
</template>

<script setup>
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import TableReport from "../Item/tableReport.vue";

import { useRevenueReport } from "@/stores/useRevenueReport";
import { otaChannels } from "@/utils/constants";
import {
  headerRevenueDetail,
  headerRevenueDetailTotal,
} from "@/utils/headerReport";

import { storeToRefs } from "pinia";
import { computed, onMounted, ref } from "vue";
import VerticalTotals from "../Item/verticalTotals.vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo doanh thu chi tiết" },
});

const report = useRevenueReport();
const { active } = storeToRefs(report);
const TAB = "detailed-revenue-report";

const ui = ref({ otaName: null });

const date = ref({ toFrom: null, toTo: null });

const time = ref({ toFrom: "", toTo: "" });

const timeCfg = {
  enableTime: true,
  noCalendar: true,
  dateFormat: "H:i",
  time_24hr: true,
};

const rowsDetail = computed(() => active.value?.rows ?? []);
const rowsTotal = computed(() => active.value?.detailTotals ?? []);
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

function pad2(x) {
  const s = String(x ?? "");
  return s.length === 1 ? `0${s}` : s;
}

function normalizeTimeToHms(val) {
  if (!val) return "";
  const s = String(val).trim();
  const parts = s.split(":");
  if (parts.length < 2) return "";
  const hh = pad2(parts[0]);
  const mm = pad2(parts[1]);
  const ss = parts[2] != null ? pad2(parts[2]) : "00";
  return `${hh}:${mm}:${ss}`;
}

function buildPayloadDetail() {
  return {
    dateFrom: date.value.toFrom || "",
    dateTo: date.value.toTo || "",
    timeFrom: normalizeTimeToHms(time.value.toFrom) || "",
    timeTo: normalizeTimeToHms(time.value.toTo) || "",
    otaName: ui.value.otaName || "",
    property: getSelectedPropertyIdFromLocalStorage(),
  };
}

let debounceTimer;
let lastSig = "";

async function runSearch() {
  const payload = buildPayloadDetail();
  const s = JSON.stringify(payload);
  if (s === lastSig) return;
  lastSig = s;

  await report.switchTab(TAB, { reset: false, fetchNow: false });
  report.replaceFilters(payload, TAB);
  await report.fetch(TAB);
}

function onSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(runSearch, 150);
}

onMounted(async () => {
  await report.switchTab(TAB, { reset: false, fetchNow: false });
  const f = active.value?.filters || {};

  ui.value.otaName = f.otaName ?? null;
  date.value.toFrom = f.dateFrom || null;
  date.value.toTo = f.dateTo || null;
  time.value.toFrom = (f.timeFrom || null).slice(0, 5);
  time.value.toTo = (f.timeTo || null).slice(0, 5);
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
  ui.value.otaName = null;
  date.value.toFrom = null;
  date.value.toTo = null;

  time.value.toFrom = "";
  time.value.toTo = "";
}

async function onClear() {
  clearTimeout(debounceTimer);

  resetUIToDefaults();

  lastSig = "";

  try {
    report.setPage(1, TAB);
  } catch (e) {}

  const emptyPayload = buildPayloadDetail();
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
  justify-content: space-between;
  padding: 16px;
  gap: 12px;
}

.title {
  margin: 0;
  font-weight: 700;
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

.ctrl,
.picker {
  inline-size: 100%;
}

.my-5 {
  margin-block: 24px;
}

:deep(.table-wrapper) {
  block-size: auto;
}

:deep(.table-wrapper .table-footer) {
  position: static;
}
</style>
