import {
  getPerformanceHeatmap,
  getPerformanceLeadTime,
  getPerformanceLOS,
  getPerformanceLOSTrend,
  getPerformanceProperty,
  getPerformanceStats,
  getPerformanceTime,
} from "@/utils/apiDashboard/dashboardPerformance";
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

function mapPerformanceTime(api) {
  const arr = Array.isArray(api) ? api : [];
  const days = [];
  const OCCs = [];
  const ADRs = [];
  const RevPARs = [];

  arr.forEach((item) => {
    const date = item.date ? item.date : "";
    const occ = item.OCC ? item.OCC : 0;
    const adr = item.ADR ? item.ADR : 0;
    const revpar = item.RevPAR ? item?.RevPAR : 0;

    days.push(formatDateShortDdMm(date));
    OCCs.push(occ);
    ADRs.push(adr);
    RevPARs.push(revpar);
  });

  return { days, OCCs, ADRs, RevPARs };
}

function mapPerformanceLeadTime(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];
  const data = [];
  const labels = {
    sameDay: "Same-day",
    sevenDays: "< 7 days",
    sevenToThirtyDays: "7-30 days",
    moreThanThirtyDays: "> 30 days",
  };
  arr.forEach((item) => {
    data.push({
      label: labels[item.timeRange],
      value: item.total,
      percent: item.percent,
    });
  });
  return { data };
}

function mapPerformanceLOS(api) {
  const arr = Array.isArray(api?.data)
    ? api.data
    : Array.isArray(api)
    ? api
    : [];
  const data = [];
  const labels = {
    oneNight: "1 đêm",
    twoThreeNight: "2-3 đêm",
    moreThanFour: "≥4 đêm",
  };
  arr.forEach((item) => {
    data.push({
      label: labels[item.timeRange],
      value: item.total,
      percent: item.percent,
    });
  });
  return { data };
}

function mapPerformanceLOSTrend(api) {
  const arr = Array.isArray(api) ? api : [];
  const days = [];
  const avgLOSs = [];

  arr.forEach((item) => {
    const date = item.date ? formatDateShortDdMm(item.date) : "";
    const avgLOS = item.avgLOS ? item.avgLOS : 0;

    days.push(date);
    avgLOSs.push(avgLOS);
  });

  return { days, avgLOSs };
}

function mapPerformanceHeatmap(api) {
  const data = Array.isArray(api) ? api : [];
  const weekdays = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
  const dayMap = {
    Monday: 0,
    Tuesday: 1,
    Wednesday: 2,
    Thursday: 3,
    Friday: 4,
    Saturday: 5,
    Sunday: 6,
  };

  const roomMap = new Map();
  data.forEach((day) => {
    day.items.forEach((item) => {
      if (!roomMap.has(item.roomId)) {
        roomMap.set(item.roomId, item.roomName.trim());
      }
    });
  });

  const roomIds = Array.from(roomMap.keys()).sort((a, b) => a - b);
  const roomTypes = roomIds.map((id) => roomMap.get(id));

  const occMatrix = Array(7)
    .fill(0)
    .map(() => Array(roomIds.length).fill(0));

  data.forEach((day) => {
    const row = Array(roomIds.length).fill(0);
    day.items.forEach((item) => {
      const idx = roomIds.indexOf(item.roomId);
      if (idx !== -1) {
        row[idx] = item.OCC;
      }
    });
    occMatrix[dayMap[day.dayOfWeek]] = row;
  });

  return { weekdays, roomTypes, occMatrix };
}

function mapPerformanceProperty(api) {
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
      propertyId: r.propertyId ?? "",
      propertyName: r.propertyName ?? "",
      OCC: r.OCC ?? 0,
      ADR: r.ADR ?? 0,
      RevPAR: r.RevPAR ?? 0,
      leadTime: r.leadTime ?? 0,
      LOS: r.LOS ?? 0,
      revenue: r.revenue ?? 0,
      percentGrowthRevenue: r.percentGrowthRevenue ?? 0,
    };
  });

  return { rows: mapped, meta: { total, size, page } };
}

export const useDashboardPerformance = defineStore("useDashboardPerformance", {
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
    time: {
      days: [],
      OCCs: [],
      ADRs: [],
      RevPARs: [],
    },
    leadTime: [],
    LOS: [],
    LOSTrend: {
      days: [],
      avgLOSs: [],
    },
    heatmap: {
      weekdays: [],
      roomTypes: [],
      occMatrix: [],
    },
    propertyRows: [],
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

        const res = await getPerformanceStats(params);
        this.stats = res?.data?.data ?? res?.data ?? res ?? {};
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchTime() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          property: this.propertyId || "",
        };

        const res = await getPerformanceTime(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapPerformanceTime(payload);
        this.time.days = mapped.days;
        this.time.OCCs = mapped.OCCs;
        this.time.ADRs = mapped.ADRs;
        this.time.RevPARs = mapped.RevPARs;
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
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

        const res = await getPerformanceLeadTime(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapPerformanceLeadTime(payload);
        this.leadTime = mapped.data;
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchLOS() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          property: this.propertyId || "",
        };

        const res = await getPerformanceLOS(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapPerformanceLOS(payload);
        this.LOS = mapped.data;
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchLOSTrend() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          property: this.propertyId || "",
        };

        const res = await getPerformanceLOSTrend(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapPerformanceLOSTrend(payload);
        this.LOSTrend.days = mapped.days;
        this.LOSTrend.avgLOSs = mapped.avgLOSs;
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchHeatmap() {
      try {
        this.loading = true;
        this.error = null;
        this.syncProperty();

        const params = {
          timeRange: this.filters.timeRange || "",
          property: this.propertyId || "",
        };

        const res = await getPerformanceHeatmap(params);
        const payload = res?.data?.data ?? res?.data ?? res ?? [];
        const mapped = mapPerformanceHeatmap(payload);
        this.heatmap.weekdays = mapped.weekdays;
        this.heatmap.roomTypes = mapped.roomTypes;
        this.heatmap.occMatrix = mapped.occMatrix;
      } catch (e) {
        this.error = e?.message || "Fetch executive stats failed";
      } finally {
        this.loading = false;
      }
    },

    async fetchProperty({ page, size }) {
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
        const res = await getPerformanceProperty(q);
        const payload = res?.data ?? res ?? {};
        const mapped = mapPerformanceProperty(payload);

        this.propertyRows = mapped.rows;
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
