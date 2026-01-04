import { $api, cleanParams } from "../api";

export function getExecutiveStat({ timeRange, type, property } = {}) {
  const query = cleanParams({
    timeRange,
    type,
    property,
  });
  return $api("/api/admin/dashboard/executive/stats", {
    method: "GET",
    query,
  });
}

export function getExecutiveDate({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/executive/date", {
    method: "GET",
    query,
  });
}

export function getExecutiveRevenue({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/executive/revenue", {
    method: "GET",
    query,
  });
}

export function getExecutiveSource({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/executive/source", {
    method: "GET",
    query,
  });
}

export function getExecutiveCountry({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/executive/country", {
    method: "GET",
    query,
  });
}

export function getExecutiveQuickWarning({ timeRange, type, property } = {}) {
  const query = cleanParams({
    timeRange,
    type,
    property,
  });
  return $api("/api/admin/dashboard/executive/quick-warning", {
    method: "GET",
    query,
  });
}
