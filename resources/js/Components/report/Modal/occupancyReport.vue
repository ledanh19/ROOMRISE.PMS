<script setup>
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { useReportManager } from "@/stores/useReportManager";
import { headerOccupancyReport } from "@/utils/headerReport";
import { storeToRefs } from "pinia";
import { onMounted, ref, watch } from "vue";
import TableReport from "../Item/tableReport.vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo công suất phòng" },
});

const report = useReportManager();
const propertyStore = usePropertyStore();

report.switchTab("occupancy-report", { reset: false, fetchNow: false });
const { active } = storeToRefs(report);

// ========= Filters =========
const date = ref({ from: "", to: "" });
const filters = ref({
  roomIds: [], // mảng value (id phòng)
});
const roomOptions = ref([]); // [{value, label}]

// Lấy value property hiện tại
function getSelectedPropertyValue() {
  const v = propertyStore.selectedProperty?.value;
  if (v == null || v === "null") return "";
  if (typeof v === "object" && v.value != null) return v.value;
  return v || "";
}

// Tạo chuỗi timeRange "YYYY-MM-DD~YYYY-MM-DD"
function makeTimeRange() {
  const f = (date.value.from || "").slice(0, 10);
  const t = (date.value.to || "").slice(0, 10);
  return f && t ? `${f} to ${t}` : "";
}

async function loadRoomOptions() {
  // dựa trên hàm bạn cung cấp trong store
  report.replaceFilters(
    { property: getSelectedPropertyValue() },
    "occupancy-report"
  );
  const list = await report.buildSelectRoom(); // trả về [{value, label}]
  roomOptions.value = Array.isArray(list) ? list : [];
}

async function onSearch() {
  report.replaceFilters(
    {
      timeRange: makeTimeRange(),
      property: getSelectedPropertyValue(),
    },
    "occupancy-report"
  );
  // store.fetch() đọc t.roomIds → set trực tiếp vào tab
  report.tabs["occupancy-report"].roomIds = filters.value.roomIds || [];

  report.setPage(1, "occupancy-report");
  await report.fetch("occupancy-report");
}

async function onClear() {
  date.value = { from: "", to: "" };
  filters.value = { roomIds: [] };

  report.replaceFilters(
    {
      timeRange: "",
      property: getSelectedPropertyValue(),
    },
    "occupancy-report"
  );
  report.tabs["occupancy-report"].roomIds = [];

  report.setPage(1, "occupancy-report");
  await report.fetch("occupancy-report");
}

function onUpdatePage(p) {
  report.setPage(p, "occupancy-report");
  report.fetch("occupancy-report");
}
function onUpdateSize(s) {
  report.setSize(s, "occupancy-report");
  report.fetch("occupancy-report");
}

onMounted(async () => {
  await loadRoomOptions();
  await onSearch();
});

watch(
  () => propertyStore.selectedProperty?.value,
  async () => {
    await loadRoomOptions();
  }
);
</script>

<template>
  <VCard class="report-card">
    <div class="toolbar">
      <h2 class="title">{{ props.title }}</h2>
      <div class="actions">
        <VBtn
          variant="outlined"
          class="reset-btn mr-2"
          color="error"
          :loading="active.loading"
          :disabled="active.loading"
          @click="onClear"
        >
          <RefreshIcon :size="18" class="refresh-icon" />

          Xoá bộ lọc
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-search" @click="onSearch"
          >Xem</VBtn
        >
      </div>
    </div>

    <div class="filters-bar">
      <div class="field">
        <div class="label">Từ ngày</div>
        <AppDateTimePicker
          v-model="date.from"
          :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
          placeholder="YYYY-MM-DD"
          density="comfortable"
        />
      </div>

      <div class="field">
        <div class="label">Đến ngày</div>
        <AppDateTimePicker
          v-model="date.to"
          :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
          placeholder="YYYY-MM-DD"
          density="comfortable"
        />
      </div>

      <div class="field">
        <div class="label">Phòng</div>
        <AppSelect
          v-model="filters.roomIds"
          :items="roomOptions"
          item-title="label"
          item-value="value"
          multiple
          chips
          closable-chips
          clearable
          placeholder="Chọn nhiều phòng"
          density="comfortable"
        />
      </div>
    </div>

    <TableReport
      :headers="headerOccupancyReport"
      :rows="active?.rows || []"
      :totals="{}"
      :total="active?.total ?? null"
      :page="active?.page || 1"
      :size="active?.size || 10"
      :loading="!!active?.loading"
      :per-page-options="[10, 20, 50, 100]"
      table-height="50vh"
      @update:page="onUpdatePage"
      @update:size="onUpdateSize"
    />
  </VCard>
</template>

<style scoped>
.report-card {
  border: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 8%, transparent);
  border-radius: 14px;
  background: var(--v-theme-surface);
}

.toolbar {
  display: flex;
  justify-content: space-between;
  padding: 16px;
}

.filters-bar {
  display: grid;
  align-items: end;
  border-radius: 12px;
  background: color-mix(in oklab, var(--v-theme-on-surface) 4%, transparent);
  gap: 14px;
  grid-template-columns: 1.2fr 1.4fr 1.6fr auto;
  margin-block: 0 12px;
  margin-inline: 16px;
  padding-block: 12px;
  padding-inline: 16px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.label {
  color: rgba(var(--v-theme-on-surface), 0.75);
  font-size: 0.85rem;
}

.date-inline {
  display: flex;
  gap: 10px;
}

.w-160 {
  inline-size: 160px;
}

.w-120 {
  inline-size: 120px;
}

@media (max-width: 980px) {
  .filters-bar {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
