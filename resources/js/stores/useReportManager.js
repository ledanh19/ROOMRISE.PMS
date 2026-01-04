import {
  getBookingManagerReport,
  getBookingManagerTotalReport,
  getBookingSourceManagerReport,
  getOccupancyReport,
} from "@/utils/apiReport/managerReport";
import { apiGetHeaderRevenueRoomReport } from "@/utils/apiReport/revenueReport";
import { defineStore } from "pinia";
import { usePropertyStore } from "./usePropertyStore";

function defaultFilters() {
  return {
    dateType: "created_at",
    timeRange: "",
    bookingStatus: null,
    paymentStatus: null,
    otaName: null,
    property: "",
    month: "",
    year: "",
    country: "",
    type: "",
    excludeSource: "",
  };
}

function mapManagerRow(r) {
  return {
    id: r && r.id,
    otaReservationCode: (r && r.otaReservationCode) ?? "",
    fullName: (r && r.fullName) ?? "",
    createdAt: r && r.createdAt ? String(r.createdAt).slice(0, 10) : "",
    checkInDate: r && r.checkInDate ? String(r.checkInDate).slice(0, 10) : "",
    checkOutDate:
      r && r.checkOutDate ? String(r.checkOutDate).slice(0, 10) : "",
    customerPaymentAmount: Number((r && r.customerPaymentAmount) ?? 0),
    commissionFee: Number((r && r.commissionFee) ?? 0),
    otaFee: Number((r && r.otaFee) ?? 0),
    totalAmount: Number((r && r.totalAmount) ?? 0),
    bookingStatus: (r && r.bookingStatus) ?? "",
    paymentStatus: (r && r.paymentStatus) ?? "",
    currency: (r && r.currency) ?? null,
  };
}

function mapManagerListPayload(payload) {
  const p = (payload && payload.data) || payload || {};
  const raw = Array.isArray(p.result) ? p.result : Array.isArray(p) ? p : [];
  const items = raw.map(mapManagerRow);
  const total = Number.isFinite(Number(p.total ?? p.count))
    ? Number(p.total ?? p.count)
    : null;
  const page = Number.isFinite(Number(p.page)) ? Number(p.page) : undefined;
  const size = Number.isFinite(Number(p.size)) ? Number(p.size) : undefined;
  return { items, total, page, size };
}

function mapManagerTotalsPayload(payload) {
  const x =
    (payload && payload.data) ||
    (payload && payload.items && payload.items[0]) ||
    payload ||
    {};
  return {
    customerPaymentAmount: Number(x.customerPaymentAmount ?? 0),
    commissionFee: Number(x.commissionFee ?? 0),
    otaFee: Number(x.otaFee ?? 0),
    totalAmount: Number(x.totalAmount ?? 0),
  };
}

function normalizeOtaKey(name) {
  const n = String(name || "").trim();
  if (n === "BookingCom") return "bookingCom";
  if (n === "Walk-in") return "walkIn";
  if (n === "offline") return "Từ đối tác";
  if (n === "Agoda") return "agoda";
  if (n === "Expedia") return "expedia";
  if (n === "Airbnb") return "airbnb";
  if (n === "CTrip") return "ctrip";
  if (n === "Widget") return "widget";
  return null;
}

function mapBookingSourceListPayloadPivot(payload) {
  const p = (payload && payload.data) || payload || {};
  const groups = Array.isArray(p.result) ? p.result : [];
  const rows = [];

  for (const g of groups) {
    const month = String((g && g.Month) || "");
    const totalRevenueFromApi = Number((g && g.Total) || 0);

    const row = {
      month,
      totalRevenue: 0,
      widget: 0,
      bookingCom: 0,
      walkIn: 0,
      offline: 0,
      agoda: 0,
      expedia: 0,
      airbnb: 0,
      ctrip: 0,
      cmRevenue: 0,
    };

    const arr = g && Array.isArray(g.items) ? g.items : [];
    for (const it of arr) {
      const k = normalizeOtaKey(it && it.otaName);
      const v = Number((it && it.total) || 0);
      if (k && Object.prototype.hasOwnProperty.call(row, k)) {
        row[k] += v;
      }
    }

    const sumOta =
      row.widget +
      row.bookingCom +
      row.walkIn +
      row.offline +
      row.agoda +
      row.expedia +
      row.airbnb +
      row.ctrip;

    row.totalRevenue = Number.isFinite(totalRevenueFromApi)
      ? totalRevenueFromApi
      : sumOta;
    row.cmRevenue =
      row.bookingCom + row.agoda + row.expedia + row.airbnb + row.ctrip;

    rows.push(row);
  }

  const total = Number.isFinite(Number(p.total))
    ? Number(p.total)
    : rows.length;
  const page = Number.isFinite(Number(p.page)) ? Number(p.page) : undefined;
  const size = Number.isFinite(Number(p.size)) ? Number(p.size) : undefined;

  return { items: rows, total, page, size };
}

function mapOccupancyPayloadPivot(payload) {
  const groups = Array.isArray(payload?.result) ? payload.result : [];
  const rows = [];
  groups.forEach((g, gi) => {
    rows.push({
      _section: true,
      date: g?.roomName,
      occupiedRooms: g?.occupiedRooms || 0,
      adults: g?.adults || 0,
      children: g?.children || 0,
      checkInRooms: g?.checkinCount || 0,
      checkOutRooms: g?.checkoutCount || 0,
      availableRooms: g?.availableRooms || 0,
      totalRooms: g?.totalRooms || 0,
      occupancyPercentage: g?.occupancyPercent || 0,
      _id: `sec:${g.roomId}`,
    });
    const items = Array.isArray(g?.items) ? g.items : [];
    items.forEach((it, ii) => {
      rows.push({
        _section: false,
        date: it?.date,
        occupiedRooms: it?.occupiedRooms || 0,
        adults: it?.adults || 0,
        children: it?.children || 0,
        checkInRooms: it?.checkinCount || 0,
        checkOutRooms: it?.checkoutCount || 0,
        availableRooms: it?.availableRooms || 0,
        totalRooms: it?.totalRooms || 0,
        occupancyPercentage: it?.occupancyPercent || 0,
        _id: `row:${ii}}`,
      });
    });
  });
  return {
    result: rows,
    total: Number(payload?.total) || rows.length,
    size: Number(payload?.size) || rows.length,
    page: Number(payload?.page) || 1,
  };
}

function applyResponse(tabState, data) {
  const items = Array.isArray(data && data.items) ? data.items : [];
  const pageNum = Number.isFinite(Number(data && data.page))
    ? Number(data.page)
    : 1;

  if (pageNum <= 1) {
    tabState.rows = items;
  } else {
    tabState.rows = (tabState.rows || []).concat(items);
  }

  if (data && Number.isFinite(Number(data.total))) {
    tabState.total = Number(data.total);
  }
  tabState.page = pageNum;
}

export const useReportManager = defineStore("useReportManager", {
  state: () => ({
    currentTab: "booking-source-report",
    tabs: {},
  }),

  getters: {
    active(state) {
      return state.tabs[state.currentTab];
    },
    hasMore(state) {
      const t = state.tabs[state.currentTab];
      if (!t) return true;

      if (state.currentTab === "booking-source-report") {
        const page = Number(t.page || 1);
        const size = Number(t.size || 10);
        const totalGroups = Number(t._totalGroups || t.total || 0);
        if (!Number.isFinite(totalGroups) || totalGroups <= 0) return true;
        return page * size < totalGroups;
      }

      if (Number.isFinite(t.total)) return (t.rows?.length || 0) < t.total;
      return true;
    },
  },

  actions: {
    ensureTab(tab) {
      if (!this.tabs[tab]) {
        this.tabs[tab] = {
          filters: defaultFilters(),
          page: 1,
          size: 10,
          total: null,
          rows: [],
          loading: false,
          error: null,
          initialized: false,
          paymentTotals: [],
          paymentTotalsFooter: null,
          _totalGroups: null,
        };
      }
    },

    resetForSearch(tab = null) {
      const key = tab || this.currentTab;
      this.ensureTab(key);
      const t = this.tabs[key];

      t.page = 1;
      t.size = 10;
      t.rows = [];
      t.total = null;
      t.error = null;

      t.paymentTotals = [];
      t.paymentTotalsFooter = null;
      t._totalGroups = null;
    },

    replaceFilters(payload = {}, tab = null) {
      const key = tab || this.currentTab;
      this.ensureTab(key);
      const t = this.tabs[key];

      const next = { ...(t.filters || {}), ...(payload || {}) };

      t.filters = next;
      t.page = 1;
    },

    updateFilters(payload = {}, tab = null) {
      const key = tab || this.currentTab;
      this.ensureTab(key);
      const t = this.tabs[key];

      const next = { ...(t.filters || {}), ...(payload || {}) };

      t.filters = next;
    },

    setPage(page = 1, tab = null) {
      const key = tab || this.currentTab;
      this.ensureTab(key);
      this.tabs[key].page = Math.max(1, Number(page) || 1);
    },

    setSize(size = 10, tab = null) {
      const key = tab || this.currentTab;
      this.ensureTab(key);
      this.tabs[key].size = Math.max(1, Number(size) || 1);
    },

    async switchTab(tab, { reset = true, fetchNow = true } = {}) {
      this.ensureTab(tab);
      this.currentTab = tab;
      const t = this.tabs[tab];

      if (reset || !t.initialized) {
        t.filters = defaultFilters();
        t.page = 1;
        t.size = 10;
        t.total = null;
        t.rows = [];
        t.error = null;
        t.paymentTotals = [];
        t.paymentTotalsFooter = null;
        t._totalGroups = null;
      }

      if (fetchNow) await this.fetch(tab);
      t.initialized = true;
    },

    async fetch(tab = null) {
      const propertyStore = usePropertyStore();
      const key = tab || this.currentTab;
      this.ensureTab(key);
      const t = this.tabs[key];
      if (t.loading) return;

      t.loading = true;
      t.error = null;
      try {
        if (key === "booking-source-report") {
          const listQuery = {
            month: Number(t.filters && t.filters.month) || "",
            year: Number(t.filters && t.filters.year) || "",
            otaName: (t.filters && t.filters.type) || "",
            property: propertyStore.selectedProperty || "",
            page: t.page || 1,
            size: t.size || 10,
          };

          const res = await getBookingSourceManagerReport(listQuery);
          const payloadList =
            (res && res.data && res.data.data) ||
            (res && res.data) ||
            res ||
            {};
          const mapped = mapBookingSourceListPayloadPivot(payloadList);

          t._totalGroups = Number.isFinite(Number(mapped.total))
            ? Number(mapped.total)
            : null;

          applyResponse(t, {
            items: mapped.items,
            total: mapped.total,
            page: listQuery.page,
          });

          t.size = listQuery.size || t.size || 10;
          return;
        }

        if (key === "booking-report") {
          const listQuery = {
            dateType: (t.filters && t.filters.dateType) || "",
            timeRange: (t.filters && t.filters.timeRange) || "",
            bookingStatus: (t.filters && t.filters.bookingStatus) || "",
            paymentStatus: (t.filters && t.filters.paymentStatus) || "",
            otaName: (t.filters && t.filters.otaName) || "",
            property: propertyStore.selectedProperty || "",
            page: t.page || 1,
            size: t.size || 10,
          };
          console.log(t.filters);
          const totalQuery = {
            dateType: listQuery.dateType,
            timeRange: listQuery.timeRange,
            bookingStatus: listQuery.bookingStatus,
            paymentStatus: listQuery.paymentStatus,
            otaName: listQuery.otaName,
            property: listQuery.property,
          };

          const wantTotals = (t.page || 1) === 1;
          const promises = [getBookingManagerReport(listQuery)];
          if (wantTotals)
            promises.push(getBookingManagerTotalReport(totalQuery));

          const results = await Promise.allSettled(promises);

          const resList = results[0];
          if (resList.status === "fulfilled") {
            const payloadList =
              (resList.value &&
                resList.value.data &&
                resList.value.data.data) ||
              (resList.value && resList.value.data) ||
              resList.value ||
              {};
            const mapped = mapManagerListPayload(payloadList);

            applyResponse(t, {
              items: mapped.items,
              total: mapped.total,
              page: listQuery.page,
            });

            t.size = listQuery.size || t.size || 10;
          } else {
            t.error =
              (resList.reason && resList.reason.message) ||
              t.error ||
              "Lỗi tải danh sách";
          }

          if (wantTotals) {
            const resTotal = results[1];
            if (resTotal && resTotal.status === "fulfilled") {
              const payloadTotal =
                (resTotal.value &&
                  resTotal.value.data &&
                  resTotal.value.data.data) ||
                (resTotal.value && resTotal.value.data) ||
                resTotal.value ||
                {};
              t.paymentTotalsFooter = mapManagerTotalsPayload(payloadTotal);
              t.paymentTotals = [t.paymentTotalsFooter];
            } else {
              t.paymentTotals = [];
              t.paymentTotalsFooter = null;
            }
          }

          return;
        }
        if (key === "occupancy-report") {
          const roomIdsArray = Array.isArray(t.roomIds)
            ? t.roomIds.map((x) => Number(x)).filter((n) => Number.isFinite(n))
            : [];
          const listQuery = {
            timeRange: (t.filters && t.filters.timeRange) || "",
            roomIds: JSON.stringify(roomIdsArray),
            property: propertyStore.selectedProperty || "",
            page: t.page || 1,
            size: t.size || 10,
          };

          const res = await getOccupancyReport(listQuery);
          const payloadList =
            (res && res.data && res.data.data) ||
            (res && res.data) ||
            res ||
            {};
          const mapped = mapOccupancyPayloadPivot(payloadList);
          t.rows = mapped.result;
          t.page = mapped.page;
          t.size = mapped.size;
          t.total = mapped.total;
          return;
        }
        return;
      } catch (err) {
        console.error("[useReportManager.fetch] error:", err);
        t.error = (err && err.message) || "Lỗi tải dữ liệu";
      } finally {
        t.loading = false;
      }
    },

    async loadMore(tab = null) {
      const key = tab || this.currentTab;
      this.ensureTab(key);
      const t = this.tabs[key];

      if (t.loading) return;

      if (key === "booking-source-report") {
        const totalGroups = Number(t._totalGroups || t.total || 0);
        const page = Number(t.page || 1);
        const size = Number(t.size || 10);
        if (Number.isFinite(totalGroups) && page * size >= totalGroups) return;
      } else {
        if (Number.isFinite(t.total) && (t.rows?.length || 0) >= t.total)
          return;
      }

      t.page = Math.max(1, (t.page || 1) + 1);
      t.size = t.size || 10;
      await this.fetch(key);
    },

    async buildSelectRoom() {
      const t = this.tabs[this.currentTab];
      const payload = { property: t?.filters?.property ?? "" };
      try {
        const res = await apiGetHeaderRevenueRoomReport(payload);
        const list = res?.data?.result ?? [];
        const select = list.map((item) => ({
          value: item?.id,
          label: item?.name,
        }));
        return select;
      } catch {
        return null;
      }
    },
  },
});
