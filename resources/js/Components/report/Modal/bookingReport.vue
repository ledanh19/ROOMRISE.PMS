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
          :loading="loading || loadingMore"
          :disabled="loading || loadingMore"
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
            class="ctrl w-160"
          />
        </div>
        <div class="group group--ota">
          <div class="group-label">Đến</div>
          <AppDateTimePicker
            v-model="dateTo"
            :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
            placeholder="Đến"
            clearable
            class="ctrl w-160"
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
      <div
        ref="viewportRef"
        class="table-viewport"
        @scroll="onViewportScroll"
        @wheel.passive="onWheel"
      >
        <table class="grid-table">
          <colgroup>
            <col v-for="c in headers" :key="c.key" :style="colStyle(c)" />
          </colgroup>

          <thead class="thead-sticky">
            <tr>
              <th v-for="c in headers" :key="c.key" :class="alignClass(c)">
                <div class="th-title">{{ c.title }}</div>
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(r, i) in rows"
              :key="r?.id ?? i"
              :class="i % 2 ? 'zebra' : ''"
            >
              <td v-for="c in headers" :key="c.key" :class="alignClass(c)">
                <span v-if="isMoney(c)"
                  >{{ money(r?.[c.key], r?.currency) }} ₫</span
                >
                <span v-else>{{ r?.[c.key] ?? "" }}</span>
              </td>
            </tr>

            <tr v-if="loading || loadingMore">
              <td :colspan="headers.length" class="loading-more">
                <VProgressCircular indeterminate size="20" class="mr-2" />
                {{ loading ? "Đang tải dữ liệu…" : "Đang tải thêm…" }}
              </td>
            </tr>

            <tr v-if="!loading && !loadingMore && rows.length === 0">
              <td :colspan="headers.length" class="empty">Không có dữ liệu</td>
            </tr>

            <tr>
              <td :colspan="headers.length" style="padding: 0">
                <div ref="sentinel" class="io-sentinel" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        class="totals-outside"
        ref="totalsOutsideRef"
        @scroll="onTotalsScroll"
      >
        <table class="grid-table totals-table">
          <colgroup>
            <col v-for="c in headers" :key="c.key" :style="colStyle(c)" />
          </colgroup>
          <tfoot>
            <tr class="footer-sep">
              <td :colspan="headers.length"></td>
            </tr>
            <tr class="totals-row">
              <td v-for="c in headers" :key="c.key" :class="alignClass(c)">
                <strong>{{ totalsCell(c) }}</strong>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </VCard>
</template>

<script setup>
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
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
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  watch,
} from "vue";

const TAB_KEY = "booking-report";
const PAGE_SIZE = 10;
const bottomGap = 80;

const report = useReportManager();
const propertyStore = usePropertyStore();
const { active } = storeToRefs(report);

const title = "Báo cáo đặt phòng";
const ready = ref(false);

const viewportRef = ref(null);
const sentinel = ref(null);
const totalsOutsideRef = ref(null);
const isSyncingScroll = ref(false);

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
  excludeSource: "",
});

let io = null;

const headers = computed(() => (headerBookingReport || []).filter(Boolean));
const loading = computed(() => !!active.value?.loading);
const rows = computed(() =>
  Array.isArray(active.value?.rows) ? active.value.rows : []
);
const footer = computed(() => active.value?.paymentTotalsFooter ?? {});

const loadingMore = ref(false);
const reachedEnd = ref(false);
const hasFirstLoaded = ref(false);
const autofillRunning = ref(false);
const userHasInteracted = ref(false);

function colStyle(c) {
  const w = c?.width;
  return { width: typeof w === "number" ? `${w}px` : String(w || "auto") };
}
function alignClass(c) {
  const a = String(c.align || "").toLowerCase();
  return a === "end"
    ? "text-end"
    : a === "center"
    ? "text-center"
    : "text-start";
}
function isMoney(c) {
  return !!c.money || /amount|fee|total/i.test(String(c.key));
}
function money(v) {
  const n = Number(v);
  return Number.isFinite(n)
    ? n.toLocaleString("vi-VN", { maximumFractionDigits: 2 })
    : "";
}
function buildOtaNameParam() {
  return String(filters.otaName || "");
}
function buildTimeRange() {
  const from = String(dateFrom.value || "").trim();
  const to = String(dateTo.value || "").trim();
  return from && to ? `${from} to ${to}` : filters.timeRange || "";
}
function canScroll(el) {
  return el && el.scrollHeight > el.clientHeight + 1;
}
function normalizeOtaSend(name) {
  if (!name) return "";
  const n = String(name).trim();
  if (/booking/i.test(n)) return "bookingCom";
  if (/walk-?in/i.test(n)) return "walkIn";
  if (/agoda/i.test(n)) return "agoda";
  if (/expedia/i.test(n)) return "expedia";
  if (/airbnb/i.test(n)) return "airbnb";
  if (/ctrip/i.test(n)) return "ctrip";
  if (/widget/i.test(n)) return "widget";
  return n;
}
function totalsCell(c) {
  const t = footer.value || {};
  if (c.key === "fullName") return "Tổng cộng";
  if (c.key === "customerPaymentAmount") {
    const v = money(t.customerPaymentAmount);
    return v ? `${v} ₫` : "";
  }
  if (c.key === "commissionFee") {
    const v = money(t.commissionFee);
    return v ? `${v} ₫` : "";
  }
  if (c.key === "otaFee") {
    const v = money(t.otaFee);
    return v ? `${v} ₫` : "";
  }
  if (c.key === "totalAmount") {
    const v = money(t.totalAmount);
    return v ? `${v} ₫` : "";
  }
  return "";
}

function maybeLoadNearBottom(el) {
  if (!hasFirstLoaded.value || loadingMore.value || reachedEnd.value) return;
  if (!el) return;
  const atBottom =
    Math.abs(el.scrollTop + el.clientHeight - el.scrollHeight) <= 1;
  if (atBottom) loadMore();
}

let rafId = 0;
function onViewportScroll(e) {
  userHasInteracted.value = true;
  const el = e?.currentTarget || viewportRef.value;
  if (!el) return;

  if (rafId) cancelAnimationFrame(rafId);
  rafId = requestAnimationFrame(() => {
    maybeLoadNearBottom(el);
  });

  try {
    if (!isSyncingScroll.value && totalsOutsideRef.value) {
      isSyncingScroll.value = true;
      totalsOutsideRef.value.scrollLeft = el.scrollLeft;
    }
  } finally {
    requestAnimationFrame(() => {
      isSyncingScroll.value = false;
    });
  }
}

const wheelCooldownMs = 300;
let lastWheelTs = 0;
function onWheel(ev) {
  userHasInteracted.value = true;
  const el = viewportRef.value;
  if (!el) return;
  if (ev.deltaY <= 0) return;
  maybeLoadNearBottom(el);
}
async function loadMore() {
  const tab = TAB_KEY;
  const t = report.tabs[tab];
  if (!t || t.loading || loadingMore.value || reachedEnd.value) return;

  loadingMore.value = true;
  const before = rows.value.length;
  try {
    await report.loadMore(tab);
    await nextTick();
    const after = rows.value.length;
    const appended = after - before;

    if (Number.isFinite(t.total)) {
      reachedEnd.value = after >= t.total;
    } else {
      reachedEnd.value = appended < (t.size || PAGE_SIZE) || !report.hasMore;
    }
  } finally {
    loadingMore.value = false;
  }
}

function onIo(entries) {
  for (const entry of entries) {
    if (entry.isIntersecting && entry.intersectionRatio >= 1 && canLoadMore()) {
      Promise.resolve().then(() => loadMore());
    }
  }
}

function canLoadMore() {
  const t = report.tabs[report.currentTab];
  if (!userHasInteracted.value) return false;
  if (autofillRunning.value) return false;
  if (!hasFirstLoaded.value || !t) return false;
  if (t.loading || loadingMore.value || reachedEnd.value) return false;
  if (Number.isFinite(t.total) && (t.rows?.length || 0) >= t.total) {
    reachedEnd.value = true;
    return false;
  }
  return true;
}
async function mountIo() {
  await nextTick();
  const rootEl = viewportRef.value;
  if (!sentinel.value) return;
  if (io) io.disconnect();
  io = new IntersectionObserver(onIo, {
    root: rootEl || null,
    rootMargin: "0px",
    threshold: 1.0,
  });
  io.observe(sentinel.value);
}
function unmountIo() {
  if (io) io.disconnect();
  io = null;
}

const ensureScrollableOnFirstLoad = async () => {
  await nextTick();
  const el = viewportRef.value;
  if (!el) return;

  autofillRunning.value = true;
  unmountIo();

  let tries = 0;
  while (tries < 5) {
    if (canScroll(el) || reachedEnd.value) break;

    const t = report.tabs[TAB_KEY];
    if (!t || t.loading) break;

    await report.loadMore(TAB_KEY);
    await nextTick();
    tries++;
  }

  autofillRunning.value = false;
};

async function onSearch() {
  await report.switchTab(TAB_KEY, { reset: false, fetchNow: false });
  report.ensureTab(TAB_KEY);

  const otaToSend = normalizeOtaSend(buildOtaNameParam());
  const pr = filters.property || propertyStore.selectedProperty?.value || "";

  const payload = {
    dateType: filters.dateType || "created_at",
    timeRange: buildTimeRange(),
    bookingStatus: filters.bookingStatus || null,
    paymentStatus: filters.paymentStatus || null,
    otaName: otaToSend || "",
    page: 1,
    size: Number(filters.pageSize || PAGE_SIZE),
    property: pr ? Number(pr) : "",
  };

  report.replaceFilters(payload, TAB_KEY);
  report.setPage(1, TAB_KEY);
  report.setSize(payload.size, TAB_KEY);

  report.resetForSearch(TAB_KEY);

  hasFirstLoaded.value = false;
  reachedEnd.value = false;
  loadingMore.value = false;
  autofillRunning.value = false;
  userHasInteracted.value = false;
  unmountIo();

  await report.fetch(TAB_KEY);

  await nextTick();
  hasFirstLoaded.value = true;

  await ensureScrollableOnFirstLoad();

  await mountIo();
}

function onTotalsScroll(e) {
  const totalsEl = e?.currentTarget || totalsOutsideRef.value;
  const vp = viewportRef.value;
  if (!totalsEl || !vp) return;

  if (isSyncingScroll.value) return;

  try {
    isSyncingScroll.value = true;
    vp.scrollLeft = totalsEl.scrollLeft;
  } finally {
    requestAnimationFrame(() => {
      isSyncingScroll.value = false;
    });
  }
}

onMounted(async () => {
  const f = active.value?.filters || {};
  filters.dateType = f.dateType || "created_at";
  filters.bookingStatus = f.bookingStatus ?? null;
  filters.paymentStatus = f.paymentStatus ?? null;
  filters.otaName = f.otaName ?? null;
  filters.property = f.property ?? "";
  filters.pageSize = f.size || PAGE_SIZE;
  filters.excludeSource = f.excludeSource || "";

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
  if (!t || !Array.isArray(t.rows) || t.rows.length === 0) {
    await report.fetch(TAB_KEY);
  }

  hasFirstLoaded.value = false;
  ready.value = true;

  await nextTick();

  if (viewportRef.value && totalsOutsideRef.value) {
    totalsOutsideRef.value.scrollLeft = viewportRef.value.scrollLeft || 0;
  }

  await ensureScrollableOnFirstLoad();

  hasFirstLoaded.value = true;

  await mountIo();
});

onBeforeUnmount(() => {
  if (rafId) cancelAnimationFrame(rafId);
  unmountIo();
});

watch(
  () => report.currentTab,
  async (tab) => {
    if (tab === TAB_KEY) {
      await nextTick();
      await report.switchTab(TAB_KEY, { reset: false, fetchNow: false });
      report.ensureTab(TAB_KEY);
      hasFirstLoaded.value = false;
      reachedEnd.value = false;
      await mountIo();
    }
  }
);

watch(
  () => [rows.value.length, ready.value],
  async () => {
    if (autofillRunning.value) return;
    await nextTick();
    await mountIo();
  },
  { deep: false }
);

async function clearAllFilters() {
  filters.dateType = "created_at";
  filters.timeRange = "";
  filters.bookingStatus = null;
  filters.paymentStatus = null;
  filters.otaName = null;
  filters.property = "";
  filters.pageSize = PAGE_SIZE;
  filters.excludeSource = "";

  dateFrom.value = "";
  dateTo.value = "";

  hasFirstLoaded.value = false;
  reachedEnd.value = false;
  loadingMore.value = false;
  autofillRunning.value = false;
  userHasInteracted.value = false;

  onSearch();
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

.group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-inline-size: 160px;
}

.group-label {
  font-size: 13px;
  font-weight: 600;
}

.table-card {
  padding: 8px;
  border-radius: 12px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 1%), transparent);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 35%);
}

.table-viewport {
  position: relative;
  overflow: auto;
  border: 1px solid rgba(255, 255, 255, 3%);
  border-radius: 8px;
  background: transparent;
  max-block-size: 58vh;
  -webkit-overflow-scrolling: touch;
}

.grid-table {
  border-collapse: collapse;
  font-size: 13px;
  inline-size: 100%;
  min-inline-size: 900px;
  table-layout: fixed;
}

.grid-table col {
  vertical-align: top;
}

.grid-table thead tr {
  block-size: 52px;
}

.grid-table th,
.grid-table td {
  overflow: hidden;
  border-block-end: 1px solid rgba(255, 255, 255, 6%);
  padding-block: 10px;
  padding-inline: 14px;
  text-overflow: ellipsis;
  vertical-align: middle;
  white-space: nowrap;
}

.grid-table thead th {
  position: sticky;
  z-index: 60;
  backdrop-filter: blur(6px);
  background: linear-gradient(
    180deg,
    rgba(10, 12, 20, 95%),
    rgba(10, 12, 20, 85%)
  );
  box-shadow: 0 2px 8px rgba(0, 0, 0, 18%);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  inset-block-start: 0;
  text-align: start;
}

.light-mode .grid-table thead th {
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 98%),
    rgba(245, 245, 245, 98%)
  );
  box-shadow: 0 2px 8px rgba(0, 0, 0, 4%);
  color: rgba(0, 0, 0, 85%);
}

.grid-table tbody tr {
  transition: background 0.15s ease;
}

.grid-table tbody tr:hover {
  background: rgba(255, 255, 255, 3%);
}

.grid-table tbody tr.zebra {
  background: rgba(255, 255, 255, 1%);
}

.light-mode .grid-table tbody tr:hover {
  background: rgba(0, 0, 0, 2%);
}

.light-mode .grid-table tbody tr.zebra {
  background: rgba(0, 0, 0, 2%);
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

.loading-more,
.empty {
  color: rgba(255, 255, 255, 85%);
  font-weight: 600;
  padding-block: 18px;
  padding-inline: 0;
  text-align: center;
}

.light-mode .loading-more,
.light-mode .empty {
  color: rgba(0, 0, 0, 72%);
}

.io-sentinel {
  block-size: 1px;
  inline-size: 100%;
}

.totals-outside {
  overflow: hidden;
  border-radius: 10px;
  background: rgba(255, 255, 255, 1%);
  border-block-start: 1px solid rgba(255, 255, 255, 3%);
  box-shadow: 0 -6px 18px rgba(0, 0, 0, 6%) inset;
  margin-block-start: 10px;
  padding-block: 6px;
  padding-inline: 10px;
}

.light-mode .totals-outside {
  background: rgba(255, 255, 255, 98%);
  border-block-start: 1px solid rgba(0, 0, 0, 6%);
  box-shadow: none;
}

.totals-table {
  border-collapse: collapse;
  inline-size: 100%;
  min-inline-size: 900px;
  table-layout: fixed;
}

.totals-table td {
  border-block-end: none;
  font-weight: 700;
  padding-block: 8px;
  padding-inline: 14px;
}

.footer-sep td {
  padding: 0;
  border: none;
}

@media (max-width: 1400px) {
  .grid-table {
    min-inline-size: 760px;
  }

  .totals-table {
    min-inline-size: 760px;
  }
}

@media (max-width: 1200px) {
  .filter-grid {
    gap: 10px;
  }

  .table-viewport {
    max-block-size: 50vh;
  }

  .grid-table thead tr {
    block-size: 48px;
  }

  .grid-table th,
  .grid-table td {
    font-size: 12px;
    padding-block: 8px;
    padding-inline: 10px;
  }
}
</style>
