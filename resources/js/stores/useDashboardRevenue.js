import {
  getRevenueAdrRevparTrend,
  getRevenuePaymentType,
  getRevenueRoom,
  getRevenueSource,
  getRevenueStat,
  getRevenueTopOtaPartner,
  getRevenueTrend,
} from "@/utils/apiDashboard/dashboardRevenue";
import { formatDateShortDdMm } from "@/utils/helper";
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

function mapRevenueTrend(api) {
  const arr = Array.isArray(api) ? api : [];
  const days = [];
  const revenues = [];
  const RevPACs = [];
  const ADRs = [];
  const RevPARs = [];

  arr.forEach((item) => {
    const date = item.date ? formatDateShortDdMm(item.date) : "";
    const revenue = item.revenue ? item.revenue : 0;
    const revpac = item.RevPAC ? item.RevPAC : 0;
    const adr = item.ADR ? item.ADR : 0;
    const revpar = item.RevPAR ? item?.RevPAR : 0;

    days.push(date);
    revenues.push(revenue);
    RevPACs.push(revpac);
    ADRs.push(adr);
    RevPARs.push(revpar);
  });

  return { days, revenues, RevPACs, ADRs, RevPARs };
}

function mapRevenueSource(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];
  const otaNames = [];
  const amounts = [];
  const rates = [];
  const lines = [];
  const revenues = [];

  arr.forEach((item) => {
    otaNames.push(item.otaName);
    amounts.push(toNum(item.total));
    rates.push(toNum(item.rate));
    lines.push({
      label: item.otaName,
      value: item.total,
      percent: item.rate,
    });
    revenues.push(item.revenue);
  });
  return { otaNames, amounts, rates, lines, revenues };
}

function mapRevenueRoom(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];
  const roomNames = [];
  const revenues = [];
  const cards = [];

  arr.forEach((item) => {
    roomNames.push(item.roomName);
    revenues.push(toNum(item.currentRevenue));
    cards.push({
      name: item.roomName,
      adr: item.currentADR,
      revpar: item.currentRevPAR,
      growth: item.revenuePercent,
    });
  });

  return { roomNames, revenues, cards };
}

function mapRevenuePaymentType(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];
  const data = [];

  arr.forEach((item) => {
    data.push({
      label: item.paymentType,
      value: item.totalAmount,
      percent: item.totalAmountPercent,
    });
  });
  return { data };
}

function mapRevenueAdrRevparTrend(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];
  const months = [];
  const adrs = [];
  const revpars = [];

  arr.forEach((item) => {
    months.push(item.month);
    adrs.push(toNum(item.ADR));
    revpars.push(toNum(item.RevPAR));
  });

  return { months, adrs, revpars };
}

function mapRevenueTopOtaPartner(api) {
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
    return {
      otaName: r.otaName ?? "",
      currentBookings: r.currentBookings ?? 0,
      previousBookings: r.previousBookings ?? 0,
      percentGrowthBookings: r.percentGrowthBookings ?? 0,
      percentTotalBookings: r.percentTotalBookings ?? 0,
      currentRevenue: r.currentRevenue ?? 0,
      previousRevenue: r.previousRevenue ?? 0,
      percentGrowthRevenue: r.percentGrowthRevenue ?? 0,
      percentTotalRevenue: r.percentTotalRevenue ?? 0,
    };
  });

  return { rows: mapped, meta: { total, size, page } };
}

export const useDashboardRevenue = defineStore("useDashboardRevenue", {
  state: () => ({
    loading: false,
    error: null,
    filters: defaultFilters(),
    propertyId: "",
    stats: {
      currentRevenue: 0,
      previousRevenue: 0,
      percentRevenue: 0,
      currentADR: 0,
      previousADR: 0,
      percentADR: 0,
      currentRevPAR: 0,
      previousRevPAR: 0,
      percentRevPAR: 0,
      currentRevPAC: 0,
      previousRevPAC: 0,
      percentRevPAC: 0,
    },
    trend: {
      days: [],
      revenues: [],
      RevPACs: [],
      ADRs: [],
      RevPARs: [],
    },
    source: {
      otaNames: [],
      amounts: [],
      rates: [],
      lines: [],
      revenues: [],
    },
    room: {
      roomNames: [],
      revenues: [],
      cards: [],
    },
    paymentType: [],
    adrRevparTrend: {
      months: [],
      adrs: [],
      revpars: [],
    },
    topOTAPartnerRows: [],
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

        const res = await getRevenueStat(params);
        this.stats = res?.data?.data ?? res?.data ?? res ?? {};
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchTrend() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          property: this.propertyId || "",
        };

        const res = await getRevenueTrend(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapRevenueTrend(payload);
        this.trend.days = mapped.days;
        this.trend.revenues = mapped.revenues;
        this.trend.RevPACs = mapped.RevPACs;
        this.trend.ADRs = mapped.ADRs;
        this.trend.RevPARs = mapped.RevPARs;
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
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
          property: this.propertyId || "",
        };

        const res = await getRevenueSource(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapRevenueSource(payload);
        this.source.otaNames = mapped.otaNames;
        this.source.amounts = mapped.amounts;
        this.source.rates = mapped.rates;
        this.source.lines = mapped.lines;
        this.source.revenues = mapped.revenues;
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
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
          type: this.filters.type || "",
          property: this.propertyId || "",
        };

        const res = await getRevenueRoom(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapRevenueRoom(payload);
        this.room.roomNames = mapped.roomNames;
        this.room.revenues = mapped.revenues;
        this.room.cards = mapped.cards;
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchPaymentType() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          property: this.propertyId || "",
        };

        const res = await getRevenuePaymentType(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapRevenuePaymentType(payload);
        this.paymentType = mapped.data;
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchRevenueAdrRevparTrend() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          property: this.propertyId || "",
        };

        const res = await getRevenueAdrRevparTrend(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapRevenueAdrRevparTrend(payload);
        this.adrRevparTrend.months = mapped.months;
        this.adrRevparTrend.adrs = mapped.adrs;
        this.adrRevparTrend.revpars = mapped.revpars;
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchRevenueTopOtaPartner({ page, size }) {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const q = {
          page: page ?? 1,
          size: size ?? 10,
          type: this.filters.type || "",
          timeRange: this.filters.timeRange || "",
          property: this.propertyId || "",
        };
        const res = await getRevenueTopOtaPartner(q);
        const payload = res?.data ?? res ?? {};
        const mapped = mapRevenueTopOtaPartner(payload);

        this.topOTAPartnerRows = mapped.rows;
        this.bookingTotal = mapped.meta.total;
        this.bookingSize = mapped.meta.size;
        this.bookingPage = mapped.meta.page;
      } catch (e) {
        this.error = e?.message || "Fetch booking details failed";
        this.topOTAPartnerRows = [];
        this.bookingTotal = 0;
        this.bookingSize = 0;
        this.bookingPage = 1;
      } finally {
        this.loading = false;
      }
    },
  },

  getters: {},
});
