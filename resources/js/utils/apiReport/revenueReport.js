export function getEfficiencyTotalReport({
  dateType,
  timeRange,
  currency,
  otaName,
  property,
} = {}) {
  const query = cleanParams({
    dateType,
    timeRange,
    currency,
    otaName,
    property,
  });
  return $api("/api/admin/report/revenue/efficiency-total", {
    method: "GET",
    query,
  });
}

export function getEfficiencyReport({
  dateType,
  timeRange,
  currency,
  otaName,
  page = 1,
  size = 10,
  property,
} = {}) {
  const query = cleanParams({
    dateType,
    timeRange,
    currency,
    otaName,
    page,
    size,
    property,
  });
  return $api("/api/admin/report/revenue/efficiency", {
    method: "GET",
    query,
  });
}

export function getRevenueDetailTotalReport({
  dateFrom,
  dateTo,
  timeFrom,
  timeTo,
  otaName,
  property,
} = {}) {
  const query = cleanParams({
    dateFrom,
    dateTo,
    timeFrom,
    timeTo,
    otaName,
    property,
  });
  return $api("/api/admin/report/revenue/detail-total", {
    method: "GET",
    query,
  });
}

export function getRevenueDetailReport({
  dateFrom,
  dateTo,
  timeFrom,
  timeTo,
  otaName,
  page = 1,
  size = 10,
  property,
} = {}) {
  const query = cleanParams({
    dateFrom,
    dateTo,
    timeFrom,
    timeTo,
    otaName,
    page,
    size,
    property,
  });
  return $api("/api/admin/report/revenue/detail", {
    method: "GET",
    query,
  });
}

export function getBookingTotalReport({
  monthFrom,
  monthTo,
  yearFrom,
  yearTo,
  currency,
  property,
} = {}) {
  const query = cleanParams({
    monthFrom,
    monthTo,
    yearFrom,
    yearTo,
    currency,
    property,
  });
  return $api("/api/admin/report/revenue/booking-total", {
    method: "GET",
    query,
  });
}

export function getBookingReport({
  monthFrom,
  monthTo,
  yearFrom,
  yearTo,
  currency,
  page = 1,
  size = 10,
  property,
} = {}) {
  const query = cleanParams({
    monthFrom,
    monthTo,
    yearFrom,
    yearTo,
    currency,
    page,
    size,
    property,
  });
  return $api("/api/admin/report/revenue/booking", {
    method: "GET",
    query,
  });
}

export function getBookingSourceReport({
  monthFrom,
  monthTo,
  yearFrom,
  yearTo,
  currency,
  property,
  otaName,
  page = 1,
  size = 10,
} = {}) {
  const query = cleanParams({
    monthFrom,
    monthTo,
    yearFrom,
    yearTo,
    currency,
    property,
    otaName,
    page,
    size,
  });
  return $api("/api/admin/report/revenue/booking-source", {
    method: "GET",
    query,
  });
}

export function getBookingSourceTotalReport({
  monthFrom,
  monthTo,
  yearFrom,
  yearTo,
  currency,
  property,
  otaName,
} = {}) {
  const query = cleanParams({
    monthFrom,
    monthTo,
    yearFrom,
    yearTo,
    currency,
    property,
    otaName,
  });
  return $api("/api/admin/report/revenue/booking-source-total", {
    method: "GET",
    query,
  });
}

export function apiGetHeaderRevenueRoomReport({
  property,
  page = 1,
  size = 100000,
} = {}) {
  const query = cleanParams({
    size,
    page,
    property,
  });
  return $api("/api/admin/rooms/list", {
    method: "GET",
    query,
  });
}

export function getRevenueRoomReport({
  monthFrom,
  monthTo,
  yearFrom,
  yearTo,
  currency,
  property,
  otaName,
  page = 1,
  size = 10,
} = {}) {
  const query = cleanParams({
    monthFrom,
    monthTo,
    yearFrom,
    yearTo,
    currency,
    property,
    otaName,
    page,
    size,
  });
  return $api("/api/admin/report/revenue/room", {
    method: "GET",
    query,
  });
}

export function getRevenueRoomTotalReport({
  monthFrom,
  monthTo,
  yearFrom,
  yearTo,
  currency,
  property,
  otaName,
} = {}) {
  const query = cleanParams({
    monthFrom,
    monthTo,
    yearFrom,
    yearTo,
    currency,
    property,
    otaName,
  });
  return $api("/api/admin/report/revenue/room-total", {
    method: "GET",
    query,
  });
}

export function getRevenueDailySaleReport({
  timeRange,
  currency,
  otaName,
  property,
  page = 1,
  size = 10,
} = {}) {
  const query = cleanParams({
    timeRange,
    currency,
    property,
    otaName,
    page,
    size,
  });
  return $api("/api/admin/report/revenue/daily", {
    method: "GET",
    query,
  });
}

export function getRevenueDailySaleTotalReport({
  timeRange,
  currency,
  property,
  otaName,
} = {}) {
  const query = cleanParams({
    timeRange,
    currency,
    property,
    otaName,
  });
  return $api("/api/admin/report/revenue/daily-total", {
    method: "GET",
    query,
  });
}

export function getRevenueActivityReport({
  timeRange,
  currency,
  otaName,
  property,
  page = 1,
  size = 10,
} = {}) {
  const query = cleanParams({
    timeRange,
    currency,
    property,
    otaName,
    page,
    size,
  });
  return $api("/api/admin/report/revenue/activity", {
    method: "GET",
    query,
  });
}

export function getRevenueActivityTotalReport({
  timeRange,
  currency,
  property,
  otaName,
} = {}) {
  const query = cleanParams({
    timeRange,
    currency,
    property,
    otaName,
  });
  return $api("/api/admin/report/revenue/activity-total", {
    method: "GET",
    query,
  });
}
