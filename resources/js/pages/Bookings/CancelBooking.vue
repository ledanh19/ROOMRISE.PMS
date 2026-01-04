<template>
  <VDialog
    :model-value="props.isCancelDialogVisible"
    @update:model-value="onReset"
    width="500"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard title="Hủy đặt phòng">
      <VForm ref="formRef" @submit.prevent="onSubmit">
        <VCardItem>
          <AppSelect
            class="mt-3"
            label="Chính sách hoàn tiền"
            v-model="form.refundOption"
            :items="refundOptions"
            :rules="[requiredValidator]"
          />
          <AppTextField
            class="mt-5"
            label="Số tiền"
            v-if="form.refundOption == 'Hoàn tiền'"
            v-model="form.paid"
            :error-messages="form.errors.paid"
            type="number"
          />
          <AppSelect
          class="mt-5"
            label="Hình thức thanh toán"
            v-model="form.payment_method"
            :items="paymentOptions"
            :error-messages="form.errors.payment_method"
          />
          <AppTextarea
          class="mt-5"
            v-if="form.refundOption == 'Hoàn tiền'"
            v-model="form.note"
            label="Ghi chú"
            placeholder="Ghi chú"
          />
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
import { ref, watch, onMounted } from "vue";
const props = defineProps({
  isCancelDialogVisible: Boolean,
  booking: Object,
});
const emit = defineEmits(["update:isCancelDialogVisible"]);

const defaultFormData = {
  refundOption: "",
  paid: "",
  note: "",
  payment_method: "",
};
const paymentOptions = [
  "Tiền mặt",
  "Chuyển khoản",
  "QR Code",
  "9Pay",
  "Momo",
  "VNPay",
  "Thẻ tín dụng",
];
const formRef = ref();
const form = useForm(defaultFormData);
const refundOptions = ["Hoàn tiền", "Không hoàn tiền"];
const onReset = () => {
  form.reset();
  emit("update:isCancelDialogVisible", false);
};

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  form.post(route("bookings.cancelBooking", props.booking.id), {
    onSuccess: () => {
      onReset();
      form.reset();
    },
    onError: () => {},
  });
};
</script>
