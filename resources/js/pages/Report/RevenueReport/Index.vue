<template>
  <Head title="Báo cáo doanh thu | Room Rise" />
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
      <VTab value="daily-sale-report">Báo Cáo doanh thu hàng ngày</VTab>
      <VTab value="activity-report">Báo cáo hoạt động</VTab>
      <VTab value="performance-report">Báo Cáo Hiệu Quả</VTab>
      <VTab value="detailed-revenue-report">Báo cáo doanh thu chi tiết</VTab>
      <VTab value="revenue-report-booking"
        >Báo cáo doanh thu theo đặt phòng</VTab
      >
      <VTab value="revenue-report-booking-source"
        >Báo cáo doanh thu theo nguồn đặt phòng</VTab
      >
      <VTab value="revenue-reports-room-type"
        >Báo cáo doanh thu theo loại phòng</VTab
      >
    </VTabs>
    <VWindow v-model="tab">
      <VWindowItem value="daily-sale-report">
        <RevenueDailySaleReport
          v-if="tab === 'daily-sale-report'"
          title="Báo Cáo doanh thu hàng ngày"
        />
      </VWindowItem>
      <VWindowItem value="activity-report">
        <RevenueActivityReport
          v-if="tab === 'activity-report'"
          title="Báo cáo hoạt động"
        />
      </VWindowItem>
      <VWindowItem value="performance-report">
        <PerformanceReport
          v-if="tab === 'performance-report'"
          title="Báo Cáo Hiệu Quả"
        />
      </VWindowItem>

      <VWindowItem value="detailed-revenue-report">
        <DetailedRevenueReport
          v-if="tab === 'detailed-revenue-report'"
          title="Báo cáo doanh thu chi tiết"
        />
      </VWindowItem>

      <VWindowItem value="revenue-report-booking">
        <RevenueBookingReport
          v-if="tab === 'revenue-report-booking'"
          title="Báo cáo doanh thu theo đặt phòng"
        />
      </VWindowItem>

      <VWindowItem value="revenue-report-booking-source">
        <RevenueBookingSourceReport
          v-if="tab === 'revenue-report-booking-source'"
          title="Báo cáo doanh thu theo nguồn đặt phòng"
        />
      </VWindowItem>

      <VWindowItem value="revenue-reports-room-type">
        <RevenueReportsRoomType v-if="tab === 'revenue-reports-room-type'" />
      </VWindowItem>
    </VWindow>
  </Layout>
</template>
<script setup>
import DetailedRevenueReport from "@/Components/report/Modal/detailedRevenueReport.vue";
import PerformanceReport from "@/Components/report/Modal/performanceReport.vue";
import RevenueActivityReport from "@/Components/report/Modal/revenueActivityReport.vue";
import RevenueBookingReport from "@/Components/report/Modal/revenueBookingReport.vue";
import RevenueBookingSourceReport from "@/Components/report/Modal/revenueBookingSourceReport.vue";
import RevenueDailySaleReport from "@/Components/report/Modal/revenueDailySaleReport.vue";
import RevenueReportsRoomType from "@/Components/report/Modal/revenueReportsRoomType.vue";
import Layout from "@/layouts/blank.vue";
import { useRevenueReport } from "@/stores/useRevenueReport";

import { Head } from "@inertiajs/vue3";

const tab = ref("daily-sale-report");
const report = useRevenueReport();

watch(
  tab,
  (val) => {
    report.switchTab(val, { reset: true, fetch: false });
  },
  { immediate: true }
);
</script>
