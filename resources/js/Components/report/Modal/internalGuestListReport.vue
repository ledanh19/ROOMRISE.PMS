<template>
  <VCard class="report-card">
    <div class="toolbar">
      <h2 class="title">{{ title }}</h2>
      <div class="actions">
        <VBtn color="primary" prepend-icon="tabler-search" @click="onSearch">
          Xem
        </VBtn>
      </div>
    </div>

    <TableReport
      :headers="headerInternalGuestListReport"
      :rows="rows"
      :totals="totals"
      :loading="loading"
      :total="total"
      :page="page"
      :size="size"
      :perPageOptions="[10, 20, 50, 100]"
      :table-height="`60vh`"
      :table-width="'100%'"
      :isPaginated="true"
      @update:page="onPage"
      @update:size="onSize"
    />
  </VCard>
</template>

<script setup>
import { useReport } from "@/stores/useReport";
import { headerInternalGuestListReport } from "@/utils/headerReport";
import { storeToRefs } from "pinia";
import { computed, onMounted } from "vue";
import TableReport from "../Item/tableReport.vue";

const props = defineProps({
  title: { type: String, default: "Báo cáo đặt phòng" },
});

const TAB = "internal-guest-list-report";

// dùng store
const report = useReport();
const { active } = storeToRefs(report);

// bind dữ liệu từ tab active
const rows = computed(() => active.value?.rows ?? []);
const totals = computed(() => active.value?.totals ?? {}); // tab này không có totals -> để {}
const loading = computed(() => !!active.value?.loading);
const total = computed(() => Number(active.value?.total || 0));
const page = computed(() => Number(active.value?.page || 1));
const size = computed(() => Number(active.value?.size || 10));

// handlers khớp với TableReport
function onPage(newPage) {
  report.setPage(newPage, TAB);
  report.fetch(TAB);
}

function onSize(newSize) {
  report.setSize(newSize, TAB);
  report.fetch(TAB);
}

function onSearch() {
  report.setPage(1, TAB);
  report.fetch(TAB);
}

onMounted(async () => {
  await report.switchTab(TAB, { reset: false, fetchNow: false });

  if (!active.value?.rows?.length) {
    await report.fetch(TAB);
  }
});
</script>

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
</style>
