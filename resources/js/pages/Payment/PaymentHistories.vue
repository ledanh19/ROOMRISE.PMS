<template>
  <VDialog
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
    width="1200"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard title="Xem thanh toán">
      <VCardItem>
        <VRow>
          <VCol cols="8">
            <VCard
              elevation="0"
              class="pa-4 border rounded-lg mb-5"
              style="border-color: #ddd"
              v-for="item in histories"
              :key="item.id"
            >
              <VCardTitle class="d-flex align-center mb-4">
                <VIcon class="me-2" icon="tabler-coin"></VIcon>
                <strong class="text-h5">Thanh toán</strong>
              </VCardTitle>
              <VRow>
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
              class="pa-4 border rounded-lg"
              style="border-color: #ddd"
            >
              <VCardTitle class="d-flex align-center mb-4">
                <VIcon class="me-2" icon="tabler-book"></VIcon>
                <strong class="text-h5">Đặt phòng</strong>
              </VCardTitle>
              <VRow>
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
              class="pa-4 border rounded-lg"
              style="border-color: #ddd"
            >
              <VCardTitle class="d-flex align-center mb-4">
                <VIcon class="me-2" icon="tabler-users"></VIcon>
                <strong class="text-h5">Khách hàng</strong>
              </VCardTitle>
              <VRow>
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
      </VCardItem>
      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn variant="tonal" color="secondary" @click="onReset"> Close </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
<script setup>
import { computed, ref, watch } from "vue";
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  historyTargetId: Number,
});
const histories = ref([]);
const booking = ref({});
const user = ref({});
const emit = defineEmits(["update:isDialogVisible"]);
const onReset = () => {
  emit("update:isDialogVisible", false);
};
watch(
  [() => props.isDialogVisible, () => props.historyTargetId],
  async ([visible, id]) => {
    if (!visible || !id) return;

    const res = await axios.get(route("payment.historiesData", { id }));
    histories.value = res.data.histories;
    booking.value = res.data.booking;
    user.value = res.data.user;
  }
);
</script>
