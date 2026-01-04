<script setup>
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import { useReport } from "@/stores/useReport";
import { headerCustomerInformationReport } from "@/utils/headerReport";
import { storeToRefs } from "pinia";
import { computed, ref, watch } from "vue";
import TableReport from "../Item/tableReport.vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo đặt phòng" },
});

const report = useReport();
report.switchTab("guest-information-report", { reset: false, fetchNow: false });
const { active } = storeToRefs(report);

const date = ref({ from: "", to: "" });

const filters = ref({
  dateType: "created_at",
  type: null,
});

const dateTypeOptions = [
  { title: "Ngày tạo", value: "created_at" },
  { title: "Ngày nhận phòng", value: "check_in_date" },
  { title: "Ngày trả phòng", value: "check_out_date" },
];

const typeOptions = [
  "Walk-in",
  "Từ đối tác",
  "BookingCom",
  "Agoda",
  "Expedia",
  "Airbnb",
  "Ctrip",
  "Sale",
  "Sale TA",
  "Social",
  "OTA",
];

const timeRange = computed(() => {
  const df = date.value.from || "";
  const dt = date.value.to || "";
  if (df && dt) return `${df} to ${dt}`;
  if (df && !dt) return `${df} to ${df}`;
  if (!df && dt) return `${dt} to ${dt}`;
  return "";
});

const payload = computed(() => ({
  dateType: filters.value.dateType,
  timeRange: timeRange.value,
  type: filters.value.type || "",
  page: active.value.page,
  size: active.value.size,
}));

async function onSearch() {
  report.replaceFilters(
    {
      dateType: payload.value.dateType,
      timeRange: payload.value.timeRange,
      type: payload.value.type,
      dateFrom: date.value.from || "",
      dateTo: date.value.to || "",
    },
    "guest-information-report"
  );
  report.setPage(1, "guest-information-report");
  await report.fetch();
}

async function onClear() {
  date.value = { from: "", to: "" };
  filters.value.type = "";
  report.replaceFilters(
    {
      dateType: filters.value.dateType,
      timeRange: "",
      type: "",
      dateFrom: "",
      dateTo: "",
    },
    "guest-information-report"
  );
  report.setPage(1, "guest-information-report");
  await report.fetch();
}

function isCleared(v) {
  return v === "" || v === null || typeof v === "undefined";
}

watch(
  () => date.value.from,
  async (nv, ov) => {
    if (nv !== ov && isCleared(nv)) {
      report.replaceFilters(
        {
          timeRange: timeRange.value,
          dateFrom: "",
          dateTo: date.value.to || "",
        },
        "guest-information-report"
      );
      await report.fetch();
    }
  }
);

watch(
  () => date.value.to,
  async (nv, ov) => {
    if (nv !== ov && isCleared(nv)) {
      report.replaceFilters(
        {
          timeRange: timeRange.value,
          dateFrom: date.value.from || "",
          dateTo: "",
        },
        "guest-information-report"
      );
      await report.fetch();
    }
  }
);

function onUpdatePage(p) {
  report.setPage(p, "guest-information-report");
  report.fetch();
}
function onUpdateSize(s) {
  report.setSize(s, "guest-information-report");
  report.fetch();
}
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
        <VBtn
          color="primary"
          class="ml-4"
          prepend-icon="tabler-search"
          @click="onSearch"
          >Lọc</VBtn
        >
      </div>
    </div>

    <VRow class="filters" dense>
      <VCol cols="12">
        <div class="filters-inline">
          <AppSelect
            class="shrink w-sm"
            label="Khung ngày"
            :items="dateTypeOptions"
            v-model="filters.dateType"
            item-title="title"
            item-value="value"
            density="comfortable"
          />

          <div class="dates-wrap">
            <div class="field">
              <div class="label">Từ</div>
              <AppDateTimePicker
                v-model="date.from"
                class="picker picker--date w-280"
                placeholder="YYYY-MM-DD"
                :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
                :style="{ inlineSize: '22vw' }"
                :clearable="true"
              />
            </div>

            <div class="field">
              <div class="label">Đến</div>
              <AppDateTimePicker
                v-model="date.to"
                class="picker picker--date w-280"
                placeholder="YYYY-MM-DD"
                :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
                :style="{ inlineSize: '22vw' }"
                :clearable="true"
              />
            </div>
          </div>

          <AppSelect
            class="shrink w-sm"
            label="Nguồn đặt phòng"
            :items="typeOptions"
            v-model="filters.type"
            clearable
            placeholder="Chọn loại"
            density="comfortable"
          />
        </div>
      </VCol>
    </VRow>

    <TableReport
      :headers="headerCustomerInformationReport"
      :rows="active.rows"
      :totals="{}"
      :total="active.total"
      :page="active.page"
      :size="active.size"
      :loading="active.loading"
      :per-page-options="[1, 10, 20, 50, 100]"
      table-height="60vh"
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

.filters {
  padding: 12px;
}

.filters-inline {
  display: flex;
  flex-wrap: wrap;
  align-items: end;
  gap: 12px;
}

.dates-wrap {
  display: flex;
  flex: 1 1 auto;
  align-items: end;
  gap: 12px;
  min-inline-size: 520px;
}

.field {
  display: flex;
  flex-direction: column;
}

.label {
  color: rgba(var(--v-theme-on-surface), 0.75);
  font-size: 0.85rem;
  margin-block-end: 4px;
}

.picker--date {
  inline-size: 280px;
  max-inline-size: 100%;
}

.shrink {
  flex: 0 0 auto;
}

.w-sm {
  inline-size: 220px;
}

@media (max-width: 1100px) {
  .picker--date {
    inline-size: 240px;
  }

  .dates-wrap {
    min-inline-size: 480px;
  }
}

@media (max-width: 900px) {
  .filters-inline {
    align-items: stretch;
  }

  .dates-wrap {
    flex: 1 1 100%;
    min-inline-size: 0;
  }

  .picker--date {
    inline-size: 100%;
  }

  .w-sm {
    inline-size: 200px;
  }
}

.w-260 {
  inline-size: 260px;
}

.w-280 {
  inline-size: 280px;
}

.w-300 {
  inline-size: 300px;
}
</style>
