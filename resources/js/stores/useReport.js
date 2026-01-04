import {
  getCustomerInformationReport,
  getDailyPaymentReport,
  getDailyPaymentTypeReport,
  getDailyPaymentTypeTotalReport,
  getInternalGuestListReport,
  getListCheckInReport,
  getListCheckOutReport,
} from "@/utils/apiReport/dailyReport";
import { formatToYMDHMS } from "@/utils/helper";
import { defineStore } from "pinia";
import { usePropertyStore } from "./usePropertyStore";

function sumVnd(items) {
  const list = Array.isArray(items) ? items : [];
  return list
    .filter(
      (it) =>
        it?.currency == null || String(it.currency).toUpperCase() === "VND"
    )
    .reduce((acc, it) => acc + Number(it?.total || 0), 0);
}

function getSelectedPropId(store) {
  const v = store?.selectedProperty;
  if (v && typeof v === "object") return v.id ?? v.value ?? "";
  return v ?? "";
}

function defaultFilters() {
  return {
    dateFrom: "",
    dateTo: "",
    timeFrom: "",
    timeTo: "",
    dateType: "created_at",
    timeRange: "",
    type: "",
    property: "",
  };
}

function isDailyPaymentTab(key) {
  return (
    key === "daily-payment-report" ||
    key === "dailyManagerReport" ||
    key === "daily-maneger-report"
  );
}

function mapBookingRow(r) {
  return {
    id: r?.id ?? "",
    code: r?.otaReservationCode ?? "",
    guestName: r?.fullName ?? r?.customerName ?? "",
    guestNumber: r?.customerQuantity ?? r?.guestQuantity ?? null,
    roomType: r?.roomName ?? "",
    roomName: r?.roomUnitName ?? "",
    checkIn: r?.checkInDateTime ?? "",
    checkOut: r?.checkOutDateTime ?? "",
    source: r?.otaName ?? "",
    status: r?.bookingRoomStatus ?? r?.roomStatus ?? "",
  };
}

function mapGuestRow(r) {
  return {
    id: r?.id ?? "",
    date: formatToYMDHMS(r?.createdAt) ?? "",
    name: r?.fullName ?? r?.name ?? "",
    idNumber: r?.idNumber ?? "",
    dob: formatToYMDHMS(r?.dob) ?? "",
    email: r?.email ?? "",
    phone: r?.phone ?? "",
    city: r?.city ?? "",
    country: r?.country ?? "",
    nationality: r?.nationality ?? "",
    address: r?.address ?? "",
    type: r?.type ?? "",
  };
}

function mapDailyPaymentTable1(payload) {
  const groups = Array.isArray(payload?.result) ? payload.result : [];
  const rows = [];
  groups.forEach((g, gi) => {
    const paymentType = g?.paymentType ?? "";
    const methodName = String(paymentType)
      .replace(/\s*\([^)]+\)\s*$/, "")
      .trim();
    rows.push({
      _section: true,
      addWhen: methodName,
      amountText: g?.total || 0,
      userName: "",
      status: "",
      note: "",
      bookingNo: "",
      guestName: "",
      checkInDate: "",
      checkOutDate: "",
      roomName: "",
      _id: `sec:${gi}`,
    });
    const items = Array.isArray(g?.items) ? g.items : [];
    items.forEach((it, ii) => {
      const incomeList = Array.isArray(it?.incomeList) ? it.incomeList : [];
      incomeList.forEach((inc, kk) => {
        rows.push({
          _section: false,
          addWhen: inc?.createdAt ? inc.createdAt.replace(" ", ", ") : "",
          collectBy: inc?.collectBy,
          userName: inc?.userName ?? "System",
          amountText: inc?.price || 0,
          bookingNo: it?.otaReservationCode ?? "",
          guestName: it?.customerName ?? "",
          checkInDate: it?.checkInDate ?? "",
          checkOutDate: it?.checkOutDate ?? "",
          roomName: it?.roomUnitName ?? "",
          _id: `row:${gi}:${ii}:${kk}`,
        });
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

function mapDailyPaymentTable2List(payload) {
  const data = payload || {};
  const result = Array.isArray(data.result) ? data.result : [];
  const rows = result.map((r, i) => ({
    method: r?.paymentType
      ? String(r.paymentType)
          .replace(/\s*\([^)]+\)\s*$/, "")
          .trim()
      : "",
    vnd: sumVnd(r?.items),
    _id: `pt:${i}`,
  }));
  return {
    rows,
    page: Number(data.page ?? 1),
    size: Number(data.size ?? rows.length) || 10,
    total: Number(data.total ?? rows.length),
  };
}

function mapDailyPaymentTable2GrandTotal(payloadArray) {
  const list = Array.isArray(payloadArray) ? payloadArray : [];
  const vndTotal = list
    .filter(
      (x) => x?.currency == null || String(x.currency).toUpperCase() === "VND"
    )
    .reduce((acc, x) => acc + Number(x?.total || 0), 0);
  return { method: "Tổng cộng", vnd: vndTotal, _id: "sum:grand" };
}

export const useReport = defineStore("useReport", {
  state: () => ({
    currentTab: "check-in-list",
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
          paymentTotals: [],
          paymentTotalsFooter: null,
          rowsTable2: [],
          pageTable2: 1,
          sizeTable2: 10,
          totalTable2: 0,
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
        t.paymentTotals = [];
        t.paymentTotalsFooter = null;
        t.rowsTable2 = [];
        t.pageTable2 = 1;
        t.sizeTable2 = 10;
        t.totalTable2 = 0;
      }
      if (fetchNow) await this.fetch(tab);
      t.initialized = true;
    },
    replaceFilters(payload = {}, tab = null) {
      const key = tab || this.currentTab;
      const t = this.tabs[key];
      t.filters = { ...(t.filters || {}), ...(payload || {}) };
      t.page = 1;
      t.pageTable2 = 1;
    },
    setPage(page = 1, tab = null) {
      const key = tab || this.currentTab;
      const t = this.tabs[key];
      const p = Number.isFinite(+page) ? Math.max(1, +page) : 1;
      t.page = p;
    },
    setSize(size = 10, tab = null) {
      const key = tab || this.currentTab;
      const t = this.tabs[key];
      const s = Number.isFinite(+size) ? Math.max(1, +size) : 10;
      t.size = s;
      t.page = 1;
    },
    setPageTable2(page = 1, tab = null) {
      const key = tab || this.currentTab;
      const t = this.tabs[key];
      const p = Number.isFinite(+page) ? Math.max(1, +page) : 1;
      t.pageTable2 = p;
    },
    setSizeTable2(size = 10, tab = null) {
      const key = tab || this.currentTab;
      const t = this.tabs[key];
      const s = Number.isFinite(+size) ? Math.max(1, +size) : 10;
      t.sizeTable2 = s;
      t.pageTable2 = 1;
    },
    applyResponse(t, data, rowMapper) {
      const result = Array.isArray(data?.result) ? data.result : [];
      const mapFn = typeof rowMapper === "function" ? rowMapper : (x) => x;
      t.rows = result.map(mapFn);
      const rawTotal =
        data?.total ??
        data?.totalCount ??
        data?.count ??
        data?.pagination?.total ??
        data?.meta?.total;
      let total;
      if (Number.isFinite(+rawTotal)) {
        total = +rawTotal;
      } else {
        const sizeNow = t.size || 10;
        total =
          (t.page - 1) * sizeNow +
          result.length +
          (result.length === sizeNow ? 1 : 0);
      }
      const rawSize = data?.size ?? data?.pageSize ?? data?.limit;
      const size = Number.isFinite(+rawSize)
        ? Math.max(1, +rawSize)
        : t.size || 10;
      const rawPage = data?.page ?? data?.currentPage ?? data?.pagination?.page;
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
      const propertyStore = usePropertyStore();
      const selectedProp = getSelectedPropId(propertyStore);
      const baseRange = {
        dateFrom: t.filters?.dateFrom || "",
        dateTo: t.filters?.dateTo || "",
        timeFrom: t.filters?.timeFrom || "",
        timeTo: t.filters?.timeTo || "",
        property: selectedProp || "",
      };
      try {
        if (key === "check-in-list") {
          const res = await getListCheckInReport({
            ...baseRange,
            page: t.page,
            size: t.size,
          });
          const data = res?.data?.data ?? res?.data ?? {};
          this.applyResponse(t, data, mapBookingRow);
        } else if (key === "check-out-list-report") {
          const res = await getListCheckOutReport({
            ...baseRange,
            page: t.page,
            size: t.size,
          });
          const data = res?.data?.data ?? res?.data ?? {};
          this.applyResponse(t, data, mapBookingRow);
        } else if (key === "guest-information-report") {
          const df = t.filters?.dateFrom || "";
          const dt = t.filters?.dateTo || "";
          const fallbackRange =
            df && dt
              ? `${df} to ${dt}`
              : df && !dt
              ? `${df} to ${df}`
              : !df && dt
              ? `${dt} to ${dt}`
              : "";
          const query = {
            dateType: t.filters?.dateType || "created_at",
            timeRange: t.filters?.timeRange || fallbackRange || "",
            type: t.filters?.type || "",
            property: selectedProp || "",
            page: t.page,
            size: t.size,
          };
          const res = await getCustomerInformationReport(query);
          const data = res?.data?.data ?? res?.data ?? {};
          this.applyResponse(t, data, mapGuestRow);
        } else if (key === "internal-guest-list-report") {
          const res = await getInternalGuestListReport({
            property: selectedProp || "",
            page: t.page,
            size: t.size,
          });
          const data = res?.data?.data ?? res?.data ?? {};
          this.applyResponse(t, data, mapBookingRow);
        } else if (isDailyPaymentTab(key)) {
          const listQuery = { ...baseRange, page: t.page, size: t.size };
          const listType = {
            ...baseRange,
            page: t.pageTable2,
            size: t.sizeTable2,
          };
          const totalQuery = { ...baseRange };
          const [resList, resListType, resTotal] = await Promise.allSettled([
            getDailyPaymentReport(listQuery),
            getDailyPaymentTypeReport(listType),
            getDailyPaymentTypeTotalReport(totalQuery),
          ]);
          if (resList.status === "fulfilled") {
            const payload =
              resList.value?.data?.data ?? resList.value?.data ?? {};
            const mapped1 = mapDailyPaymentTable1(payload);
            this.applyResponse(t, mapped1, (x) => x);
          } else {
            t.error = resList.reason?.message || t.error;
          }
          if (resListType.status === "fulfilled") {
            const payload2 =
              resListType.value?.data?.data ?? resListType.value?.data ?? {};
            const mapped2 = mapDailyPaymentTable2List(payload2);
            t.rowsTable2 = mapped2.rows || [];
            t.pageTable2 = Number.isFinite(+mapped2.page)
              ? +mapped2.page
              : t.pageTable2 || 1;
            t.sizeTable2 = Number.isFinite(+mapped2.size)
              ? +mapped2.size
              : t.sizeTable2 || 10;
            t.totalTable2 = Number.isFinite(+mapped2.total)
              ? +mapped2.total
              : t.rowsTable2.length || 0;
          } else {
            t.rowsTable2 = [];
            t.totalTable2 = 0;
          }
          if (resTotal.status === "fulfilled") {
            const payloadTotal =
              resTotal.value?.data?.data ?? resTotal.value?.data ?? [];
            t.paymentTotals = mapDailyPaymentTable2GrandTotal(payloadTotal);
          } else {
            t.paymentTotals = {};
          }
        }
      } catch (err) {
        console.error("[useReport.fetch] error:", err);
        t.error = err?.message || "Lỗi tải dữ liệu";
      } finally {
        t.loading = false;
      }
    },
  },
});
