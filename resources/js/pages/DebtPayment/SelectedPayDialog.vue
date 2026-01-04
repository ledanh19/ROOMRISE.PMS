<template>
  <VDialog
    :model-value="props.isSelectedDialogVisible"
    @update:model-value="onReset"
    class="v-dialog-sm"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard title="Thanh toán">
      <VForm ref="formRef" @submit.prevent="onSubmit">
        <VCardItem>
          <AppTextField
            v-model="form.total_paid"
            :error-messages="form.errors.total_paid"
            type="number"
            label="Số tiền thanh toán"
            :rules="[requiredValidator]"
          />
          <span v-if="partnerError" class="text-error text-sm mt-1">
            {{ partnerError }}
          </span>
          <AppSelect
            class="mt-2"
            :rules="[requiredValidator]"
            label="Hình thức thanh toán"
            v-model="form.payment_method"
            :items="paymentOptions"
            :error-messages="form.errors.payment_method"
          />
          <AppTextField
            class="mt-2"
            v-model="form.issued_date"
            :error-messages="form.errors.issued_date"
            type="date"
            label="Ngày thanh toán"
            :rules="[requiredValidator]"
          />
          <AppTextarea
            class="mt-2"
            label="Ghi chú"
            placeholder="Ghi chú"
            v-model="form.note"
            :error-messages="form.errors.note"
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
  isSelectedDialogVisible: {
    type: Boolean,
    required: true,
  },
  selectedBookingIds: Array,
  partnerId: String,
});
const paymentOptions = [
  "Tiền mặt",
  "Chuyển khoản",
  "QR Code",
  "9Pay",
  "Momo",
  "VNPay",
  "Thẻ tín dụng",
];

const emit = defineEmits(["update:isSelectedDialogVisible", "update:payment"]);
const onReset = () => {
  emit("update:isSelectedDialogVisible", false);
  form.reset();
  form.errors = {};
};

watch(
  () => props.isSelectedDialogVisible,
  async (visible) => {
    if (!visible || !props.selectedBookingIds.length) {
      form.total_paid = "";
      return;
    }
    try {
      const res = await axios.get(
        route("debtPayment.getRemainings", { ids: props.selectedBookingIds })
      );
      form.total_paid = res.data;
    } catch (error) {
      console.error("Lỗi khi lấy dữ liệu remaining:", error);
    }
  }
);

const defaultFormData = {
  total_paid: "",
  partner_id: null,
  payment_method: "",
  issued_date: "",
  note: "",
  ids: [],
};

const formRef = ref();
const form = useForm({ ...defaultFormData });
const partnerError = ref("");

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  form.partner_id = props.partnerId;
  form.ids = props.selectedBookingIds;

  partnerError.value = "";

  form.post(route("debtPayment.storeRemainings"), {
    onSuccess: () => {
      onReset();
      form.reset();
      emit("update:payment");
    },
    onError: (errors) => {
      if (errors.partner_id) {
        partnerError.value = errors.partner_id;
      }
    },
  });
};
</script>
