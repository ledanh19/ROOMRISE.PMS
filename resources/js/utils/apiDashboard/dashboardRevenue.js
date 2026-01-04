import { $api, cleanParams } from "../api";

export function getRevenueStat({ timeRange, type, property } = {}) {
  const query = cleanParams({
    timeRange,
    type,
    property,
  });
  return $api("/api/admin/dashboard/revenue/stats", {
    method: "GET",
    query,
  });
}

export function getRevenueTrend({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/revenue/trend", {
    method: "GET",
    query,
  });
}

export function getRevenueSource({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/revenue/source", {
    method: "GET",
    query,
  });
}

export function getRevenueRoom({ timeRange, type, property } = {}) {
  const query = cleanParams({
    timeRange,
    type,
    property,
  });
  return $api("/api/admin/dashboard/revenue/room", {
    method: "GET",
    query,
  });
}

export function getRevenuePaymentType({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/revenue/payment-type", {
    method: "GET",
    query,
  });
}

export function getRevenueAdrRevparTrend({ property } = {}) {
  const query = cleanParams({
    property,
  });
  return $api("/api/admin/dashboard/revenue/adr-revpar-trend", {
    method: "GET",
    query,
  });
}

export function getRevenueTopOtaPartner({
  timeRange,
  property,
  type,
  page,
  size,
} = {}) {
  const query = cleanParams({
    timeRange,
    property,
    type,
    size,
    page,
  });
  return $api("/api/admin/dashboard/revenue/top-ota-partner", {
    method: "GET",
    query,
  });
}
