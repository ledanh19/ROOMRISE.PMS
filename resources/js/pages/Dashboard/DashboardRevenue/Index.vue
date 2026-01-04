<script setup>
import KpiCard from "@/Components/dashboard/cards/KpiCard.vue";
import PanelCard from "@/Components/dashboard/cards/PanelCard.vue";
import AdrRevparTrend from "@/Components/dashboard/charts/AdrRevparTrend.vue";
import ChartPie from "@/Components/dashboard/charts/ChartPie.vue";
import RoomTypeBar from "@/Components/dashboard/charts/RoomTypeBar.vue";
import TrendNetOccAdrRevPar from "@/Components/dashboard/charts/TrendNetOccAdrRevPar.vue";
import LeadTimeList from "@/Components/dashboard/misc/LeadTimeList.vue";
import Layout from "@/layouts/blank.vue";
import { useDashboardRevenue } from "@/stores/useDashboardRevenue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { useFilterDashboardStore } from "@/stores/useStoreDashboard";
import { QICK_DATE_RANGES, TIME_RANGE } from "@/utils/constants";
import { revenueHeaders } from "@/utils/headerDashboard";
import {
  buildDateRange,
  formatCurrencyVND,
  hasFullRange,
  makeRangeKey,
  noteStatsCard,
} from "@/utils/helper";
import { useUiTone } from "@/utils/useUiTone";
import { Icon } from "@iconify/vue";
import { Head } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import { computed, onMounted, ref, watch } from "vue";

const filterStore = useFilterDashboardStore();
const propertyStore = usePropertyStore();
const propertyId = ref(
  propertyStore.selectedProperty ? propertyStore.selectedProperty.id : null
);
const exec = useDashboardRevenue();

const {
  stats,
  trend,
  source,
  room,
  paymentType,
  adrRevparTrend,
  topOTAPartnerRows,
  bookingTotal,
  bookingPage,
} = storeToRefs(exec);

const { tooltipTheme, isDark } = useUiTone();

const isReady = ref(false);
const lastRangeKey = ref("");

const localBookingPage = ref(bookingPage.value ?? 1);
const bookingPerPage = ref(5);
const itemsPerPage = ref(5);
let updatingFromStore = false;

const lastBookingFetchKey = ref("");
let bookingFetchDebounceTimer = null;
const BOOKING_FETCH_DEBOUNCE_MS = 120;

async function fetchTopOtaPartnerIfNeeded(pageNum, sizeNum) {
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
        await exec.fetchRevenueTopOtaPartner({ page: p, size: s });
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

const typeFilter = computed({
  get: () => exec.filters.type,
  set: async (val) => {
    exec.setFilters({ type: val });
    filterStore.type = val;
    await reloadIfReadyAndChanged();
  },
});

async function reloadAll() {
  await exec.fetchStats();
  await exec.fetchTrend();
  await exec.fetchSource();
  await exec.fetchRoom();
  await exec.fetchPaymentType();
  await exec.fetchRevenueAdrRevparTrend();
  lastBookingFetchKey.value = "";
  await fetchTopOtaPartnerIfNeeded(
    Number(localBookingPage.value ?? 1),
    Number(bookingPerPage.value ?? 20)
  );
}

const statsCard = computed(() => {
  const currentRevenue = formatCurrencyVND(stats.value?.currentRevenue);
  const previousRevenue = formatCurrencyVND(stats.value?.previousRevenue);
  const percentRevenue = stats.value?.percentRevenue;
  const currentADR = formatCurrencyVND(stats.value?.currentADR);
  const previousADR = formatCurrencyVND(stats.value?.previousADR);
  const percentADR = stats.value?.percentADR;
  const currentRevPAR = formatCurrencyVND(stats.value?.currentRevPAR);
  const previousRevPAR = formatCurrencyVND(stats.value?.previousRevPAR);
  const percentRevPAR = stats.value?.percentRevPAR;
  const currentRevPAC = formatCurrencyVND(stats.value?.currentRevPAC);
  const previousRevPAC = formatCurrencyVND(stats.value?.previousRevPAC);
  const percentRevPAC = stats.value?.percentRevPAC;
  return {
    currentRevenue,
    previousRevenue,
    percentRevenue,
    currentADR,
    previousADR,
    percentADR,
    currentRevPAR,
    previousRevPAR,
    percentRevPAR,
    currentRevPAC,
    previousRevPAC,
    percentRevPAC,
  };
});

const trendsData = computed(() => {
  const days = trend.value.days;
  const revenues = trend.value.revenues;
  const RevPACs = trend.value.RevPACs;
  const ADRs = trend.value.ADRs;
  const RevPARs = trend.value.RevPARs;
  return { days, revenues, RevPACs, ADRs, RevPARs };
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
    await fetchTopOtaPartnerIfNeeded(p, s);
  },
  { immediate: false }
);

watch(bookingPerPage, async (newSize) => {
  if (!isReady.value) return;
  localBookingPage.value = 1;
  await fetchTopOtaPartnerIfNeeded(1, Number(newSize ?? 20));
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
  <Head title="Doanh thu | Room Rise" />
  <Layout>
    <div class="page">
      <section class="header">
        <div class="title-row">
          <div>
            <h1 class="title">Doanh thu</h1>
            <p class="subtitle">
              Phân tích chi tiết dòng tiền, nguồn doanh thu và xu hướng tài
              chính
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
            title="Doanh thu"
            :value="statsCard.currentRevenue"
            :trend="statsCard.percentRevenue"
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
            title="RevPAC"
            :value="statsCard.currentRevPAC"
            :trend="statsCard.percentRevPAC"
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
      </VRow>

      <PanelCard title="Xu hướng doanh thu và chỉ số hiệu quả">
        <p class="hint">
          Theo dõi doanh thu (Net), ADR, RevPAR và Occupancy theo thời gian
        </p>
        <TrendNetOccAdrRevPar
          :categories="trendsData.days"
          :net-data="trendsData.revenues"
          :revpac-data="trendsData.RevPACs"
          :adr-data="trendsData.ADRs"
          :revpar-data="trendsData.RevPARs"
          :height="360"
        />
      </PanelCard>
      <PanelCard title="Hiệu quả theo loại phòng">
        <p class="hint">So sánh doanh thu và chỉ số các loại phòng</p>
        <RoomTypeBar
          :categories="room.roomNames"
          :data="room.revenues"
          :height="260"
        />
        <div class="room-grid">
          <div v-for="r in room.cards" :key="r.name" class="room-card">
            <div class="room-name">{{ r.name }}</div>
            <div class="row">
              <span class="lbl">ADR:</span
              ><span class="val">{{ formatCurrencyVND(r.adr) }}</span>
            </div>
            <div class="row">
              <span class="lbl">RevPAR:</span
              ><span class="val">{{ formatCurrencyVND(r.revpar) }}</span>
            </div>
            <div class="row">
              <span class="lbl">Growth:</span>
              <span class="val" :class="r.growth >= 0 ? 'up' : 'down'">
                <Icon
                  :icon="
                    r.growth >= 0
                      ? 'tabler:trending-up'
                      : 'tabler:trending-down'
                  "
                  width="16"
                  height="16"
                />
                {{ Math.abs(r.growth) }}%
              </span>
            </div>
          </div>
        </div>
      </PanelCard>
      <VRow dense>
        <VCol cols="12" lg="12">
          <PanelCard title="Doanh thu theo nguồn" class="source">
            <p class="hint">Phân tích doanh thu Gross vs Net theo kênh</p>

            <div class="chart-split">
              <!-- pie chart -->
              <div class="chart">
                <ChartPie
                  :series="source.rates"
                  :labels="source.otaNames"
                  :revenues="source.revenues"
                  :height="280"
                  legend-position="bottom"
                  :colors="[
                    '#2962FF',
                    '#00C853',
                    '#FFAB00',
                    '#D50000',
                    '#9C27B0',
                  ]"
                  :tooltip-theme="tooltipTheme"
                />
              </div>

              <!-- right meta list -->
              <div class="side left">
                <ul class="meta-list">
                  <li
                    v-for="(label, i) in source.otaNames"
                    :key="label || i"
                    class="meta-item"
                  >
                    <div class="logo-item">
                      <span
                        class="dot"
                        :style="{
                          background:
                            [
                              '#2962FF',
                              '#00C853',
                              '#FFAB00',
                              '#D50000',
                              '#9C27B0',
                            ][i] || '#cbd5e1',
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
                      formatCurrencyVND(
                        source.revenues && source.revenues[i]
                          ? source.revenues[i]
                          : 0
                      )
                    }}</span>
                    <span class="sub right"
                      >{{
                        source.rates && source.rates[i] ? source.rates[i] : 0
                      }}
                      %</span
                    >
                  </li>
                </ul>
              </div>
            </div>
            <!-- <LeadTimeList
              :items="source.lines"
              bar-color="primary"
              track-color="surface-variant"
              :height="10"
            /> -->
          </PanelCard>
        </VCol>

        <!-- <VCol cols="12" lg="6">
          <PanelCard title="Phương thức thanh toán">
            <p class="hint">Phân bố và doanh thu theo hình thức thanh toán</p>
            <LeadTimeList
              :items="paymentType"
              bar-color="primary"
              track-color="surface-variant"
              :height="10"
              :unit="'đ'"
            />
          </PanelCard>
        </VCol> -->
      </VRow>
      <PanelCard title="Phương thức thanh toán">
        <p class="hint">Phân bố và doanh thu theo hình thức thanh toán</p>
        <LeadTimeList
          :items="paymentType"
          bar-color="primary"
          track-color="surface-variant"
          :height="10"
          :unit="'đ'"
        />
      </PanelCard>
      <VRow dense>
        <VCol cols="12" lg="6"> </VCol>
      </VRow>

      <PanelCard title="Xu hướng ADR vs RevPAR">
        <p class="hint">
          So sánh xu hướng ADR và RevPAR (Net) 12 tháng gần nhất
        </p>
        <AdrRevparTrend
          :categories="adrRevparTrend.months"
          :adr-data="adrRevparTrend.adrs"
          :revpar-data="adrRevparTrend.revpars"
          :height="300"
        />
      </PanelCard>

      <PanelCard title="Hiệu suất OTA/Parner">
        <div class="hint">Đánh giá hiệu quả các đối tác và kênh phân phối</div>

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
          :headers="revenueHeaders"
          :items="topOTAPartnerRows"
          item-key="id"
          density="comfortable"
          hover
          :page="localBookingPage"
          :items-per-page="Number(bookingPerPage)"
        >
          <template #item.otaName="{ item }">
            <div class="d-flex align-center gap-2">
              <img
                v-if="item?.otaName && otaLogos[item.otaName.toLowerCase()]"
                :src="otaLogos[item.otaName.toLowerCase()]"
                alt="OTA Logo"
                class="h-6 logo w-auto"
              />
              <span class="mono">{{ item.otaName }}</span>
            </div>
          </template>

          <template #item.currentRevenue="{ item }">
            <span class="prop-strong">{{
              formatCurrencyVND(item.currentRevenue)
            }}</span>
          </template>

          <template #item.percentTotalRevenue="{ item }">
            <div>{{ item.percentTotalRevenue }}%</div>
          </template>

          <template #item.percentGrowthRevenue="{ item }">
            <div>{{ item.percentGrowthRevenue }}%</div>
          </template>

          <template #item.currentBookings="{ item }">
            <span class="prop-strong">{{ item.currentBookings }}</span>
          </template>

          <template #item.percentTotalBookings="{ item }">
            <div>{{ item.percentTotalBookings }}%</div>
          </template>

          <template #item.percentGrowthBookings="{ item }">
            <div>{{ item.percentGrowthBookings }}%</div>
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
/* Page layout */
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
  margin-block-start: 22px;
}

.picker :where(.v-field),
.f-ctrl :where(.v-field),
.filters :deep(.v-field) {
  border-radius: 10px;
}

.picker {
  inline-size: 100%;
}

.picker--date :where(.flatpickr-input) {
  inline-size: 100%;
}

/* KPIs and hints */
.kpi-row {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(4, minmax(220px, 1fr));
}

.hint {
  font-size: 12px;
  margin-block: 0 8px;
  margin-inline: 0;
  opacity: 0.65;
}

/* Room grid/cards */
.room-grid {
  display: grid;
  overflow: hidden auto;
  gap: 10px;
  grid-template-columns: repeat(4, 1fr);
  margin-block-start: 10px;
  max-block-size: 240px;
}

.room-card {
  padding: 10px;
  border: 1px solid;
  border-color: color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  border-radius: 6px;
  background: var(--v-theme-surface);
}

.room-name {
  font-weight: 700;
  margin-block-end: 6px;
}

.room-card .row {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  margin-block: 2px;
  margin-inline: 0;
}

.room-card .up {
  color: #2e7d32;
  font-weight: 700;
}

.room-card .down {
  color: #c62828;
  font-weight: 700;
}

/* small inline mini bar helper */
.mini {
  display: flex;
  align-items: center;
  gap: 8px;
}

.mini-bar {
  position: relative;
  border-radius: 999px;
  background: color-mix(in oklab, var(--v-theme-on-surface) 14%, transparent);
  block-size: 8px;
  inline-size: 120px;
}

.mini-text {
  font-size: 12px;
  opacity: 0.8;
}

/* Responsive kpi grid */
@media (max-width: 1280px) {
  .kpi-row {
    grid-template-columns: repeat(2, minmax(220px, 1fr));
  }

  .split {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .kpi-row {
    grid-template-columns: 1fr;
  }

  .room-grid {
    grid-template-columns: 1fr;
  }
}

/* Chart split and pie/source containers */
.split,
.chart-split {
  display: grid;
  gap: 12px;
  grid-template-columns: 1fr 1.5fr;
}

.pie,
.chart-split .chart {
  inline-size: 100%;
  min-inline-size: 0;
}

.source,
.pie-source {
  block-size: 100%;
}

/* side panel styling used next to pie charts */
.chart-split .side,
.split .side {
  border: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  border-radius: 12px;
  background: color-mix(in oklab, var(--v-theme-primary) 4%, transparent);
  padding-block: 8px;
  padding-inline: 10px;
}

/* Meta lists (used for pie legend/details) */
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

/* legacy / vertical meta item */
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

/* logo item styles inside meta list */
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

/* small colored dot */
.meta-item .dot {
  border-radius: 50%;
  block-size: 10px;
  inline-size: 10px;
  margin-block-start: 6px;
}

/* logo image details */
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

/* Recent table (OTA partner table) */
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

/* small animation */
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

/* Table footer / pagination */
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

/* Status chips */
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

/* Amount highlight */
.amount-highlight {
  color: rgb(var(--v-theme-success));
  font-size: 1.1rem;
  font-weight: 700;
}

/* Alerts (if reused) */
.alerts {
  display: grid;
  gap: 10px;
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
}

.alert-sub {
  color: color-mix(in oklab, var(--v-theme-on-surface) 70%, transparent);
  font-size: 12px;
  line-height: 1.3;
}

:where(.theme--dark) .alert-item {
  background: color-mix(in oklab, var(--v-theme-on-surface) 3%, transparent);
}

:where(.theme--dark) .alert-sub {
  color: rgba(121, 121, 121, 78%) !important;
}

/* Helpers */
.prop-strong {
  font-weight: 700;
}

/* Responsive tweaks */
@media (max-width: 768px) {
  .meta-list-right {
    grid-template-columns: 1fr;
  }

  .split,
  .chart-split {
    grid-template-columns: 1fr;
  }

  .room-grid {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 600px) {
  .v-col-sm-3 {
    flex: 0 0 25%;
    max-width: 15%;
  }
}
</style>
