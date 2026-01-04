import { defineStore } from "pinia";
import { reactive, ref } from "vue";
import {
  getControlPanel,
  getControlPanelTotals,
  getQuantityDirtyRooms,
  getStatics,
} from "../utils/api";
const TAB_MAP = {
  departure: { store: "departure", api: "departure" },
  arrival: { store: "arrival", api: "arrival" },
  "in-house-guest": { store: "inhouse", api: "in-house-guest" },
  "new-booking": { store: "new", api: "new-booking" },
  "payment-required": { store: "payment", api: "payment-required" },
  housekeeping: { store: "housekeeping", api: "housekeeping" },
  inhouse: { store: "inhouse", api: "in-house-guest" },
  new: { store: "new", api: "new-booking" },
  payment: { store: "payment", api: "payment-required" },
};

const toStoreKey = (t) => TAB_MAP[t]?.store ?? t;
const toApiSlug = (t) => TAB_MAP[t]?.api ?? t;

function cryptoRandom() {
  return Math.random().toString(36).slice(2) + Date.now().toString(36);
}

const TAB_ADAPTERS = {
  departure: (items) => items,
  arrival: (items) => items,
  inhouse: (items) => items,
  new: (items) => items,
  payment: (items) => items,
  housekeeping: (items) => {
    const list = Array.isArray(items) ? items : [];
    return list.map((r) => {
      const id = r.id ?? r.code ?? cryptoRandom();
      const name = r.roomName ?? "";
      const roomType = r.roomUnitName ?? "";
      const isAvailable = !!(r.isAvailable ?? 0);
      const status = r.status ?? r.hkStatus ?? "";
      const notes = r.note ?? r.notes ?? null;
      return { id, name, roomType, status, isAvailable, notes };
    });
  },
};

export const useStoreControlPanel = defineStore("controlPanel", () => {
  const tab = ref("departure");

  const filters = reactive({
    timeRange: "",
    property: null,
    bookingStatus: null,
    otaName: null,
    roomStatus: null,
    q: "",
  });

  const sortField = ref("updated_at");
  const sortFieldRoom = ref("updated_at");
  const sortOrder = ref("DESC");
  const page = ref(1);
  const size = ref(10);

  const dataByTab = reactive({
    departure: [],
    arrival: [],
    inhouse: [],
    new: [],
    payment: [],
    housekeeping: [],
  });

  const totalByTab = reactive({
    departure: 0,
    arrival: 0,
    inhouse: 0,
    new: 0,
    payment: 0,
    housekeeping: 0,
  });

  const loadingByTab = reactive({
    departure: false,
    arrival: false,
    inhouse: false,
    new: false,
    payment: false,
    housekeeping: false,
  });

  const errorByTab = reactive({
    departure: null,
    arrival: null,
    inhouse: null,
    new: null,
    payment: null,
    housekeeping: null,
  });

  const stats = reactive({
    dirtyRooms: { dirty: 0, total: 0 },
    kpis: {
      departure: { current: 0, previous: 0, diff: 0 },
      arrival: { current: 0, previous: 0, diff: 0 },
      inhouseGuest: { current: 0, previous: 0, diff: 0 },
      newBooking: { current: 0, previous: 0, diff: 0 },
      paymentRequired: { current: 0, previous: 0, diff: 0 },
    },

    payments: {
      overdue: { count: 0, sum: 0 },
      required: { count: 0, sum: 0 },
    },
  });

  async function fetchTab(targetTab = tab.value, overrides = {}) {
    const storeKey = toStoreKey(String(targetTab));
    const apiSlug = toApiSlug(String(targetTab));
    if (!(storeKey in dataByTab)) return;

    if (loadingByTab[storeKey]) return;
    loadingByTab[storeKey] = true;
    errorByTab[storeKey] = null;

    try {
      const isHousekeeping = apiSlug === "housekeeping";
      console.log(apiSlug);
      const sortFieldParam = isHousekeeping
        ? "sortFieldRoom" in overrides
          ? overrides.sortFieldRoom
          : "sortField" in overrides
          ? overrides.sortField
          : sortFieldRoom.value
        : "sortField" in overrides
        ? overrides.sortField
        : sortField.value;
      console.log(sortFieldParam);
      const res = await getControlPanel({
        tab: apiSlug,
        timeRange:
          "timeRange" in overrides ? overrides.timeRange : filters.timeRange,
        property:
          "property" in overrides ? overrides.property : filters.property,
        bookingStatus:
          "bookingStatus" in overrides
            ? overrides.bookingStatus
            : filters.bookingStatus,
        otaName: "otaName" in overrides ? overrides.otaName : filters.otaName,
        q: "q" in overrides ? overrides.q : filters.q,
        sortField: sortFieldParam,
        sortOrder:
          "sortOrder" in overrides ? overrides.sortOrder : sortOrder.value,
        page: "page" in overrides ? overrides.page : page.value,
        size: "size" in overrides ? overrides.size : size.value,
        roomStatus:
          "roomStatus" in overrides ? overrides.roomStatus : filters.roomStatus,
      });

      const payload = res?.data || {};
      const rawList = Array.isArray(payload.result) ? payload.result : [];

      const adapt = TAB_ADAPTERS[storeKey] || ((x) => x);
      dataByTab[storeKey] = adapt(rawList);

      totalByTab[storeKey] = Number(payload.total ?? rawList.length);
      if (payload.page != null) page.value = Number(payload.page);
      if (payload.size != null) size.value = Number(payload.size);
    } catch (e) {
      console.error(`[controlPanel] fetchTab ${storeKey} error:`, e);
      errorByTab[storeKey] = e;
    } finally {
      loadingByTab[storeKey] = false;
    }
  }

  async function fetchTotals(overrides = {}) {
    try {
      const res = await getControlPanelTotals({
        timeRange:
          "timeRange" in overrides ? overrides.timeRange : filters.timeRange,
        property:
          "property" in overrides ? overrides.property : filters.property,
        bookingStatus:
          "bookingStatus" in overrides
            ? overrides.bookingStatus
            : filters.bookingStatus,
        otaName: "otaName" in overrides ? overrides.otaName : filters.otaName,
        sortField:
          "sortField" in overrides ? overrides.sortField : sortField.value,
        sortOrder:
          "sortOrder" in overrides ? overrides.sortOrder : sortOrder.value,
        roomStatus:
          "roomStatus" in overrides ? overrides.roomStatus : filters.roomStatus,
      });

      const d = res?.data || {};
      totalByTab.departure = Number(d.departure) || 0;
      totalByTab.arrival = Number(d.arrival) || 0;
      totalByTab.inhouse = Number(d.inHouse) || 0;
      totalByTab.new = Number(d.newBooking) || 0;
      totalByTab.payment = Number(d.paymentRequired) || 0;
      totalByTab.housekeeping = Number(d.housekeeping) || 0;
    } catch (e) {
      console.error("[controlPanel] fetchTotals error:", e);
    }
  }

  async function fetchDirtyRooms(overrides = {}) {
    try {
      const res = await getQuantityDirtyRooms({
        property:
          "property" in overrides ? overrides.property : filters.property,
      });
      const root = res?.data ?? res;
      const d = root?.data ?? root ?? {};
      stats.dirtyRooms = {
        dirty: Number(d.dirtyRooms ?? 0),
        total: Number(d.totalRooms ?? 0),
      };
    } catch (e) {
      console.error("[controlPanel] fetchDirtyRooms error:", e);
      stats.dirtyRooms = { dirty: 0, total: 0 };
    }
  }
  async function fetchStatics(overrides = {}) {
    try {
      const res = await getStatics({
        timeRange:
          "timeRange" in overrides ? overrides.timeRange : filters.timeRange,
        property:
          "property" in overrides ? overrides.property : filters.property,
      });

      const root = res?.data ?? res;
      const d = root?.data ?? root ?? {};

      const num = (v, def = 0) => {
        const n = Number(v);
        return Number.isFinite(n) ? n : def;
      };

      if (!stats.kpis) stats.kpis = {};
      if (!stats.payments)
        stats.payments = {
          overdue: { count: 0, sum: 0 },
          required: { count: 0, sum: 0 },
        };

      stats.kpis.departure = {
        current: num(d.departureCurrent),
        previous: num(d.departurePrevious),
        diff: num(d.departureDiff),
      };
      stats.kpis.arrival = {
        current: num(d.arrivalCurrent),
        previous: num(d.arrivalPrevious),
        diff: num(d.arrivalDiff),
      };
      stats.kpis.inhouseGuest = {
        current: num(d.inhouseGuestCurrent),
        previous: num(d.inhouseGuestPrevious),
        diff: num(d.inhouseGuestDiff),
      };
      stats.kpis.newBooking = {
        current: num(d.newBookingCurrent),
        previous: num(d.newBookingPrevious),
        diff: num(d.newBookingDiff),
      };
      stats.kpis.paymentRequired = {
        current: num(d.paymentRequiredCount),
        previous: 0,
        diff: 0,
      };

      stats.payments.overdue = {
        count: num(d.overdueCount),
        sum: num(d.overdueSum),
      };
      stats.payments.required = {
        count: num(d.paymentRequiredCount),
        sum: num(d.paymentRequiredSum),
      };
    } catch (e) {
      console.error("[controlPanel] fetchStatics error:", e);
      stats.kpis = {
        departure: { current: 0, previous: 0, diff: 0 },
        arrival: { current: 0, previous: 0, diff: 0 },
        inhouseGuest: { current: 0, previous: 0, diff: 0 },
        newBooking: { current: 0, previous: 0, diff: 0 },
        paymentRequired: { current: 0, previous: 0, diff: 0 },
      };
      stats.payments = {
        overdue: { count: 0, sum: 0 },
        required: { count: 0, sum: 0 },
      };
    }
  }

  function setTab(nextTab) {
    tab.value = toStoreKey(nextTab);
  }

  function setFilters(next = {}) {
    for (const k of [
      "timeRange",
      "property",
      "bookingStatus",
      "otaName",
      "roomStatus",
      "q",
    ]) {
      if (Object.prototype.hasOwnProperty.call(next, k)) {
        filters[k] = next[k];
      }
    }
  }

  function setTimeRange(a, b) {
    if (a && b) {
      filters.timeRange = `${a} to ${b}`;
      return;
    }
    if (a && typeof a === "object") {
      const { start, end } = a;
      filters.timeRange = start && end ? `${start} to ${end}` : "";
      return;
    }
    filters.timeRange = typeof a === "string" ? a.trim() : "";
  }

  function resetFilters() {
    filters.timeRange = "";
    filters.property = null;
    filters.bookingStatus = null;
    filters.otaName = null;
    filters.q = "";
    filters.roomStatus = null;
    filters.sortFieldRoom = "updated_at";
    filters.sortField = "updated_at";
    filters.sortOrder = "DESC";
    filters.page = 1;
    filters.size = 10;
  }

  function setSort(field, order = "ASC", isHousekeeping = false) {
    if (isHousekeeping) {
      sortFieldRoom.value = field || "updated_at";
      sortOrder.value = order === "DESC" ? "DESC" : "ASC";
      return;
    }
    sortField.value = field || "updated_at";
    sortOrder.value = order === "DESC" ? "DESC" : "ASC";
  }
  function setPage(p) {
    page.value = Math.max(1, Number(p) || 1);
  }
  function setSize(s) {
    size.value = Math.max(1, Number(s) || 10);
  }
  function nextPage() {
    setPage(page.value + 1);
  }
  function prevPage() {
    setPage(Math.max(1, page.value - 1));
  }

  function clearTabData(t = tab.value) {
    const storeKey = toStoreKey(t);
    if (storeKey in dataByTab) {
      dataByTab[storeKey] = [];
      totalByTab[storeKey] = 0;
      errorByTab[storeKey] = null;
    }
  }

  async function fetchStats() {}

  return {
    // state
    tab,
    filters,
    sortField,
    sortFieldRoom,
    sortOrder,
    page,
    size,
    dataByTab,
    totalByTab,
    loadingByTab,
    errorByTab,
    stats,

    // actions
    fetchTab,
    fetchTotals,
    fetchStats,
    fetchStatics,
    fetchDirtyRooms,
    setTab,
    setFilters,
    setTimeRange,
    resetFilters,
    setSort,
    setPage,
    setSize,
    nextPage,
    prevPage,
    clearTabData,

    // helpers
    toStoreKey,
    toApiSlug,
  };
});
