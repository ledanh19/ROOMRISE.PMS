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
          Xem
        </VBtn>
      </div>
    </div>

    <div class="filter-one-line">
      <div class="group">
        <div class="group-label">Phạm vi ngày</div>
        <div class="range-grid">
          <AppSelect
            v-model="form.monthFrom"
            :items="monthOptions"
            item-title="title"
            item-value="value"
            placeholder="Tháng"
            class="ctrl ctrl-sm"
            clearable
          />
          <AppSelect
            v-model="form.yearFrom"
            :items="yearOptions"
            item-title="title"
            item-value="value"
            placeholder="Năm"
            class="ctrl ctrl-sm"
            clearable
          />
          <div class="sep">đến</div>
          <AppSelect
            v-model="form.monthTo"
            :items="monthOptions"
            item-title="title"
            item-value="value"
            placeholder="Tháng"
            class="ctrl ctrl-sm"
            clearable
          />
          <AppSelect
            v-model="form.yearTo"
            :items="yearOptions"
            item-title="title"
            item-value="value"
            placeholder="Năm"
            class="ctrl ctrl-sm"
            clearable
          />

          <div class="cell">
            <div class="group-label">Tiền tệ</div>
            <AppSelect
              v-model="form.currency"
              :items="currencyOptions"
              item-title="label"
              item-value="value"
              placeholder="Chọn đơn vị"
              clearable
              class="ctrl ctrl-md"
            />
          </div>

          <div class="cell">
            <div class="group-label">Loại phòng</div>
            <AppSelect
              v-model="form.roomId"
              :items="roomOptions"
              item-title="title"
              item-value="value"
              placeholder="Chọn loại phòng"
              clearable
              class="ctrl ctrl-md"
            />
          </div>
        </div>
      </div>
    </div>

    <TableReport
      :headers="headersRoom"
      :rows="rows"
      :loading="loading"
      table-height="60vh"
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
import TableReport from "../Item/tableReport.vue";

import { useRevenueReport } from "@/stores/useRevenueReport";
import { currencyOptions } from "@/utils/constants";
import { storeToRefs } from "pinia";
import { computed, onMounted, ref } from "vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo doanh thu theo loại phòng" },
});

const report = useRevenueReport();
const { active } = storeToRefs(report);

const TAB = "revenue-reports-room-type";

const now = new Date();
const form = ref({
  monthFrom: null,
  yearFrom: null,
  monthTo: null,
  yearTo: null,
  currency: null,
  roomId: null,
});

const monthOptions = Array.from({ length: 12 }, (_, i) => ({
  title: `Tháng ${i + 1}`,
  value: i + 1,
}));
const yearOptions = (() => {
  const y = now.getFullYear();
  const arr = [];
  for (let k = y + 1; k >= y - 6; k--) arr.push({ title: String(k), value: k });
  return arr;
})();

const headersRoom = computed(() =>
  active.value?.headerRevenueRoom?.length
    ? active.value.headerRevenueRoom
    : [
        { key: "date", title: "Ngày", align: "center" },
        { key: "totalRevenue", title: "Tổng", align: "center" },
      ]
);
const roomOptions = computed(() =>
  (active.value?.headerRevenueRoom || [])
    .filter((h) => h.key !== "date" && h.key !== "totalRevenue")
    .map((h) => ({
      title: h.title,
      value: Number(String(h.key).split("_").pop()) || null,
    }))
);

const rows = computed(() => active.value?.rows ?? []);
const loading = computed(() => !!active.value?.loading);
console.log(rows);
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
    roomId: form.value.roomId ?? "",
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

  const property = getSelectedPropertyIdFromLocalStorage();
  report.replaceFilters({ property }, TAB);

  await report.buildHeaderRevenueRoom();

  const f = active.value?.filters || {};
  form.value.monthFrom = f.monthFrom ?? form.value.monthFrom;
  form.value.yearFrom = f.yearFrom ?? form.value.yearFrom;
  form.value.monthTo = f.monthTo ?? form.value.monthTo;
  form.value.yearTo = f.yearTo ?? form.value.yearTo;
  form.value.currency = f.currency ?? null;
  form.value.roomId = f.roomId ?? null;
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
  form.value.roomId = null;
  form.value.otaName = null;
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

<style>
.report-card {
  overflow: hidden;
  box-sizing: border-box;
  border: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 8%, transparent);
  border-radius: 14px;
  background: var(--v-theme-surface);
  max-inline-size: 100%;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  gap: 12px;
  padding-block: 14px;
  padding-inline: 16px;
}

.title {
  margin: 0;
  font-weight: 700;
  min-inline-size: 0;
}

.actions {
  flex-shrink: 0;
}

.filter-one-line {
  box-sizing: border-box;
  background: color-mix(in oklab, var(--v-theme-on-surface) 4%, transparent);
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  max-inline-size: 100%;
  overflow-x: hidden;
  padding-block: 10px;
  padding-inline: 16px;
}

.group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-inline-size: 0;
}

.group-label {
  color: color-mix(in oklab, var(--v-theme-on-surface) 70%, transparent);
  font-size: 13.5px;
  font-weight: 600;
}

.range-grid {
  display: grid;
  align-items: end;
  gap: 8px;
  grid-template-columns:
    minmax(160px, 180px)
    minmax(120px, 140px)
    minmax(38px, 60px)
    minmax(160px, 180px)
    minmax(120px, 140px)
    minmax(260px, 1fr)
    minmax(240px, 1fr);
  min-inline-size: 0;
}

.sep {
  display: flex;
  align-items: center;
  justify-content: center;
  color: color-mix(in oklab, var(--v-theme-on-surface) 60%, transparent);
  hyphens: none;
  justify-self: center;
  line-height: 1;
  margin-block-end: 8px;
  min-inline-size: 38px;
  overflow-wrap: normal;
  text-align: center;
  white-space: nowrap;
  word-break: keep-all;
}

.cell {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-inline-size: 0;
}

.ctrl {
  inline-size: 100%;
  min-inline-size: 0;
}

.ctrl .v-input,
.ctrl .v-field,
.ctrl .v-field__input,
.ctrl .v-select,
.ctrl .v-select__selection-text {
  inline-size: 100%;
  min-inline-size: 0 !important;
}

.ctrl-sm .v-field,
.ctrl-md .v-field {
  border-radius: 10px;
}

.ctrl-sm .v-field {
  min-block-size: 38px;
}

.ctrl-md .v-field {
  min-block-size: 40px;
}

.ctrl-sm .v-field__input,
.ctrl-sm .v-select__selection-text {
  overflow: hidden;
  font-size: 13.8px;
  line-height: 18px;
  padding-block: 6px 7px;
  padding-inline: 10px;
  text-overflow: ellipsis;
  white-space: nowrap;
  word-break: keep-all;
}

.ctrl-md .v-field__input,
.ctrl-md .v-select__selection-text {
  overflow: hidden;
  font-size: 14px;
  line-height: 20px;
  padding-block: 8px;
  padding-inline: 12px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ctrl .v-field {
  box-shadow: none !important;
}

.range-grid .v-field__input {
  word-break: normal !important;
}

@media (max-width: 1280px) {
  .range-grid {
    grid-template-columns:
      clamp(120px, 22vw, 1fr) clamp(90px, 16vw, 1fr) 26px
      clamp(120px, 22vw, 1fr) clamp(90px, 16vw, 1fr)
      minmax(160px, 1fr) minmax(160px, 1fr);
  }
}

@media (max-width: 1000px) {
  .range-grid {
    grid-template-columns: 1fr 1fr;
    row-gap: 10px;
  }

  .sep {
    grid-column: 1 / -1;
    justify-self: center;
  }

  .cell {
    grid-column: 1 / -1;
  }
}

@media (max-width: 480px) {
  .toolbar {
    padding-inline: 12px;
  }

  .filter-one-line {
    padding-inline: 12px;
  }
}
</style>
