<script setup>
import { Head } from "@inertiajs/vue3";
import Layout from "../../../layouts/blank.vue";

import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import KpiCard from "@/Components/dashboard/cards/KpiCard.vue";
import PanelCard from "@/Components/dashboard/cards/PanelCard.vue";
import ChartBar from "@/Components/dashboard/charts/ChartBar.vue";
import ChartLine from "@/Components/dashboard/charts/ChartLine.vue";
import ChartPie from "@/Components/dashboard/charts/ChartPie.vue";

import { useDashboardExecutive } from "@/stores/useDashboardExecutive";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { useFilterDashboardStore } from "@/stores/useStoreDashboard";
import { QICK_DATE_RANGES, TIME_RANGE } from "@/utils/constants";
import {
  buildDateRange,
  formatCurrencyVND,
  formatDate,
  hasFullRange,
  makePercentNote,
  makeRangeKey,
  noteStatsCard,
  toNum,
} from "@/utils/helper";
import { useUiTone } from "@/utils/useUiTone";
import { storeToRefs } from "pinia";
import { computed, onMounted, ref, watch } from "vue";

const filterStore = useFilterDashboardStore();
const propertyStore = usePropertyStore();
const propertyId = ref(
  propertyStore.selectedProperty ? propertyStore.selectedProperty.id : null
);

const { tooltipTheme, titleTone, isDark } = useUiTone();
const exec = useDashboardExecutive();
const isReady = ref(false);

const lastRangeKey = ref("");

const {
  stats,
  dateDays,
  dateValues,
  revenueDays,
  revenueValues,
  sourceOtaNames,
  sourceTotal,
  sourceRevenue,
  sourceValues,
  sourceLines,
  countries,
  countryValues,
  quickWarningData,
} = storeToRefs(exec);

const kpiBookings = computed(() => {
  const current = toNum(stats.value?.bookings.current);
  const percent = toNum(stats.value?.bookings.percent);

  return {
    value: current,
    trend: percent,
    note: makePercentNote(exec.filters.type, percent),
  };
});

const kpiRevenue = computed(() => {
  const current = toNum(stats.value?.revenue.current);
  const percent = toNum(stats.value?.revenue.percent);

  return {
    value: formatCurrencyVND(current),
    trend: percent,
    note: makePercentNote(exec.filters.type, percent),
  };
});

const kpiADR = computed(() => {
  const current = toNum(stats.value?.adr.current);
  const percent = toNum(stats.value?.adr.percent);

  return {
    value: formatCurrencyVND(current),
    trend: percent,
    note: makePercentNote(exec.filters.type, percent),
  };
});

const kpiOCC = computed(() => {
  const current = toNum(stats.value?.occ.current);
  const percent = toNum(stats.value?.occ.percent);

  return {
    value: `${current}%`,
    trend: percent,
    note: makePercentNote(exec.filters.type, percent),
  };
});

const kpiRevPAR = computed(() => {
  const current = toNum(stats.value?.revpar.current);
  const percent = toNum(stats.value?.revpar.percent);

  return {
    value: formatCurrencyVND(current),
    trend: percent,
    note: makePercentNote(exec.filters.type, percent),
  };
});

const kpiCancelRate = computed(() => {
  const current = toNum(stats.value?.cancellationRate.current);
  const percent = toNum(stats.value?.cancellationRate.percent);

  return {
    value: `${current.toFixed(1)}%`,
    trend: percent,
    note: makePercentNote(exec.filters.type, percent),
  };
});

function initDefaultFilters() {
  const patch = {};
  const hasDateFrom = !!exec.filters.dateFrom;
  const hasDateTo = !!exec.filters.dateTo;

  if (filterStore.dateFrom && filterStore.dateTo) {
    patch.dateFrom = filterStore.dateFrom;
    patch.dateTo = filterStore.dateTo;
    patch.timeRange = buildDateRange(patch.dateFrom, patch.dateTo);
  } else if (!hasDateFrom && !hasDateTo) {
    const today = new Date();
    const fromDate = new Date();
    fromDate.setDate(today.getDate() - 7);

    const dateTo = formatDate(today);
    const dateFrom = formatDate(fromDate);

    patch.dateFrom = dateFrom;
    patch.dateTo = dateTo;
    patch.timeRange = buildDateRange(dateFrom, dateTo);

    filterStore.dateFrom = dateFrom;
    filterStore.dateTo = dateTo;
  } else {
    filterStore.dateFrom = exec.filters.dateFrom;
    filterStore.dateTo = exec.filters.dateTo;
  }

  if (filterStore.type) {
    patch.type = filterStore.type;
  } else if (!exec.filters.type) {
    patch.type = "previous";
  } else {
    filterStore.type = exec.filters.type;
  }

  if (Object.keys(patch).length) {
    exec.setFilters(patch);
  }
}

async function reloadAll() {
  await exec.fetchStats();
  await exec.fetchDate();
  await exec.fetchRevenue();
  await exec.fetchSource();
  await exec.fetchCountry();

  if (typeof exec.fetchQuickWarning === "function") {
    await exec.fetchQuickWarning();
  }
}

async function reloadIfReadyAndChanged() {
  if (!isReady.value) return;

  const from = exec.filters.dateFrom;
  const to = exec.filters.dateTo;
  const type = exec.filters.type;

  if (!hasFullRange(from, to)) return;

  const key = makeRangeKey(from, to, type);
  if (key === lastRangeKey.value) {
    return;
  }

  lastRangeKey.value = key;
  await reloadAll();
}

onMounted(async () => {
  initDefaultFilters();

  await reloadAll();

  lastRangeKey.value = makeRangeKey(
    exec.filters.dateFrom,
    exec.filters.dateTo,
    exec.filters.type
  );

  isReady.value = true;
  isInitialLoad.value = false;
});

const dateFrom = computed({
  get: () => exec.filters.dateFrom,
  set: async (val) => {
    if (val === exec.filters.dateFrom) return;
    isQuickChanging.value = true;
    qickTimeRange.value = 0;

    const currentTo = exec.filters.dateTo;
    const nextRange = buildDateRange(val, currentTo);
    exec.setFilters({ dateFrom: val, timeRange: nextRange });
    filterStore.dateFrom = val;
    await reloadIfReadyAndChanged();
    isInitialLoad.value = false;
    isQuickChanging.value = false;
  },
});

const dateTo = computed({
  get: () => exec.filters.dateTo,
  set: async (val) => {
    if (val === exec.filters.dateTo) return;
    isQuickChanging.value = true;
    qickTimeRange.value = 0;

    const currentFrom = exec.filters.dateFrom;
    const nextRange = buildDateRange(currentFrom, val);
    exec.setFilters({ dateTo: val, timeRange: nextRange });
    filterStore.dateTo = val;
    await reloadIfReadyAndChanged();
    isInitialLoad.value = false;
    isQuickChanging.value = false;
  },
});

const typeFilter = computed({
  get: () => exec.filters.type,
  set: async (val) => {
    exec.setFilters({ type: val });
    filterStore.type = val;
    await reloadIfReadyAndChanged();
  },
});

const isInitialLoad = ref(true);
const isQuickChanging = ref(false);
const qickTimeRange = ref(
  filterStore.quickdate != null
    ? filterStore.quickdate
    : QICK_DATE_RANGES[0].value
);

const days = computed(() => dateDays.value);

const bookingsSeries = computed(() => [
  {
    name: "Số booking",
    data: dateValues.value,
  },
]);

const revenueCategories = computed(() => revenueDays.value);

const revenueSeries = computed(() => [
  {
    name: "Doanh thu",
    data: revenueValues.value,
  },
]);

const byCountrySeries = computed(() => {
  const data = Array.isArray(countryValues.value)
    ? countryValues.value.map((v) => Number(v) || 0)
    : [];

  if (!data.length) return [];

  return [
    {
      name: "Số booking",
      data,
    },
  ];
});

const palette = {
  linePrimary: ["#7C4DFF"],
  lineSecondary: ["#00B8D9"],
  barTeal: ["#26A69A"],
  pie: ["#2962FF", "#00C853", "#FFAB00", "#D50000", "#9C27B0"],
};

const quickWarning = computed(() => {
  const q = quickWarningData?.value ?? {};

  const currentCancellationRate = Number(q.currentCancellationRate ?? 0);
  const averageCancellationRate = Number(
    q.averageCancellationRate ?? q.previousCancellationRate ?? 0
  );
  const cancellationDiff = +(
    currentCancellationRate - averageCancellationRate
  ).toFixed(1);
  const cancellationAlert = currentCancellationRate > 15;

  const revenueDownRateRaw = Number(q.revenueDownRate ?? 0);
  const revenueDownAlert = revenueDownRateRaw < -10;
  const revenueDownRate = revenueDownAlert ? revenueDownRateRaw * -1 : 0;

  const reviewAverage =
    typeof q.reviewAverage !== "undefined" ? Number(q.reviewAverage) : null;
  const reviewAlert = reviewAverage !== null ? reviewAverage < 4.2 : false;

  return {
    currentCancellationRate: currentCancellationRate.toFixed(1),
    averageCancellationRate: averageCancellationRate.toFixed(1),
    cancellationDiff,
    cancellationAlert,
    revenueDownAlert,
    revenueDownRate,
    reviewAverage,
    reviewAlert,
  };
});

watch(qickTimeRange, async (val) => {
  if (isQuickChanging.value) return;
  if (val === null || val === undefined) return;

  const today = new Date();
  let fromDate;
  let toDate = formatDate(today);

  if (val > 0) {
    const d = new Date();
    d.setDate(today.getDate() - val);
    fromDate = formatDate(d);
  } else {
    const y = today.getFullYear();
    fromDate = `${y}-01-01`;
    toDate = `${y}-12-31`;
  }
  const range = buildDateRange(fromDate, toDate);

  exec.setFilters({
    dateFrom: fromDate,
    dateTo: toDate,
    timeRange: range,
  });

  filterStore.dateFrom = fromDate;
  filterStore.dateTo = toDate;
  filterStore.quickdate = val;

  await reloadIfReadyAndChanged();
});

watch(propertyId, async (val) => {
  propertyStore.setProperty(val);
  reloadAll();
});

watch(
  () => propertyStore.selectedProperty,
  (val) => {
    propertyId.value = val;
    reloadAll();
  }
);
</script>

<template>
  <Head title="Điều hành | Room Rise" />
  <Layout>
    <div class="dashboard">
      <section class="header">
        <div class="title-row">
          <h1 class="title">Điều hành</h1>
        </div>

        <p class="subtitle">Xem toàn bộ hiệu suất kinh doanh trong kỳ</p>

        <div class="filters">
          <VRow dense>
            <VCol cols="12" sm="3">
              <div class="filter"></div>
              <VSelect
                class="f-ctrl"
                label="Chọn nhanh"
                placeholder="Chọn nhanh"
                v-model="qickTimeRange"
                :items="QICK_DATE_RANGES"
                density="comfortable"
                hide-details
              />
            </VCol>
            <VCol cols="12" sm="3" class="field-col">
              <div class="field">
                <div class="label">Từ ngày</div>
                <AppDateTimePicker
                  v-model="dateFrom"
                  class="picker picker--date"
                  placeholder="YYYY-MM-DD"
                  :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
                  :clearable="true"
                />
              </div>
            </VCol>
            <VCol cols="12" sm="3" class="field-col">
              <div class="field">
                <div class="label">Đến ngày</div>
                <AppDateTimePicker
                  v-model="dateTo"
                  class="picker picker--date"
                  placeholder="YYYY-MM-DD"
                  :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
                  :clearable="true"
                />
              </div>
            </VCol>
            <VCol cols="12" sm="3">
              <div class="filter"></div>
              <VSelect
                class="f-ctrl"
                label="Phạm vi"
                placeholder="Phạm vi"
                v-model="typeFilter"
                :items="TIME_RANGE"
                density="comfortable"
                hide-details
              />
            </VCol>

            <VCol cols="12" sm="3">
              <div class="filter"></div>
              <VBtn
                variant="tonal"
                color="primary"
                prepend-icon="tabler-adjustments-horizontal"
                @click="reloadAll()"
              >
                Lọc
              </VBtn>
            </VCol>
          </VRow>
        </div>
      </section>

      <VRow dense class="kpis">
        <VCol cols="12" md="6" lg="3">
          <KpiCard
            icon="tabler-calendar-stats"
            title="Tổng đặt phòng"
            :value="kpiBookings.value"
            :note="noteStatsCard(exec.filters.type)"
            :trend="kpiBookings.trend"
            borderTone="primary"
            bgTone="primary"
            titleTone="muted"
            valueTone="default"
            iconTone="primary"
            iconShape="rounded"
            iconSize="md"
            :bg="`${isDark ? 'rgba(47,51,73)' : '#ffffff'}`"
            :color="`${isDark ? '#ffffff' : 'rgb(85 80 80)'}`"
            :iconBg="'#ffe5e5'"
            :iconColor="'#e57373'"
          />
        </VCol>

        <VCol cols="12" md="6" lg="3">
          <KpiCard
            icon="tabler-currency-dong"
            title="Doanh thu"
            :value="kpiRevenue.value"
            :note="noteStatsCard(exec.filters.type)"
            :trend="kpiRevenue.trend"
            borderTone="primary"
            bgTone="primary"
            titleTone="muted"
            valueTone="primary"
            iconTone="primary"
            iconShape="circle"
            iconSize="md"
            :bg="`${isDark ? 'rgba(47,51,73)' : '#ffffff'}`"
            :color="`${isDark ? '#ffffff' : 'rgb(85 80 80)'}`"
            :iconBg="'#e3f2fd'"
            :iconColor="'#64b5f6'"
          />
        </VCol>

        <VCol cols="12" md="6" lg="3">
          <KpiCard
            icon="tabler-currency-dollar"
            title="ADR"
            :value="kpiADR.value"
            :note="noteStatsCard(exec.filters.type)"
            :trend="kpiADR.trend"
            borderTone="info"
            bgTone="default"
            titleTone="muted"
            valueTone="info"
            iconTone="info"
            iconShape="rounded"
            iconSize="md"
            :bg="`${isDark ? 'rgba(47,51,73)' : '#ffffff'}`"
            :color="`${isDark ? '#ffffff' : 'rgb(85 80 80)'}`"
            :iconBg="'#e8f5e9'"
            :iconColor="'#81c784'"
          />
        </VCol>

        <VCol cols="12" md="6" lg="3">
          <KpiCard
            icon="tabler-bed"
            title="Occupancy"
            :value="kpiOCC.value"
            :note="noteStatsCard(exec.filters.type)"
            :trend="kpiOCC.trend"
            borderTone="success"
            bgTone="success"
            titleTone="muted"
            valueTone="success"
            iconTone="success"
            iconShape="rounded"
            iconSize="md"
            :bg="`${isDark ? 'rgba(47,51,73)' : '#ffffff'}`"
            :color="`${isDark ? '#ffffff' : 'rgb(85 80 80)'}`"
            :iconBg="'#fff9e6'"
            :iconColor="'#fbc02d'"
          />
        </VCol>

        <VCol cols="12" md="6" lg="3">
          <KpiCard
            icon="tabler-building-skyscraper"
            title="RevPAR"
            :value="kpiRevPAR.value"
            :note="noteStatsCard(exec.filters.type)"
            :trend="kpiRevPAR.trend"
            borderTone="primary"
            bgTone="default"
            titleTone="muted"
            valueTone="primary"
            iconTone="primary"
            iconShape="rounded"
            :bg="`${isDark ? 'rgba(47,51,73)' : '#ffffff'}`"
            :color="`${isDark ? '#ffffff' : 'rgb(85 80 80)'}`"
            :iconBg="'#fff0e2'"
            :iconColor="'#ffb74d'"
          />
        </VCol>

        <VCol cols="12" md="6" lg="3">
          <KpiCard
            icon="tabler-alert-circle"
            title="Tỉ lệ huỷ"
            :value="kpiCancelRate.value"
            :note="noteStatsCard(exec.filters.type)"
            :trend="kpiCancelRate.trend"
            borderTone="danger"
            bgTone="danger"
            titleTone="muted"
            valueTone="danger"
            iconTone="danger"
            iconShape="rounded"
            :bg="`${isDark ? 'rgba(47,51,73)' : '#ffffff'}`"
            :color="`${isDark ? '#ffffff' : 'rgb(85 80 80)'}`"
            :iconBg="'#f3e5f5'"
            :iconColor="'#ba68c8'"
          />
        </VCol>

        <VCol cols="12" md="6" lg="3">
          <KpiCard
            class="disable"
            icon="tabler-user"
            title="Tỉ lệ vắng mặt"
            :value="'Đang phát triển'"
            :trend="0"
            borderTone="danger"
            bgTone="danger"
            titleTone="muted"
            valueTone="danger"
            iconTone="danger"
            iconShape="rounded"
            :bg="`${isDark ? 'rgb(71 71 71)' : '#f2f2f2'}`"
            :color="'#9e9e9e'"
            :iconBg="'#e0e0e0'"
            :iconColor="'#9e9e9e'"
          />
        </VCol>

        <VCol cols="12" md="6" lg="3">
          <KpiCard
            class="disable"
            icon="tabler-star"
            title="Đánh giá Trung bình"
            :value="'Đang phát triển'"
            :trend="0"
            borderTone="danger"
            bgTone="danger"
            titleTone="muted"
            valueTone="danger"
            iconTone="danger"
            iconShape="rounded"
            :bg="`${isDark ? 'rgb(71 71 71)' : '#f2f2f2'}`"
            :color="'#9e9e9e'"
            :iconBg="'#e0e0e0'"
            :iconColor="'#9e9e9e'"
          />
        </VCol>
      </VRow>

      <PanelCard title="Đặt phòng theo ngày">
        <ChartLine
          :series="bookingsSeries"
          :categories="days"
          :height="260"
          :y-label-formatter="(v) => Math.round(v)"
          :colors="palette.lineSecondary"
          :dash-array="[0]"
          stroke-cap="round"
          :tooltip-theme="tooltipTheme"
          :unit="'Đặt phòng'"
        />
      </PanelCard>

      <PanelCard title="Doanh thu theo ngày">
        <ChartLine
          :series="revenueSeries"
          :categories="revenueCategories"
          :height="260"
          :y-label-formatter="(v) => Math.round(v)"
          :colors="palette.lineSecondary"
          :dash-array="[0]"
          stroke-cap="round"
          :tooltip-theme="tooltipTheme"
        />
      </PanelCard>

      <VRow dense>
        <VCol cols="12" lg="12">
          <PanelCard title="Cơ cấu doanh thu theo nguồn" class="pie-source">
            <div class="chart-split">
              <div class="chart">
                <ChartPie
                  :series="sourceValues"
                  :labels="sourceOtaNames"
                  :hint="sourceTotal"
                  :revenues="sourceRevenue"
                  :height="260"
                  legend-position="bottom"
                  :colors="palette.pie"
                  :tooltip-theme="tooltipTheme"
                />
              </div>

              <div class="side left">
                <ul class="meta-list">
                  <li
                    v-for="(label, i) in sourceOtaNames"
                    :key="label || i"
                    class="meta-item"
                  >
                    <div class="logo-item">
                      <span
                        class="dot"
                        :style="{
                          background:
                            (palette.pie && palette.pie[i]) || '#cbd5e1',
                        }"
                        aria-hidden="true"
                      ></span>

                      <img
                        v-if="
                          label && otaLogos && otaLogos[label.toLowerCase()]
                        "
                        :src="otaLogos[label.toLowerCase()]"
                        alt="OTA Logo"
                        class="h-6 logo-detail"
                      />
                      <div class="label">{{ label }}</div>
                    </div>

                    <span class="sub">{{
                      sourceTotal && sourceTotal[i] ? sourceTotal[i] : "-"
                    }}</span>
                    <span class="sub">{{
                      formatCurrencyVND(
                        sourceRevenue && sourceRevenue[i] ? sourceRevenue[i] : 0
                      )
                    }}</span>
                    <span class="sub right"
                      >{{
                        sourceValues && sourceValues[i] ? sourceValues[i] : 0
                      }}
                      %</span
                    >
                  </li>
                </ul>
              </div>
            </div>

            <!-- optional compact lines (kept if you want) -->
            <!--
  <LeadTimeList
    :items="sourceLines"
    bar-color="primary"
    track-color="surface-variant"
    :height="10"
  />
  -->
          </PanelCard>
        </VCol>
        <!-- <VCol cols="12" lg="6">
          <PanelCard title="Doanh thu theo quốc gia (Top 5)">
            <ChartBar
              :series="byCountrySeries"
              :categories="countries"
              :height="260"
              :colors="palette.barTeal"
              :horizontal="true"
              :border-radius="2"
              :tooltip-theme="tooltipTheme"
              :unit="'Đặt phòng'"
            />
          </PanelCard>
        </VCol> -->
      </VRow>
      <PanelCard title="Doanh thu theo quốc gia (Top 5)">
        <ChartBar
          :series="byCountrySeries"
          :categories="countries"
          :height="260"
          :colors="palette.barTeal"
          :horizontal="true"
          :border-radius="2"
          :tooltip-theme="tooltipTheme"
          :unit="'Đặt phòng'"
        />
      </PanelCard>
      <PanelCard title="Cảnh báo nhanh" class="alert-panel">
        <div class="alerts">
          <div class="alert-item" v-if="quickWarning.cancellationAlert">
            <div class="alert-bullet bullet-danger"></div>
            <div class="alert-body">
              <div class="alert-title">Tỉ lệ huỷ &gt; 15%</div>
              <div class="alert-sub">
                Tỉ lệ huỷ đang ở mức
                <strong>{{ quickWarning.currentCancellationRate }}%</strong>,
                {{
                  `${
                    quickWarning.cancellationDiff > 0 ? "cao hơn" : "thấp hơn"
                  } `
                }}
                <strong
                  >{{
                    `${
                      quickWarning.cancellationDiff > 0
                        ? quickWarning.cancellationDiff
                        : quickWarning.cancellationDiff * -1
                    } `
                  }}%</strong
                >
                so với mức trung bình
                <strong>{{ quickWarning.averageCancellationRate }}%</strong>.
              </div>
            </div>
          </div>

          <div class="alert-item" v-if="quickWarning.revenueDownAlert">
            <div class="alert-bullet bullet-warning"></div>
            <div class="alert-body">
              <div class="alert-title">Doanh thu (Net) giảm &gt; 10%</div>
              <div class="alert-sub">
                Doanh thu đang giảm
                <strong>{{ quickWarning.revenueDownRate.toFixed(1) }}%</strong>
                {{ noteStatsCard(exec.filters.type) }}
              </div>
            </div>
          </div>

          <div
            class="alert-item"
            v-if="
              !quickWarning.cancellationAlert && !quickWarning.revenueDownAlert
            "
          >
            <div class="alert-bullet bullet-ok"></div>
            <div class="alert-body">
              <div class="alert-title">Không có cảnh báo nghiêm trọng</div>
              <div class="alert-sub">Tình hình hoạt động ổn định trong kỳ.</div>
            </div>
          </div>
        </div>
      </PanelCard>
    </div>
  </Layout>
</template>
<style scoped>
/* Layout */
.dashboard {
  display: grid;
  padding: 10px;
  gap: 14px;
  margin-inline: auto;
  max-inline-size: 1400px;
}

.header {
  display: grid;
  gap: 8px;
}

.title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.title {
  margin: 0;
  font-size: clamp(18px, 2vw, 22px);
  font-weight: 800;
}

.subtitle {
  margin: 0;
  font-size: 12px;
  opacity: 0.7;
}

/* Filters */
.filters {
  display: grid;
}

.field {
  display: grid;
  gap: 4px;
}

.label {
  font-size: 12px;
  opacity: 0.8;
}

.filter {
  margin-block-start: 27px;
}

.picker :where(.v-field),
.f-ctrl :where(.v-field),
.filters :deep(.v-field) {
  border-radius: 10px;
}

.kpis {
  margin-block-start: 2px;
}

.mini-metrics .mm-title {
  font-size: 12px;
  opacity: 0.7;
}

.mini-metrics .mm-val {
  font-weight: 700;
}

.mm-title {
  font-size: 13px !important;
  font-weight: 600;
  opacity: 1 !important;
}

.panel-subtitle {
  font-size: 12px;
  margin-block: 2px 8px;
  opacity: 0.6;
}

.chart-split {
  display: grid;
  gap: 12px;
  grid-template-columns: 1fr 1.5fr;
}

.chart-columns {
  display: grid;
  align-items: center;
  gap: 12px;
  grid-template-columns: 1fr;
}

.chart-split .chart {
  inline-size: 100%;
  min-inline-size: 0;
}

.chart-split .side {
  border: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  border-radius: 12px;
  background: color-mix(in oklab, var(--v-theme-primary) 4%, transparent);
  padding-block: 8px;
  padding-inline: 10px;
}

.meta-list,
.meta-list-right {
  display: grid;
  padding: 0;
  margin: 0;
  gap: 10px;
  list-style: none;
}

.meta-list {
  grid-template-columns: repeat(1, 1fr);
}

.meta-list-right {
  overflow: hidden auto;
  gap: 20px;
  grid-template-columns: repeat(4, 1fr);
  max-block-size: 260px;
  padding-inline-end: 6px;
}

.meta-item {
  display: grid;
  padding: 8px;
  border-radius: 10px;
  background: color-mix(in oklab, var(--v-theme-on-surface) 1%, transparent);
  gap: 8px 12px;
  grid-template-columns: repeat(4, 1fr);
  min-inline-size: 0;
  place-items: center left;
}

.meta-item .right {
  justify-self: end !important;
}

.meta-item .sub {
  font-size: 12px;
  justify-self: center;
  opacity: 0.75;
  text-align: center;
  white-space: nowrap;
}

.meta-item .label {
  font-weight: 600;
  line-height: 1.2;
}

.meta-item .label.strong {
  font-weight: 700;
}

.meta-item-columns {
  display: grid;
  align-items: start;
  gap: 4px 8px;
  grid-template-columns: 1fr;
  padding-block: 0;
  padding-inline: 20px;
}

.meta-item-columns > span:first-child {
  color: var(--v-theme-on-surface);
  font-size: 0.95rem;
  font-weight: 600;
  line-height: 1.25rem;
}

.meta-item-columns > span:last-child {
  font-size: 0.75rem;
  line-height: 1rem;
}

.logo-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  text-align: start;
}

.logo-item .label {
  font-size: 0.95rem;
  font-weight: 600;
  line-height: 1.2;
}
.meta-item .dot {
  border-radius: 50%;
  block-size: 10px;
  inline-size: 10px;
  margin-block-start: 6px;
}

.logo-detail {
  border-radius: 50%;
  block-size: 30px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 8%);
  inline-size: 30px;
  object-fit: cover;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.logo-detail:hover {
  box-shadow: 0 6px 18px rgba(0, 0, 0, 10%);
  transform: scale(1.03);
}

.recent-table {
  overflow: visible;
  padding: 6px;
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  border-radius: 16px;
  animation: fadeInUp 0.6s ease-out;
  background-color: rgb(var(--v-theme-surface));
  box-shadow: 0 4px 20px rgba(0, 0, 0, 8%);
}

.recent-table :deep(.v-table__wrapper table) {
  border-collapse: separate;
  border-spacing: 0 0;
  inline-size: 100%;
}

.recent-table :deep(.v-data-table__header),
.recent-table :deep(.v-data-table__top) {
  border-block-end: 1px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  padding-block: 12px;
  padding-inline: 16px;
}

.recent-table :deep(.v-data-table__th),
.recent-table :deep(.v-data-table__th .v-data-table-header__content) {
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  line-height: 1.2;
  text-transform: uppercase;
  white-space: nowrap;
}

.recent-table :deep(.v-data-table__tr) {
  overflow: hidden;
  border: none;
  border-radius: 12px;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 6px 18px rgba(8, 20, 48, 4%);
  transition: transform 0.22s ease, box-shadow 0.22s ease;
}

.recent-table :deep(.v-data-table__tr):hover {
  box-shadow: 0 12px 28px rgba(8, 20, 48, 6%);
  transform: translateY(-2px);
}

.recent-table :deep(.v-data-table__td) {
  overflow: hidden;
  border: none;
  background: transparent;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  font-size: 0.95rem;
  line-height: 1.35;
  padding-block: 18px;
  padding-inline: 16px;
  text-overflow: ellipsis;
  vertical-align: middle;
  white-space: nowrap;
}

.recent-table .wrap {
  white-space: normal;
  word-break: break-word;
}

.recent-table :deep(.v-table__wrapper) {
  border-radius: 0 0 12px 12px;
  -webkit-overflow-scrolling: touch;
  overflow-x: auto;
}

.recent-table :deep(.v-chip) {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 8%);
  font-size: 0.78rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  text-transform: none;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.recent-table :deep(.v-chip:hover) {
  box-shadow: 0 6px 16px rgba(0, 0, 0, 10%);
  transform: translateY(-2px);
}

.recent-table :deep(.v-chip .v-icon) {
  font-size: 14px;
}

.recent-table .logo {
  border-radius: 6px;
  block-size: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 8%);
  inline-size: 2rem;
  object-fit: contain;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.recent-table .logo:hover {
  box-shadow: 0 6px 18px rgba(0, 0, 0, 10%);
  transform: scale(1.06);
}

.mono {
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.2px;
}

.prop-strong {
  font-weight: 700;
}

.muted {
  opacity: 0.75;
}

.revenue {
  color: var(--v-theme-success);
  font-weight: 700;
}

.chip-source,
.chip-status {
  border-radius: 999px;
  padding-inline: 8px;
}

.nights {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

@media (max-width: 1024px) {
  .recent-table :deep(.v-data-table__th),
  .recent-table :deep(.v-data-table__td) {
    font-size: 0.9rem;
  }
}

@media (max-width: 768px) {
  .recent-table {
    padding: 4px;
    border-radius: 12px;
  }

  .recent-table :deep(.v-data-table__td),
  .recent-table :deep(.v-data-table__th) {
    font-size: 0.9rem;
    padding-block: 12px;
    padding-inline: 10px;
    white-space: normal;
  }

  .recent-table :deep(.v-table__wrapper) {
    overflow-x: auto;
  }

  .recent-table .tbl-footer {
    margin: 8px;
  }
}

.chart-split .side.right {
  max-block-size: 320px;
  -webkit-overflow-scrolling: touch;
  overflow-y: auto;
  padding-inline-end: 6px;
}

.chart-split .side.right::-webkit-scrollbar {
  inline-size: 8px;
}

.chart-split .side.right::-webkit-scrollbar-thumb {
  border-radius: 8px;
  background: color-mix(in oklab, var(--v-theme-on-surface) 20%, transparent);
}

.chart-split .side.right::-webkit-scrollbar-track {
  background: transparent;
}

.tbl-footer {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  justify-content: space-between;
  background: transparent;
  border-block-start: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  gap: 12px;
  padding-block: 10px;
  padding-inline: 14px;
}

.tbl-footer .foot-left {
  overflow: hidden;
  flex: 1 1 auto;
  color: rgba(var(--v-theme-on-surface), 0.85);
  font-size: 0.95rem;
  text-align: start;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tbl-footer .foot-right {
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: flex-end;
}

@media (max-width: 480px) {
  .tbl-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .tbl-footer .foot-left {
    text-align: start;
  }

  .tbl-footer .foot-right {
    justify-content: flex-start;
  }
}

.status-chip {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
  font-size: 0.75rem;
  font-weight: 500;
  letter-spacing: 0.025em;
  text-transform: uppercase;
  transition: all 0.3s ease;
}

.status-chip :deep(.v-icon) {
  font-size: 14px;
}

.amount-highlight {
  color: rgb(var(--v-theme-success));
  font-size: 1.1rem;
  font-weight: 700;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.alerts {
  display: grid;
  gap: 10px;
  color: color-mix(in oklab, var(--v-theme-on-surface) 70%, transparent);
}

.alert-item {
  display: flex;
  align-items: flex-start;
  padding: 12px;
  border: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 8%, transparent);
  border-radius: 8px;
  background: var(--v-theme-surface);
  gap: 12px;
  background-color: white;
}

.alert-bullet {
  flex: 0 0 12px;
  border-radius: 50%;
  block-size: 12px;
  inline-size: 12px;
  margin-block-start: 6px;
}

.bullet-danger {
  background: #d32f2f;
}

.bullet-warning {
  background: #ff9800;
  box-shadow: 0 0 0 6px color-mix(in oklab, #ff9800 6%, transparent);
}

.bullet-ok {
  background: #26a69a;
  box-shadow: 0 0 0 6px color-mix(in oklab, #26a69a 6%, transparent);
}

.alert-body {
  display: block;
  min-inline-size: 0;
}

.alert-title {
  font-size: 13px;
  font-weight: 700;
  margin-block-end: 6px;
  color: #656565;
}

.alert-sub {
  /* color: color-mix(in oklab, var(--v-theme-on-surface) 70%, transparent); */
  color: #656565;
  font-size: 12px;
  line-height: 1.3;
}

:where(.theme--dark) .alert-item {
  background: color-mix(in oklab, var(--v-theme-on-surface) 3%, transparent);
}

:where(.theme--dark) .alert-sub {
  color: rgba(121, 121, 121, 78%) !important;
}

.booking-trend,
.booking-source,
.pie-source {
  block-size: 100%;
}

@media (min-width: 600px) {
  .v-col-sm-3 {
    flex: 0 0 25%;
    max-inline-size: 15%;
  }
}

.filters-row {
  display: grid;
  align-items: end;
  gap: 8px;
  grid-template-columns: repeat(3, minmax(200px, 1fr));
}

@media (max-width: 768px) {
  .filters-row {
    grid-template-columns: 1fr;
  }

  .meta-list-right {
    grid-template-columns: 1fr;
  }
}

.panel-subtitle {
  font-size: 12px;
  margin-block: 2px 8px;
  opacity: 0.6;
}

.chart-split .chart :where(svg, canvas) {
  max-inline-size: 100%;
}

.prop-strong {
  font-weight: 700;
}

@media (min-width: 1100px) {
  .meta-list {
    grid-template-columns: repeat(1, 1fr);
  }
}

.field-col {
  margin-top: 4px;
}

.alert-panel {
  border-width: 2px;
  border-style: solid;
  border-color: hsl(38 92% 50%);
  background-color: hsl(38deg 92% 50% / 5%);
}

.disable {
  background-color: #a9a9a9;
}
</style>
