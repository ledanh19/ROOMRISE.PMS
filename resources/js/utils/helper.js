import agoda from "@images/avatar-chat/agoda.png";
import airbnbLogo from "@images/avatar-chat/airbnb.png";
import bookingLogo from "@images/avatar-chat/bookingcom.png";
import ctripLogo from "@images/avatar-chat/ctrip.png";
import defaultLogo from "@images/avatar-chat/default.png";
import expediaLogo from "@images/avatar-chat/expedia.png";
import moment from "moment";
const PROVIDER_LOGOS = {
  airbnb: airbnbLogo,
  expedia: expediaLogo,
  ctrip: ctripLogo,
  tripcom: ctripLogo,
  bookingcom: bookingLogo,
  agoda: agoda,
  default: defaultLogo,
};

const TOKEN_KEYS = ["nest_token", "access_token", "token"];

const BASE_URL =
  (import.meta.env.VITE_NEST_URL &&
    String(import.meta.env.VITE_NEST_URL).trim()) ||
  (typeof window !== "undefined"
    ? window.location.origin
    : "http://localhost:3000");

const toProviderKey = (val) =>
  String(val || "")
    .toLowerCase()
    .replace(/\s+/g, "")
    .replace(/\./g, "");

const timeOf = (x) =>
  new Date(x?.chat?.lastMessage?.time || x?.updatedAt || 0).getTime();

export const uniqById = (arr) => {
  const map = new Map();
  for (const x of arr) if (x?.id && !map.has(x.id)) map.set(x.id, x);
  return [...map.values()];
};

export const logoFromOta = (otaName) => {
  const key = toProviderKey(otaName);
  if (key.includes("airbnb")) return PROVIDER_LOGOS.airbnb;
  if (key.includes("expedia")) return PROVIDER_LOGOS.expedia;
  if (key.includes("ctrip") || key.includes("tripcom") || key === "tripcom")
    return PROVIDER_LOGOS.ctrip;
  if (key.includes("bookingcom") || key === "booking")
    return PROVIDER_LOGOS.bookingcom;
  if (key.includes("agoda")) return PROVIDER_LOGOS.agoda;
  return PROVIDER_LOGOS.default;
};

export function parseRange(val) {
  if (!val) return [];
  if (Array.isArray(val)) {
    return val
      .filter(Boolean)
      .map((d) => (d instanceof Date ? dateToYMD(d) : ""))
      .filter(Boolean);
  }
  if (typeof val === "string") {
    const [a = "", b = ""] = val.split(" to ");
    return [a || "", b || ""].filter(Boolean);
  }
  return [];
}

export function toDDMMYYYY(ymd) {
  if (!ymd) return "";
  const [y, m, d] = ymd.split("-");
  if (!y || !m || !d) return "";
  return `${d}-${m}-${y}`;
}

export const dateToYMD = (d) => {
  if (!(d instanceof Date) || isNaN(d)) return "";
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
};

export function toDateInputLike(val) {
  if (!val) return "";
  if (val instanceof Date) return dateToYMD(val);
  if (typeof val === "string") {
    if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val;
    const t = Date.parse(val);
    if (!Number.isNaN(t)) return dateToYMD(new Date(t));
  }
  return "";
}

export function formatCurrencyVND(amount) {
  if (amount == null || isNaN(amount)) return "0 VND";
  return amount.toLocaleString("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  });
}

export function convertNegativeNumberToPositiveNumber(number) {
  if (typeof number !== "number") return null;
  return Math.abs(number);
}
export function initialsOf(fullName, maxLetters = 4) {
  if (!fullName) return "";

  const cleaned = String(fullName)
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^\w\s'-]/g, "")
    .trim()
    .replace(/\s+/g, " ");

  if (!cleaned) return "";

  const parts = cleaned.split(" ").filter(Boolean);
  const firstLetter = (s) => (s && s[0] ? s[0].toUpperCase() : "");

  if (parts.length === 1) return firstLetter(parts[0]);
  if (parts.length === 2)
    return (firstLetter(parts[1]) + firstLetter(parts[0])).slice(0, maxLetters);

  const first = firstLetter(parts[0]);
  const last = firstLetter(parts[parts.length - 1]);
  const middles = parts.slice(1, -1).map(firstLetter);

  let out = [first, ...middles, last];
  if (out.length > maxLetters) {
    const middleSlots = Math.max(0, maxLetters - 2);
    out = [first, ...middles.slice(0, middleSlots), last];
  }
  return out.slice(0, maxLetters).join("");
}

export const mergeContactPreserveMessages = (oldItem, newItem) => {
  if (!oldItem) return newItem;

  const merged = {
    ...oldItem,
    ...newItem,
    chat: {
      ...(oldItem.chat || {}),
      ...(newItem.chat || {}),
    },
  };

  const oldMsgs = oldItem?.chat?.messages || [];
  const newMsgs = newItem?.chat?.messages || [];
  merged.chat.messages = oldMsgs.length ? oldMsgs : newMsgs;

  if (newItem?.chat?.lastMessage)
    merged.chat.lastMessage = newItem.chat.lastMessage;
  if (typeof newItem?.chat?.unseenMsgs === "number")
    merged.chat.unseenMsgs = newItem.chat.unseenMsgs;

  return merged;
};

export const parseJSONSafe = (s, fallback = null) => {
  try {
    if (typeof s !== "string") return fallback;
    return JSON.parse(s);
  } catch {
    return fallback;
  }
};

export const buildUrl = (path, params) => {
  const url = new URL(path, BASE_URL);
  Object.entries(params || {}).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== "")
      url.searchParams.set(k, String(v));
  });
  return url;
};

export const getToken = () =>
  TOKEN_KEYS.map((k) => localStorage.getItem(k)).find(Boolean) || null;

export const authHeaders = () => {
  const t = getToken();
  return t ? { Authorization: `Bearer ${t}` } : {};
};

export const fetchJSON = async (url, options = {}) => {
  const res = await fetch(url, {
    ...options,
    headers: {
      Accept: "application/json",
      ...(options.headers || {}),
      ...authHeaders(),
    },
  });
  if (res.status === 401) {
    const text = await res.text().catch(() => "");
    console.error("[fetchJSON][401]", url.toString(), text);
    throw new Error("Unauthorized (401) – thiếu/không hợp lệ JWT token");
  }
  if (!res.ok) {
    const body = await res.text().catch(() => "");
    console.error(
      "[fetchJSON][!ok]",
      url.toString(),
      res.status,
      res.statusText,
      body
    );
    throw new Error(
      `HTTP ${res.status} ${res.statusText || ""} ${body || ""}`.trim()
    );
  }
  try {
    return await res.json();
  } catch {
    return {};
  }
};

export const sortChats = (list) => list.sort((a, b) => timeOf(b) - timeOf(a));

export function parseDateRange(val) {
  if (!val || typeof val !== "string") return { from: "", to: "" };

  const sep = val.includes(" to ") ? " to " : " - ";
  if (!val.includes(sep)) return { from: "", to: "" };

  const [from, to] = val.split(sep).map((s) => s.trim());
  return {
    from: from || "",
    to: to || "",
  };
}

export function makeRangeKey(from, to, type) {
  return `${from || ""}_${to || ""}_${type || ""}`;
}

export function hasFullRange(from, to) {
  return !!(from && to);
}

export function buildDateRange(from, to) {
  if (!from && !to) return "";
  if (from && to) return `${from} to ${to}`;
  return from || to || "";
}

export function makePercentNote(type, p) {
  const valueType = type === "previous" ? "với kỳ trước" : "với năm ngoái";
  if (!p) {
    return valueType;
  }
  const v = toNum(p);
  if (v > 0) return `↑ ${v.toFixed(1)}% vs ${valueType}`;
  if (v < 0) return `↓ ${Math.abs(v).toFixed(1)}% vs ${valueType}`;
  return `↔ 0.0% vs ${valueType}`;
}

export function toNum(v) {
  const n = Number(v);
  return Number.isFinite(n) ? n : 0;
}

export function formatDate(d) {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

export function fmtDMY(input) {
  if (!input) return "";
  const d = new Date(input);
  if (isNaN(d)) return "";
  const dd = String(d.getDate()).padStart(2, "0");
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const yyyy = d.getFullYear();
  return `${dd}-${mm}-${yyyy}`;
}

export function fmtMoneyVND(amount = 0) {
  const num = Number(amount) || 0;
  return num.toLocaleString("vi-VN", {
    maximumFractionDigits: 0,
  });
}

export function normalizeOtaKey(name) {
  const n = String(name || "").trim();
  switch (n) {
    case "BookingCom":
      return "bookingCom";
    case "Walk-in":
      return "walkIn";
    case "Từ đối tác":
      return "Từ đối tác";
    case "Agoda":
      return "agoda";
    case "Expedia":
      return "expedia";
    case "Airbnb":
      return "airbnb";
    case "Ctrip":
      return "ctrip";
    case "Widget":
      return "widget";
    default:
      return null;
  }
}

export function toAsciiSlug(input) {
  return (
    String(input ?? "")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "_")
      .replace(/^_+|_+$/g, "") || "col"
  );
}

export function makeRoomKey(name, id) {
  const slug = toAsciiSlug(name || "unknown");
  return `${slug}_${id ?? "x"}`;
}

export function formatToYMDHMS(v) {
  if (!v) return "";

  let d = new Date(v);
  if (isNaN(d.getTime())) {
    const cleaned = String(v)
      .replace(/T/, " ")
      .replace("Z", "")
      .replace(/-/g, " ")
      .trim();

    const parts = cleaned.split(/\s+/);

    if (parts.length >= 4) {
      let day = parts[0];
      let hms = parts[1].split(".")[0];
      let month = parts[2];
      let year = parts[3];

      d = new Date(`${year}-${month}-${day} ${hms}`);
    }
  }

  if (isNaN(d.getTime())) return "";

  const pad = (n) => String(n).padStart(2, "0");

  const Y = d.getFullYear();
  const M = pad(d.getMonth() + 1);
  const D = pad(d.getDate());
  const H = pad(d.getHours());
  const Min = pad(d.getMinutes());
  const S = pad(d.getSeconds());

  return `${Y}-${M}-${D} ${H}:${Min}:${S}`;
}

export const noteStatsCard = (type) => {
  return type === "previous" ? "so với kỳ trước" : "so với năm ngoái";
};

export function formatDateShortDdMm(date) {
  const data = date ? moment(date).format("DD/MM") : "";

  return data;
}

export const statusColor = (status) => {
  switch (status) {
    case "Mới":
      return "primary";
    case "Xác nhận":
      return "success";
    case "Yêu cầu":
      return "warning";
    case "Hủy":
      return "error";
    case "Hoàn thành":
      return "success";
    default:
      return "grey";
  }
};

export const getStatusIcon = (status) => {
  switch (status) {
    case "Mới":
      return "tabler-circle";
    case "Xác nhận":
      return "tabler-check";
    case "Yêu cầu":
      return "tabler-alert-triangle";
    case "Hủy":
      return "tabler-x";
    case "Hoàn thành":
      return "tabler-check";
    default:
      return "tabler-circle";
  }
};
