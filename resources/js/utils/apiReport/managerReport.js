import { $api, cleanParams } from "../api";

export function getBookingManagerReport({
  dateType,
  timeRange,
  bookingStatus,
  paymentStatus,
  otaName,
  page = 1,
  size = 10,
  property,
} = {}) {
  const query = cleanParams({
    dateType,
    timeRange,
    bookingStatus,
    paymentStatus,
    otaName,
    page,
    size,
    property,
  });
  return $api("/api/admin/report/management/booking", {
    method: "GET",
    query,
  });
}

export function getBookingManagerTotalReport({
  dateType,
  timeRange,
  bookingStatus,
  paymentStatus,
  otaName,
  property,
} = {}) {
  const query = cleanParams({
    dateType,
    timeRange,
    bookingStatus,
    paymentStatus,
    otaName,
    property,
  });
  return $api("/api/admin/report/management/booking-total", {
    method: "GET",
    query,
  });
}

export function getBookingSourceManagerReport({
  month,
  year,
  otaName,
  page = 1,
  size = 10,
  property,
} = {}) {
  const query = cleanParams({
    month,
    year,
    otaName,
    page,
    size,
    property,
  });
  return $api("/api/admin/report/management/room-booking-source", {
    method: "GET",
    query,
  });
}

export function getOccupancyReport({
  timeRange,
  roomIds,
  page = 1,
  size = 10,
  property,
} = {}) {
  const query = cleanParams({
    timeRange,
    roomIds,
    page,
    size,
    property,
  });
  return $api("/api/admin/report/management/occupancy", {
    method: "GET",
    query,
  });
}
