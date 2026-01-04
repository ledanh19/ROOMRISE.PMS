import { $api, cleanParams } from "../api";

export function getListCheckInReport({
  dateFrom,
  dateTo,
  timeFrom,
  timeTo,
  page = 1,
  size = 10,
  property,
} = {}) {
  const query = cleanParams({
    dateFrom,
    dateTo,
    timeFrom,
    timeTo,
    page,
    size,
    property,
  });
  return $api("/api/admin/report/daily/checkin", {
    method: "GET",
    query,
  });
}

export function getListCheckOutReport({
  dateFrom,
  dateTo,
  timeFrom,
  timeTo,
  page = 1,
  size = 10,
  property,
} = {}) {
  const query = cleanParams({
    dateFrom,
    dateTo,
    timeFrom,
    timeTo,
    page,
    size,
    property,
  });
  return $api("/api/admin/report/daily/checkout", {
    method: "GET",
    query,
  });
}

export function getCustomerInformationReport({
  dateType,
  timeRange,
  type,
  page = 1,
  size = 10,
  property,
} = {}) {
  const query = cleanParams({
    dateType,
    timeRange,
    type,
    page,
    size,
    property,
  });
  return $api("/api/admin/report/daily/customer", {
    method: "GET",
    query,
  });
}

export function getInternalGuestListReport({
  page = 1,
  size = 10,
  property,
} = {}) {
  const query = cleanParams({
    page,
    size,
    property,
  });
  return $api("/api/admin/report/daily/in-house-guest", {
    method: "GET",
    query,
  });
}

export function getDailyPaymentReport({
  dateFrom,
  dateTo,
  timeFrom,
  timeTo,
  page = 1,
  size = 10,
  property,
} = {}) {
  const query = cleanParams({
    dateFrom,
    dateTo,
    timeFrom,
    timeTo,
    page,
    size,
    property,
  });
  return $api("/api/admin/report/daily/payment", {
    method: "GET",
    query,
  });
}

export function getDailyPaymentTypeReport({
  dateFrom,
  dateTo,
  timeFrom,
  timeTo,
  property,
  page = 1,
  size = 10,
} = {}) {
  const query = cleanParams({
    dateFrom,
    dateTo,
    timeFrom,
    timeTo,
    page,
    property,
    size,
  });
  return $api("/api/admin/report/daily/payment-type", {
    method: "GET",
    query,
  });
}

export function getDailyPaymentTypeTotalReport({
  dateFrom,
  dateTo,
  timeFrom,
  timeTo,
  property,
} = {}) {
  const query = cleanParams({
    dateFrom,
    dateTo,
    timeFrom,
    timeTo,
    property,
  });
  return $api("/api/admin/report/daily/payment-type-total", {
    method: "GET",
    query,
  });
}
