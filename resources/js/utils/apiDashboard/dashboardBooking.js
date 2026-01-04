import { $api, cleanParams } from "../api";

export function getBookingStat({ timeRange, type, property } = {}) {
  const query = cleanParams({
    timeRange,
    type,
    property,
  });
  return $api("/api/admin/dashboard/booking/stats", {
    method: "GET",
    query,
  });
}

export function getBookingStatus({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/booking/status", {
    method: "GET",
    query,
  });
}

export function getBookingSource({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/booking/source", {
    method: "GET",
    query,
  });
}

export function getBookingRoom({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/booking/room", {
    method: "GET",
    query,
  });
}

export function getBookingCustomer({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/booking/customer", {
    method: "GET",
    query,
  });
}

export function getBookingLeadtime({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/booking/lead-time", {
    method: "GET",
    query,
  });
}

export function getBookingDetails({ timeRange, property, page, size } = {}) {
  const query = cleanParams({
    timeRange,
    property,
    size,
    page,
  });
  return $api("/api/admin/dashboard/booking/booking-detail", {
    method: "GET",
    query,
  });
}
