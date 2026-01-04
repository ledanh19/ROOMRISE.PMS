<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";

const props = defineProps({
  headers: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  totals: { type: Object, default: () => ({}) },
  tableHeight: { type: [Number, String], default: "65vh" },
  tableWidth: { type: [String, Number], default: "100%" },
  total: { type: Number, default: 0 },
  page: { type: Number, default: 1 },
  size: { type: Number, default: 10 },
  loading: { type: Boolean, default: false },
  perPageOptions: { type: Array, default: () => [10, 20, 50, 100] },
  isPaginated: { type: Boolean, default: true },
});
const emit = defineEmits(["update:page", "update:size"]);

const wrapperStyle = computed(() => ({
  "--tbl-w":
    typeof props.tableWidth === "number"
      ? `${props.tableWidth}px`
      : String(props.tableWidth),
}));

const sortBy = ref([]);

const safeHeaders = computed(() =>
  (props.headers || []).map((h) => ({ ...h, sortable: false }))
);

function colStyle(col) {
  if (!col) return {};
  if (col.width == null) return {};

  const raw = col.width;
  const w = typeof raw === "number" ? `${raw}px` : String(raw);

  if (col.wrap === false) {
    return { minWidth: w, whiteSpace: "nowrap" };
  }

  return { minWidth: w };
}

const numericKeys = computed(() => {
  const keywords = [
    "price",
    "total",
    "amount",
    "tax",
    "discount",
    "fee",
    "sum",
    "vnd",
    "adr",
    "revpar",
    "roomrevenue",
    "commissionfee",
    "bookingCom",
    "walkIn",
    "agoda",
    "expedia",
    "airbnb",
    "offline",
    "ctrip",
  ];
  const deniedKeyWord = ["roomRevenuepct", "totalrooms", "totalrevenuepct"];

  return new Set(
    safeHeaders.value
      .filter((h) => {
        if (h.money === true) return true;
        if (typeof h.key !== "string") return false;
        const key = h.key.toLowerCase();
        const hasKeyword = keywords.some((k) => key.includes(k.toLowerCase()));
        const hasDenied = deniedKeyWord.some((d) =>
          key.includes(d.toLowerCase())
        );
        return hasKeyword && !hasDenied;
      })
      .map((h) => h.key)
  );
});

function alignClass(col) {
  const a = (col?.align ?? "").toString().toLowerCase();
  if (a === "center") return "text-center";
  if (a === "right" || a === "end") return "text-end";
  if (a === "left" || a === "start") return "text-start";
  return numericKeys.value.has(col?.key) ? "text-end" : "text-start";
}

const hasTotals = computed(
  () => !!props.totals && Object.keys(props.totals).length > 0
);

function formatMoney(value) {
  const n = Number(value);
  if (!Number.isFinite(n) || value === null) return "";
  if (n === 0) return "0";
  return ` ${n.toLocaleString("vi-VN", {
    maximumFractionDigits: 0,
  })} ₫`;
}

function onUpdatePage(p) {
  if (Number(p) !== Number(props.page)) emit("update:page", Number(p));
}
function onUpdateSize(s) {
  if (Number(s) !== Number(props.size)) emit("update:size", Number(s));
}

const pageCount = computed(() =>
  Math.max(1, Math.ceil(Number(props.total || 0) / Number(props.size || 1)))
);

const rangeText = computed(() => {
  const start = Math.min((props.page - 1) * props.size + 1, props.total || 0);
  const end = Math.min(props.page * props.size, props.total || 0);
  return `${start}–${end} of ${props.total || 0}`;
});

const MIN_SPINNER_MS = 1000;
const localLoading = ref(false);
let _startedAt = 0;
let _timer = null;

watch(
  () => props.loading,
  (val) => {
    if (val) {
      _startedAt = Date.now();
      localLoading.value = true;
      if (_timer) {
        clearTimeout(_timer);
        _timer = null;
      }
    } else {
      const elapsed = Date.now() - _startedAt;
      const remain = Math.max(0, MIN_SPINNER_MS - elapsed);
      _timer = setTimeout(() => {
        localLoading.value = false;
        _timer = null;
      }, remain);
    }
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  if (_timer) clearTimeout(_timer);
});

const displayedRows = computed(() => (localLoading.value ? [] : props.rows));

function isSectionRow(item) {
  return !!item && item._section === true;
}
</script>

<template>
  <!-- Frame wrapper -->
  <div class="frame">
    <!-- Inner table card -->
    <div class="table-wrap" :style="wrapperStyle">
      <VDataTableServer
        class="custom-table"
        :headers="safeHeaders"
        :items="displayedRows"
        v-model:sort-by="sortBy"
        :must-sort="false"
        :multi-sort="false"
        fixed-header
        :height="tableHeight"
        :items-length="Number(total)"
        :page="page"
        :items-per-page="size"
        :loading="localLoading"
        :items-per-page-options="perPageOptions"
        :hide-default-footer="true"
      >
        <template #loading>
          <div class="table-loader">
            <VProgressCircular indeterminate size="24" />
            <span>Đang tải dữ liệu…</span>
          </div>
        </template>

        <template #headers="{ columns }">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              class="th"
              :class="[alignClass(col)]"
              :style="colStyle(col)"
            >
              <div class="th-title" :title="col.title">{{ col.title }}</div>
            </th>
          </tr>
        </template>

        <template #item="{ item, index }">
          <tr
            :class="[
              index % 2 === 1 ? 'zebra' : '',
              isSectionRow(item) ? 'section' : '',
            ]"
          >
            <td
              v-for="col in safeHeaders"
              :key="col.key"
              :class="[alignClass(col)]"
              :style="colStyle(col)"
            >
              <span v-if="numericKeys.has(col.key)">{{
                formatMoney(item?.[col.key])
              }}</span>
              <span v-else>{{ item?.[col.key] ?? "" }}</span>
            </td>
          </tr>
        </template>

        <template #body.append>
          <tr v-if="hasTotals" class="totals-row">
            <td
              v-for="col in safeHeaders"
              :key="col.key"
              :class="[alignClass(col), 'totals-cell']"
              :style="colStyle(col)"
            >
              <strong v-if="numericKeys.has(col.key)">
                {{ formatMoney(totals?.[col.key]) }}
              </strong>
              <strong v-else>{{ totals?.[col.key] ?? "" }}</strong>
            </td>
          </tr>
        </template>
      </VDataTableServer>

      <div class="table-footer" v-if="isPaginated">
        <div class="footer-left">
          <VSelect
            density="comfortable"
            variant="outlined"
            hide-details
            :items="perPageOptions"
            :model-value="size"
            style="max-inline-size: 120px"
            label="Số dòng"
            @update:model-value="onUpdateSize"
          />
          <span class="range">{{ rangeText }}</span>
        </div>

        <div class="footer-right">
          <VPagination
            class="no-arrows"
            :model-value="page"
            :length="pageCount"
            :total-visible="7"
            density="comfortable"
            :show-first-last-page="false"
            @update:model-value="onUpdatePage"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.frame {
  padding: 12px;
  border: 1px solid rgba(0, 0, 0, 6%);
  border-radius: 14px;
  background: transparent;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 6%);
  color: rgba(var(--v-theme-on-surface), 0.95);
  font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji",
    "Segoe UI Symbol", sans-serif;
  font-size: 13px;
  margin-block: 12px;
}

.table-wrap {
  position: relative;
  overflow: auto;
  padding: 6px;
  border-radius: 10px;
  background: var(--v-theme-surface);
}

.header-common {
  display: flex;
  align-items: center;
  border: 1px solid transparent;
  border-radius: 8px;
  background: linear-gradient(
    180deg,
    color-mix(in oklab, var(--v-theme-surface) 94%, transparent),
    var(--v-theme-surface)
  );
  box-shadow: 0 1px 0
    color-mix(in oklab, var(--v-theme-on-surface) 6%, transparent) inset;
  gap: 12px;
  padding-block: 10px;
  padding-inline: 12px;
}

.toolbar,
.header-row {
  position: sticky;
  z-index: 10;
  backdrop-filter: blur(6px);
  inset-block-start: 0;
  margin-block-end: 8px;
}

.toolbar .title,
.header-row .title,
.heading .title {
  display: inline-block;
  margin: 0;
  color: rgba(var(--v-theme-on-surface), 0.95);
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 0.01em;
  line-height: 1.05;
}

.toolbar .actions,
.header-row .actions {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-inline-start: auto;
}

.toolbar,
.header-row,
.custom-table .th {
  background: linear-gradient(
    180deg,
    color-mix(in oklab, var(--v-theme-surface) 92%, transparent),
    var(--v-theme-surface)
  );
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 14%, transparent);
  color: rgba(var(--v-theme-on-surface), 0.95);
  font-family: inherit;
  font-size: 13px;
}

.custom-table table {
  border-collapse: separate;
  border-spacing: 0;
  inline-size: var(--tbl-w, 100%);
  table-layout: fixed;
}

.custom-table th,
.custom-table td {
  overflow: hidden;
  background-clip: padding-box;
  block-size: 38px;
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  font-family: inherit;
  font-size: inherit;
  padding-block: 8px;
  padding-inline: 12px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.custom-table .th {
  position: sticky;
  z-index: 3;
  display: table-cell;
  background: linear-gradient(
    180deg,
    color-mix(in oklab, var(--v-theme-surface) 92%, transparent),
    var(--v-theme-surface)
  );
  font-weight: 600;
  inset-block-start: 0;
  padding-block: 8px;
  vertical-align: middle;
}

.th-title {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  color: rgba(var(--v-theme-on-surface), 0.85);
  font-family: inherit;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.02em;
  -webkit-line-clamp: 2;
  line-height: 1.1;
  text-align: center;
  white-space: normal;
  word-break: keep-all;
}

.custom-table th:first-child,
.custom-table td:first-child {
  z-index: 2;
  background: var(--v-theme-surface);
  box-shadow: 1px 0 0 0
    color-mix(in oklab, var(--v-theme-on-surface) 12%, transparent);
  inset-inline-start: 0;
}

.custom-table th.text-end,
.custom-table td.text-end {
  font-family: inherit;
  font-variant-numeric: tabular-nums;
  text-align: end;
}

.custom-table th.text-center,
.custom-table td.text-center {
  font-family: inherit;
  text-align: center;
}

.custom-table th.text-start,
.custom-table td.text-start {
  font-family: inherit;
  text-align: start;
}

.custom-table tbody tr:nth-child(odd) td {
  background: color-mix(in oklab, var(--v-theme-on-surface) 3%, transparent);
}

.custom-table tbody tr:hover td {
  background: color-mix(in oklab, var(--v-theme-primary) 8%, transparent);
}

.custom-table .v-data-table-header__sort-icon {
  display: none !important;
}

.table-loader {
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(var(--v-theme-on-surface), 0.8);
  font-family: inherit;
  font-weight: 500;
  gap: 10px;
  padding-block: 16px;
}

.custom-table tbody tr.totals-row td {
  z-index: 2;
  background: var(--v-theme-surface) !important;
  border-block-end: 0;
  border-block-start: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 14%, transparent);
  font-family: inherit;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  inset-block-end: 0;
}

.custom-table tbody tr.totals-row td:first-child {
  z-index: 4;
  box-shadow: 1px 0 0 0
    color-mix(in oklab, var(--v-theme-on-surface) 12%, transparent);
}

.custom-table tbody tr.totals-row td.text-end {
  font-family: inherit;
  font-variant-numeric: tabular-nums;
  text-align: end;
}

.custom-table tbody tr.section td {
  font-weight: 800;
}

.table-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-family: inherit;
  gap: 12px;
  padding-block: 10px;
  padding-inline: 14px;
}

.footer-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.range {
  opacity: 0.8;
}

.footer-right {
  display: flex;
  align-items: center;
  gap: 6px;
}

@media (max-width: 1200px) {
  .custom-table th,
  .custom-table td {
    font-size: 12px;
    padding-block: 8px;
    padding-inline: 8px;
  }

  .frame {
    padding: 8px;
    border-radius: 10px;
  }
}

.no-arrows .v-pagination__first,
.no-arrows .v-pagination__last,
.no-arrows .v-pagination__prev,
.no-arrows .v-pagination__next {
  display: none !important;
}

.toolbar,
.header-row,
.header-row .heading,
.toolbar .title {
  background: linear-gradient(
    180deg,
    color-mix(in oklab, var(--v-theme-surface) 92%, transparent),
    var(--v-theme-surface)
  );
}

.table-wrap > .toolbar,
.table-wrap > .header-row {
  margin-block-end: 6px;
}
</style>
