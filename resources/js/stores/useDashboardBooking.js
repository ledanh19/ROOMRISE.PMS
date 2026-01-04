import {
  getBookingCustomer,
  getBookingDetails,
  getBookingLeadtime,
  getBookingRoom,
  getBookingSource,
  getBookingStat,
  getBookingStatus,
} from "@/utils/apiDashboard/dashboardBooking";
import { formatCurrencyVND, formatDateShortDdMm, toNum } from "@/utils/helper";
import { defineStore } from "pinia";
import { usePropertyStore } from "./usePropertyStore";

function defaultFilters() {
  return { type: null, timeRange: null, dateFrom: null, dateTo: null };
}

function getSelectedPropId(propertyStore) {
  const v = propertyStore?.selectedProperty;
  if (v && typeof v === "object") return v.id ?? v.value ?? "";
  return v ?? "";
}

function mapBookingStat(api) {
  const d = api || {};
  const model = {
    newBookings: toNum(d.newBookings),
    checkIn: toNum(d.checkin),
    percentNewBookings: toNum(d.percentNewBookings),
    percentTotalBookings: toNum(d.percentTotalBookings),
    percentCheckIn: toNum(d.percentCheckin),
    cancel: toNum(d.cancel),
    percentCancellationRate: toNum(d.percentCancellationRate),
    percentCancellation: toNum(d.percentCancellation),
  };

  const kpis = [];
  return { model, kpis };
}

function mapStatusToTrend(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];
  const days = [];
  const totals = [];
  const STATUS_ORDER = ["Mới", "Huỷ", "Yêu cầu", "Xác nhận"];
  const statusMap = {};
  STATUS_ORDER.forEach((s) => (statusMap[s] = []));

  arr.forEach((day) => {
    days.push(formatDateShortDdMm(day.date) ?? "");
    totals.push(Number(day.total ?? 0));
    const items = Array.isArray(day.items) ? day.items : [];
    const tmp = {};
    items.forEach((it) => {
      const key = it.status ?? "";
      tmp[key] = Number(it.total ?? 0);
    });
    STATUS_ORDER.forEach((s) => {
      statusMap[s].push(tmp[s] ?? 0);
    });
  });

  const series = [
    { name: "Tổng", type: "area", data: totals },
    ...STATUS_ORDER.map((s) => ({
      name: s,
      type: "column",
      data: statusMap[s],
    })),
  ];

  return { days, totals, series, statuses: STATUS_ORDER };
}

function mapExecutiveSource(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];
  const otaNames = [];
  const totals = [];
  const rates = [];
  const revenues = [];

  arr.forEach((item) => {
    otaNames.push(item.otaName ?? "");
    totals.push(toNum(item.total));
    rates.push(toNum(item.rate));
    revenues.push(toNum(item.revenue));
  });

  return { otaNames, totals, rates, revenues };
}

function mapRoomData(api, moneyFormatter = (v) => v) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];

  const roomTypes = [];
  const totals = [];
  const adrs = [];
  const meta = [];

  arr.forEach((r) => {
    const name = r.roomName ?? "";
    const total = toNum(r.total);
    const adr = toNum(r.ADR);
    const los = r.LOS ?? 0;
    const id = r.roomId ?? null;

    roomTypes.push(name);
    totals.push(total);
    adrs.push(adr);

    meta.push({
      name,
      los,
      adr,
      adrStr: formatCurrencyVND(adr),
      total,
      roomId: id,
    });
  });

  const byRoomType = [
    { name: "Total", data: totals },
    { name: "ADR", data: adrs },
  ];

  return { roomTypes, totals, adrs, byRoomType, meta };
}

function mapBookingCustomer(api) {
  const d = api || {};
  const model = {
    avgLOS: d.avgLOS,
    avgLeadTime: d.avgLeadTime,
    avgADR: d.avgADR,
    newCustomerRate: d.newCustomerRate,
    returningCustomerRate: d.returningCustomerRate,
  };

  return { model };
}

const LEADTIME_LABELS = {
  sameDay: "Same-day",
  sevenDays: "< 7 days",
  sevenToThirtyDays: "7-30 days",
  moreThanThirtyDays: "> 30 days",
};

function mapLeadTimeData(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];
  const COLORS = ["primary", "success", "warning", "error"];

  return arr.map((it, idx) => {
    const key = it.timeRange ?? "";
    const label = LEADTIME_LABELS[key] ?? key;
    const total = Number(it.total ?? 0);
    const percent = Number(it.percent ?? 0);
    return {
      label,
      value: total || null,
      percent,
      color: COLORS[idx % COLORS.length],
    };
  });
}

function formatDateShort(iso) {
  if (!iso) return "";
  try {
    const d = new Date(iso);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${yyyy}-${mm}-${dd}`;
  } catch (e) {
    return iso;
  }
}

const BOOKING_STATUS_MAP = {
  Mới: { text: "Mới", color: "primary", icon: "tabler-plus" },
  "Xác nhận": {
    text: "Xác nhận",
    color: "success",
    icon: "tabler-circle-check",
  },
  Hủy: { text: "Đã huỷ", color: "error", icon: "tabler-x" },
  Huỷ: { text: "Đã huỷ", color: "error", icon: "tabler-x" },
};

function mapBookingDetails(api) {
  const payload = api?.result ?? api?.data ?? api ?? [];
  const rows = Array.isArray(payload)
    ? payload
    : Array.isArray(api?.result)
    ? api.result
    : [];

  const total = Number(api?.total ?? api?.result?.length ?? 0);
  const size = Number(api?.size ?? api?.result?.length ?? rows.length);
  const page = Number(api?.page ?? 1);

  const mapped = rows.map((r) => {
    const statusRaw = r.bookingStatus ?? r.booking_status ?? "";
    const statusMeta = BOOKING_STATUS_MAP[statusRaw] ?? {
      text: statusRaw,
      color: "grey",
      icon: "tabler-help",
    };
    return {
      id: r.id ?? null,
      code: r.otaReservationCode ?? "",
      property: r.propertyName ?? "",
      room: r.roomName ?? "",
      customer: r.customerName ?? "",
      checkInDate: r.checkInDate ?? "",
      checkOutDate: r.checkOutDate ?? "",
      checkInDateShort: formatDateShort(r.checkInDate),
      checkOutDateShort: formatDateShort(r.checkOutDate),
      nights: Number(r.nights ?? 0),
      leadTime: Number(r.leadTime ?? 0),
      amount: Number(r.amount ?? 0),
      bookingStatus: statusRaw,
      otaName: r.otaName,
      statusMeta,
    };
  });

  return { rows: mapped, meta: { total, size, page } };
}

export const useDashboardBooking = defineStore("useDashboardBooking", {
  state: () => ({
    loading: false,
    error: null,
    filters: defaultFilters(),
    propertyId: "",

    stats: {
      newBookings: 0,
      checkIn: 0,
      percentNewBookings: 0,
      percentTotalBookings: 0,
      percentCheckIn: 0,
      cancel: 0,
      percentCancellationRate: 0,
      percentCancellation: 0,
    },
    customer: {
      avgLOS: 0,
      avgLeadTime: 0,
      avgADR: 0,
      newCustomerRate: 0,
      returningCustomerRate: 0,
    },
    kpis: [],

    dateDays: [],
    dateValues: [],
    statusSeries: [],
    statusDays: [],

    otaNameSources: [],
    totalSources: [],
    rateSources: [],
    revenues: [],

    roomTypes: [],
    byRoomType: [],
    roomMeta: [],
    totalRooms: 0,
    totalBookings: 0,

    leadTimeItems: [],

    bookingRows: [],
    bookingTotal: 0,
    bookingSize: 0,
    bookingPage: 1,
  }),

  actions: {
    syncProperty() {
      const ps = usePropertyStore();
      this.propertyId = getSelectedPropId(ps);
    },

    setFilters(partial) {
      this.filters = { ...this.filters, ...partial };
    },

    resetFilters() {
      this.filters = defaultFilters();
    },

    async fetchStats() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          type: this.filters.type || "",
          property: this.propertyId || "",
        };

        const res = await getBookingStat(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? {};
        const mapped = mapBookingStat(payload);
        this.stats = mapped.model;
        this.kpis = mapped.kpis;
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchStatus() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          type: this.filters.type || "",
          property: this.propertyId || "",
        };

        const res = await getBookingStatus(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? {};
        const mapped = mapStatusToTrend({ data: payload });
        this.dateDays = mapped.days;
        this.dateValues = mapped.totals;
        this.statusSeries = mapped.series;
        this.statusDays = mapped.days;
      } catch (e) {
        console.log(e);
        this.error = e?.message || "Fetch status failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchSource() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          type: this.filters.type || "",
          property: this.propertyId || "",
        };

        const res = await getBookingSource(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? {};
        const mapped = mapExecutiveSource(payload);
        this.otaNameSources = mapped.otaNames;
        this.totalSources = mapped.totals;
        this.rateSources = mapped.rates;
        this.revenues = mapped.revenues;
      } catch (e) {
        this.error = e?.message || "Fetch source failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchRoom() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          property: this.propertyId || "",
        };

        const res = await getBookingRoom(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapRoomData(payload, (v) => {
          if (v === null || v === undefined) return "-";
          return Number(v).toLocaleString("vi-VN");
        });

        this.roomTypes = mapped.roomTypes;
        this.byRoomType = mapped.byRoomType;
        this.roomMeta = mapped.meta;
        this.totalRooms = mapped.roomTypes.length;
        this.totalBookings = mapped.totals.reduce((s, n) => s + n, 0);
      } catch (e) {
        this.error = e?.message || "Fetch room data failed";
        this.roomTypes = [];
        this.byRoomType = [];
        this.roomMeta = [];
        this.totalRooms = 0;
        this.totalBookings = 0;
      } finally {
        this.loading = false;
      }
    },
    async fetchCustomer() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          property: this.propertyId || "",
        };

        const res = await getBookingCustomer(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapBookingCustomer(payload);
        this.customer = mapped.model;
      } catch (e) {
        this.error = e?.message || "Fetch room data failed";
        this.customer = [];
      } finally {
        this.loading = false;
      }
    },

    async fetchLeadTime() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          property: this.propertyId || "",
        };

        const res = await getBookingLeadtime(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        this.leadTimeItems = mapLeadTimeData(payload);
      } catch (e) {
        this.error = e?.message || "Fetch lead time failed";
        this.leadTimeItems = [];
      } finally {
        this.loading = false;
      }
    },

    async fetchBookingDetails({ page, size }) {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const q = {
          page: page ?? 1,
          size: size ?? 10,
          timeRange: this.filters.timeRange || "",
          property: this.propertyId || "",
        };
        const res = await getBookingDetails(q);
        const payload = res?.data ?? res ?? {};
        const mapped = mapBookingDetails(payload);

        this.bookingRows = mapped.rows;
        this.bookingTotal = mapped.meta.total;
        this.bookingSize = mapped.meta.size;
        this.bookingPage = mapped.meta.page;
      } catch (e) {
        this.error = e?.message || "Fetch booking details failed";
        this.bookingRows = [];
        this.bookingTotal = 0;
        this.bookingSize = 0;
        this.bookingPage = 1;
      } finally {
        this.loading = false;
      }
    },
  },

  getters: {
    totalSeries(state) {
      const s = state.byRoomType.find((x) => x.name === "Total");
      return s ? [s] : [];
    },
    adrSeries(state) {
      const s = state.byRoomType.find((x) => x.name === "ADR");
      return s ? [s] : [];
    },
  },
});
