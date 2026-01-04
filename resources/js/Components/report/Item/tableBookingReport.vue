<template>
  <VCard class="report-card">
    <div class="toolbar">
      <h2 class="title">{{ title }}</h2>
      <div class="actions">
        <VBtn
          :loading="loading"
          :disabled="loading"
          color="primary"
          prepend-icon="tabler-search"
          @click="onSearch"
        >
          Lọc
        </VBtn>
      </div>
    </div>

    <div class="table-head">
      <table>
        <colgroup>
          <col v-for="c in cols" :key="c.key" :style="colStyle(c)" />
        </colgroup>
        <thead>
          <tr>
            <th v-for="c in cols" :key="c.key" :class="['th', alignClass(c)]">
              <div class="th-title" :title="c.title">{{ c.title }}</div>
            </th>
          </tr>
        </thead>
      </table>
    </div>

    <div ref="bodyRef" class="table-body">
      <table>
        <colgroup>
          <col v-for="c in cols" :key="c.key" :style="colStyle(c)" />
        </colgroup>
        <tbody>
          <tr
            v-for="(r, i) in rows"
            :key="r.id ?? i"
            :class="i % 2 ? 'zebra' : ''"
          >
            <td v-for="c in cols" :key="c.key" :class="alignClass(c)">
              <span v-if="isMoney(c)">{{ money(r[c.key], r.currency) }}</span>
              <span v-else>{{ r[c.key] ?? "" }}</span>
            </td>
          </tr>

          <tr v-if="loadingMore">
            <td :colspan="cols.length" class="loading-more">
              <VProgressCircular indeterminate size="20" class="mr-2" /> Đang
              tải thêm…
            </td>
          </tr>

          <tr ref="sentinelRef">
            <td :colspan="cols.length" />
          </tr>

          <tr v-if="!loading && rows.length === 0">
            <td :colspan="cols.length" class="empty">Không có dữ liệu</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="table-totals">
      <table>
        <colgroup>
          <col v-for="c in cols" :key="c.key" :style="colStyle(c)" />
        </colgroup>
        <tfoot>
          <tr class="totals-row">
            <td v-for="c in cols" :key="c.key" :class="alignClass(c)">
              <template v-if="totalsCell(c)">
                <strong>{{ totalsCell(c) }}</strong>
              </template>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </VCard>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from "vue";

async function getBookingRevenueList({ page, size }) {
  return Promise.resolve({
    data: {
      result: [],
      total: 0,
      size,
      page,
    },
  });
}
async function getBookingRevenueTotals({}) {
  return Promise.resolve({
    data: {
      customerPaymentAmount: 0,
      commissionFee: 0,
      otaFee: 0,
      totalAmount: 0,
    },
  });
}

const cols = [
  {
    key: "otaReservationCode",
    title: "Tham khảo #",
    width: 160,
    align: "start",
  },
  { key: "fullName", title: "Tên khách", width: 180, align: "start" },
  { key: "createdAt", title: "Ngày tạo", width: 120, align: "center" },
  { key: "checkInDate", title: "Nhận phòng", width: 120, align: "center" },
  { key: "checkOutDate", title: "Trả phòng", width: 120, align: "center" },
  {
    key: "customerPaymentAmount",
    title: "Giá trị phòng",
    width: 140,
    align: "end",
    money: true,
  },
  {
    key: "commissionFee",
    title: "Thuế/Phí (Commission)",
    width: 160,
    align: "end",
    money: true,
  },
  {
    key: "otaFee",
    title: "Phụ phí (OTA fee)",
    width: 140,
    align: "end",
    money: true,
  },
  {
    key: "totalAmount",
    title: "Tổng cộng",
    width: 150,
    align: "end",
    money: true,
  },
  { key: "bookingStatus", title: "Trạng thái", width: 140, align: "center" },
  { key: "paymentStatus", title: "Thanh toán", width: 140, align: "center" },
];

const props = defineProps({
  title: { type: String, default: "Báo cáo đặt phòng" },
});

const rows = ref([]);
const totals = reactive({
  customerPaymentAmount: 0,
  commissionFee: 0,
  otaFee: 0,
  totalAmount: 0,
});
const page = ref(1);
const size = ref(50);
const total = ref(0);

const loading = ref(false);
const loadingMore = ref(false);

const bodyRef = ref(null);
const sentinelRef = ref(null);
let io;

function colStyle(c) {
  if (!c?.width) return {};
  return {
    width: typeof c.width === "number" ? `${c.width}px` : String(c.width),
  };
}
function alignClass(c) {
  const a = String(c?.align || "").toLowerCase();
  return a === "end" || a === "right"
    ? "text-end"
    : a === "center"
    ? "text-center"
    : "text-start";
}
function isMoney(c) {
  return !!c.money || /amount|fee|total|price|vnd|usd/i.test(c.key);
}
function money(v, currency = null) {
  const n = Number(v);
  if (!Number.isFinite(n)) return "";
  return n.toLocaleString("vi-VN", { maximumFractionDigits: 2 });
}
function mapRow(r) {
  return {
    id: r?.id,
    otaReservationCode: r?.otaReservationCode ?? "",
    fullName: r?.fullName ?? "",
    createdAt: r?.createdAt?.slice(0, 10) ?? "",
    checkInDate: r?.checkInDate?.slice(0, 10) ?? "",
    checkOutDate: r?.checkOutDate?.slice(0, 10) ?? "",
    customerPaymentAmount: r?.customerPaymentAmount ?? 0,
    commissionFee: r?.commissionFee ?? 0,
    otaFee: r?.otaFee ?? 0,
    totalAmount: r?.totalAmount ?? 0,
    bookingStatus: r?.bookingStatus ?? "",
    paymentStatus: r?.paymentStatus ?? "",
    currency: r?.currency ?? null,
  };
}

async function fetchFirstPage() {
  loading.value = true;
  try {
    page.value = 1;
    const [{ data: list }, { data: sum }] = await Promise.all([
      getBookingRevenueList({ page: page.value, size: size.value }),
      getBookingRevenueTotals({}),
    ]);

    const arr = Array.isArray(list?.result) ? list.result : [];
    rows.value = arr.map(mapRow);
    total.value = Number(list?.total) || arr.length;

    totals.customerPaymentAmount = Number(
      sum?.customerPaymentAmount ?? sum?.items?.[0]?.customerPaymentAmount ?? 0
    );
    totals.commissionFee = Number(
      sum?.commissionFee ?? sum?.items?.[0]?.commissionFee ?? 0
    );
    totals.otaFee = Number(sum?.otaFee ?? sum?.items?.[0]?.otaFee ?? 0);
    totals.totalAmount = Number(
      sum?.totalAmount ?? sum?.items?.[0]?.totalAmount ?? 0
    );
  } finally {
    loading.value = false;
  }
}

async function fetchNextPage() {
  if (loading.value || loadingMore.value) return;
  if (rows.value.length >= total.value) return;

  loadingMore.value = true;
  try {
    const next = page.value + 1;
    const { data: list } = await getBookingRevenueList({
      page: next,
      size: size.value,
    });
    const arr = Array.isArray(list?.result) ? list.result : [];
    rows.value = rows.value.concat(arr.map(mapRow));
    total.value = Number(list?.total) || total.value;
    page.value = next;
  } finally {
    loadingMore.value = false;
  }
}

function totalsCell(c) {
  if (c.key === "fullName") return "Tổng cộng";
  if (c.key === "customerPaymentAmount")
    return money(totals.customerPaymentAmount);
  if (c.key === "commissionFee") return money(totals.commissionFee);
  if (c.key === "otaFee") return money(totals.otaFee);
  if (c.key === "totalAmount") return money(totals.totalAmount);
  return null;
}

function onSearch() {
  fetchFirstPage();
}

onMounted(() => {
  fetchFirstPage();

  io = new IntersectionObserver(
    (entries) => {
      const e = entries[0];
      if (e && e.isIntersecting) fetchNextPage();
    },
    { root: bodyRef.value, rootMargin: "300px 0px", threshold: 0.0 }
  );
  if (sentinelRef.value) io.observe(sentinelRef.value);
});
onBeforeUnmount(() => {
  if (io && sentinelRef.value) io.unobserve(sentinelRef.value);
});
</script>

<style scoped>
.report-card {
  display: grid;
  overflow: hidden;
  border: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 8%, transparent);
  border-radius: 14px;
  background: var(--v-theme-surface);
  grid-template-rows: auto auto 1fr auto;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding-block: 12px;
  padding-inline: 16px;
}

.title {
  margin: 0;
  font-weight: 700;
}

.table-head,
.table-totals {
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 14%, transparent);
}

.table-totals {
  border-block-end: 0;
  border-block-start: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 14%, transparent);
}

.table-head table,
.table-body table,
.table-totals table {
  border-collapse: separate;
  border-spacing: 0;
  inline-size: 100%;
  table-layout: fixed;
}

.table-body {
  overflow: auto;
  max-block-size: 60vh;
}

th,
td {
  overflow: hidden;
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  padding-block: 8px;
  padding-inline: 10px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.th {
  position: sticky;
  background: linear-gradient(
    180deg,
    color-mix(in oklab, var(--v-theme-surface) 92%, transparent),
    var(--v-theme-surface)
  );
  font-weight: 700;
  inset-block-start: 0;
}

.th-title {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  font-size: 12px;
  letter-spacing: 0.02em;
  -webkit-line-clamp: 2;
  text-align: center;
}

.table-body tbody tr:nth-child(odd) td {
  background: color-mix(in oklab, var(--v-theme-on-surface) 3%, transparent);
}

.table-body tbody tr:hover td {
  background: color-mix(in oklab, var(--v-theme-primary) 8%, transparent);
}

.text-start {
  text-align: start;
}

.text-center {
  text-align: center;
}

.text-end {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-variant-numeric: tabular-nums;
  text-align: end;
}

.table-totals .totals-row td {
  background: linear-gradient(
    0deg,
    color-mix(in oklab, var(--v-theme-surface) 92%, transparent),
    var(--v-theme-surface)
  );
  border-block-start: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 16%, transparent);
  font-weight: 700;
}

.loading-more,
.empty {
  color: rgba(var(--v-theme-on-surface), 0.8);
  text-align: center;
}
</style>
