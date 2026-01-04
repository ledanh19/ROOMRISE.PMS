<template>
  <Head title="Bảng điều khiển | Room Rise" />
  <Layout>
    <div class="toolbar d-flex align-center flex-wrap gap-2 mb-4">
      <div>Chọn ngày:</div>
      <div class="date-picker-inline">
        <VSelect
          class="f-ctrl"
          label="Ngày"
          placeholder="Chọn ngày"
          v-model="timeRange"
          :items="SHORT_TIME_RANGES"
          density="comfortable"
          hide-details
        />
      </div>

      <VBtn
        variant="tonal"
        size="small"
        density="compact"
        class="btn-sm"
        @click="goToBooking"
      >
        Quản lý đặt phòng
        <ExternalArrowIcon :size="16" class="ml-1" />
      </VBtn>

      <VBtn
        variant="tonal"
        size="small"
        density="compact"
        class="btn-sm"
        @click="openSearchModal"
      >
        <VIcon icon="tabler-search" size="18" class="mr-1" />
        Tìm kiếm đặt phòng
      </VBtn>

      <VBtn
        color="primary"
        prepend-icon="tabler-plus"
        size="small"
        density="compact"
        class="btn-sm"
        @click="createNewBooking"
      >
        Tạo đặt phòng mới
      </VBtn>
    </div>

    <VRow dense class="mb-4">
      <VCol cols="12" md="6" class="d-flex">
        <VCard class="pa-2 flex-grow-1">
          <VRow dense>
            <VCol cols="12" md="3">
              <StatMini
                title="Nhận phòng"
                :value="cp.stats?.kpis?.arrival?.current"
                :trend="cp.stats?.kpis?.arrival?.diff >= 0 ? 'up' : 'down'"
                :trend-text="
                  convertNegativeNumberToPositiveNumber(
                    cp.stats?.kpis?.arrival?.diff
                  )
                "
                icon="tabler-door-enter"
              />
            </VCol>
            <VCol cols="12" md="3">
              <StatMini
                title="Đang lưu trú"
                :value="cp.stats?.kpis?.inhouseGuest?.current"
                :trend="cp.stats?.kpis?.inhouseGuest?.diff >= 0 ? 'up' : 'down'"
                :trend-text="
                  convertNegativeNumberToPositiveNumber(
                    cp.stats?.kpis?.inhouseGuest?.diff
                  )
                "
                icon="tabler-bed"
              />
            </VCol>
            <VCol cols="12" md="3">
              <StatMini
                title="Trả phòng"
                :value="cp.stats?.kpis?.departure?.current"
                :trend="cp.stats?.kpis?.departure?.diff >= 0 ? 'up' : 'down'"
                :trend-text="
                  convertNegativeNumberToPositiveNumber(
                    cp.stats?.kpis?.departure?.diff
                  )
                "
                icon="tabler-door-exit"
              />
            </VCol>
            <VCol cols="12" md="3">
              <StatMini
                title="Mới"
                :value="cp.stats?.kpis?.newBooking?.current"
                :trend="cp.stats?.kpis?.newBooking?.diff >= 0 ? 'up' : 'down'"
                :trend-text="
                  convertNegativeNumberToPositiveNumber(
                    cp.stats?.kpis?.newBooking?.diff
                  )
                "
                icon="tabler-calendar-plus"
              />
            </VCol>
          </VRow>
        </VCard>
      </VCol>

      <VCol cols="12" md="4" class="d-flex">
        <VCard class="pa-2 flex-grow-1">
          <VRow dense>
            <VCol cols="12" md="6">
              <StatMini
                title="Cần thanh toán"
                :value="cp.stats?.payments?.required?.count"
                :currency="formatCurrencyVND(cp.stats?.payments?.required?.sum)"
                tone="error"
                icon="tabler-cash"
              />
            </VCol>
            <VCol cols="12" md="6">
              <StatMini
                title="Quá hạn"
                :value="cp.stats?.payments?.overdue?.count"
                :currency="formatCurrencyVND(cp.stats?.payments?.overdue?.sum)"
                tone="error"
                icon="tabler-alert-triangle"
              />
            </VCol>
          </VRow>
        </VCard>
      </VCol>

      <VCol cols="12" md="2" class="d-flex">
        <VCard class="pa-2 flex-grow-1">
          <StatMini
            title="Phòng bẩn"
            :value="dirtyRoomsText"
            icon="tabler-home-exclamation"
          />
        </VCard>
      </VCol>
    </VRow>

    <VTabs
      v-model="tab"
      color="primary"
      density="comfortable"
      align-tabs="start"
      class="mb-1"
    >
      <VTab value="arrival">Nhận phòng ({{ tabCounts.arrival }})</VTab>
      <VTab value="in-house-guest">Đang lưu trú ({{ tabCounts.inhouse }})</VTab>
      <VTab value="departure">Trả phòng ({{ tabCounts.departure }})</VTab>
      <VTab value="new-booking">Đặt phòng mới ({{ tabCounts.new }})</VTab>
      <VTab value="payment-required"
        >Cần thanh toán ({{ tabCounts.payment }})</VTab
      >
      <VTab value="housekeeping">Dọn phòng ({{ tabCounts.housekeeping }})</VTab>
    </VTabs>

    <div class="filters-bar mb-3 mt-5">
      <VSelect
        v-if="!isHousekeeping"
        v-model="selectedSort"
        :items="sortOptions"
        item-title="title"
        item-value="value"
        label="Sắp xếp theo"
        density="compact"
        hide-details
        class="filter-field"
        :menu-props="{ maxHeight: 300 }"
        clearable
      />

      <VSelect
        v-if="isHousekeeping"
        v-model="selectedRoomSort"
        :items="sortRoomOptions"
        item-title="title"
        item-value="value"
        label="Sắp xếp theo"
        density="compact"
        hide-details
        class="filter-field"
        :menu-props="{ maxHeight: 300 }"
        clearable
      />

      <VSelect
        v-if="isHousekeeping"
        v-model="selectedRoomStatus"
        :items="roomStatusOptions"
        item-title="title"
        item-value="value"
        label="Trạng thái phòng"
        density="compact"
        hide-details
        clearable
        class="filter-field ml-3"
        :menu-props="{ maxHeight: 300 }"
      />

      <template v-else>
        <VSelect
          v-model="selectedStatus"
          :items="statusOptionsSelect"
          label="Trạng thái đặt phòng"
          density="compact"
          hide-details
          clearable
          class="filter-field ml-3"
          :menu-props="{ maxHeight: 300 }"
        />
        <VSelect
          v-model="selectedChannel"
          :items="otaChannelsSelect"
          label="Kênh đặt phòng"
          density="compact"
          hide-details
          clearable
          class="filter-field ml-3"
          :menu-props="{ maxHeight: 300 }"
        />
      </template>
    </div>

    <VWindow v-model="tab">
      <VWindowItem value="arrival">
        <ControlPanelList tab="arrival" @checkin="openCheckIn" />
      </VWindowItem>
      <VWindowItem value="in-house-guest">
        <ControlPanelList
          tab="in-house-guest"
          @redirectCustomer="redirectCustomer"
        />
      </VWindowItem>
      <VWindowItem value="departure">
        <ControlPanelList tab="departure" @checkout="openCheckout" />
      </VWindowItem>
      <VWindowItem value="new-booking">
        <ControlPanelList tab="new-booking" />
      </VWindowItem>
      <VWindowItem value="payment-required">
        <ControlPanelList tab="payment-required" />
      </VWindowItem>
      <VWindowItem value="housekeeping">
        <HousekeepingList tab="housekeeping" />
      </VWindowItem>
    </VWindow>

    <BookingFormDialog v-model:is-dialog-visible="isFormDialogVisible" />

    <SearchResultsDialog
      v-model="searchDialog"
      :items="searchResults"
      :loading="searchLoading"
      :error="searchError"
    />

    <UpdatePayment
      v-model:is-pay-dialog-visible="isCheckOutDialogVisible"
      :booking="selectedBooking"
      :checkout="checkoutMode"
      @update:Payment="onPaymentUpdated"
    />

    <CustomerCheckIn
      v-model:is-check-in-dialog-visible="isCheckInDialogVisible"
      :booking="bookingForCheckIn"
      :customer="customerForCheckIn"
      :room-id="bookingRoomId"
      @update:customerCheckIn="onArrivalUpdated"
    />
  </Layout>
</template>

<script setup>
import BookingFormDialog from "@/Components/bookings/BookingFormDialog.vue";
import ControlPanelList from "@/Components/control-panel/ControlPanelList.vue";
import ExternalArrowIcon from "@/Components/control-panel/ExternalArrowIcon.vue";
import HousekeepingList from "@/Components/control-panel/HousekeepingList.vue";
import SearchResultsDialog from "@/Components/control-panel/SearchResultsDialog.vue";
import StatMini from "@/Components/control-panel/StatMini.vue";
import Layout from "@/layouts/blank.vue";
import { useCustomerStore } from "@/stores/useCustomerStore";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { useStoreControlPanel } from "@/stores/useStoreControlPanel";
import { Head } from "@inertiajs/vue3";
import { VSelect } from "vuetify/components";

import { searchBookings } from "@/utils/api";
import {
  ALL_OPTION,
  otaChannels,
  ROOM_STATUS,
  SHORT_TIME_RANGES,
  sortOptions,
  sortRoomOptions,
  statusOptions,
} from "@/utils/constants";
import {
  convertNegativeNumberToPositiveNumber,
  formatCurrencyVND,
  formatDate,
  toDateInputLike,
} from "@/utils/helper";
import { router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

import CustomerCheckIn from "../Bookings/CustomerCheckIn.vue";
import UpdatePayment from "../Bookings/UpdatePayment.vue";

const DEFAULT_SORT = { field: "updated_at", order: "DESC" };

const tab = ref("arrival");
const dateRange = ref("");

const bookingRoomId = ref(null);
const selectedStatus = ref(null);
const selectedChannel = ref(null);
const selectedRoomStatus = ref(null);

const isFormDialogVisible = ref(false);
const isCheckInDialogVisible = ref(false);

const searchDialog = ref(false);
const searchLoading = ref(false);
const searchError = ref(null);
const searchResults = ref([]);

const isCheckOutDialogVisible = ref(false);
const selectedBooking = ref(null);
const checkoutMode = ref("checkout");

const selectedSort = ref(DEFAULT_SORT);
const selectedRoomSort = ref(DEFAULT_SORT);

const currentBooking = ref(null);
const customerForCheckIn = ref(null);

const timeRange = ref(SHORT_TIME_RANGES[1].value);

const cp = useStoreControlPanel();
const propertyStore = usePropertyStore();
const customerStore = useCustomerStore();

const isHousekeeping = computed(() => tab.value === "housekeeping");

const tabCounts = computed(() => ({
  departure: cp.totalByTab?.departure ?? 0,
  arrival: cp.totalByTab?.arrival ?? 0,
  inhouse: cp.totalByTab?.inhouse ?? 0,
  new: cp.totalByTab?.new ?? 0,
  payment: cp.totalByTab?.payment ?? 0,
  housekeeping: cp.totalByTab?.housekeeping ?? 0,
}));

const startISO = ref(formatDate(new Date()));
const endISO = ref(formatDate(new Date()));

const propertyId = computed(() => {
  const sel = propertyStore.selectedProperty;
  return sel?.id ?? sel ?? null;
});

const roomStatusOptions = [
  ALL_OPTION,
  ...ROOM_STATUS.map((s) => ({ title: s, value: s })),
];
const statusOptionsSelect = [ALL_OPTION, ...statusOptions];
const otaChannelsSelect = [ALL_OPTION, ...otaChannels];
const dirtyRoomsText = computed(() => {
  const d = cp.stats?.dirtyRooms?.dirty ?? 0;
  const t = cp.stats?.dirtyRooms?.total ?? 0;
  return `${d} / ${t}`;
});

const bookingForCheckIn = computed(() =>
  currentBooking.value ? adaptBookingForCheckIn(currentBooking.value) : null
);

function debounce(fn, ms = 300) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
}

function currentTimeRange() {
  const a = startISO.value;
  const b = endISO.value;
  return a && b ? `${a} to ${b}` : "";
}

function syncFilters() {
  cp.setFilters({
    bookingStatus: selectedStatus.value || null,
    otaName: selectedChannel.value || null,
    property: propertyId.value,
  });
  cp.setTimeRange(startISO.value, endISO.value);
}

function refetch(withTotals = false) {
  cp.setTab(tab.value);
  cp.fetchTab(tab.value);
  if (withTotals) {
    cp.fetchTotals({ property: propertyId.value });
    cp.fetchStatics({
      property: propertyId.value,
      timeRange: currentTimeRange(),
    });
  }
}

function adaptBookingForPayDialog(src = {}) {
  const num = (v, d = 0) => {
    const n = Number(v);
    return Number.isFinite(n) ? n : d;
  };

  const remaining = num(src.remaining, 0);
  const customerPaymentAmount = num(src.customerPaymentAmount, remaining);
  const payment_status = remaining <= 0 ? "Đã thanh toán" : "Chưa thanh toán";

  return {
    id: src.id,
    ota_reservation_code: src.otaReservationCode,
    ota_name: src.otaName,
    customer_avatar: src.customerAvatar,
    customer_name: src.customerName,
    check_in_date: src.checkInDate,
    check_out_date: src.checkOutDate,
    room_name: src.roomName,
    room_unit_name: src.roomUnitName,
    room_status: src.roomStatus,
    customer_payment_amount: customerPaymentAmount,
    remaining,
    paid: num(src.paid, 0),
    payment_method: src.paymentMethod || "-",
    payment_status,
  };
}

function adaptCustomerForCheckIn(customer) {
  const c = customer || {};
  return {
    id: c.id ?? null,
    user_id: c.id ?? null,
    full_name: c.fullName ?? "",
    email: c.email ?? "",
    phone: c.phone ?? "",
    dob: toDateInputLike(c.dob),
    note: "",
    address: c.address ?? "",
    city: c.city ?? "",
    country: c.country ?? "",
    nationality: c.nationality ?? "",
    id_number: c.idNumber ?? "",
    issue_date: toDateInputLike(c.issueDate),
    image: "",
  };
}

function adaptBookingForCheckIn(src = {}) {
  const checkIn = src.checkInDate ?? src.check_in_date ?? src.checkinDate;
  const checkOut = src.checkOutDate ?? src.check_out_date ?? src.checkoutDate;

  return {
    id: src.id ?? src.bookingId ?? src.booking_id,
    customer_name: src.customerName ?? src.customer_name ?? "",
    ota_name: src.otaName ?? src.ota_name ?? "",
    check_in_date: toDateInputLike(checkIn),
    check_out_date: toDateInputLike(checkOut),
    room_name: src.roomName ?? src.room_name ?? "",
    room_unit_name: src.roomUnitName ?? src.room_unit_name ?? "",
    room_status: src.roomStatus ?? src.room_status ?? "",
    room_id:
      src.roomId ?? src.room_id ?? src.roomUnitId ?? src.room_unit_id ?? null,
    raw: src,
  };
}

const debouncedRefetchWithTotals = debounce(() => refetch(true), 300);
const debouncedRefetchListOnly = debounce(() => refetch(false), 300);

async function runSearch(text) {
  try {
    searchLoading.value = true;
    searchError.value = null;

    const res = await searchBookings({
      textSearch: text,
      property: propertyId.value,
    });

    const root = res?.data ?? res;
    const d = root?.data ?? root ?? {};
    const list = Array.isArray(d.result) ? d.result : [];

    searchResults.value = list;
    searchDialog.value = true;
  } catch (e) {
    searchError.value = e?.message || "Search failed";
    searchDialog.value = true;
  } finally {
    searchLoading.value = false;
  }
}

function createNewBooking() {
  isFormDialogVisible.value = true;
}

function goToBooking() {
  router.visit(route("bookings.list"));
}

function openCheckout(booking) {
  selectedBooking.value = adaptBookingForPayDialog(booking);
  checkoutMode.value = "checkout";
  isCheckOutDialogVisible.value = true;
}

function onPaymentUpdated() {
  cp.fetchTab(tab.value);
  cp.fetchTotals({ property: propertyId.value });
}

function onArrivalUpdated() {
  cp.fetchTab("arrival");
  cp.fetchTotals({ property: propertyId.value });
}

async function openCheckIn(booking) {
  bookingRoomId.value =
    booking.bookingRoomId || booking.roomUnitId || booking.roomId || null;

  try {
    const customer = await customerStore.fetchDetail(booking.customerId);
    customerForCheckIn.value = adaptCustomerForCheckIn(customer);
  } catch (err) {
    customerForCheckIn.value = adaptCustomerForCheckIn(null);
  }

  currentBooking.value = booking;
  isCheckInDialogVisible.value = true;
}

function redirectCustomer(id) {
  router.visit(route("customers.show", { id }));
}

watch(tab, () => {
  cp.setPage(1);
  refetch(true);
});

watch(
  propertyId,
  () => {
    syncFilters();
    cp.setPage(1);
    refetch(true);
    cp.fetchDirtyRooms({ property: propertyId.value });
  },
  { immediate: true }
);

watch([selectedStatus, selectedChannel], () => {
  syncFilters();
  cp.setPage(1);
  debouncedRefetchWithTotals();
});

watch([startISO, endISO], () => {
  syncFilters();
  cp.setPage(1);
  cp.fetchStatics({
    property: propertyId.value,
    timeRange: currentTimeRange(),
  });
  cp.fetchTotals({ property: propertyId.value });
  debouncedRefetchListOnly();
});

watch([() => cp.page, () => cp.size], () => {
  debouncedRefetchListOnly();
});

/* sync sort */
selectedSort.value =
  sortOptions.find(
    (o) => o.value.field === cp.sortField && o.value.order === cp.sortOrder
  )?.value || DEFAULT_SORT;

selectedRoomSort.value =
  sortRoomOptions.find(
    (o) => o.value.field === cp.sortFieldRoom && o.value.order === cp.sortOrder
  )?.value || DEFAULT_SORT;

watch(selectedSort, (val) => {
  const next = val ?? DEFAULT_SORT;
  if (val == null) selectedSort.value = DEFAULT_SORT;
  cp.setSort(next.field, next.order);
  cp.setPage(1);
  cp.fetchTab(tab.value);
});

watch(selectedRoomSort, (val) => {
  const next = val ?? DEFAULT_SORT;
  if (val == null) selectedRoomSort.value = DEFAULT_SORT;
  cp.setSort(next.field, next.order, isHousekeeping.value);
  cp.setPage(1);
  cp.fetchTab(tab.value);
});

/* housekeeping room status filter */
watch(selectedRoomStatus, (val) => {
  if (!isHousekeeping.value) return;
  cp.setFilters({ roomStatus: val || null });
  cp.setPage(1);
  cp.fetchTab("housekeeping");
});

watch(isHousekeeping, (isHK) => {
  if (!isHK) {
    selectedRoomStatus.value = null;
    cp.setFilters({ roomStatus: null });
  }
});

function openSearchModal() {
  searchError.value = null;
  searchResults.value = [];
  searchLoading.value = false;
  searchDialog.value = true;
}

watch(timeRange, async (val) => {
  const day = new Date();
  day.setDate(day.getDate() + val);
  startISO.value = formatDate(day);
  endISO.value = formatDate(day);
});
</script>

<style scoped>
.filters-bar {
  display: flex;
  grid-auto-flow: column;
}

.filter-field {
  max-inline-size: 260px;
  min-inline-size: 120px;
}

@media (max-width: 960px) {
  .sort-btn {
    justify-content: flex-start;
    inline-size: 100%;
  }

  .filter-field {
    max-inline-size: 100%;
    min-inline-size: 100%;
  }
}

.search-input {
  max-inline-size: 180px;
}

.search-input :is(input) {
  min-inline-size: 0;
}

.btn-refresh,
.btn-refresh .refresh-icon {
  color: inherit;
}

.refresh-gap {
  display: inline-flex;
  align-items: center;
  margin-inline-end: 6px;
}

.btn-sm {
  --btn-pad-y: 6px;
  --btn-pad-x: 10px;

  min-block-size: 30px;
  padding-block: var(--btn-pad-y) !important;
  padding-inline: var(--btn-pad-x) !important;
}

.date-picker-inline {
  inline-size: clamp(230px, 22vw, 230px);
}
</style>

<style>
.date-picker-inline .flatpickr-input[readonly] {
  border: 1px solid var(--v-theme-outline);
  border-radius: 10px;
  background-color: var(--v-theme-surface);
  color: var(--v-theme-on-surface);
  cursor: pointer;
  font-size: 0.9rem;
  inline-size: 100%;
  padding-block: 8px;
  padding-inline: 36px 12px;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.date-picker-inline .flatpickr-input[readonly]:focus {
  border-color: var(--v-theme-primary);
  box-shadow: 0 0 0 3px
    color-mix(in oklab, var(--v-theme-primary) 25%, transparent);
}

.date-picker-inline .flatpickr-calendar {
  border: 1px solid var(--v-theme-outline-variant);
  border-radius: 10px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 10%);
}

.date-picker-inline .flatpickr-months {
  background: var(--v-theme-surface-variant);
  border-block-end: 1px solid var(--v-theme-outline-variant);
  padding-block: 6px;
}

.date-picker-inline .flatpickr-day {
  border-radius: 8px;
  margin: 2px;
}

.date-picker-inline .flatpickr-day.selected,
.date-picker-inline .flatpickr-day.startRange,
.date-picker-inline .flatpickr-day.endRange {
  background: var(--v-theme-primary);
  color: var(--v-theme-on-primary);
}

.date-picker-inline .flatpickr-day.inRange {
  background: color-mix(in oklab, var(--v-theme-primary) 15%, transparent);
  color: var(--v-theme-on-surface);
}

.date-picker-inline .flatpickr-day:hover {
  background: color-mix(in oklab, var(--v-theme-primary) 20%, transparent);
}

.v-theme--dark .date-picker-inline .flatpickr-input[readonly] {
  border-color: color-mix(in oklab, var(--v-theme-outline) 60%, transparent);
  background-color: var(--v-theme-surface);
}

.v-theme--dark .date-picker-inline .flatpickr-calendar {
  background: var(--v-theme-surface);
}

@media (max-width: 640px) {
  .date-picker-inline {
    inline-size: 100%;
  }
}

.v-btn.v-btn--density-compact {
  height: calc(var(--v-btn-height) + 8px);
}

body .v-btn:not(.v-btn--icon).v-btn--size-small {
  border-radius: 10px;
}

.v-field.v-field--appended {
  border-radius: 10px !important;
}

.gap-2 {
  gap: 0.5rem !important;
}

.v-input.v-input--density-compact .v-field .v-field__input {
  line-height: 2.375rem;
}
</style>
