import {
  apiGetHeaderRevenueRoomReport,
  getBookingReport,
  getBookingSourceReport,
  getBookingSourceTotalReport,
  getBookingTotalReport,
  getEfficiencyReport,
  getEfficiencyTotalReport,
  getRevenueActivityReport,
  getRevenueActivityTotalReport,
  getRevenueDailySaleReport,
  getRevenueDailySaleTotalReport,
  getRevenueDetailReport,
  getRevenueDetailTotalReport,
  getRevenueRoomReport,
  getRevenueRoomTotalReport,
} from "@/utils/apiReport/revenueReport";
import { makeRoomKey, toAsciiSlug } from "@/utils/helper";
import { defineStore } from "pinia";

function defaultFilters() {
  return {
    dateFrom: "",
    dateTo: "",
    timeFrom: null,
    timeTo: null,
    dateType: null,
    timeRange: "",
    currency: null,
    monthFrom: null,
    monthTo: null,
    yearFrom: null,
    yearTo: null,
    otaName: null,
    property: null,
    roomId: null,
  };
}

function mapListDataPerformance(r, isTotal = false) {
  if (isTotal) {
    return {
      _section: true,
      otaName: "Tổng cộng",
      numberBookings: r?.totalBookings ?? 0,
      numberGuests: r?.totalGuests ?? 0,
      numberNights: r?.totalGuests ?? 0,
      avgNights: r?.avgNights ?? 0,
      totalRevenue: r?.totalRevenue ?? 0,
      avgTotalBookingValue: r?.avgBookingValue ?? 0,
      avgTotalNightlyValue: r?.avgNightlyValue ?? 0,
      avgLeadTime: r?.avgLeadTime ?? 0,
    };
  }
  return {
    otaName: r?.otaName ?? "",
    numberBookings: r?.totalBookings ?? "",
    numberGuests: r?.totalGuests ?? "",
    numberNights: r?.totalGuests ?? "",
    avgNights: r?.avgNights ?? "",
    totalRevenue: r?.totalRevenue ?? "",
    avgTotalBookingValue: r?.avgBookingValue ?? "",
    avgTotalNightlyValue: r?.avgNightlyValue ?? "",
    avgLeadTime: r?.avgLeadTime ?? "",
  };
}

function mapRevenueRow(r) {
  const d = (v) => (v ? String(v).slice(0, 10) : "");
  const n = (v) => (Number.isFinite(+v) ? +v : 0);
  return {
    id: r?.id ?? null,
    otaReservationCode: r?.otaReservationCode ?? "",
    bookingCom: r?.bookingCom ?? "",
    customerName: r?.customerName ?? "",
    checkInDate: d(r?.checkInDate),
    checkOutDate: d(r?.checkOutDate),
    nights: n(r?.nights),
    guestQuantity: n(r?.guestQuantity),
    customerPaymentAmount: n(r?.customerPaymentAmount),
    commissionFee: n(r?.commissionFee),
    otaFee: n(r?.otaFee),
    totalAmount: n(r?.totalAmount),
    currency: r?.currency ?? null,
    propertyName: r?.propertyName ?? "",
    otaName: r?.otaName ?? "",
  };
}

function mapRevenueTotals(obj) {
  if (!obj) return null;
  const n = (v) => (Number.isFinite(+v) ? +v : 0);
  return {
    bookingQuantity: n(obj?.bookingQuantity),
    nights: n(obj?.nights),
    guestQuantity: n(obj?.guestQuantity),
    customerPaymentAmount: n(obj?.customerPaymentAmount),
    commissionFee: n(obj?.commissionFee),
    otaFee: n(obj?.otaFee),
    totalAmount: n(obj?.totalAmount),
    currency: obj?.currency ?? null,
  };
}

function mapBookingRow(r) {
  const n = (v) => (Number.isFinite(+v) ? +v : 0);
  return {
    date: r?.date ?? "",
    bookingQuantity: n(r?.bookingQuantity),
    customerPaymentAmount: n(r?.customerPaymentAmount),
    commissionFee: n(r?.commissionFee),
    otaFee: n(r?.otaFee),
    totalAmount: n(r?.totalAmount),
    currency: r?.currency ?? null,
  };
}

function mapBookingTotals(obj) {
  if (!obj) return null;
  const n = (v) => (Number.isFinite(+v) ? +v : 0);
  return {
    date: "Tổng cộng",
    bookingQuantity: n(obj?.bookingQuantity),
    customerPaymentAmount: n(obj?.customerPaymentAmount),
    commissionFee: n(obj?.commissionFee),
    otaFee: n(obj?.otaFee),
    totalAmount: n(obj?.totalAmount),
    currency: obj?.currency ?? null,
  };
}

function mapDailySaleRow(r) {
  const n = (v) => (Number.isFinite(+v) ? +v : 0);

  return {
    roomType: r?.roomName ?? "Unallocated",
    roomRevenue: n(r?.roomRevenue),
    roomNights: n(r?.nightsBooked),
    occ: n(r?.OCC),
    adr: n(r?.ADR),
    revpar: n(r?.RevPAR),
    extraRevenue: 0,
    totalRevenue: n(r?.totalAmount ?? r?.roomRevenue),
    trevpar: n(r?.TRevPAR),
  };
}

function mapDailySaleTotals(obj) {
  if (!obj) return null;
  const n = (v) => (Number.isFinite(+v) ? +v : 0);

  return {
    _section: true,
    roomType: "Tổng cộng",
    roomRevenue: n(obj?.roomRevenue),
    roomNights: n(obj?.nightsBooked),
    occ: n(obj?.OCC),
    adr: n(obj?.ADR),
    revpar: n(obj?.RevPAR),
    extraRevenue: 0,
    totalRevenue: n(obj?.totalAmount ?? obj?.roomRevenue),
    trevpar: n(obj?.TRevPAR),
  };
}

function buildListQueryPerformance(t) {
  const f = t.filters || {};
  return {
    dateType: f.dateType || "",
    timeRange: f.timeRange || "",
    currency: f.currency || "",
    otaName: f.otaName || "",
    property: f.property || null,
    page: t.page || 1,
    size: t.size || 10,
  };
}

function buildTotalQueryPerformance(t) {
  const f = t.filters || {};
  return {
    dateType: f.dateType || "",
    timeRange: f.timeRange || "",
    currency: f.currency || "",
    otaName: f.otaName || "",
    property: f.property || null,
  };
}

function buildListQueryDetail(t) {
  const f = t.filters || {};
  return {
    dateFrom: f.dateFrom || "",
    dateTo: f.dateTo || "",
    timeFrom: f.timeFrom || "",
    timeTo: f.timeTo || "",
    otaName: f.otaName || "",
    property: f.property || null,
    page: t.page || 1,
    size: t.size || 10,
  };
}

function buildTotalQueryDetail(t) {
  const f = t.filters || {};
  return {
    dateFrom: f.dateFrom || "",
    dateTo: f.dateTo || "",
    timeFrom: f.timeFrom || "",
    timeTo: f.timeTo || "",
    otaName: f.otaName || "",
    property: f.property || null,
  };
}

function buildListQueryBooking(t) {
  const f = t.filters || {};
  return {
    monthFrom: f.monthFrom ?? "",
    monthTo: f.monthTo ?? "",
    yearFrom: f.yearFrom ?? "",
    yearTo: f.yearTo ?? "",
    currency: f.currency ?? "",
    property: f.property ?? "",
    page: t.page || 1,
    size: t.size || 10,
  };
}

function buildTotalQueryBooking(t) {
  const f = t.filters || {};
  return {
    monthFrom: f.monthFrom ?? "",
    monthTo: f.monthTo ?? "",
    yearFrom: f.yearFrom ?? "",
    yearTo: f.yearTo ?? "",
    currency: f.currency ?? "",
    property: f.property ?? "",
  };
}

function buildListQueryDailySale(t) {
  const f = t.filters || {};
  return {
    timeRange: f.timeRange || "",
    currency: f.currency || "",
    otaName: f.otaName || "",
    property: f.property || "",
    page: t.page || 1,
    size: t.size || 10,
  };
}
function buildTotalQueryDailySale(t) {
  const f = t.filters || {};
  return {
    timeRange: f.timeRange || "",
    currency: f.currency || "",
    otaName: f.otaName || "",
    property: f.property || "",
  };
}

const OTA_KEY = {
  bookingcom: "bookingCom",
  "walk-in": "walkIn",
  "walk in": "walkIn",
  "từ đối tác": "offline",
  "tu doi tac": "offline",
  agoda: "agoda",
  expedia: "expedia",
  airbnb: "airbnb",
  ctrip: "ctrip",
};

function normalizeName(name) {
  if (!name) return "";
  return String(name)
    .trim()
    .toLowerCase()
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "");
}

function toOtaKey(name) {
  const k = normalizeName(name);
  return OTA_KEY[k] || null;
}

function safeNumber(v) {
  const n = Number(v);
  return Number.isFinite(n) ? n : 0;
}

function mapBookingSourceRow(row) {
  const out = {
    date: row?.date ?? "",
    totalRevenue: safeNumber(row?.totalAmount),
    bookingCom: 0,
    walkIn: 0,
    offline: 0,
    agoda: 0,
    expedia: 0,
    airbnb: 0,
    ctrip: 0,
  };

  const items = Array.isArray(row?.items) ? row.items : [];

  for (const it of items) {
    const key = toOtaKey(it?.otaName);
    const amt = safeNumber(it?.totalAmount);
    if (key) {
      out[key] += amt;
    } else {
      out.offline += amt;
    }
  }
  return out;
}

export function mapBookingSourceRows(rows) {
  return (Array.isArray(rows) ? rows : []).map(mapBookingSourceRow);
}
function mapBookingSourceTotals(arr) {
  const out = {
    totalRevenue: 0,
    bookingCom: 0,
    walkIn: 0,
    offline: 0,
    agoda: 0,
    expedia: 0,
    airbnb: 0,
    ctrip: 0,
  };
  const list = Array.isArray(arr) ? arr : [];
  for (const it of list) {
    const key = toOtaKey(it?.ota_name || it?.otaName);
    const amt = Number.isFinite(+it?.totalAmount) ? +it.totalAmount : 0;
    if (key && Object.prototype.hasOwnProperty.call(out, key)) out[key] += amt;
    out.totalRevenue += amt;
  }

  return out;
}

function mapActivitySourceRow(row) {
  const n = (v) => Number(v ?? 0);

  return {
    roomType: row?.roomName ?? "",

    occ: n(row?.OCC),
    adr: n(row?.ADR),
    revpar: n(row?.RevPAR),
    trevpar: n(row?.TRevPAR),

    roomRevenue: n(row?.roomRevenue),
    roomRevenuePct: n(row?.roomRevenuePercent),

    commissionFee: n(row?.commissionFee),
    otaFee: n(row?.otaFee),
    serviceRevenuePercent: n(row?.serviceRevenuePercent),

    totalRevenue: n(row?.totalRevenue),
    totalRevenuePct: n(row?.totalRevenuePercent),
  };
}

function mapActivitySourceTotals(t) {
  const n = (v) => Number(v ?? 0);

  const roomRevenue = n(t?.roomRevenue);
  const commissionFee = n(t?.commissionFee);
  const otaFee = n(t?.otaFee);
  const totalRevenue = n(t?.totalRevenue);

  return {
    _section: true,
    roomType: "Tổng cộng",

    occ: n(t?.OCC),
    adr: n(t?.ADR),
    revpar: n(t?.RevPAR),
    trevpar: n(t?.TRevPAR),

    roomRevenue,
    roomRevenuePct: null,

    commissionFee,
    otaFee,

    serviceRevenuePercent: null,

    totalRevenue,
    totalRevenuePct: null,
  };
}

function buildListQueryBookingSource(t) {
  const f = t.filters || {};
  return {
    monthFrom: f.monthFrom ?? "",
    monthTo: f.monthTo ?? "",
    yearFrom: f.yearFrom ?? "",
    yearTo: f.yearTo ?? "",
    currency: f.currency ?? "",
    property: f.property ?? "",
    otaName: f.otaName ?? "",
    page: t.page || 1,
    size: t.size || 10,
  };
}

function buildTotalQueryBookingSource(t) {
  const f = t.filters || {};
  return {
    monthFrom: f.monthFrom ?? "",
    monthTo: f.monthTo ?? "",
    yearFrom: f.yearFrom ?? "",
    yearTo: f.yearTo ?? "",
    currency: f.currency ?? "",
    property: f.property ?? "",
    otaName: f.otaName ?? "",
  };
}

function buildListQueryRoom(t) {
  const f = t.filters || {};
  return {
    monthFrom: f.monthFrom || "",
    monthTo: f.monthTo || "",
    yearFrom: f.yearFrom || "",
    yearTo: f.yearTo || "",
    currency: f.currency || "",
    roomId: f.roomId || "",
    property: f.property || "",
    page: t.page || 1,
    size: t.size || 10,
  };
}

function buildTotalQueryRoom(t) {
  const f = t.filters || {};
  return {
    monthFrom: f.monthFrom || "",
    monthTo: f.monthTo || "",
    yearFrom: f.yearFrom || "",
    yearTo: f.yearTo || "",
    currency: f.currency || "",
    roomId: f.roomId || "",
    property: f.property || "",
  };
}

function buildListQueryActivityReport(t) {
  const f = t.filters || {};
  return {
    timeRange: f.timeRange || "",
    currency: f.currency || "",
    otaName: f.otaName || "",
    property: f.property || null,
    page: t.page || 1,
    size: t.size || 10,
  };
}

function buildTotalQueryActivityReport(t) {
  const f = t.filters || {};
  return {
    timeRange: f.timeRange || "",
    currency: f.currency || "",
    otaName: f.otaName || "",
    property: f.property || null,
  };
}

function formatMoney(value) {
  const n = Number(value);
  if (!Number.isFinite(n) || value === null) return "";
  if (n === 0) return "0";
  return ` ${n.toLocaleString("vi-VN", {
    maximumFractionDigits: 0,
  })} ₫`;
}

function mapRoomRow(row, headerList) {
  const out = { date: row?.date ?? "" };
  // khởi các cột dynamic = 0
  for (const h of headerList) {
    if (h.key !== "date" && h.key !== "totalRevenue") out[h.key] = 0;
  }

  let total = 0;
  const items = Array.isArray(row?.items) ? row.items : [];
  for (const it of items) {
    const amt = Number.isFinite(+it?.total) ? +it.total : 0;
    const key = makeRoomKey(it?.roomName, it?.roomId);
    if (key in out) out[key] += amt;
    total += amt;
  }

  // tạo trường _text cho mọi cột dynamic vừa khởi
  for (const h of headerList) {
    if (h.key !== "date" && h.key !== "totalRevenue") {
      const v = out[h.key];
      out[`${h.key}_text`] = formatMoney(v);
    }
  }

  // totalRevenue: giữ number + text
  out.totalRevenue = total;
  out.totalRevenue_text = formatMoney(total);

  return out;
}

function mapRoomTotals(arr, headerList) {
  const out = { date: "Tổng cộng" };
  for (const h of headerList) {
    if (h.key !== "date" && h.key !== "totalRevenue") out[h.key] = 0;
  }
  let grand = 0;
  const list = Array.isArray(arr) ? arr : [];
  for (const it of list) {
    const amt = Number.isFinite(+it?.total) ? +it.total : 0;
    const key = makeRoomKey(it?.roomName, it?.roomId);
    if (key in out) out[key] += amt;
    grand += amt;
  }

  for (const h of headerList) {
    if (h.key !== "date" && h.key !== "totalRevenue") {
      const v = out[h.key];
      out[`${h.key}_text`] = formatMoney(v);
    }
  }

  out.totalRevenue = grand;
  out.totalRevenue_text = formatMoney(grand);

  return out;
}
export const useRevenueReport = defineStore("useRevenueReport", {
  state: () => ({
    currentTab: "detailed-revenue-report",
    tabs: {},
  }),
  getters: {
    active(state) {
      return state.tabs[state.currentTab];
    },
  },
  actions: {
    ensureTab(tab) {
      if (!this.tabs[tab]) {
        this.tabs[tab] = {
          filters: defaultFilters(),
          page: 1,
          size: 10,
          total: 0,
          rows: [],
          loading: false,
          error: null,
          initialized: false,
          efficiencyTotal: null,
          detailTotals: [],
          bookingTotal: null,
          bookingSourceTotal: [],
          headerRevenueRoom: [],
          dailySaleTotal: null,
          activityTotal: null,
        };
      }
    },
    async switchTab(tab, { reset = true, fetchNow = true } = {}) {
      this.ensureTab(tab);
      this.currentTab = tab;
      const t = this.tabs[tab];
      if (reset || !t.initialized) {
        t.filters = defaultFilters();
        t.page = 1;
        t.size = 10;
        t.total = 0;
        t.rows = [];
        t.error = null;
        t.efficiencyTotal = null;
        t.detailTotals = [];
        t.bookingTotal = null;
      }
      if (fetchNow) await this.fetch(tab);
      t.initialized = true;
    },
    replaceFilters(payload = {}, tab = null) {
      const key = tab || this.currentTab;
      const t = this.tabs[key];
      const allowed = [
        "dateFrom",
        "dateTo",
        "timeFrom",
        "timeTo",
        "dateType",
        "timeRange",
        "currency",
        "otaName",
        "monthFrom",
        "monthTo",
        "yearFrom",
        "yearTo",
        "property",
        "roomId",
      ];
      const cleaned = {};
      for (const k of allowed) cleaned[k] = payload[k] ?? t.filters[k];
      t.filters = { ...t.filters, ...cleaned };
      t.page = 1;
    },
    setPage(page = 1, tab = null) {
      const key = tab || this.currentTab;
      const t = this.tabs[key];
      t.page = Number.isFinite(+page) ? Math.max(1, +page) : 1;
    },
    setSize(size = 10, tab = null) {
      const key = tab || this.currentTab;
      const t = this.tabs[key];
      t.size = Number.isFinite(+size) ? Math.max(1, +size) : 10;
      t.page = 1;
    },
    applyResponse(t, data, rowMapper) {
      const payload = data || {};
      const result =
        (Array.isArray(payload?.result) && payload.result) ||
        (Array.isArray(payload?.data?.result) && payload.data.result) ||
        [];
      const mapFn = typeof rowMapper === "function" ? rowMapper : (x) => x;
      t.rows = result.map(mapFn);
      const rawTotal =
        payload?.total ??
        payload?.totalCount ??
        payload?.count ??
        payload?.pagination?.total ??
        payload?.meta?.total;
      const total = Number.isFinite(+rawTotal)
        ? +rawTotal
        : (t.page - 1) * (t.size || 10) +
          result.length +
          (result.length === (t.size || 10) ? 1 : 0);
      const rawSize = payload?.size ?? payload?.pageSize ?? payload?.limit;
      const size = Number.isFinite(+rawSize)
        ? Math.max(1, +rawSize)
        : t.size || 10;
      const rawPage =
        payload?.page ?? payload?.currentPage ?? payload?.pagination?.page;
      let page = Number.isFinite(+rawPage)
        ? Math.max(1, +rawPage)
        : t.page || 1;
      const maxPage = Math.max(1, Math.ceil(total / size));
      if (page > maxPage) page = maxPage;
      t.total = total;
      t.size = size;
      t.page = page;
    },
    async fetch(tab = null) {
      const key = tab || this.currentTab;
      this.ensureTab(key);
      const t = this.tabs[key];
      t.loading = true;
      t.error = null;
      try {
        if (key === "performance-report") {
          const listQuery = buildListQueryPerformance(t);
          const totalQuery = buildTotalQueryPerformance(t);
          const [resList, resTotal] = await Promise.allSettled([
            getEfficiencyReport(listQuery),
            getEfficiencyTotalReport(totalQuery),
          ]);
          if (resList.status === "fulfilled") {
            const payload =
              resList.value?.data?.data ?? resList.value?.data ?? {};
            this.applyResponse(t, payload, (r) => mapListDataPerformance(r));
          }
          if (resTotal.status === "fulfilled") {
            const totalObj =
              resTotal.value?.data?.data ?? resTotal.value?.data ?? null;
            t.efficiencyTotal = mapListDataPerformance(totalObj, true);
          }
        } else if (key === "detailed-revenue-report") {
          const listQuery = buildListQueryDetail(t);
          const totalQuery = buildTotalQueryDetail(t);
          const [resList, resTotal] = await Promise.allSettled([
            getRevenueDetailReport(listQuery),
            getRevenueDetailTotalReport(totalQuery),
          ]);
          if (resList.status === "fulfilled") {
            const payload =
              resList.value?.data?.data ?? resList.value?.data ?? {};
            this.applyResponse(t, payload, mapRevenueRow);
          }
          if (resTotal.status === "fulfilled") {
            const arr =
              resTotal.value?.data?.data ?? resTotal.value?.data ?? [];
            t.detailTotals = arr.map(mapRevenueTotals).filter(Boolean);
          }
        } else if (key === "revenue-report-booking") {
          const listQuery = buildListQueryBooking(t);
          const totalQuery = buildTotalQueryBooking(t);
          const [resList, resTotal] = await Promise.allSettled([
            getBookingReport(listQuery),
            getBookingTotalReport(totalQuery),
          ]);
          if (resList.status === "fulfilled") {
            const payload =
              resList.value?.data?.data ?? resList.value?.data ?? {};
            this.applyResponse(t, payload, mapBookingRow);
          }
          if (resTotal.status === "fulfilled") {
            const totalObj =
              resTotal.value?.data?.data ?? resTotal.value?.data ?? null;
            t.bookingTotal = mapBookingTotals(totalObj);
          } else {
            t.bookingTotal = null;
          }
        } else if (key === "revenue-report-booking-source") {
          const listQuery = buildListQueryBookingSource(t);
          const totalQuery = buildTotalQueryBookingSource(t);
          const [resList, resTotal] = await Promise.allSettled([
            getBookingSourceReport(listQuery),
            getBookingSourceTotalReport(totalQuery),
          ]);
          if (resList.status === "fulfilled") {
            const payload =
              resList.value?.data?.data ?? resList.value?.data ?? {};
            this.applyResponse(t, payload, mapBookingSourceRow);
          }
          let totalsRow = null;
          if (resTotal.status === "fulfilled") {
            const tot =
              resTotal.value?.data?.data ?? resTotal.value?.data ?? [];
            const mapped = mapBookingSourceTotals(tot);
            if (mapped) totalsRow = { date: "Tổng cộng", ...mapped };
          }
          if (totalsRow) t.rows = [...(t.rows || []), totalsRow];
          t.bookingSourceTotal = totalsRow || null;
        } else if (key === "revenue-reports-room-type") {
          if (!t.headerRevenueRoom || !t.headerRevenueRoom.length) {
            await this.buildHeaderRevenueRoom();
          }
          const listQuery = buildListQueryRoom(t);
          const totalQuery = buildTotalQueryRoom(t);
          const [resList, resTotal] = await Promise.allSettled([
            getRevenueRoomReport(listQuery),
            getRevenueRoomTotalReport(totalQuery),
          ]);
          if (resList.status === "fulfilled") {
            const payload =
              resList.value?.data?.data ?? resList.value?.data ?? {};
            this.applyResponse(t, payload, (row) =>
              mapRoomRow(row, t.headerRevenueRoom)
            );
          }
          if (resTotal.status === "fulfilled") {
            const totArr =
              resTotal.value?.data?.data ?? resTotal.value?.data ?? [];
            const totalsRow = mapRoomTotals(totArr, t.headerRevenueRoom);
            if (totalsRow) t.rows = [...(t.rows || []), totalsRow];
          }
        } else if (key === "daily-sale-report") {
          // NEW: Daily sale
          const [resList, resTotal] = await Promise.allSettled([
            getRevenueDailySaleReport(buildListQueryDailySale(t)),
            getRevenueDailySaleTotalReport(buildTotalQueryDailySale(t)),
          ]);
          if (resList.status === "fulfilled") {
            const payload =
              resList.value?.data?.data ?? resList.value?.data ?? {};
            this.applyResponse(t, payload, mapDailySaleRow);
          }
          if (resTotal.status === "fulfilled") {
            const totalObj =
              resTotal.value?.data?.data ?? resTotal.value?.data ?? null;
            t.dailySaleTotal = mapDailySaleTotals(totalObj);
            if (t.dailySaleTotal) {
              t.rows = [...(t.rows || []), t.dailySaleTotal];
            }
          } else {
            t.dailySaleTotal = null;
          }
        } else if (key === "activity-report") {
          const listQuery = buildListQueryActivityReport(t);
          const totalQuery = buildTotalQueryActivityReport(t);
          const [resList, resTotal] = await Promise.allSettled([
            getRevenueActivityReport(listQuery),
            getRevenueActivityTotalReport(totalQuery),
          ]);
          if (resList.status === "fulfilled") {
            const payload =
              resList.value?.data?.data ?? resList.value?.data ?? {};
            this.applyResponse(t, payload, mapActivitySourceRow);
          }
          let totalsRow = null;
          if (resTotal.status === "fulfilled") {
            const tot =
              resTotal.value?.data?.data ?? resTotal.value?.data ?? [];
            const mapped = mapActivitySourceTotals(tot);
            if (mapped) totalsRow = { ...mapped };
          }
          if (totalsRow) t.rows = [...(t.rows || []), totalsRow];
          t.activityTotal = totalsRow || null;
        }
      } catch (err) {
        t.error = err?.message || "Lỗi tải dữ liệu";
        t.detailTotals = [];
        t.bookingTotal = null;
      } finally {
        t.loading = false;
      }
    },
    async buildHeaderRevenueRoom() {
      const t = this.tabs[this.currentTab];
      const payload = { property: t?.filters?.property ?? "" };
      try {
        const res = await apiGetHeaderRevenueRoomReport(payload);
        const list = res?.data?.result ?? [];
        const dynamicHeaders = list.map((item) => ({
          key: `${toAsciiSlug(item?.name)}_${item?.id ?? "x"}`,
          title: item?.name ?? "Unnamed",
          align: "center",
          width: 150,
          money: true,
        }));
        const headers = [
          { key: "date", title: "Ngày", align: "center" },
          ...dynamicHeaders,
          { key: "totalRevenue", title: "Tổng", align: "center" },
        ];
        t.headerRevenueRoom = headers;
        return headers;
      } catch {
        const fallback = [
          { key: "date", title: "Ngày", align: "center" },
          { key: "totalRevenue", title: "Tổng", align: "center" },
        ];
        t.headerRevenueRoom = fallback;
        return fallback;
      }
    },
  },
});
