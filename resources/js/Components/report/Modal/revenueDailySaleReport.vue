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
          :loading="loading"
          :disabled="loading"
          @click="onFilter"
        >
          Xem
        </VBtn>
      </div>
    </div>

    <div class="filter-bar">
      <div class="filter-grid">
        <div class="group">
          <div class="group-label">Phạm vi ngày</div>
          <div class="range">
            <AppDateTimePicker
              v-model="ui.dateFrom"
              class="ctrl"
              placeholder="Từ ngày"
              :config="dateCfg"
              :clearable="true"
            />
            <span class="sep">đến</span>
            <AppDateTimePicker
              v-model="ui.dateTo"
              class="ctrl"
              placeholder="Đến ngày"
              :config="dateCfg"
              :clearable="true"
            />
          </div>
        </div>

        <div class="group">
          <div class="group-label">Tiền tệ</div>
          <AppSelect
            v-model="ui.currency"
            :items="currencyOptions"
            item-title="label"
            item-value="value"
            placeholder="VND"
            clearable
            class="ctrl"
          />
        </div>

        <div class="group">
          <div class="group-label">Nguồn</div>
          <AppSelect
            v-model="ui.otaName"
            :items="otaChannels"
            item-title="title"
            item-value="value"
            placeholder="Chọn nguồn đặt phòng…"
            clearable
            class="ctrl"
          />
        </div>
      </div>
    </div>

    <TableReport
      :headers="headerDailySaleReport"
      :rows="rows"
      :totals="totals"
      :loading="loading"
      :isPaginated="true"
      table-height="56vh"
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
import { useRevenueReport } from "@/stores/useRevenueReport";
import { currencyOptions, otaChannels } from "@/utils/constants";
import { headerDailySaleReport } from "@/utils/headerReport";
import { storeToRefs } from "pinia";
import { computed, ref } from "vue";
import TableReport from "../Item/tableReport.vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo doanh thu hằng ngày" },
});

const report = useRevenueReport();
const TAB = "daily-sale-report";
const { active } = storeToRefs(report);

const rows = computed(() => active.value?.rows ?? []);
const totals = computed(() => active.value?.totals ?? {});
const loading = computed(() => !!active.value?.loading);

const today = new Date();
const y = today.getFullYear();
const m = String(today.getMonth() + 1).padStart(2, "0");
const d = String(today.getDate()).padStart(2, "0");

const ui = ref({
  dateFrom: null,
  dateTo: null,
  currency: null,
  otaName: null,
});

const dateCfg = { enableTime: false, dateFormat: "Y-m-d" };

function getSelectedPropertyIdFromLocalStorage() {
  try {
    const raw = localStorage.getItem("selectedProperty");
    if (!raw || raw === "null") return "";
    const v = JSON.parse(raw);
    const id = v?.id ?? v?.value ?? v;
    const n = Number(id);
    return Number.isFinite(n) ? n : id || "";
  } catch {
    return "";
  }
}

function buildTimeRange(from, to) {
  const f = (from || "").trim();
  const t = (to || "").trim();

  if (f && t) return `${f} to ${t}`;
  if (f) return f;
  if (t) return t;
  return "";
}

function buildFilters() {
  return {
    timeRange: buildTimeRange(ui.value.dateFrom, ui.value.dateTo) || "",
    currency: ui.value.currency || "",
    otaName: ui.value.otaName || "",
    property: getSelectedPropertyIdFromLocalStorage() || "",
  };
}

let debounceTimer = null;
let lastSig = "";
async function runSearch() {
  const payload = buildFilters();
  const sig = JSON.stringify(payload);
  if (sig === lastSig) return;
  lastSig = sig;
  await report.switchTab(TAB, { reset: false, fetchNow: false });
  report.replaceFilters(payload, TAB);
  await report.fetch(TAB);
}

function onFilter() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(runSearch, 120);
}

function onUpdatePage(p) {
  report.setPage(p, TAB);
  report.fetch(TAB);
}
function onUpdateSize(s) {
  report.setSize(s, TAB);
  report.fetch(TAB);
}
function resetUIToDefaults() {
  ui.value.dateFrom = null;
  ui.value.dateTo = null;
  ui.value.currency = null;
  ui.value.otaName = null;
}

async function onClear() {
  clearTimeout(debounceTimer);

  resetUIToDefaults();

  lastSig = "";

  try {
    report.setPage(1, TAB);
  } catch (e) {}

  const emptyPayload = buildFilters();
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

.filter-bar {
  background: color-mix(in oklab, var(--v-theme-on-surface) 4%, transparent);
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  padding-block: 12px;
  padding-inline: 16px;
}

.filter-grid {
  display: grid;
  align-items: end;
  gap: 12px 16px;
  grid-template-columns:
    minmax(380px, 520px) minmax(220px, 320px) minmax(240px, 360px)
    auto;
}

.group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-inline-size: 0;
}

.group-label {
  color: color-mix(in oklab, var(--v-theme-on-surface) 70%, transparent);
  font-size: 13px;
  font-weight: 600;
}

.range {
  display: grid;
  align-items: center;
  gap: 8px;
  grid-template-columns: 1fr auto 1fr;
}

.sep {
  color: color-mix(in oklab, var(--v-theme-on-surface) 60%, transparent);
  font-size: 13px;
  text-align: center;
}

.ctrl {
  inline-size: 100%;
}

@media (max-width: 1280px) {
  .filter-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }

  .range {
    grid-template-columns: 1fr auto 1fr;
  }
}
</style>
