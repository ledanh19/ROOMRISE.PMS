<template>
  <VCard>
    <VCardItem>
      <h3>Danh sách booking đã quyết toán</h3>
      <VDataTable
        :headers="headers"
        :items="dataBookings"
        item-value="id"
        return-object
        :items-per-page="-1"
        class="text-no-wrap"
        hide-default-footer
      >
        <template #item.property_id="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            <div>{{ item?.property ? item.property.name : "" }}</div>
          </div>
        </template>
        <template #item.created_at="{ item }">
          <div class="text-body-1 pa-3">
            {{ formatDate(item.settlement.created_at) }}
          </div>
        </template>
        <template #item.check_in="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            {{ formatDate(item.check_in_date) }}
          </div>
        </template>
        <template #item.net_estimate="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            <div>
              {{ formatCurrency(item.net_estimate) }}
            </div>
          </div>
        </template>
        <template #item.payout_received="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            <div class="text-success">
              {{ formatCurrency(item.payout_received) }}
            </div>
          </div>
        </template>
        <template #item.code="{ item }">
          <div class="text-blue text-body-1 pa-3">
            {{ item?.settlement ? item.settlement.code : "" }}
          </div>
        </template>
        <template #item.status="{ item }">
          <div class="text-success text-body-1 pa-3">
            {{ item?.settlement ? item.settlement.status : "" }}
          </div>
        </template>
      </VDataTable>
    </VCardItem>
  </VCard>
</template>
<script setup>
import axios from "axios";
import { onMounted, ref, toRefs, watch } from "vue";
import { formatCurrency, formatDate } from "@/utils/formatters";

const props = defineProps({
  currentTabSecond: Number,
  bookingIdSettlement: String,
  otaChannelSettlement: String,
  partnerIdSettlement: Number,
  rangeDateSettlement: String,
});

const { currentTabSecond } = toRefs(props);

const isDialogVisible = ref(false);
const dataBookings = ref([]);

const headers = [
  { title: "Booking ID", key: "booking_code", sortable: false },
  { title: "Chỗ nghỉ", key: "property_id", sortable: false },
  { title: "Kênh OTA", key: "ota_name", sortable: false },
  { title: "Check-in/Check-out", key: "check_in", sortable: false },
  { title: "Số tiền net", key: "net_estimate", sortable: false },
  { title: "Số tiền nhận", key: "payout_received", sortable: false },
  { title: "Ngày quyết toán", key: "created_at", sortable: false },
  { title: "Phiếu quyết toán", key: "code", sortable: false },
  { title: "Trạng thái", key: "status", sortable: false },
];

function parseDateRange(rangeStr) {
  if (!rangeStr) return { start_date: null, end_date: null };
  const [start_date, end_date] = rangeStr.split(" to ");
  return { start_date, end_date };
}

const loadSettlementBookings = async () => {
  try {
    const { start_date, end_date } = parseDateRange(props.rangeDateSettlement);

    const response = await axios.get(
      route("ota-receipt.loadSettlementBookings"),
      {
        params: {
          booking_id: props.bookingIdSettlement,
          ota_channel:
            props.otaChannelSettlement !== "Tất cả các kênh"
              ? props.otaChannelSettlement
              : null,
          partner_id:
            props.partnerIdSettlement !== 0 ? props.partnerIdSettlement : null,
          start_date,
          end_date,
        },
      }
    );

    dataBookings.value = response.data;
    console.log("Loaded settlement bookings:", dataBookings.value);
  } catch (error) {
    console.error("Lỗi tải danh sách phiếu quyết toán:", error);
  }
};

watch(currentTabSecond, (val) => {
  if (val === 0) {
    loadSettlementBookings();
  }
});

watch(
  () => [
    props.bookingIdSettlement,
    props.otaChannelSettlement,
    props.partnerIdSettlement,
    props.rangeDateSettlement,
  ],
  () => {
    if (currentTabSecond.value === 0) {
      loadSettlementBookings();
    }
  }
);

onMounted(() => {
  if (currentTabSecond.value === 0) {
    loadSettlementBookings();
  }
});
</script>
