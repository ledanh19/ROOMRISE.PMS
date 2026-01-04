<template>
  <Head title="Báo cáo quản lý | Room Rise" />
  <Layout>
    <VTabs
      v-model="tab"
      color="primary"
      density="comfortable"
      align-tabs="start"
      class="mb-1"
      show-arrows
      prev-icon="tabler-chevron-left"
      next-icon="tabler-chevron-right"
    >
      <VTab value="booking-source-report">Báo cáo nguồn đặt phòng</VTab>
      <VTab value="booking-report">Báo cáo đặt phòng</VTab>
      <!-- <VTab value="group-booking-report">Báo cáo đặt phòng theo nhóm</VTab> -->
      <VTab value="occupancy-report">Báo cáo công suất phòng</VTab>
    </VTabs>
    <VWindow v-model="tab">
      <VWindowItem value="booking-source-report"
        ><BookingSourceReport
          v-if="tab === 'booking-source-report'"
        ></BookingSourceReport>
      </VWindowItem>
      <VWindowItem value="booking-report">
        <BookingReport2 v-if="tab === 'booking-report'"></BookingReport2>
      </VWindowItem>
      <!-- <VWindowItem value="group-booking-report"
        ><GroupBookingReport></GroupBookingReport>
      </VWindowItem> -->
      <VWindowItem value="occupancy-report">
        <OccupancyReport v-if="tab === 'occupancy-report'"></OccupancyReport>
      </VWindowItem>
    </VWindow>
  </Layout>
</template>
<script setup>
import BookingReport2 from "@/Components/report/Modal/bookingReport2.vue";
import BookingSourceReport from "@/Components/report/Modal/bookingSourceReport.vue";
import OccupancyReport from "@/Components/report/Modal/occupancyReport.vue";
import Layout from "@/layouts/blank.vue";
import { useReportManager } from "@/stores/useReportManager";

import { Head } from "@inertiajs/vue3";

const tab = ref("booking-source-report");
const report = useReportManager();

watch(
  tab,
  (val) => {
    report.switchTab(val);
  },
  { immediate: true }
);
</script>
