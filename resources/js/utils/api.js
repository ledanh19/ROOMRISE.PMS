import { ofetch } from "ofetch";

function getCookie(name) {
  if (typeof document === "undefined") return null;
  const match = document.cookie.match(
    new RegExp(
      "(?:^|; )" + name.replace(/([$?*|{}\]\\^])/g, "\\$1") + "=([^;]*)"
    )
  );
  return match ? decodeURIComponent(match[1]) : null;
}

function getAccessToken() {
  const fromCookie = getCookie("accessToken");
  if (fromCookie) return fromCookie;
  try {
    return localStorage.getItem("nest_token") || null;
  } catch {
    return null;
  }
}

function normalizeTimeRange(timeRange) {
  if (!timeRange) return undefined;

  const toISO = (v) => {
    if (!v) return undefined;
    if (v instanceof Date) return v.toISOString();

    if (typeof v === "string") {
      const onlyDate = /^\d{4}[-/]\d{2}[-/]\d{2}$/.test(v);
      if (onlyDate) return new Date(v).toISOString();
      return v;
    }
    return v;
  };

  if (Array.isArray(timeRange)) {
    const [s, e] = timeRange;
    const start = toISO(s);
    const end = toISO(e);
    return start && end ? [start, end] : undefined;
  }

  if (typeof timeRange === "object") {
    const start = toISO(timeRange.start || timeRange.from);
    const end = toISO(timeRange.end || timeRange.to);
    return start && end ? [start, end] : undefined;
  }

  if (typeof timeRange === "string") return timeRange;

  return undefined;
}

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_NEST_URL || "http://localhost:3000",
  onRequest({ options }) {
    const token = getAccessToken();
    options.headers = {
      Accept: "application/json",
      ...(options.headers || {}),
    };
    if (token) options.headers.Authorization = `Bearer ${token}`;
  },
  async onResponseError({ response }) {
    let payload = null;
    try {
      payload = response?._data ?? (await response.json());
    } catch {}
    const err = new Error(
      payload?.message || response?.statusText || "Request error"
    );
    err.status = response?.status;
    err.data = payload;
    throw err;
  },
});

export function cleanParams(obj) {
  if (!obj || typeof obj !== "object") return obj;

  const isPlainObject = (v) =>
    Object.prototype.toString.call(v) === "[object Object]";

  const out = Array.isArray(obj) ? [] : {};

  const assign = (key, val) => {
    if (Array.isArray(out)) out.push(val);
    else out[key] = val;
  };

  for (const [k, v] of Object.entries(obj)) {
    if (v === undefined || v === null) continue;
    if (typeof v === "string" && v.trim() === "") continue;
    if (typeof v === "number" && Number.isNaN(v)) continue;

    if (Array.isArray(v)) {
      const arr = v
        .map((item) => cleanParams(item))
        .filter(
          (item) =>
            item !== undefined &&
            item !== null &&
            !(typeof item === "string" && item.trim() === "") &&
            !(isPlainObject(item) && Object.keys(item).length === 0)
        );
      if (arr.length) assign(k, arr);
      continue;
    }

    if (isPlainObject(v)) {
      const nested = cleanParams(v);
      if (Object.keys(nested).length) assign(k, nested);
      continue;
    }

    assign(k, v);
  }

  return out;
}
export function getControlPanel({
  tab = "departure",
  timeRange,
  property,
  bookingStatus,
  otaName,
  sortField,
  sortOrder,
  roomStatus,
  q,
  page,
  size,
} = {}) {
  const query = cleanParams({
    timeRange: normalizeTimeRange(timeRange),
    property,
    bookingStatus,
    otaName,
    sortField,
    sortOrder,
    q,
    page,
    size,
    roomStatus,
  });

  return $api(`/api/admin/bookings/control-panel/${encodeURIComponent(tab)}`, {
    method: "GET",
    query,
  });
}

export function getControlPanelTotals({
  timeRange,
  property,
  bookingStatus,
  otaName,
  sortField = "updated_at",
  sortOrder = "DESC",
  roomStatus,
} = {}) {
  return $api("/api/admin/bookings/control-panel/total-tab", {
    method: "GET",
    query: cleanParams({
      timeRange: normalizeTimeRange(timeRange),
      property,
      bookingStatus,
      otaName,
      sortField,
      sortOrder,
      roomStatus,
    }),
  });
}

export function getQuantityDirtyRooms({ property } = {}) {
  return $api("/api/admin/bookings/control-panel/quantity-dirty-rooms", {
    method: "GET",
    query: cleanParams({ property }),
  });
}

export function searchBookings({ textSearch, property, page, size } = {}) {
  return $api("/api/admin/bookings/search", {
    method: "GET",
    query: cleanParams({ textSearch, property, page, size }),
  });
}

export function getStatics({ property, timeRange } = {}) {
  return $api("/api/admin/bookings/control-panel/statistics", {
    method: "GET",
    query: cleanParams({
      property,
      timeRange: normalizeTimeRange(timeRange),
    }),
  });
}

export const apiGet = (url, query) => $api(url, { method: "GET", query });
export const apiPost = (url, body) => $api(url, { method: "POST", body });
export const apiPut = (url, body) => $api(url, { method: "PUT", body });
export const apiPatch = (url, body) => $api(url, { method: "PATCH", body });
export const apiDelete = (url, query) => $api(url, { method: "DELETE", query });
