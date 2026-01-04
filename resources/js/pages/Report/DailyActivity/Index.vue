<template>
  <Head title="Báo cáo hoạt động hằng ngày | Room Rise" />
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
      <VTab value="check-in-list">Danh sách nhận phòng</VTab>
      <VTab value="check-out-list-report">Báo cáo danh sách trả phòng</VTab>
      <VTab value="guest-information-report">Báo cáo thông tin khách</VTab>
      <VTab value="internal-guest-list-report"
        >Báo cáo danh sách khách nội bộ</VTab
      >
      <VTab value="daily-payment-report">Báo cáo thanh toán hàng ngày</VTab>
    </VTabs>
    <VWindow v-model="tab">
      <VWindowItem value="check-in-list"
        ><CheckInListReport
          v-if="tab === 'check-in-list'"
          title="Danh sách nhận phòng"
        ></CheckInListReport>
      </VWindowItem>
      <VWindowItem value="check-out-list-report">
        <CheckOutListReport
          v-if="tab === 'check-out-list-report'"
          title="Báo Cáo Danh Sách Trả Phòng"
        ></CheckOutListReport>
      </VWindowItem>
      <VWindowItem value="guest-information-report"
        ><CustomerInformationReport
          v-if="tab === 'guest-information-report'"
          title="Báo Cáo Thông Tin Khách"
        ></CustomerInformationReport>
      </VWindowItem>
      <VWindowItem value="internal-guest-list-report">
        <InternalGuestListReport
          v-if="tab === 'internal-guest-list-report'"
          title="Báo Cáo Danh Sách Khách Nội Bộ"
        ></InternalGuestListReport>
      </VWindowItem>
      <VWindowItem value="daily-payment-report"
        ><DailyPaymentReport
          v-if="tab === 'daily-payment-report'"
          title="Báo Cáo Thanh Toán Hàng Ngày"
        ></DailyPaymentReport>
      </VWindowItem>
    </VWindow>
  </Layout>
</template>
<script setup>
import CheckInListReport from "@/Components/report/Modal/checkInListReport.vue";
import CheckOutListReport from "@/Components/report/Modal/checkOutListReport.vue";
import CustomerInformationReport from "@/Components/report/Modal/customerInformationReport.vue";
import DailyPaymentReport from "@/Components/report/Modal/dailyPaymentReport.vue";
import InternalGuestListReport from "@/Components/report/Modal/internalGuestListReport.vue";
import Layout from "@/layouts/blank.vue";
import { useReport } from "@/stores/useReport";
import { Head } from "@inertiajs/vue3";

const tab = ref("check-in-list");
const report = useReport();

watch(
  tab,
  (val) => {
    report.switchTab(val, { reset: true, fetch: false });
  },
  { immediate: true }
);
</script>
