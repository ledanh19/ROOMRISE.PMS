<!-- src/views/reports/CheckInListReport.vue -->
<script setup>
import AppDateTimePicker from "@/@core/components/app-form-elements/AppDateTimePicker.vue";
import RefreshIcon from "@/Components/control-panel/RefreshIcon.vue";
import { useReport } from "@/stores/useReport";
import { headerCheckInCheckOutListReport } from "@/utils/headerReport";
import { storeToRefs } from "pinia";
import { computed, ref, watch } from "vue";
import TableReport from "../Item/tableReport.vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo đặt phòng" },
});

const date = ref({ from: "", to: "" });
const time = ref({ from: "", to: "" });

const report = useReport();
report.switchTab("check-in-list", { reset: false, fetchNow: false });
const { active } = storeToRefs(report);

const payload = computed(() => ({
  dateFrom: date.value.from,
  dateTo: date.value.to,
  timeFrom: time.value.from,
  timeTo: time.value.to,
}));

async function onSearch() {
  report.replaceFilters(payload.value, "check-in-list");
  await report.fetch();
}

async function syncAndFetch(patch) {
  report.replaceFilters(patch, "check-in-list");
  await report.fetch();
}

const hasDate = computed(() => !!(date.value.from || date.value.to));
const hasTime = computed(() => !!(time.value.from || time.value.to));

function isCleared(v) {
  return v === "" || v === null || typeof v === "undefined";
}

watch(
  () => date.value.from,
  async (nv, ov) => {
    if (nv !== ov && isCleared(nv)) {
      await syncAndFetch({ dateFrom: "" });
    }
  }
);

watch(
  () => date.value.to,
  async (nv, ov) => {
    if (nv !== ov && isCleared(nv)) {
      await syncAndFetch({ dateTo: "" });
    }
  }
);

watch(
  () => time.value.from,
  async (nv, ov) => {
    if (nv !== ov && isCleared(nv)) {
      await syncAndFetch({ timeFrom: "" });
    }
  }
);

watch(
  () => time.value.to,
  async (nv, ov) => {
    if (nv !== ov && isCleared(nv)) {
      await syncAndFetch({ timeTo: "" });
    }
  }
);

async function clearAllFilters() {
  date.value = { from: "", to: "" };
  time.value = { from: "", to: "" };
  await syncAndFetch({
    dateFrom: "",
    dateTo: "",
    timeFrom: "",
    timeTo: "",
  });
}
</script>

<template>
  <VCard class="report-card">
    <div class="toolbar">
      <h2 class="title">{{ title }}</h2>
      <div class="actions">
        <VBtn
          variant="outlined"
          class="reset-btn mr-2"
          color="error"
          :loading="active.loading"
          :disabled="active.loading"
          @click="clearAllFilters"
        >
          <RefreshIcon :size="18" class="refresh-icon" />

          Xoá bộ lọc
        </VBtn>

        <VBtn
          class="ml-2"
          color="primary"
          prepend-icon="tabler-search"
          :loading="active.loading"
          :disabled="active.loading"
          @click="onSearch"
        >
          Lọc
        </VBtn>
      </div>
    </div>

    <VRow class="filters" dense>
      <VCol cols="12" md="6">
        <div class="filter-group">
          <div class="filter-title d-flex align-center justify-space-between">
            <span>Ngày thanh toán</span>
          </div>

          <div class="inline-controls">
            <div class="field">
              <div class="label">Từ ngày</div>
              <AppDateTimePicker
                v-model="date.from"
                class="picker picker--date"
                placeholder="YYYY-MM-DD"
                :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
                :clearable="true"
              />
            </div>

            <span class="sep">đến</span>

            <div class="field">
              <div class="label">Đến ngày</div>
              <AppDateTimePicker
                v-model="date.to"
                class="picker picker--date"
                placeholder="YYYY-MM-DD"
                :config="{ enableTime: false, dateFormat: 'Y-m-d' }"
                :clearable="true"
              />
            </div>
          </div>
        </div>
      </VCol>

      <VCol cols="12" md="6">
        <div class="filter-group">
          <div class="filter-title d-flex align-center justify-space-between">
            <span>Thời gian thanh toán</span>
          </div>

          <div class="inline-controls">
            <div class="field">
              <div class="label">Từ giờ</div>
              <AppDateTimePicker
                v-model="time.from"
                class="picker picker--time"
                placeholder="HH:mm"
                :config="{
                  enableTime: true,
                  noCalendar: true,
                  dateFormat: 'H:i',
                  time_24hr: true,
                  allowInput: true,
                }"
                :clearable="true"
              />
            </div>

            <span class="sep">đến</span>

            <div class="field">
              <div class="label">Đến giờ</div>
              <AppDateTimePicker
                v-model="time.to"
                class="picker picker--time"
                placeholder="HH:mm"
                :config="{
                  enableTime: true,
                  noCalendar: true,
                  dateFormat: 'H:i',
                  time_24hr: true,
                  allowInput: true,
                }"
                :clearable="true"
              />
            </div>
          </div>
        </div>
      </VCol>
    </VRow>

    <TableReport
      :headers="headerCheckInCheckOutListReport"
      :rows="active.rows"
      :totals="{}"
      :total="active.total"
      :page="active.page"
      :size="active.size"
      :loading="active.loading"
      :per-page-options="[10, 20, 50, 100]"
      :is-paginated="true"
      table-height="65vh"
      @update:page="
        async (p) => {
          if (p !== active.page) {
            report.setPage(p);
            await report.fetch();
          }
        }
      "
      @update:size="
        async (s) => {
          if (s !== active.size) {
            report.setSize(s);
            await report.fetch();
          }
        }
      "
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

.filter-group {
  display: grid;
  gap: 10px;
}

.filter-title {
  color: rgba(var(--v-theme-on-surface), 0.92);
  font-weight: 700;
}

.inline-controls {
  display: grid;
  align-items: end;
  gap: 12px;
  grid-template-columns: 1fr auto 1fr;
}

.label {
  color: rgba(var(--v-theme-on-surface), 0.75);
  font-size: 0.85rem;
  margin-block-end: 4px;
}

.sep {
  color: rgba(var(--v-theme-on-surface), 0.7);
}

.picker--date {
  inline-size: 230px;
}

.picker--time {
  inline-size: 130px;
}
</style>
