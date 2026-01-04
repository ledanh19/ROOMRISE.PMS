<script setup>
import KpiCard from "@/Components/dashboard/cards/KpiCard.vue";
import PanelCard from "@/Components/dashboard/cards/PanelCard.vue";
import AreaLosTrend from "@/Components/dashboard/charts/AreaLosTrend.vue";
import PerfOverviewChart from "@/Components/dashboard/charts/PerfOverviewChart.vue";
import HeatmapOccupancy from "@/Components/dashboard/misc/HeatmapOccupancy.vue";
import LeadTimeList from "@/Components/dashboard/misc/LeadTimeList.vue";
import Layout from "@/layouts/blank.vue";
import { useDashboardPerformance } from "@/stores/useDashboardPerformance";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { useFilterDashboardStore } from "@/stores/useStoreDashboard";
import { QICK_DATE_RANGES } from "@/utils/constants";
import { performanceHeaders } from "@/utils/headerDashboard";
import {
  buildDateRange,
  formatCurrencyVND,
  hasFullRange,
  makeRangeKey,
  noteStatsCard,
} from "@/utils/helper";
import { Head } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import { computed, onMounted, ref, watch } from "vue";

const filterStore = useFilterDashboardStore();
const propertyStore = usePropertyStore();
const propertyId = ref(
  propertyStore.selectedProperty ? propertyStore.selectedProperty.id : null
);
const exec = useDashboardPerformance();
const { isDark } = useUiTone();

const {
  stats,
  bookingTotal,
  bookingPage,
  time,
  leadTime,
  LOS,
  LOSTrend,
  heatmap,
  propertyRows,
} = storeToRefs(exec);

const isReady = ref(false);
const lastRangeKey = ref("");

const localBookingPage = ref(bookingPage.value ?? 1);
const bookingPerPage = ref(5);
const itemsPerPage = ref(5);
let updatingFromStore = false;

const lastBookingFetchKey = ref("");
let bookingFetchDebounceTimer = null;
const BOOKING_FETCH_DEBOUNCE_MS = 120;

async function fetchPropertyIfNeeded(pageNum, sizeNum) {
  const p = Number(pageNum ?? 1);
  const s = Number(sizeNum ?? 20);
  const key = `${p}:${s}`;

  if (bookingFetchDebounceTimer) clearTimeout(bookingFetchDebounceTimer);
  if (lastBookingFetchKey.value === key) return;

  return new Promise((resolve) => {
    bookingFetchDebounceTimer = setTimeout(async () => {
      bookingFetchDebounceTimer = null;
      try {
        lastBookingFetchKey.value = key;
        await exec.fetchProperty({ page: p, size: s });
        resolve(true);
      } catch (err) {
        lastBookingFetchKey.value = "";
        resolve(false);
      }
    }, BOOKING_FETCH_DEBOUNCE_MS);
  });
}

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

async function reloadIfReadyAndChanged() {
  if (!isReady.value) return;
  const from = exec?.filters?.dateFrom;
  const to = exec?.filters?.dateTo;
  const type = exec?.filters?.type;
  if (!from || !to) return;
  if (!hasFullRange(from, to)) return;
  const key = makeRangeKey(from, to, type ?? "previous");
  if (key === lastRangeKey.value) return;
  lastRangeKey.value = key;
  await reloadAll();
}

async function reloadAll() {
  await exec.fetchStats();
  await exec.fetchTime();
  await exec.fetchLeadTime();
  await exec.fetchLOS();
  await exec.fetchLOSTrend();
  await exec.fetchHeatmap();
  lastBookingFetchKey.value = "";
  await fetchPropertyIfNeeded(
    Number(localBookingPage.value ?? 1),
    Number(bookingPerPage.value ?? 20)
  );
}

const statsCard = computed(() => {
  const currentOCC = `${stats.value?.currentOCC}%`;
  const previousOCC = stats.value?.previousOCC;
  const percentOCC = stats.value?.percentOCC;
  const currentADR = formatCurrencyVND(stats.value?.currentADR);
  const previousADR = formatCurrencyVND(stats.value?.previousADR);
  const percentADR = stats.value?.percentADR;
  const currentRevPAR = formatCurrencyVND(stats.value?.currentRevPAR);
  const previousRevPAR = formatCurrencyVND(stats.value?.previousRevPAR);
  const percentRevPAR = stats.value?.percentRevPAR;
  const currentLOS = `${stats.value?.currentLOS} đêm`;
  const previousLOS = stats.value?.previousLOS;
  const percentLOS = stats.value?.percentLOS;
  const currentLeadtime = `${stats.value?.currentLeadtime} ngày`;
  const previousLeadtime = stats.value?.previousLeadtime;
  const percentLeadtime = stats.value?.percentLeadtime;
  return {
    currentOCC,
    previousOCC,
    percentOCC,
    currentADR,
    previousADR,
    percentADR,
    currentRevPAR,
    previousRevPAR,
    percentRevPAR,
    currentLOS,
    previousLOS,
    percentLOS,
    currentLeadtime,
    previousLeadtime,
    percentLeadtime,
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

onMounted(async () => {
  initDefaultFilters();
  localBookingPage.value = Number(bookingPage.value ?? 1);
  await reloadAll();
  lastRangeKey.value = makeRangeKey(
    exec.filters.dateFrom,
    exec.filters.dateTo,
    exec.filters.type
  );
  isReady.value = true;
  isInitialLoad.value = false;
});

const days = computed(() =>
  time.value && Array.isArray(time.value.days) ? time.value.days : []
);

const perfSeries = computed(() => [
  {
    name: "Occupancy",
    yAxisIndex: 1,
    type: "line",
    data:
      time.value && Array.isArray(time.value.OCCs)
        ? time.value.OCCs.map((v) => v)
        : [],
  },
  {
    name: "ADR (Net)",
    yAxisIndex: 0,
    type: "line",
    data: time.value && Array.isArray(time.value.ADRs) ? time.value.ADRs : [],
  },
  {
    name: "RevPAR (Net)",
    yAxisIndex: 0,
    type: "line",
    data:
      time.value && Array.isArray(time.value.RevPARs) ? time.value.RevPARs : [],
  },
]);

const heatmapWeekdays = computed(() =>
  heatmap.value && Array.isArray(heatmap.value.weekdays)
    ? heatmap.value.weekdays
    : []
);
const heatmapRoomTypes = computed(() =>
  heatmap.value && Array.isArray(heatmap.value.roomTypes)
    ? heatmap.value.roomTypes
    : []
);
const heatmapOccMatrix = computed(() =>
  heatmap.value && Array.isArray(heatmap.value.occMatrix)
    ? heatmap.value.occMatrix
    : []
);

const losTrendDays = computed(() =>
  LOSTrend.value && Array.isArray(LOSTrend.value.days)
    ? LOSTrend.value.days
    : []
);
const losTrendSeries = computed(() => [
  {
    name: "LOS TB",
    data:
      LOSTrend.value && Array.isArray(LOSTrend.value.avgLOSs)
        ? LOSTrend.value.avgLOSs
        : [],
  },
]);

watch(
  bookingPage,
  (newVal) => {
    const p = Number(newVal ?? 1);
    if (p === Number(localBookingPage.value ?? 1)) return;
    updatingFromStore = true;
    localBookingPage.value = p;
    setTimeout(() => {
      updatingFromStore = false;
    }, 0);
  },
  { immediate: false }
);

watch(
  localBookingPage,
  async (newPage) => {
    if (!isReady.value) return;
    if (updatingFromStore) return;
    const p = Number(newPage ?? 1);
    const s = Number(bookingPerPage.value ?? 20);
    await fetchPropertyIfNeeded(p, s);
  },
  { immediate: false }
);

watch(bookingPerPage, async (newSize) => {
  if (!isReady.value) return;
  localBookingPage.value = 1;
  await fetchPropertyIfNeeded(1, Number(newSize ?? 20));
});

const isInitialLoad = ref(true);
const isQuickChanging = ref(false);
const qickTimeRange = ref(
  filterStore.quickdate != null
    ? filterStore.quickdate
    : QICK_DATE_RANGES[0].value
);

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

const totalBookings = computed(() => Number(bookingTotal.value ?? 0));

const showingFrom = computed(() => {
  const total = totalBookings.value;
  if (total === 0) return 0;
  const p = Number(localBookingPage.value ?? 1);
  const per = Number(bookingPerPage.value ?? 20);
  const from = (p - 1) * per + 1;
  return Math.min(from, total);
});

const showingTo = computed(() => {
  const total = totalBookings.value;
  if (total === 0) return 0;
  const p = Number(localBookingPage.value ?? 1);
  const per = Number(bookingPerPage.value ?? 20);
  return Math.min(p * per, total);
});

const showingText = computed(() => {
  const total = totalBookings.value;
  if (total === 0) return `Hiển thị 0 trong 0 mục`;
  return `Hiển thị ${showingFrom.value}–${showingTo.value} trong ${total} mục`;
});
</script>

<template>
  <Head title="Hiệu suất | Room Rise" />
  <Layout>
    <div class="page">
      <section class="header">
        <div class="title-row">
          <div>
            <h1 class="title">Hiệu suất</h1>
            <p class="subtitle">
              Đo lường hiệu quả kinh doanh và phân tích xu hướng hoạt động chi
              tiết
            </p>
          </div>
        </div>

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
            <VCol cols="12" sm="3">
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
            <VCol cols="12" sm="3">
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
            title="Occupancy"
            :value="statsCard.currentOCC"
            :trend="statsCard.percentOCC"
            :note="noteStatsCard(exec.filters.type)"
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
            icon="tabler-calendar-stats"
            title="ADR"
            :value="statsCard.currentADR"
            :trend="statsCard.percentADR"
            :note="noteStatsCard(exec.filters.type)"
            borderTone="primary"
            bgTone="primary"
            titleTone="muted"
            valueTone="default"
            iconTone="primary"
            iconShape="rounded"
            iconSize="md"
            :bg="`${isDark ? 'rgba(47,51,73)' : '#ffffff'}`"
            :color="`${isDark ? '#ffffff' : 'rgb(85 80 80)'}`"
            :iconBg="'#e3f2fd'"
            :iconColor="'#64b5f6'"
          />
        </VCol>

        <VCol cols="12" md="6" lg="3">
          <KpiCard
            icon="tabler-calendar-stats"
            title="RevPAR"
            :value="statsCard.currentRevPAR"
            :trend="statsCard.percentRevPAR"
            :note="noteStatsCard(exec.filters.type)"
            borderTone="primary"
            bgTone="primary"
            titleTone="muted"
            valueTone="default"
            iconTone="primary"
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
            icon="tabler-calendar-stats"
            title="LOS TB"
            :value="statsCard.currentLOS"
            :trend="statsCard.percentLOS"
            :note="noteStatsCard(exec.filters.type)"
            borderTone="primary"
            bgTone="primary"
            titleTone="muted"
            valueTone="default"
            iconTone="primary"
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
            icon="tabler-calendar-stats"
            title="Lead time TB"
            :value="statsCard.currentLeadtime"
            :trend="statsCard.percentLeadtime"
            :note="noteStatsCard(exec.filters.type)"
            borderTone="primary"
            bgTone="primary"
            titleTone="muted"
            valueTone="default"
            iconTone="primary"
            iconShape="rounded"
            iconSize="md"
            :bg="`${isDark ? 'rgba(47,51,73)' : '#ffffff'}`"
            :color="`${isDark ? '#ffffff' : 'rgb(85 80 80)'}`"
            :iconBg="'#fff0e2'"
            :iconColor="'#ffb74d'"
          />
        </VCol>
      </VRow>

      <PanelCard title="Tổng quan hiệu suất theo thời gian">
        <p class="hint">Theo dõi Occupancy, ADR và RevPAR (Net) hằng ngày</p>
        <PerfOverviewChart
          :series="perfSeries"
          :categories="days"
          :height="360"
        />
      </PanelCard>

      <PanelCard title="Heatmap lấp đầy theo ngày & loại phòng">
        <p class="hint">Visualize occupancy patterns trong tuần</p>
        <HeatmapOccupancy
          :weekdays="heatmapWeekdays"
          :roomTypes="heatmapRoomTypes"
          :matrix="heatmapOccMatrix"
          :colors="{
            gte95: '#2ECC71',
            b8594: '#1E88E5',
            o7584: '#FB8C00',
            lt75: '#8E44AD',
            head: 'color-mix(in oklab,var(--v-theme-on-surface) 6%, transparent)',
          }"
          :colWidth="'130px'"
          labelColWidth="120px"
          :cellRadius="12"
          :cellPadding="12"
        />
      </PanelCard>

      <VRow dense>
        <VCol cols="12" lg="6">
          <PanelCard title="Lead Time Distribution">
            <p class="hint">Phân tích thời gian đặt trước của khách</p>
            <LeadTimeList
              :items="leadTime"
              unit="bookings"
              :computePercent="true"
            />
          </PanelCard>
        </VCol>
        <VCol cols="12" lg="6">
          <PanelCard title="LOS Distribution" class="los-dis">
            <p class="hint">Phân bố thời gian lưu trú của khách</p>
            <LeadTimeList :items="LOS" unit="bookings" :computePercent="true" />
          </PanelCard>
        </VCol>
      </VRow>

      <PanelCard title="Length of Stay Trend">
        <p class="hint">Xu hướng thời gian lưu trú trung bình theo ngày</p>
        <AreaLosTrend
          :series="losTrendSeries"
          :categories="losTrendDays"
          :height="280"
        />
      </PanelCard>

      <PanelCard title="So sánh hiệu quả theo chỗ nghỉ">
        <div class="hint">
          Phân tích đầy đủ các chỉ số hiệu suất từng property
        </div>

        <div
          class="tbl-top"
          style="
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin-block-end: 8px;
          "
        >
          <div style="display: flex; align-items: center; gap: 12px">
            <div style="min-inline-size: 160px">
              <VSelect
                v-model="bookingPerPage"
                :items="[5, 10, 20, 50, 100]"
                density="compact"
                variant="outlined"
                hide-details
                label="Số hàng / trang"
                style="max-inline-size: 180px"
              />
            </div>
          </div>
        </div>

        <VDataTable
          class="recent-table"
          :headers="performanceHeaders"
          :items="propertyRows"
          item-key="id"
          density="comfortable"
          hover
          :page="localBookingPage"
          :items-per-page="Number(bookingPerPage)"
        >
          <template #item.propertyName="{ item }">
            <span class="mono">{{ item.propertyName }}</span>
          </template>

          <template #item.OCC="{ item }">
            <span class="prop-strong">{{ item.OCC }}%</span>
          </template>

          <template #item.ADR="{ item }">
            <div>{{ formatCurrencyVND(item.ADR) }}</div>
          </template>

          <template #item.RevPAR="{ item }">
            <div>{{ formatCurrencyVND(item.RevPAR) }}</div>
          </template>

          <template #item.LOS="{ item }">
            <VChip color="info" variant="flat" size="small" class="status-chip">
              <VIcon icon="tabler-moon" size="14" class="mr-1"></VIcon>
              {{ item.LOS }} đêm
            </VChip>
          </template>

          <template #item.leadTime="{ item }">
            <div>{{ item.leadTime }} ngày</div>
          </template>

          <template #item.revenue="{ item }">
            <div>{{ formatCurrencyVND(item.revenue) }}</div>
          </template>

          <template #item.percentGrowthRevenue="{ item }">
            <div>{{ item.percentGrowthRevenue }}%</div>
          </template>

          <template #bottom>
            <div class="tbl-footer">
              <div class="foot-left">{{ showingText }}</div>
              <div class="foot-right">
                <VPagination
                  v-model="localBookingPage"
                  :length="
                    Math.max(
                      1,
                      Math.ceil((bookingTotal ?? 0) / (bookingPerPage ?? 20))
                    )
                  "
                  total-visible="5"
                  density="comfortable"
                />
              </div>
            </div>
          </template>
        </VDataTable>
      </PanelCard>
    </div>
  </Layout>
</template>

<style scoped>
.page {
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

/* Filters / search styling (consistent with booking/revenue templates) */
.filters {
  display: grid;
}

.f-ctrl :where(.v-field) {
  border-radius: 10px;
}

/* ensure Vuetify fields get rounded corners in filters */
.filters :deep(.v-field) {
  border-radius: 10px;
}

/* label + control layout */
.field {
  display: grid;
  gap: 4px;
}

/* pushes inputs down to align with other controls */
.filter {
  margin-block-start: 27px;
}

/* date picker helpers */
.picker {
  inline-size: 100%;
}

.picker--date :where(.flatpickr-input) {
  inline-size: 100%;
}

/* keep small columns consistent with other pages */
@media (min-width: 600px) {
  .v-col-sm-3 {
    flex: 0 0 25%;
    max-inline-size: 15%;
  }
}

/* KPI grid */
.kpis {
  margin-block-start: 2px;
}

.kpi-row {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(5, minmax(180px, 1fr));
}

.hint,
.panel-subtitle {
  font-size: 12px;
  margin-block: 2px 8px;
  margin-inline: 0;
  opacity: 0.65;
}

/* subpanel style */
.subpanel {
  padding: 10px;
  border: 1px dashed
    color-mix(in oklab, var(--v-theme-on-surface) 16%, transparent);
  border-radius: 12px;
  background: color-mix(in oklab, var(--v-theme-primary) 4%, transparent);
  margin-block-start: 12px;
}

/* table utilities */
.tbl-wrap {
  overflow: auto;
  border-radius: 12px;
}

.tbl {
  border-collapse: collapse;
  inline-size: 100%;
  white-space: nowrap;
}

.tbl thead th {
  position: sticky;
  z-index: 1;
  background: var(--v-theme-surface);
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  font-size: 12.5px;
  font-weight: 700;
  inset-block-start: 0;
  padding-block: 10px;
  padding-inline: 12px;
  text-align: start;
}

.tbl tbody td {
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 8%, transparent);
  padding-block: 10px;
  padding-inline: 12px;
  vertical-align: middle;
}

.tbl .mono {
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.2px;
}

.tbl .strong {
  font-weight: 700;
}

.tbl .green {
  color: #1b5e20;
}

.tbl .text-right {
  text-align: end;
}

/* mini bar helpers (kept for any small bars in table rows) */
.mini-bar {
  position: relative;
  border-radius: 999px;
  background: color-mix(in oklab, var(--v-theme-on-surface) 14%, transparent);
  block-size: 8px;
  inline-size: 120px;
}

.mini-bar .bar {
  position: absolute;
  border-radius: 999px;
  background: var(--v-theme-primary);
  inline-size: 0;
  inset: 0;
}

.mini-bar .mini-label {
  font-size: 12px;
  margin-inline-start: 8px;
  opacity: 0.8;
}

/* responsive KPI grid */
@media (max-width: 1200px) {
  .kpi-row {
    grid-template-columns: repeat(3, minmax(180px, 1fr));
  }
}

@media (max-width: 780px) {
  .kpi-row {
    grid-template-columns: repeat(2, minmax(160px, 1fr));
  }
}

@media (max-width: 560px) {
  .kpi-row {
    grid-template-columns: 1fr;
  }
}

/* recent-table styling (harmonized with other templates) */
.recent-table {
  overflow: visible;
  padding: 6px;
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  border-radius: 16px;
  animation: fadeInUp 0.5s ease-out;
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

.recent-table :deep(.v-data-table__th) {
  font-size: 13px;
  font-weight: 600;
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
  padding-block: 16px;
  padding-inline: 16px;
  text-overflow: ellipsis;
  vertical-align: middle;
  white-space: nowrap;
}

.recent-table .mono {
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.2px;
}

.prop-strong {
  font-weight: 700;
}

.muted {
  opacity: 0.75;
}

.recent-table .v-chip {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 8%);
  font-size: 0.78rem;
  font-weight: 600;
}

.recent-table .logo {
  border-radius: 6px;
  block-size: 2rem;
  inline-size: 2rem;
  object-fit: contain;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

/* animation */
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

/* Footer / pagination style */
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

@media (min-width: 1280px) {
  .v-col-lg-3 {
    flex: 0 0 20%;
    max-width: 25%;
  }
}
</style>
