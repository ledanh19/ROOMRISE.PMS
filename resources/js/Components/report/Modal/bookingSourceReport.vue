<script setup>
import AppSelect from "@/@core/components/app-form-elements/AppSelect.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { useReportManager } from "@/stores/useReportManager";
import { monthOptions, otaChannels } from "@/utils/constants";
import { headerBookingSourceReport } from "@/utils/headerReport";
import { storeToRefs } from "pinia";
import { computed, onMounted, ref } from "vue";
import TableReport from "../Item/tableReport.vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo nguồn đặt phòng" },
});

const report = useReportManager();
const propertyStore = usePropertyStore();

report.switchTab("booking-source-report", { reset: false, fetchNow: false });
const { active } = storeToRefs(report);

const now = new Date();
const filters = ref({
  month: null,
  year: null,
  type: null,
});

const yearOptions = computed(() => {
  const y = now.getFullYear();
  return Array.from({ length: 11 }, (_, i) => y - 5 + i);
});

function getSelectedPropertyValue() {
  const sp = propertyStore.selectedProperty;
  if (!sp || sp === "null") return "";
  if (typeof sp === "object" && sp.value != null) return sp.value;
  return sp || "";
}

async function onSearch() {
  report.replaceFilters(
    {
      month: filters.value.month,
      year: filters.value.year,
      type: filters.value.type,
      property: getSelectedPropertyValue(),
    },
    "booking-source-report"
  );
  report.setPage(1, "booking-source-report");
  await report.fetch("booking-source-report");
}

async function onClear() {
  filters.value = {
    month: null,
    year: null,
    type: null,
  };
  report.replaceFilters(
    {
      month: "",
      year: "",
      otaName: "",
      type: "",
      property: getSelectedPropertyValue(),
    },
    "booking-source-report"
  );
  report.setPage(1, "booking-source-report");
  await report.fetch("booking-source-report");
}

function onUpdatePage(p) {
  report.setPage(p, "booking-source-report");
  report.fetch("booking-source-report");
}
function onUpdateSize(s) {
  report.setSize(s, "booking-source-report");
  report.fetch("booking-source-report");
}

onMounted(async () => {
  report.replaceFilters(
    {
      month: filters.value.month,
      year: filters.value.year,
      otaName: "",
      property: getSelectedPropertyValue(),
    },
    "booking-source-report"
  );
  await report.fetch("booking-source-report");
});
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
        <div class="label">Tháng/Năm</div>
        <div class="date-inline">
          <AppSelect
            class="w-160"
            :items="monthOptions"
            placeholder="Tháng"
            item-title="title"
            item-value="value"
            v-model="filters.month"
            density="comfortable"
          />
          <AppSelect
            class="w-120"
            :items="yearOptions"
            v-model="filters.year"
            density="comfortable"
            placeholder="Năm"
          />
        </div>
      </div>

      <div class="field">
        <div class="label">Nguồn đặt phòng</div>
        <AppSelect
          :items="otaChannels"
          v-model="filters.type"
          placeholder="Chọn nguồn đặt phòng"
          clearable
          density="comfortable"
        />
      </div>
    </div>

    <TableReport
      :headers="headerBookingSourceReport"
      :rows="active?.rows || []"
      :totals="{}"
      :total="active?.total ?? null"
      :page="active?.page || 1"
      :size="active?.size || 10"
      :loading="!!active?.loading"
      :per-page-options="[10, 20, 50, 100]"
      table-height="55vh"
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
