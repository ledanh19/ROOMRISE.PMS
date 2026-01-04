import {
  getExecutiveCountry,
  getExecutiveDate,
  getExecutiveQuickWarning,
  getExecutiveRevenue,
  getExecutiveSource,
  getExecutiveStat,
} from "@/utils/apiDashboard/dashboardExecutive";
import moment from "moment";
import { defineStore } from "pinia";
import { usePropertyStore } from "./usePropertyStore";

function defaultFilters() {
  return { type: null, timeRange: null };
}

function getSelectedPropId(propertyStore) {
  const v = propertyStore?.selectedProperty;
  if (v && typeof v === "object") return v.id ?? v.value ?? "";
  return v ?? "";
}

function toNum(v) {
  const n = Number(v);
  return Number.isFinite(n) ? n : 0;
}

function mapExecutiveStat(api) {
  const d = api || {};
  const model = {
    bookings: {
      current: toNum(d.currentTotalBookings),
      previous: toNum(d.previousTotalBookings),
      percent: toNum(d.percentTotalBookings),
    },
    revenue: {
      current: toNum(d.currentRevenue),
      previous: toNum(d.previousRevenue),
      percent: toNum(d.revenuePercent),
    },
    adr: {
      current: toNum(d.currentADR),
      previous: toNum(d.previousADR),
      percent: toNum(d.percentADR),
    },
    occ: {
      current: toNum(d.currentOCC),
      previous: toNum(d.previousOCC),
      percent: toNum(d.percentOCC),
    },
    revpar: {
      current: toNum(d.currentRevPAR),
      previous: toNum(d.previousRevPAR),
      percent: toNum(d.percentRevPAR),
    },
    cancellationRate: {
      current: toNum(d.currentCancellationRate),
      previous: toNum(d.previousCancellationRate),
      percent: toNum(d.percentCancellationRate),
    },
  };

  const kpis = [
    { key: "revenue", label: "Doanh thu", unit: "VND" },
    { key: "adr", label: "ADR", unit: "VND" },
    { key: "revpar", label: "RevPAR", unit: "VND" },
    { key: "occ", label: "OCC", unit: "%" },
    { key: "bookings", label: "Bookings", unit: "" },
    { key: "cancellationRate", label: "Tỉ lệ huỷ", unit: "%" },
  ].map((k) => {
    const v = model[k.key] || { percent: 0, current: 0, previous: 0 };
    const trend = v.percent > 0 ? "up" : v.percent < 0 ? "down" : "flat";
    return {
      key: k.key,
      label: k.label,
      unit: k.unit,
      current: v.current,
      previous: v.previous,
      percent: v.percent,
      trend,
    };
  });

  return { model, kpis };
}

function mapExecutiveDate(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];

  const days = [];
  const values = [];

  arr.forEach((item) => {
    const date = item?.date ? moment(item.date).format("DD/MM") : "";

    const value = item?.total ?? 0;

    days.push(date);
    values.push(value);
  });

  return { days, values };
}

function mapExecutiveRevenue(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];

  const days = [];
  const values = [];

  arr.forEach((item) => {
    const date = item.date ? moment(item.date).format("DD/MM") : "";
    const value = item.total ?? 0;

    days.push(date);
    values.push(value);
  });
  return { days, values };
}

function mapExecutiveSource(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];

  const otaNames = [];
  const values = [];
  const revenues = [];
  const totals = [];
  const lines = [];

  arr.forEach((item) => {
    const otaName = item.otaName != null ? item.otaName : "";
    const value = item.rate != null ? item.rate : "";
    const revenue = item.revenue != null ? item.revenue : "";
    const total = item.total != null ? item.total : "";

    lines.push({
      label: item.otaName,
      value: item.total,
      percent: item.rate,
    });

    otaNames.push(otaName);
    values.push(value);
    revenues.push(revenue);
    totals.push(total);
  });

  return { otaNames, values, revenues, totals, lines };
}

function mapExecutiveCountry(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];

  const countries = [];
  const values = [];

  arr.forEach((item) => {
    const country = item.country != null ? item.country : "";

    const value = item.total != null ? item.total : "";

    countries.push(country);
    values.push(value);
  });

  return { countries, values };
}

function mapExecutiveQuickWarning(api) {
  return {
    currentCancellationRate: api.currentCancellationRate || 0,
    averageCancellationRate: api.averageCancellationRate || 0,
    revenueDownRate: api.revenueDownRate || 0,
  };
}

export const useDashboardExecutive = defineStore("useDashboardExecutive", {
  state: () => ({
    loading: false,
    error: null,
    filters: defaultFilters(),
    propertyId: "",
    stats: {
      bookings: { current: 0, previous: 0, percent: 0 },
      revenue: { current: 0, previous: 0, percent: 0 },
      adr: { current: 0, previous: 0, percent: 0 },
      occ: { current: 0, previous: 0, percent: 0 },
      revpar: { current: 0, previous: 0, percent: 0 },
      cancellationRate: { current: 0, previous: 0, percent: 0 },
    },
    kpis: [],
    dateDays: [],
    dateValues: [],
    revenueDays: [],
    revenueValues: [],
    sourceOtaNames: [],
    sourceValues: [],
    sourceRevenue: [],
    sourceLines: [],
    sourceTotal: [],
    countries: [],
    countryValues: [],
    quickWarningData: {
      currentCancellationRate: 0,
      averageCancellationRate: 0,
      revenueDownRate: 0,
    },
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

        const res = await getExecutiveStat(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? {};
        const mapped = mapExecutiveStat(payload);
        this.stats = mapped.model;
        this.kpis = mapped.kpis;
      } catch (e) {
        this.error = e?.message || "fetchStats failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchDate() {
      try {
        this.loading = true;
        this.error = null;

        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          type: this.filters.type || "",
          property: this.propertyId || "",
        };

        const res = await getExecutiveDate(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? {};
        const mapped = mapExecutiveDate(payload);

        this.dateDays = mapped.days;
        this.dateValues = mapped.values;
      } catch (e) {
        this.error = e?.message || "fetchDate failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchRevenue() {
      try {
        this.loading = true;
        this.error = null;

        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          type: this.filters.type || "",
          property: this.propertyId || "",
        };

        const res = await getExecutiveRevenue(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? {};
        const mapped = mapExecutiveRevenue(payload);

        this.revenueDays = mapped.days;
        this.revenueValues = mapped.values;
      } catch (e) {
        this.error = e?.message || "fetchRevenue failed";
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

        const res = await getExecutiveSource(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? {};
        const mapped = mapExecutiveSource(payload);

        this.sourceOtaNames = mapped.otaNames;
        this.sourceValues = mapped.values;
        this.sourceTotal = mapped.totals;
        this.sourceRevenue = mapped.revenues;
        this.sourceLines = mapped.lines;
      } catch (e) {
        this.error = e?.message || "fetchSource failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchCountry() {
      try {
        this.loading = true;
        this.error = null;

        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          type: this.filters.type || "",
          property: this.propertyId || "",
        };

        const res = await getExecutiveCountry(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? {};
        const mapped = mapExecutiveCountry(payload);

        this.countries = mapped.countries;
        this.countryValues = mapped.values;
      } catch (e) {
        this.error = e?.message || "fetchCountry failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchQuickWarning() {
      try {
        this.loading = true;
        this.error = null;

        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          type: this.filters.type || "",
          property: this.propertyId || "",
        };

        const res = await getExecutiveQuickWarning(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? {};
        const mapped = mapExecutiveQuickWarning(payload);

        this.quickWarningData = mapped;
      } catch (e) {
        this.error = e?.message || "fetchQuickWarning failed";
      } finally {
        this.loading = false;
      }
    },
  },
});
