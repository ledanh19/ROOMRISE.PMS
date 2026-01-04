<template>
  <VDialog
    :model-value="props.isPayDialogVisible"
    @update:model-value="onReset"
    class="v-dialog-sm"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard title="Thanh toán">
      <VForm ref="formRef" @submit.prevent="onSubmit">
        <VCardItem>
          <AppTextField
            v-model="form.paid"
            :error-messages="form.errors.paid"
            type="number"
            label="Số tiền thanh toán"
            :rules="[requiredValidator]"
          />
        </VCardItem>
        <VCardText class="d-flex justify-end flex-wrap gap-3">
          <VBtn
            type="submit"
            :disabled="form.processing"
            variant="tonal"
            color="info"           
          >
            Thanh toán
          </VBtn>
          <VBtn variant="tonal" color="secondary" @click="onReset">
            Close
          </VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>
</template>
<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
const props = defineProps({
  isPayDialogVisible: {
    type: Boolean,
    required: true,
  },
  paymentTargetId: Number,
});
const emit = defineEmits(["update:isPayDialogVisible", "update:payment"]);
const onReset = () => {
  emit("update:isPayDialogVisible", false);
};
watch(
  () => props.isPayDialogVisible,

  (isVisible) => {
    if (!isVisible) return;
  }
);
const defaultFormData = {
  paid: "",
};
const formRef = ref();
const form = useForm(defaultFormData);

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  form.post(route("payment.storePayment", props.paymentTargetId), {
    onSuccess: () => {
      onReset();
      form.reset();
      emit("update:payment");
    },
    onError: () => {

    },
  });
};
</script>
