<template>
  <VDialog
    :model-value="props.isEditDialogVisible"
    @update:model-value="onReset"
    width="1200"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard title="Thông tin đặt phòng">
      <VForm ref="formRef" @submit.prevent="onSubmit">
        <VCardItem>
          <VCard class="border pa-6">
            <VRow>
              <VCol cols="12" sm="12" md="6" lg="6">
                <VRow
                  class="font-weight-medium text-high-emphasis d-flex align-center"
                >
                  <VCol cols="4"><div>Nguồn</div></VCol>
                  <VCol cols="8">
                    <AppSelect
                      v-model="form.ota_name"
                      :items="otaChannels"
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                </VRow>
                <VRow
                  class="font-weight-medium text-high-emphasis d-flex align-center"
                >
                  <VCol cols="4"><div>PPTT tiền phòng</div></VCol>
                  <VCol cols="8">
                    <AppSelect
                      v-model="form.room_payment_method"
                      :items="roomPaymentMethod"
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                </VRow>
                <VRow
                  class="font-weight-medium text-high-emphasis d-flex align-center"
                >
                  <VCol cols="4"><div>Phí hoa hồng</div></VCol>
                  <VCol cols="8">
                    <AppTextField
                      v-model="form.commission_fee"
                      :error-messages="form.errors.commission_fee"
                      type="number"
                    />
                  </VCol>
                </VRow>
                <VRow
                  v-if="props.data?.is_imported"
                  class="font-weight-medium text-high-emphasis d-flex align-center"
                >
                  <VCol cols="4"><div>Tổng tiền phòng</div></VCol>
                  <VCol cols="8">
                    <AppTextField
                      v-model="form.total_amount"
                      :error-messages="form.errors.total_amount"
                      type="number"
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="6">
                <VRow
                  class="font-weight-medium text-high-emphasis d-flex align-center"
                >
                  <VCol cols="4"><div>Ngày đặt</div></VCol>
                  <VCol cols="8">
                    <AppTextField
                      v-model="form.created_at"
                      :error-messages="form.errors.created_at"
                      type="date"
                    />
                  </VCol>
                </VRow>
                <VRow
                  class="font-weight-medium text-high-emphasis d-flex align-center"
                >
                  <VCol cols="4"><div>Ghi chú đoàn khách</div></VCol>
                  <VCol cols="8">
                    <AppTextarea
                      v-model="form.note"
                      rows="3"
                      placeholder="Ghi chú"
                    />
                  </VCol>
                </VRow>
              </VCol>
            </VRow>
          </VCard>
        </VCardItem>
        <VCardText class="d-flex justify-end flex-wrap gap-3">
          <VBtn type="submit" :disabled="form.processing" variant="tonal">
            Lưu
          </VBtn>
          <VBtn variant="tonal" color="secondary" @click="onReset">
            Hủy bỏ
          </VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>
</template>
<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
const props = defineProps({
  isEditDialogVisible: {
    type: Boolean,
    required: true,
  },
  data: Object,
});
const emit = defineEmits(["update:isEditDialogVisible", "update:payment"]);
const onReset = () => {
  emit("update:isEditDialogVisible", false);
};
const defaultFormData = {
  ota_name: "",
  created_at: "",
  room_payment_method: "",
  commission_fee: "",
  total_amount: "",
  note: "",
};
const formRef = ref();
const form = useForm(defaultFormData);
const otaChannels = ["Walk-in", "Từ đối tác"];
const roomPaymentMethod = ["Thu tại KS", "Thu bởi đối tác"];
const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  form.post(route("bookings.updateBookingInformation", props.data.id), {
    onSuccess: () => {
      onReset();
      form.reset();
      emit("update:payment");
    },
    onError: () => {},
  });
};
watch(
  () => props.isEditDialogVisible,
  (visible) => {
    if (!visible) return;

    form.clearErrors();

    if (!props.data) {
      form.defaults(defaultFormData);
      form.reset();
    } else {
      const formattedData = {
        ota_name: props.data.ota_name ?? "",
        created_at: props.data.created_at
          ? props.data.created_at.slice(0, 10)
          : "",
        room_payment_method: props.data.room_payment_method ?? "",
        commission_fee: props.data.commission_fee ?? "",
        total_amount: props.data.total_amount ?? "",
        note: props.data.note ?? "",
      };

      form.defaults(formattedData);
      form.reset();
    }
  }
);
</script>
