<script setup>
import { useStoreControlPanel } from "@/stores/useStoreControlPanel";
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from "vue";
import { VSelect } from "vuetify/components";
import RoomUnitFormDialog from "../room-units/RoomUnitFormDialog.vue";

const store = useStoreControlPanel();

const items = computed(() => store.dataByTab.housekeeping || []);
const total = computed(() => store.totalByTab.housekeeping || 0);
const page = computed(() => store.page);
const size = computed(() => store.size);
const loading = computed(() => store.loadingByTab.housekeeping);

const MIN_SPINNER_MS = 600;
const localLoading = ref(false);
let startedAt = 0;
let timer = null;

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

const totalPages = computed(() =>
  Math.max(1, Math.ceil(total.value / size.value))
);

const startIdx = computed(() => (page.value - 1) * size.value + 1);
const endIdx = computed(() => Math.min(page.value * size.value, total.value));

const showingText = computed(() =>
  total.value
    ? `Hiển thị ${startIdx.value}-${endIdx.value} trong ${total.value} phòng`
    : "Không có dữ liệu"
);

function onChangeSize(v) {
  store.setSize(Number(v) || 10);
  store.setPage(1);
  store.fetchTab("housekeeping");
}

const isFormDialogVisible = ref(false);
const selectedData = ref(null);

function toUiStatus({ status }) {
  const s = String(status ?? "")
    .trim()
    .toLowerCase()
    .replace(/_/g, "-");

  if (["dirty", "phòng bẩn", "bẩn"].includes(s)) return "Phòng bẩn";
  if (["in-progress", "working", "đang dọn"].includes(s)) return "Đang dọn";
  if (["blocked", "unavailable", "đóng"].includes(s)) return "Đóng";
  return "Sẵn sàng";
}

async function openEditDialog(room) {
  selectedData.value = {
    id: room.id,
    name: room.name,
    status: toUiStatus(room),
    note: room.notes ?? "",
  };
  await nextTick();
  isFormDialogVisible.value = true;
}

function handleVisible(val) {
  isFormDialogVisible.value = val;
  if (!val) {
    store.fetchTab("housekeeping");
    store.fetchDirtyRooms();
  }
}

onMounted(async () => {
  store.setTab("housekeeping");
  await Promise.all([store.fetchTotals(), store.fetchTab("housekeeping")]);
});

const norm = (v) =>
  String(v ?? "")
    .trim()
    .toLowerCase();

function availabilityColor(v) {
  return v ? "success" : "grey";
}

function statusColor(label) {
  const map = {
    [norm("Sẵn sàng")]: "success",
    [norm("Phòng bẩn")]: "error",
    [norm("Đang dọn")]: "warning",
    [norm("Đóng")]: "grey",
  };
  return map[norm(label)] || "primary";
}
</script>

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
        v-for="room in displayedItems"
        :key="room.id"
        class="room-row"
        flat
      >
        <div class="row-wrap">
          <div>
            <div class="room-type">{{ room.roomType }}</div>
            <div class="room-link">{{ room.name }}</div>
          </div>

          <div class="col-center">
            <VChip
              size="small"
              variant="tonal"
              :color="availabilityColor(room.isAvailable)"
            >
              {{ room.isAvailable ? "Có sẵn" : "Không sẵn" }}
            </VChip>

            <VChip
              size="small"
              class="mt-1"
              :color="statusColor(toUiStatus(room))"
              variant="tonal"
            >
              {{ toUiStatus(room) }}
            </VChip>
          </div>

          <div class="notes">
            {{ room.notes || "-" }}
          </div>

          <div class="actions">
            <VBtn
              icon
              variant="tonal"
              color="primary"
              @click="openEditDialog(room)"
            >
              <VIcon icon="tabler-edit" />
            </VBtn>
          </div>
        </div>
      </VCard>
    </div>

    <div class="tbl-footer">
      <div class="footer-left">
        <VSelect
          class="page-size-select"
          :items="[10, 20, 50]"
          density="comfortable"
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
        :length="totalPages"
        total-visible="5"
        :disabled="localLoading"
        @update:model-value="store.setPage"
      />
    </div>

    <RoomUnitFormDialog
      v-model:is-dialog-visible="isFormDialogVisible"
      v-model:data="selectedData"
      @update:is-dialog-visible="handleVisible"
    />
  </div>
</template>

<style scoped>
.tbl-body {
  min-block-size: 160px;
}

.room-row {
  background: rgb(var(--v-theme-surface));
  border-radius: 0;
  margin-bottom: 2px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 6%);
  transition: transform 0.22s ease, box-shadow 0.22s ease;
}

.room-row:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(8, 20, 48, 8%);
}

.row-wrap {
  display: grid;
  grid-template-columns: 1.6fr 1fr 2fr auto;
  gap: 20px;
  align-items: center;
  padding: 20px;
}

.col-center {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.room-link {
  font-weight: 600;
  color: rgb(var(--v-theme-primary));
}

.room-type {
  opacity: 0.7;
  font-size: 0.9rem;
}

.notes {
  opacity: 0.8;
}

.actions {
  display: flex;
  justify-content: flex-end;
}

.tbl-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  border-top: 1px solid rgba(0, 0, 0, 0.08);
}

.page-size-select {
  width: 140px;
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

@media (hover: hover) and (pointer: fine) {
  .room-row:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(8, 20, 48, 8%);
  }
}
</style>
