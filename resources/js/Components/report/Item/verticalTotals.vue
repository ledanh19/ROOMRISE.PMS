<!-- VerticalTotals.vue -->
<!-- reference-image: /mnt/data/62c20a7c-cb2b-43a4-848e-416aec391b23.png -->
<script setup>
import { computed } from "vue";

const props = defineProps({
  headers: { type: Array, required: true },
  rows: { type: Array, default: () => [] }, // expect rows[0] contains totals
  loading: { type: Boolean, default: false },
  height: { type: [String, Number], default: "28vh" },
  labelWidth: { type: [String, Number], default: "240px" }, // optional fixed width for label column
});

function formatMoney(v) {
  const n = Number(v);
  if (!Number.isFinite(n) || v === null || v === undefined || v === "")
    return "";
  return `${n.toLocaleString("vi-VN", { maximumFractionDigits: 0 })} ₫`;
}

const totalsRow = computed(
  () => (Array.isArray(props.rows) && props.rows[0]) || {}
);

// helper to detect numeric-like keys
function isNumericKey(key) {
  return /(amount|total|fee|commission|price|vnd|roomrevenue|customerpayment|commissionfee)/i.test(
    String(key)
  );
}
</script>

<template>
  <div
    class="vertical-totals"
    :style="{
      height: typeof height === 'number' ? height + 'px' : String(height),
    }"
  >
    <div class="vt-scroll">
      <table class="vt-table grid-table">
        <tbody v-if="!loading">
          <tr v-for="h in headers" :key="h.key" class="vt-row">
            <th
              class="vt-label"
              :style="{
                width:
                  typeof labelWidth === 'number'
                    ? labelWidth + 'px'
                    : String(labelWidth),
              }"
            >
              {{ h.title }}
            </th>
            <td class="vt-value" :class="h.align ? `text-${h.align}` : ''">
              <span v-if="isNumericKey(h.key)">{{
                formatMoney(totalsRow[h.key])
              }}</span>
              <span v-else>{{ totalsRow[h.key] ?? "" }}</span>
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr>
            <td :colspan="2" class="vt-loading">Đang tải…</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
/* Outer container */
.vertical-totals {
  display: block;
  overflow: hidden;
  padding: 14px;
  border: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 6%, transparent);
  border-radius: 10px;
  background: color-mix(in oklab, var(--v-theme-surface) 98%, white);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 3%);
}

/* Scroll area */
.vt-scroll {
  overflow: auto;
  block-size: 100%;
  inline-size: 100%;
  -webkit-overflow-scrolling: touch;
}

/* GRID: collapse borders so each cell has visible border */
.grid-table {
  background: transparent;
  border-collapse: collapse; /* important for continuous gridlines */
  inline-size: 100%;
  min-inline-size: var(--tbl-w, 100%);
  table-layout: fixed;
}

/* cells: visible gridlines for every cell */
.grid-table th,
.grid-table td {
  overflow: hidden;
  border: 1px solid rgba(0, 0, 0, 6%); /* gridline color */
  background: #fff; /* keep white cell background */
  font-size: 13px;
  padding-block: 12px;
  padding-inline: 14px;
  text-overflow: ellipsis;
  vertical-align: middle;
  white-space: nowrap;
}

/* header cell styling */
.grid-table thead th,
.grid-table th.vt-label {
  background: color-mix(in oklab, var(--v-theme-surface) 96%, white);
  color: rgba(var(--v-theme-on-surface), 0.95);
  font-weight: 800;
  text-align: start;
}

/* ensure sticky header if you add a thead - here not used, but keep rule */
.grid-table thead th {
  position: sticky;
  z-index: 12;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 4%);
  inset-block-start: 0;
}

/* align helpers */
.grid-table td.text-end,
.grid-table th.text-end {
  font-family: ui-monospace, Menlo, Monaco, Consolas, monospace;
  font-variant-numeric: tabular-nums;
  text-align: end;
}

.grid-table td.text-center,
.grid-table th.text-center {
  text-align: center;
}

.grid-table td.text-start,
.grid-table th.text-start {
  text-align: start;
}

/* totals row (stick to bottom inside scroll viewport) */
.grid-table tbody tr.totals-row th,
.grid-table tbody tr.totals-row td {
  position: sticky;
  z-index: 14;
  background: color-mix(in oklab, var(--v-theme-surface) 100%, white);
  border-block-start: 2px solid rgba(0, 0, 0, 6%);
  font-weight: 800;
  inset-block-end: 0;
}

/* section row styling */
.grid-table tbody tr.section th,
.grid-table tbody tr.section td {
  background: color-mix(in oklab, var(--v-theme-on-surface) 4%, white);
  font-weight: 800;
}

/* hover effect */
.grid-table tbody tr:hover th,
.grid-table tbody tr:hover td {
  background: color-mix(in oklab, var(--v-theme-primary) 4%, white);
}

/* loading row */
.vt-loading {
  padding: 18px;
  inline-size: 100%;
  text-align: center;
}

/* small-screen tweaks */
@media (max-width: 1200px) {
  .grid-table th,
  .grid-table td {
    font-size: 12px;
    padding-block: 10px;
    padding-inline: 8px;
  }

  .vertical-totals {
    padding: 10px;
    border-radius: 8px;
  }
}
</style>
