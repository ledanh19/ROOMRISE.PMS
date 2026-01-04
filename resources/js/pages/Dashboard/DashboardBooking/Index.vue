<script setup>
import { Head } from "@inertiajs/vue3";
import Layout from "../../../layouts/blank.vue";

import KpiCard from "@/Components/dashboard/cards/KpiCard.vue";
import PanelCard from "@/Components/dashboard/cards/PanelCard.vue";
import ChartBar from "@/Components/dashboard/charts/ChartBar.vue";
import ChartMixed from "@/Components/dashboard/charts/ChartMixed.vue";
import ChartPie from "@/Components/dashboard/charts/ChartPie.vue";
import LeadTimeList from "@/Components/dashboard/misc/LeadTimeList.vue";
import { useDashboardBooking } from "@/stores/useDashboardBooking";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { useFilterDashboardStore } from "@/stores/useStoreDashboard";
import {
  otaLogos,
  palette,
  QICK_DATE_RANGES,
  TIME_RANGE,
} from "@/utils/constants";
import {
  buildDateRange,
  formatCurrencyVND,
  formatDate,
  getStatusIcon,
  hasFullRange,
  makeRangeKey,
  noteStatsCard,
  statusColor,
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
const exec = useDashboardBooking();

const {
  stats,
  statusSeries,
  statusDays,
  roomTypes,
  roomMeta,
  leadTimeItems,
  bookingRows,
  bookingTotal,
  bookingPage,
} = storeToRefs(exec);

const totalSeries = computed(() => exec.totalSeries);

const isReady = ref(false);
const lastRangeKey = ref("");

const localBookingPage = ref(bookingPage.value ?? 1);
const bookingPerPage = ref(5);
let updatingFromStore = false;

const compactBarOptions = {
  plotOptions: {
    bar: { horizontal: false, columnWidth: "28%", borderRadius: 2 },
  },
};

const lastBookingFetchKey = ref("");
let bookingFetchDebounceTimer = null;
const BOOKING_FETCH_DEBOUNCE_MS = 120;

async function fetchBookingDetailsIfNeeded(pageNum, sizeNum) {
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
        await exec.fetchBookingDetails({ page: p, size: s });
        resolve(true);
      } catch (err) {
        lastBookingFetchKey.value = "";
        resolve(false);
      }
    }, BOOKING_FETCH_DEBOUNCE_MS);
  });
}

async function reloadAll() {
  await exec.fetchStats();
  await exec.fetchStatus();
  await exec.fetchSource();
  await exec.fetchRoom();
  await exec.fetchLeadTime();
  await exec.fetchCustomer();

  lastBookingFetchKey.value = "";
  await fetchBookingDetailsIfNeeded(
    Number(localBookingPage.value ?? 1),
    Number(bookingPerPage.value ?? 20)
  );
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
    exec.filterStore.dateTo = val;
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
    await fetchBookingDetailsIfNeeded(p, s);
  },
  { immediate: false }
);

watch(bookingPerPage, async (newSize) => {
  if (!isReady.value) return;
  localBookingPage.value = 1;
  await fetchBookingDetailsIfNeeded(1, Number(newSize ?? 20));
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
  filterStore.quickdate = val;
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
  <Head title="Đặt phòng | Room Rise" />
  <Layout>
    <div class="dashboard">
      <section class="header">
        <div class="title-row">
          <h1 class="title">Đặt phòng</h1>
        </div>
        <p class="subtitle">
          Phân tích chi tiết trạng thái đặt phòng, hành vi khách hàng và nguồn
          booking
        </p>

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
            title="Booking mới"
            :value="Number(stats.newBookings)"
            :trend="stats.percentNewBookings"
            :note-percent="`Đã check-in: ${stats.checkIn}`"
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
            icon="tabler-users"
            title="Đã check-in"
            :value="stats.checkIn"
            :trend="stats.percentCheckIn"
            :note-percent="`${stats.percentTotalBookings}% tổng booking`"
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
            icon="tabler-cancel"
            title="Hủy"
            :value="Number(stats.cancel)"
            :trend="stats.percentCancellation"
            :note-percent="`Tỉ lệ: ${stats.percentCancellationRate} %`"
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
            class="disable"
            icon="tabler-star"
            title="Đánh giá Trung bình"
            :value="'Đang phát triển'"
            :trend="0"
            :note-percent="'Đang phát triển'"
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

      <PanelCard title="Xu hướng đặt phòng theo trạng thái">
        <p class="panel-subtitle">
          Theo dõi số lượng booking và trạng thái theo thời gian
        </p>
        <ChartMixed
          :series="statusSeries"
          :categories="statusDays"
          :height="340"
          :colors="palette.mixed"
          :stacked="false"
          :tooltip-y-formatter="(v) => v"
          :tooltip-theme="tooltipTheme"
        />
      </PanelCard>
      <PanelCard title="Booking theo loại phòng">
        <p class="panel-subtitle">Hiệu suất các loại phòng</p>
        <div class="chart-columns">
          <div class="chart">
            <ChartBar
              :series="totalSeries"
              :categories="roomTypes"
              :height="280"
              :colors="['#FB8C00']"
              :options="compactBarOptions"
              :tooltip-theme="tooltipTheme"
              :unit="'Đặt phòng'"
            />
          </div>

          <div class="room-grid">
            <div v-for="r in roomMeta" :key="r.roomId" class="room-card">
              <div class="room-name">{{ r.name }}</div>
              <div class="row">
                <span class="lbl">LOS:</span
                ><span class="val">{{ r.los }}</span>
              </div>
              <div class="row">
                <span class="lbl">ADR:</span
                ><span class="val">{{ r.adrStr }}</span>
              </div>
            </div>
          </div>
        </div>
      </PanelCard>

      <PanelCard title="Booking theo nguồn" class="booking-source">
        <p class="panel-subtitle">Phân bố booking theo kênh phân phối</p>
        <div class="chart-split">
          <div class="chart">
            <ChartPie
              :hint="exec.totalSources"
              :series="exec.rateSources"
              :labels="exec.otaNameSources"
              :height="280"
              legend-position="bottom"
              :colors="palette.pie"
              :tooltip-theme="tooltipTheme"
            />
          </div>
          <div class="side left">
            <ul class="meta-list">
              <li
                v-for="(label, i) in exec.otaNameSources"
                :key="label"
                class="meta-item"
              >
                <div class="logo-item">
                  <span
                    class="dot"
                    :style="{
                      background: (palette.pie && palette.pie[i]) || '#cbd5e1',
                    }"
                    aria-hidden="true"
                  ></span>

                  <img
                    v-if="label && otaLogos[label.toLowerCase()]"
                    :src="otaLogos[label.toLowerCase()]"
                    alt="OTA Logo"
                    class="h-6 logo-detail"
                  />
                  <div class="label">{{ label }}</div>
                </div>

                <span class="sub"> {{ exec.totalSources[i] }}</span>
                <span class="sub right">{{ exec.rateSources[i] }} %</span>
              </li>
            </ul>
          </div>
        </div>
      </PanelCard>
      <VRow dense>
        <VCol cols="12" lg="6">
          <PanelCard title="Phân tích khách hàng" class="booking-trend">
            <p class="panel-subtitle">Hành vi đặt phòng và xu hướng</p>
            <VRow dense class="mini-metrics">
              <VCol cols="4">
                <div class="mm-title">Lead time trung bình</div>
                <div class="mm-val">
                  {{ exec.customer?.avgLeadTime ?? "-" }}
                </div>
              </VCol>
              <VCol cols="4">
                <div class="mm-title">LOS trung bình</div>
                <div class="mm-val">{{ exec.customer?.avgLOS ?? "-" }} đêm</div>
              </VCol>
              <VCol cols="4">
                <div class="mm-title">ADR trung bình</div>
                <div class="mm-val">
                  {{ formatCurrencyVND(exec.customer?.avgADR ?? 0) }}
                </div>
              </VCol>
            </VRow>
            <hr
              style="
                border: none;
                border-block-start: 1px solid #ddd;
                margin-block: 12px;
                margin-inline: 0;
              "
            />

            <LeadTimeList
              :items="[
                {
                  label: 'Khách mới',
                  value: null,
                  percent: exec.customer?.newCustomerRate ?? 0,
                  color: 'primary',
                },
                {
                  label: 'Khách quay lại',
                  value: null,
                  percent: exec.customer?.returningCustomerRate ?? 0,
                  color: 'success',
                },
              ]"
              unit="%"
              :compute-percent="false"
              bar-color="primary"
              track-color="surface-variant"
              :height="10"
            />
          </PanelCard>
        </VCol>

        <VCol cols="12" lg="6">
          <PanelCard title="Phân bố thời gian đặt trước">
            <div class="panel-subtitle">Thời gian đặt trước của khách</div>
            <LeadTimeList
              :items="leadTimeItems"
              unit="bookings"
              :compute-percent="false"
              bar-color="primary"
              track-color="surface-variant"
              :height="10"
            />
          </PanelCard>
        </VCol>
      </VRow>

      <PanelCard title="Chi tiết đặt phòng gần đây">
        <div class="panel-subtitle">
          Danh sách booking mới nhất với thông tin đầy đủ
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
          :headers="bookingHeaders"
          :items="bookingRows"
          item-key="id"
          density="comfortable"
          hover
          :page="localBookingPage"
          :items-per-page="Number(bookingPerPage)"
        >
          <template #item.code="{ item }">
            <span class="mono">{{ item.code }}</span>
          </template>

          <template #item.property="{ item }">
            <span>{{ item.property }}</span>
          </template>

          <template #item.otaName="{ item }">
            <div class="d-flex align-center gap-2">
              <img
                v-if="item?.otaName && otaLogos[item.otaName.toLowerCase()]"
                :src="otaLogos[item.otaName.toLowerCase()]"
                alt="OTA Logo"
                class="h-6 logo w-auto"
              />
              <span class="font-weight-medium">{{ item.otaName }}</span>
            </div>
          </template>

          <template #item.room="{ item }">
            <div>
              <VIcon
                icon="tabler-bed"
                size="14"
                class="mr-1 text-primary"
              ></VIcon
              >{{ item.room }}
            </div>
          </template>

          <template #item.customer="{ item }">
            <div>{{ item.customer }}</div>
          </template>

          <template #item.checkInDateShort="{ item }">
            <VChip
              color="success"
              variant="flat"
              size="small"
              class="status-chip"
            >
              <VIcon icon="tabler-plane-arrival" size="14" class="mr-1"></VIcon>
              {{ item.checkInDateShort }}
            </VChip>
          </template>

          <template #item.checkOutDateShort="{ item }">
            <VChip
              color="warning"
              variant="flat"
              size="small"
              class="status-chip"
            >
              <VIcon icon="tabler-plane-departure" size="14" class="mr-1" />
              {{ item.checkOutDateShort }}
            </VChip>
          </template>

          <template #item.nights="{ item }">
            <VChip color="info" variant="flat" size="small" class="status-chip">
              <VIcon icon="tabler-moon" size="14" class="mr-1"></VIcon>
              {{ item.nights }} đêm
            </VChip>
          </template>

          <template #item.leadTime="{ item }">
            <span class="muted">{{ item.leadTime }} ngày</span>
          </template>

          <template #item.amount="{ item }">
            <span class="prop-strong font-weight-bold amount-highlight">{{
              formatCurrencyVND(item.amount)
            }}</span>
          </template>

          <template #item.bookingStatus="{ item }">
            <VChip
              :color="statusColor(item.bookingStatus)"
              variant="flat"
              class="text-white status-chip"
              size="small"
            >
              <VIcon
                :icon="getStatusIcon(item.bookingStatus)"
                size="14"
                class="mr-1"
              ></VIcon>
              {{ item.bookingStatus }}
            </VChip>
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

.filters {
  display: grid;
}

.f-ctrl :where(.v-field) {
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

.panel-subtitle {
  font-size: 12px;
  margin-block: 2px 8px;
  opacity: 0.6;
}

.chart-split {
  display: grid;

  /* align-items: center; */
  gap: 12px;
  grid-template-columns: 1fr 1.5fr;
}

.chart-columns {
  display: grid;
  align-items: center;
  grid-template-columns: 1fr;
}

.chart-columns .chart {
  height: 290px;
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

/* -------- meta-list & items (CHANGED) -------- */
.meta-list,
.meta-list-right {
  display: grid;
  padding: 0;
  margin: 0;
  gap: 10px;
  list-style: none;
}

/* desktop: 2 items per row; mobile -> 1 column */
.meta-list {
  grid-template-columns: repeat(1, 1fr);
}

@media (max-width: 768px) {
  .meta-list {
    grid-template-columns: 1fr;
  }
}

.meta-list-right {
  overflow: hidden auto;
  gap: 20px;
  grid-template-columns: repeat(4, 1fr);
  max-block-size: 260px;
  padding-inline-end: 6px;
}

/* each li: two columns layout (logo-area | text-area) and content centered */
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
  justify-self: center;
  white-space: nowrap;
}

/* logo area stacks logo above label (centered) */
.logo-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  text-align: start;
}

/* label inside logo-item */
.logo-item .label {
  font-size: 0.95rem;
  font-weight: 600;
  line-height: 1.2;
}

/* sub text (commission etc.) aligned center, smaller */
.meta-item .sub {
  font-size: 12px;
  opacity: 0.75;
  text-align: center;
}

.sub.right {
  text-align: end;
}

/* If you want the text area to keep left-aligned while logo is centered,
   change justify-items to start on the text cell using this rule: */
.meta-item > :nth-child(2) {
  display: flex;
  flex-direction: column;
  align-items: center; /* change to 'flex-start' to left-align */
  justify-content: center;
  text-align: center;
}

/* legacy multi-column variant preserved but not used by default */
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

/* small colored dot (if used) */
.meta-item .dot {
  border-radius: 50%;
  block-size: 10px;
  inline-size: 10px;
  margin-block-start: 6px;
}

/* fallback label styles */
.meta-item .label {
  font-weight: 600;
  line-height: 1.2;
}

.meta-item .label.strong {
  font-weight: 700;
}

/* -------- rest of table/list styles (kept) -------- */
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

.field {
  display: grid;
  gap: 4px;
}

.filter {
  margin-block-start: 27px;
}

.booking-trend,
.booking-source {
  block-size: 100%;
}

@media (min-width: 600px) {
  .v-col-sm-3 {
    flex: 0 0 25%;
    max-inline-size: 15%;
  }
}

.filters :deep(.v-field) {
  border-radius: 10px;
}

.mm-title {
  font-size: 13px !important;
  font-weight: 600;
  opacity: 1 !important;
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

.logo-detail {
  border-radius: 50%;
  block-size: 30px;
  inline-size: 30px;
  object-fit: cover;
}

.disable {
  background-color: darkgray;
}

/* Room grid/cards */
.room-grid {
  display: grid;
  overflow: hidden auto;
  gap: 10px;
  grid-template-columns: repeat(4, 1fr);
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

.vue-apexcharts {
  min-height: 0px;
}
</style>
