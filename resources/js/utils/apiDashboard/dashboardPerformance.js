import { $api, cleanParams } from "../api";

export function getPerformanceStats({ timeRange, type, property } = {}) {
  const query = cleanParams({
    timeRange,
    type,
    property,
  });
  return $api("/api/admin/dashboard/performance/stats", {
    method: "GET",
    query,
  });
}

export function getPerformanceTime({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/performance/time", {
    method: "GET",
    query,
  });
}

export function getPerformanceHeatmap({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/performance/heatmap", {
    method: "GET",
    query,
  });
}

export function getPerformanceLeadTime({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/performance/lead-time", {
    method: "GET",
    query,
  });
}

export function getPerformanceLOS({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/performance/los", {
    method: "GET",
    query,
  });
}

export function getPerformanceLOSTrend({ timeRange, property } = {}) {
  const query = cleanParams({
    timeRange,
    property,
  });
  return $api("/api/admin/dashboard/performance/los-trend", {
    method: "GET",
    query,
  });
}

export function getPerformanceProperty({
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
  return $api("/api/admin/dashboard/performance/property", {
    method: "GET",
    query,
  });
}
