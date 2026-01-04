<template>
  <div>
    <div class="tbl-body">
      <VProgressLinear v-if="localLoading" indeterminate />

      <div v-else-if="!displayedItems.length" class="empty-wrap">
        <VIcon icon="tabler-inbox" size="36" class="mb-2" />
        <div>Không có dữ liệu</div>
      </div>

      <VCard
        v-else
        v-for="bk in displayedItems"
        :key="bk.id"
        class="booking-row"
        flat
      >
        <div class="row-wrap">
          <div>
            <div class="from">
              <div class="d-flex align-center gap-2">
                <img
                  v-if="bk.otaName && otaLogos[bk.otaName.toLowerCase()]"
                  :src="otaLogos[bk.otaName.toLowerCase()]"
                  alt="OTA Logo"
                  class="logo"
                />
                <b>{{ bk.otaName || "—" }}</b>
              </div>
            </div>

            <Link :href="route('bookings.show', bk.id)" class="code-link">
              {{ bk.otaReservationCode || bk.id }}
            </Link>
          </div>

          <div class="col-center">
            <div class="line guest-name">
              <VIcon icon="tabler-user-circle" size="16" />
              {{ bk.customerName || "Guest" }}
            </div>

            <div class="line">
              <VIcon icon="tabler-bed" size="16" />
              {{ roomLabel(bk) }}
            </div>
          </div>

          <div class="col-center">
            <div class="stay-flow">
              <VChip color="success" size="small" variant="flat">
                <VIcon
                  icon="tabler-plane-arrival"
                  size="14"
                  class="mr-1"
                ></VIcon>
                {{ formatDate(bk.checkInDate) }}
              </VChip>

              <VIcon icon="tabler-arrow-right" size="18" class="stay-arrow" />

              <VChip color="warning" size="small" variant="flat">
                <VIcon icon="tabler-plane-departure" size="14" class="mr-1" />
                {{ formatDate(bk.checkOutDate) }}
              </VChip>

              <VChip
                color="info"
                size="small"
                variant="flat"
                class="night-chip"
              >
                <VIcon icon="tabler-moon" size="14" class="mr-1"></VIcon>
                {{ bk.nights }} đêm
              </VChip>
            </div>

            <div class="status-wrap">
              <VChip
                :color="statusColor(bk.roomStatus)"
                size="small"
                variant="flat"
                class="text-white status-chip"
              >
                {{ bk.roomStatus || "—" }}
              </VChip>
            </div>
          </div>

          <div class="right">
            <div class="amount">
              <div class="amount-line">
                <span class="muted">Cần thanh toán:</span>
                <b class="text-error">{{ formatVND(bk.remaining) }}</b>
              </div>

              <div class="amount-line">
                <span class="muted">Tổng cộng:</span>
                <b>{{ formatVND(bk.customerPaymentAmount) }}</b>
              </div>
            </div>

            <VBtn
              v-if="tab === 'arrival'"
              variant="tonal"
              color="primary"
              class="action-btn"
              @click="onCheckIn(bk)"
            >
              Nhận phòng
            </VBtn>

            <VBtn
              v-if="tab === 'departure'"
              variant="tonal"
              color="primary"
              class="action-btn"
              @click="onCheckout(bk)"
            >
              Trả phòng
            </VBtn>

            <VBtn
              v-if="tab === 'payment-required' || tab === 'new-booking'"
              variant="tonal"
              color="primary"
              class="action-btn"
              @click="onCheckout(bk)"
            >
              Xem chi tiết
            </VBtn>

            <VBtn
              v-if="tab === 'in-house-guest'"
              variant="tonal"
              color="primary"
              class="action-btn"
              @click="redirectCustomer(bk.customerId)"
            >
              Thông tin khách
            </VBtn>
          </div>
        </div>
      </VCard>
    </div>

    <div class="tbl-footer">
      <div class="footer-left">
        <VSelect
          class="page-size-select"
          :items="[10, 20, 50, 100]"
          density="comfortable"
          variant="outlined"
          hide-details
          label="Số hàng / trang"
          :model-value="size"
          @update:model-value="onChangeSize"
          :disabled="localLoading"
        />

        <div class="muted">{{ showingText }}</div>
      </div>

      <VPagination
        :model-value="page"
        @update:model-value="cp.setPage"
        :length="totalPages"
        total-visible="5"
        :disabled="localLoading"
      />
    </div>
  </div>
</template>

<script setup>
import { useStoreControlPanel } from "@/stores/useStoreControlPanel";
import { otaLogos } from "@/utils/constants";
import { Link } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { VSelect } from "vuetify/components";

const props = defineProps({
  tab: { type: String, required: true },
});

const emit = defineEmits(["checkin", "checkout", "redirectCustomer"]);

const cp = useStoreControlPanel();
const storeKey = computed(() => cp.toStoreKey(props.tab));

onMounted(() => {
  if (cp.tab !== storeKey.value) cp.setTab(props.tab);
  cp.fetchTab(props.tab);
});

const items = computed(() => cp.dataByTab[storeKey.value] || []);
const loading = computed(() => cp.loadingByTab[storeKey.value]);
const total = computed(() => cp.totalByTab[storeKey.value] || 0);
const page = computed(() => cp.page);
const size = computed(() => cp.size);

const totalPages = computed(() =>
  Math.max(1, Math.ceil(total.value / size.value))
);

const startIdx = computed(() => (page.value - 1) * size.value + 1);
const endIdx = computed(() => Math.min(page.value * size.value, total.value));

const showingText = computed(() =>
  total.value
    ? `Hiển thị ${startIdx.value}-${endIdx.value} trong ${total.value} mục`
    : "Không có dữ liệu"
);

const MIN_SPINNER_MS = 600;
const localLoading = ref(false);
let timer = null;
let startedAt = 0;

watch(
  loading,
  (val) => {
    if (val) {
      startedAt = Date.now();
      localLoading.value = true;
      clearTimeout(timer);
    } else {
      const remain = Math.max(0, MIN_SPINNER_MS - (Date.now() - startedAt));
      timer = setTimeout(() => (localLoading.value = false), remain);
    }
  },
  { immediate: true }
);

onBeforeUnmount(() => clearTimeout(timer));

const displayedItems = computed(() => (localLoading.value ? [] : items.value));

function onChangeSize(v) {
  cp.setSize(v);
  cp.setPage(1);
}

function onCheckIn(bk) {
  emit("checkin", bk);
}

function onCheckout(bk) {
  emit("checkout", bk);
}

function redirectCustomer(id) {
  emit("redirectCustomer", id);
}
function roomLabel(bk) {
  return [bk.roomName, bk.roomUnitName].filter(Boolean).join(" • ");
}

function statusColor(st) {
  switch ((st || "").toLowerCase()) {
    case "chưa nhận phòng":
      return "primary";
    case "đang lưu trú":
      return "success";
    case "đã trả phòng":
      return "secondary";
    default:
      return "default";
  }
}

function formatDate(d) {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("vi-VN");
}

function formatVND(v) {
  if (!Number.isFinite(+v)) return "—";
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(v);
}

function viewBooking(id) {
  if (!id) return;
  router.visit(route("bookings.show", { booking: id }));
}
</script>

<style scoped>
.tbl-top {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 8px;
  padding-top: 10px;
}

.page-size-select {
  width: 125px;
  min-width: 125px;
  flex-shrink: 0;
}

.booking-row {
  background: rgb(var(--v-theme-surface));
  border-radius: 0;
  margin-bottom: 2px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 6%);
  transition: transform 0.22s ease, box-shadow 0.22s ease;
}

.booking-row:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(8, 20, 48, 8%);
}

.row-wrap {
  display: grid;
  align-items: center;
  gap: 20px;
  padding: 22px 16px;
  grid-template-columns:
    minmax(160px, 1.2fr)
    minmax(260px, 2fr)
    minmax(260px, 1.8fr)
    minmax(360px, 2.2fr);
}

.col-center {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.code-link {
  font-weight: 700;
  color: rgb(var(--v-theme-primary));
}

.guest-name {
  font-weight: 700;
}

.muted {
  opacity: 0.7;
}

.line {
  display: flex;
  align-items: center;
  gap: 6px;
}

.stay-flow {
  display: flex;
  align-items: center;
  gap: 8px;
}

.stay-arrow {
  opacity: 0.6;
}

.night-chip {
  font-weight: 600;
}

.status-wrap {
  margin-top: 10px;
}

.status-chip {
  font-size: 0.75rem;
  font-weight: 600;
}

.right {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 28px;
}

.amount {
  text-align: right;
  white-space: nowrap;
}

.amount-line {
  display: flex;
  justify-content: flex-end;
  gap: 6px;
}

.action-btn {
  min-width: 130px;
  font-weight: 600;
}

.tbl-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  border-top: 1px solid rgba(0, 0, 0, 0.08);
}

.footer-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.empty-wrap {
  padding: 28px;
  text-align: center;
  opacity: 0.7;
}

.logo {
  inline-size: 2rem;
  block-size: 2rem;
  object-fit: contain;
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 8%);
}

.muted {
  opacity: 0.7;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
