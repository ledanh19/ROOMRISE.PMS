<template>
  <Head title="Xem thanh toán | Room Rise" />
  <Layout>
    <VCardTitle><h2>Xem thanh toán</h2></VCardTitle>
    <VRow>
      <VCol cols="8">
        <VCard
          elevation="0"
          class="border rounded-lg mb-5"
          style="border-color: #ddd"
          v-for="item in histories"
          :key="item.id"
        >
          <VCardTitle class="d-flex align-center mb-4 border-b">
            <VIcon class="me-2" icon="tabler-coin"></VIcon>
            <strong class="text-h5">Thanh toán</strong>
          </VCardTitle>
          <VRow class="pa-4">
            <VCol cols="6">
              <strong>Mã thanh toán:</strong>
              <span class="text-primary font-weight-medium ms-2">
                {{ item.id || "–" }}
              </span>
            </VCol>
            <VCol cols="6">
              <strong>Số tiền:</strong>
              <span class="ms-2">{{ formatCurrency(item.paid) }}</span>
            </VCol>
            <VCol cols="6">
              <strong>Nhân viên:</strong>
              <span class="ms-2">{{ item.staff }}</span>
            </VCol>
            <VCol cols="6">
              <strong>Phương thức:</strong>
              <span class="ms-2">{{ item.payment_method || "–" }}</span>
            </VCol>
          </VRow>
        </VCard>
        <VCard
          elevation="0"
          class="border rounded-lg"
          style="border-color: #ddd"
        >
          <VCardTitle class="d-flex align-center mb-4 border-b">
            <VIcon class="me-2" icon="tabler-book"></VIcon>
            <strong class="text-h5">Đặt phòng</strong>
          </VCardTitle>
          <VRow class="pa-4">
            <VCol cols="6">
              <strong>Trạng thái:</strong>
              <span class="text-primary font-weight-medium ms-2">
                {{ booking.status || "–" }}
              </span>
            </VCol>
            <VCol cols="6">
              <strong>Mã đặt phòng:</strong>
              <span class="ms-2">{{ booking.id || "–" }}</span>
            </VCol>
            <VCol cols="6">
              <strong>Tổng số tiền:</strong>
              <span class="ms-2">{{
                formatCurrency(booking.total_amount)
              }}</span>
            </VCol>
            <VCol cols="6">
              <strong>Trạng thái thanh toán:</strong>
              <VChip
                v-if="booking.payment_status == 'Chờ thanh toán'"
                color="warning"
                class="ms-2"
                >{{ booking.payment_status || "–" }}</VChip
              >
              <VChip
                v-if="booking.payment_status == 'Đã thanh toán'"
                color="success"
                class="ms-2"
                >{{ booking.payment_status || "–" }}</VChip
              >
              <VChip
                v-if="booking.payment_status == 'Chưa thanh toán'"
                color="secondary"
                class="ms-2"
                >{{ booking.payment_status || "–" }}</VChip
              >
            </VCol>
            <VCol cols="6">
              <strong>Nguồn :</strong>
              <span class="ms-2">{{ "–" }}</span>
            </VCol>
            <VCol cols="6">
              <strong>Nhận phòng:</strong>
              <span class="ms-2">{{ booking.check_in_date || "–" }}</span>
            </VCol>
            <VCol cols="6">
              <strong>Trả phòng:</strong>
              <span class="ms-2">{{ booking.check_out_date || "–" }}</span>
            </VCol>
          </VRow>
        </VCard>
      </VCol>
      <VCol cols="4">
        <VCard
          elevation="0"
          class="border rounded-lg"
          style="border-color: #ddd"
        >
          <VCardTitle class="d-flex align-center mb-4 border-b">
            <VIcon class="me-2" icon="tabler-users"></VIcon>
            <strong class="text-h5">Khách hàng</strong>
          </VCardTitle>
          <VRow class="pa-4">
            <VCol cols="12">
              <strong>Tên đầy đủ:</strong>
              <span class="text-primary font-weight-medium ms-2">
                {{ user.full_name || "–" }}
              </span>
            </VCol>
            <VCol cols="12">
              <strong>SĐT:</strong>
              <span class="ms-2">{{ user.phone || "–" }}</span>
            </VCol>
            <VCol cols="12">
              <strong>Email:</strong>
              <span class="ms-2">{{ user.email || "–" }}</span>
            </VCol>
            <VCol cols="12">
              <strong>Quốc gia:</strong>
              <span class="ms-2">{{ user.country || "–" }}</span>
            </VCol>
            <VCol cols="12">
              <strong>Loại giấy tờ :</strong>
              <span class="ms-2">{{ user.id_type || "–" }}</span>
            </VCol>
            <VCol cols="12">
              <strong>Số định danh:</strong>
              <span class="ms-2">{{ user.id_number || "–" }}</span>
            </VCol>
          </VRow>
        </VCard>
      </VCol>
    </VRow>
  </Layout>
</template>
<script setup>
import Layout from "@/layouts/blank.vue";
import { Head, router, Link } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { ref, watch } from "vue";
import dayjs from "dayjs";
const props = defineProps({
  booking: Object,
  user: Object,
  histories: Object,
});
</script>
