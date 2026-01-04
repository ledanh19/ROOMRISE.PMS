<template>
  <VDialog :model-value="modelValue" width="1100" @update:model-value="close">
    <VCard class="data-table-card search-dialog" elevation="2">
      <VCardItem class="table-header">
        <div class="d-flex align-center justify-space-between flex-wrap gap-2">
          <div>
            <h1 class="text-h5 font-weight-bold text-primary mb-1">
              Tìm kiếm đặt phòng
            </h1>
          </div>
          <div class="d-flex align-center gap-3">
            <VBtn icon variant="text" @click="close">
              <VIcon icon="tabler-x" />
            </VBtn>
          </div>
        </div>
      </VCardItem>

      <VDivider />

      <VCardItem>
        <div
          class="d-flex align-center justify-space-between flex-wrap gap-4 mb-2 mt-1"
        >
          <div class="d-flex gap-2 align-center flex-wrap">
            <AppTextField
              v-model="q"
              placeholder="Nhập mã đặt phòng"
              style="inline-size: 20rem"
              prepend-inner-icon="tabler-search"
              class="custom-input"
              @keyup.enter="triggerSearch"
              clearable
            />
            <VBtn
              color="primary"
              variant="elevated"
              class="action-btn"
              :loading="loading"
              @click="triggerSearch"
            >
              <VIcon icon="tabler-search" class="mr-2" /> Tìm
            </VBtn>

            <VBtn
              variant="outlined"
              color="secondary"
              class="action-btn"
              :disabled="loading"
              @click="refresh"
            >
              <VIcon icon="tabler-refresh" class="mr-2" /> Làm mới
            </VBtn>
          </div>
        </div>
      </VCardItem>

      <VDivider />

      <VCardItem class="table-content">
        <div v-if="loading && items.length === 0" class="py-9 text-center">
          <VProgressCircular indeterminate size="24" class="mr-2" />
          Đang tải dữ liệu…
        </div>

        <VAlert
          v-else-if="error"
          type="error"
          variant="tonal"
          class="ma-4"
          :text="errorMessage"
        />

        <template v-else-if="items.length > 0">
          <VDataTableServer
            class="text-no-wrap custom-data-table recent-table"
            :headers="headers"
            :items="items"
            :items-length="total"
            v-model:page="page"
            v-model:items-per-page="itemsPerPage"
            :loading="loading"
            density="comfortable"
            :fixed-header="true"
            height="60vh"
            hover
            @update:page="onUpdatePage"
            @update:items-per-page="onUpdateItemsPerPage"
            @click:row="onRowClick"
          >
            <template #item.code="{ item }">
              <div class="mono font-weight-medium code-link">
                {{ item.otaReservationCode || "-" }}
              </div>
            </template>

            <template #item.ota="{ item }">
              <div class="d-flex align-center gap-2">
                <img
                  v-if="logoSrc(item.otaName)"
                  :src="logoSrc(item.otaName)"
                  alt="OTA"
                  class="h-6 logo w-auto"
                />
                <VChip
                  size="small"
                  class="ota-chip"
                  :color="otaColor(item.otaName)"
                  variant="tonal"
                >
                  {{ item.otaName || "-" }}
                </VChip>
              </div>
            </template>

            <template #item.customer="{ item }">
              <div>
                <p
                  class="font-weight-medium mb-1 customer-name truncate"
                  :title="item.customerName"
                >
                  {{ item.customerName || "-" }}
                </p>
                <div class="guest-info" v-if="item.customerPhone">
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    <VIcon icon="tabler-phone" size="14" class="mr-1" />
                    {{ item.customerPhone }}
                  </p>
                </div>
              </div>
            </template>

            <template #item.room="{ item }">
              <div class="truncate" :title="item.roomName">
                {{ item.roomName || "-" }}
              </div>
              <div class="text-caption text-medium-emphasis">
                #{{ item.roomUnitName }}
              </div>
            </template>

            <template #item.checkin="{ item }">
              <VChip
                color="success"
                variant="flat"
                size="small"
                class="status-chip"
              >
                <VIcon icon="tabler-plane-arrival" size="14" class="mr-1" />
                {{ fmt(item.checkInDate) }}
              </VChip>
            </template>

            <template #item.checkout="{ item }">
              <VChip
                color="warning"
                variant="flat"
                size="small"
                class="status-chip"
              >
                <VIcon icon="tabler-plane-departure" size="14" class="mr-1" />
                {{ fmt(item.checkOutDate) }}
              </VChip>
            </template>

            <template #item.nights="{ item }">
              <VChip
                color="info"
                variant="flat"
                size="small"
                class="status-chip"
              >
                <VIcon icon="tabler-moon" size="14" class="mr-1" />
                {{ nights(item) }} đêm
              </VChip>
            </template>

            <template #item.remaining="{ item }">
              <span class="font-weight-bold text-warning">
                {{ money(item.remaining) }}
              </span>
            </template>

            <template #item.property="{ item }">
              <span
                class="font-weight-medium property-name truncate"
                :title="item.propertyName"
              >
                {{ item.propertyName || "-" }}
              </span>
            </template>

            <template #bottom>
              <div
                class="px-4 py-3 d-flex align-center justify-space-between flex-wrap gap-2 mb-4"
                v-if="total > 0"
              >
                <div class="text-medium-emphasis">
                  Hiển thị {{ pageStart }}–{{ pageEnd }} / {{ total }}
                </div>
                <VPagination
                  v-model="page"
                  :length="pageCount"
                  :total-visible="7"
                  size="small"
                  @update:modelValue="onUpdatePage"
                />
              </div>
            </template>
          </VDataTableServer>
        </template>
        <div v-else class="py-9 text-center">
          Không có kết quả tìm kiếm phù hợp.
        </div>
      </VCardItem>

      <VCardActions class="justify-end px-4 pb-4 mt-5">
        <VBtn variant="tonal" @click="close">Đóng</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { searchBookings } from "@/utils/api";
import { computed, onMounted, ref, watch } from "vue";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  propertyId: { type: [Number, String], default: null },
  autoLoadOnOpen: { type: Boolean, default: true },
});
const emit = defineEmits(["update:modelValue", "select"]);

const q = ref("");
const items = ref([]);
const loading = ref(false);
const error = ref(null);

const page = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref([]);

const total = ref(0);
const pageSizeOptions = [10, 20, 50, 100];

const headers = [
  { title: "Mã đặt phòng", key: "code", width: 160, sortable: false },
  { title: "Nguồn", key: "ota", width: 160, sortable: false },
  { title: "Khách", key: "customer", minWidth: 220, sortable: false },

  { title: "Phòng", key: "room", minWidth: 220, sortable: false },
  { title: "Nhận phòng", key: "checkin", width: 160, sortable: false },
  { title: "Trả phòng", key: "checkout", width: 160, sortable: false },
  {
    title: "Số đêm",
    key: "nights",
    width: 110,
    align: "center",
    sortable: false,
  },
  {
    title: "Còn lại",
    key: "remaining",
    width: 140,
    align: "end",
    sortable: false,
  },
  { title: "Chỗ nghỉ", key: "property", minWidth: 200, sortable: false },
];

const otaLogos = {
  bookingcom: "/images/bookingcom.png",
  ctrip: "/images/ctrip.png",
  expedia: "/images/expedia.png",
  airbnb: "/images/airbnb.png",
  agoda: "/images/agoda.png",
};

const errorMessage = computed(() =>
  typeof error.value === "string"
    ? error.value
    : error.value?.message || "Đã xảy ra lỗi khi tải dữ liệu"
);

const pageCount = computed(() =>
  Math.max(
    1,
    Math.ceil(Number(total.value || 0) / Number(itemsPerPage.value || 1))
  )
);
const pageStart = computed(() =>
  total.value ? (page.value - 1) * itemsPerPage.value + 1 : 0
);
const pageEnd = computed(() =>
  Math.min(page.value * itemsPerPage.value, total.value)
);

function close() {
  emit("update:modelValue", false);
}
function onRowClick(_, row) {
  emit("select", row.item);
}
function fmt(d) {
  if (!d) return "-";
  const dt = new Date(d);
  if (isNaN(dt)) return "-";
  const dd = String(dt.getDate()).padStart(2, "0");
  const mm = String(dt.getMonth() + 1).padStart(2, "0");
  const yy = dt.getFullYear();
  return `${dd}-${mm}-${yy}`;
}
function nights(item) {
  const a = new Date(item.checkInDate);
  const b = new Date(item.checkOutDate);
  const ms = b - a;
  const days = Math.ceil(ms / (1000 * 60 * 60 * 24));
  return Number.isFinite(days) && days > 0 ? days : 1;
}
function money(v) {
  if (v == null) return "-";
  const num = Number(v);
  return isNaN(num) ? String(v) : num.toLocaleString("vi-VN");
}
function logoSrc(ota) {
  const k = String(ota || "")
    .toLowerCase()
    .replace(/\s+/g, "");
  return otaLogos[k] || null;
}
function otaColor(name = "") {
  const k = String(name).toLowerCase();
  if (k.includes("airbnb")) return "pink";
  if (k.includes("booking")) return "blue";
  if (k.includes("agoda")) return "deep-purple";
  if (k.includes("expedia")) return "indigo";
  if (k.includes("ctrip") || k.includes("trip")) return "cyan";
  return "primary";
}

function onUpdateOptions(opts) {
  const nextSort = Array.isArray(opts.sortBy) ? opts.sortBy : [];
  if (JSON.stringify(sortBy.value || []) !== JSON.stringify(nextSort || [])) {
    sortBy.value = nextSort;
    page.value = 1;
    fetchList();
  }
}
function onUpdatePage(p) {
  const next = Number(p) || 1;
  if (next !== page.value) {
    page.value = next;
  }
  fetchList();
}
function onUpdateItemsPerPage(sz) {
  const size = Number(sz) || 10;
  if (size !== itemsPerPage.value) {
    itemsPerPage.value = size;
    page.value = 1;
    fetchList();
  }
}

let currentAbort = null;

async function fetchList() {
  if (currentAbort) currentAbort.abort();
  currentAbort = new AbortController();
  const signal = currentAbort.signal;

  error.value = null;
  loading.value = true;

  try {
    const sort =
      Array.isArray(sortBy.value) && sortBy.value[0] ? sortBy.value[0] : null;

    const res = await searchBookings(
      {
        textSearch: (q.value || "").trim() || undefined,
        property: props.propertyId ?? undefined,
        page: page.value,
        size: itemsPerPage.value,
        sortField: sort?.key,
        sortOrder: sort?.order,
        _ts: Date.now(),
      },
      { signal }
    );

    if (signal.aborted) return;

    const root = res?.data ?? res;
    const d = root?.data ?? root ?? {};
    const list = Array.isArray(d.result) ? d.result : Array.isArray(d) ? d : [];

    items.value = list;
    total.value = Number(d.total ?? root?.total ?? list.length);

    if (typeof d.page === "number") page.value = d.page;
    if (typeof d.size === "number") itemsPerPage.value = d.size;
  } catch (e) {
    if (e?.name === "AbortError") return;
    console.error("[SearchResultsDialog] fetch error:", e);
    error.value = e;
    items.value = [];
    total.value = 0;
  } finally {
    if (!signal.aborted) loading.value = false;
  }
}

function triggerSearch() {
  page.value = 1;
  fetchList();
}
function refresh() {
  q.value = "";
  page.value = 1;
  fetchList();
}
function onChangeSize(sz) {
  onUpdateItemsPerPage(sz);
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      if (props.autoLoadOnOpen) fetchList();
    } else {
      if (currentAbort) currentAbort.abort();
      loading.value = false;
      error.value = null;
      items.value = [];
      total.value = 0;
      q.value = "";
      page.value = 1;
      itemsPerPage.value = 10;
      sortBy.value = [];
    }
  }
);

watch(
  () => props.propertyId,
  (val, oldVal) => {
    if (props.modelValue && val !== oldVal) {
      page.value = 1;
      fetchList();
    }
  }
);

onMounted(() => {
  if (props.modelValue && props.autoLoadOnOpen) fetchList();
});
</script>

<style scoped>
.v-theme--light .search-dialog {
  --surface: #fff;
  --border-color: rgba(15, 23, 42, 6%);
  --hdr-grad-start: #fff;
  --hdr-grad-end: #f7fafc;
  --tbl-hdr-start: #fff;
  --tbl-hdr-end: #f6f7fb;
  --title-color: #111827;
  --subtle-text: #6b7280;
  --row-divider: rgba(17, 24, 39, 6%);
  --row-zebra: rgba(17, 24, 39, 2%);
  --row-hover: rgba(14, 165, 233, 6%);
  --row-hover-shadow: rgba(14, 165, 233, 12%);
  --card-shadow: rgba(0, 0, 0, 7%);
  --logo-shadow: rgba(0, 0, 0, 10%);
  --input-shadow: rgba(0, 0, 0, 4%);
  --input-shadow-h: rgba(14, 165, 233, 15%);
  --input-shadow-f: rgba(14, 165, 233, 25%);
}

/* Dark theme */
.v-theme--dark .search-dialog {
  --surface: #1e293b;
  --border-color: #334155;
  --hdr-grad-start: #334155;
  --hdr-grad-end: #475569;
  --tbl-hdr-start: #475569;
  --tbl-hdr-end: #64748b;
  --title-color: #e2e8f0;
  --subtle-text: #94a3b8;
  --row-divider: color-mix(in oklab, #fff 6%, transparent);
  --row-zebra: color-mix(in oklab, #fff 4%, transparent);
  --row-hover: color-mix(in oklab, #667eea 12%, transparent);
  --row-hover-shadow: rgba(102, 126, 234, 15%);
  --card-shadow: rgba(0, 0, 0, 25%);
  --logo-shadow: rgba(0, 0, 0, 35%);
  --input-shadow: rgba(255, 255, 255, 6%);
  --input-shadow-h: rgba(102, 126, 234, 20%);
  --input-shadow-f: rgba(102, 126, 234, 30%);
}

.search-dialog {
  overflow: hidden;
  border: 1px solid var(--border-color);
  border-radius: 12px;
  animation: fade-in-up 0.35s ease-out;
  background: var(--surface);
  box-shadow: 0 12px 24px var(--card-shadow);
}

.search-dialog .table-header {
  background: linear-gradient(
    180deg,
    var(--hdr-grad-start) 0%,
    var(--hdr-grad-end) 100%
  );
  border-block-end: 1px solid var(--border-color);
  padding-block: 9px;
  padding-inline: 10px;
}

.search-dialog .table-content {
  padding: 0;
}

.search-dialog .custom-data-table .v-data-table__wrapper {
  overflow: auto;
  border-radius: 0 0 12px 12px;
}

.search-dialog .custom-data-table .v-data-table-header {
  position: sticky;
  z-index: 2;
  background: linear-gradient(
    180deg,
    var(--tbl-hdr-start) 0%,
    var(--tbl-hdr-end) 100%
  );
  border-block-end: 1px solid var(--row-divider);
  inset-block-start: 0;
}

.search-dialog
  .custom-data-table
  .v-data-table-header
  .v-data-table-header__content {
  color: var(--title-color);
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

:deep(.search-dialog .custom-data-table tbody tr) {
  transition: none;
  height: 70px;
}

:deep(.search-dialog .custom-data-table tbody tr > td) {
  background: var(--surface);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

:deep(.search-dialog .custom-data-table tbody tr:hover > td) {
  transform: translateY(-1px);
  box-shadow: 0 6px 14px var(--row-hover-shadow);
}

:deep(.search-dialog .custom-data-table tbody tr > td:first-child) {
  border-radius: 10px 0 0 10px;
}

:deep(.search-dialog .custom-data-table tbody tr > td:last-child) {
  border-radius: 0 10px 10px 0;
}

.search-dialog .custom-data-table .v-data-table__td {
  border-block-end: 1px solid var(--row-divider);
  padding-block: 12px;
  padding-inline: 12px;
  vertical-align: middle;
}

.status-chip {
  border-radius: 10px;
  box-shadow: 0 1px 4px var(--card-shadow);
  font-size: 0.72rem;
  text-transform: uppercase;
}

.ota-chip {
  background-color: #eef2f7 !important;
  color: #111827 !important;
  text-transform: none;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.truncate {
  overflow: hidden;
  max-inline-size: 280px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.property-name,
.customer-name {
  color: var(--title-color);
  font-size: 0.88rem;
  font-weight: 600;
}

.guest-info p {
  display: flex;
  align-items: center;
  color: var(--subtle-text);
  font-size: 0.75rem;
  margin-block: 2px;
  margin-inline: 0;
}

.logo {
  border-radius: 6px;
  block-size: 2rem;
  box-shadow: 0 2px 6px var(--logo-shadow);
  inline-size: 2rem;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.logo:hover {
  box-shadow: 0 4px 10px var(--logo-shadow);
  transform: scale(1.05);
}

.custom-input .v-field {
  border-radius: 10px;
  box-shadow: 0 1px 3px var(--input-shadow);
  transition: box-shadow 0.25s ease, border-color 0.25s ease;
}

.custom-input .v-field:hover {
  box-shadow: 0 3px 10px var(--input-shadow-h);
}

.custom-input .v-field.v-field--focused {
  border-color: #0ea5e9;
  box-shadow: 0 4px 12px var(--input-shadow-f);
}

.action-btn {
  border-radius: 10px;
  box-shadow: 0 2px 8px var(--card-shadow);
  font-weight: 500;
  letter-spacing: 0.025em;
  text-transform: none;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.action-btn:hover {
  transform: translateY(-1px);
}

.search-dialog ::-webkit-scrollbar {
  block-size: 8px;
  inline-size: 8px;
}

.search-dialog ::-webkit-scrollbar-track {
  background: transparent;
}

.search-dialog ::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: rgba(2, 6, 23, 16%);
}

.search-dialog ::-webkit-scrollbar-thumb:hover {
  background: rgba(2, 6, 23, 28%);
}

@media (max-width: 768px) {
  .search-dialog .table-header {
    padding-block: 10px;
    padding-inline: 12px;
  }

  .truncate {
    max-inline-size: 200px;
  }
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.code-link {
  font-weight: 700;
  color: rgb(var(--v-theme-primary));
}

.recent-table :deep(.v-data-table__th),
.recent-table :deep(.v-data-table__th .v-data-table-header__content) {
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  line-height: 1.2;
  text-transform: uppercase;
  white-space: nowrap;
}
</style>
