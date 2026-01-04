<template>
  <VDialog
    :model-value="props.isPayDialogVisible"
    @update:model-value="onReset"
    class="v-dialog-sm"
    persistent
    max-width="500"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="payment-dialog-card" elevation="8">
      <!-- Header with gradient background -->
      <VCardItem class="payment-header">
        <div class="d-flex align-center">
          <VIcon
            icon="tabler-credit-card"
            size="32"
            color="white"
            class="me-3"
          />
          <div>
            <VCardTitle class="text-white text-h5 font-weight-bold">
              Thanh toán
            </VCardTitle>
            <VCardSubtitle class="text-white text-opacity-80">
              Hoàn tất giao dịch thanh toán
            </VCardSubtitle>
          </div>
        </div>
      </VCardItem>

      <VForm ref="formRef" @submit.prevent="onSubmit">
        <VCardItem class="pa-6">
          <!-- Amount Input with enhanced styling -->
          <div class="amount-section mb-6">
            <VLabel class="text-subtitle-1 font-weight-medium mb-2 d-block">
              <VIcon
                icon="tabler-currency-dollar"
                size="20"
                class="me-2"
                color="success"
              />
              Số tiền thanh toán
            </VLabel>
            <AppTextField
              v-model="form.paid"
              :error-messages="form.errors.paid"
              type="number"
              placeholder="Nhập số tiền..."
              :rules="[requiredValidator]"
              variant="outlined"
              density="comfortable"
              class="amount-input"
              prepend-inner-icon="tabler-currency-dollar"
              hide-details="auto"
            />
          </div>

          <!-- Payment Method Selection with dropdown -->
          <div class="payment-method-section">
            <VLabel class="text-subtitle-1 font-weight-medium mb-2 d-block">
              <VIcon
                icon="tabler-credit-card"
                size="20"
                class="me-2"
                color="primary"
              />
              Hình thức thanh toán
            </VLabel>
            <AppSelect
              v-model="form.payment_method"
              :items="paymentOptions"
              :error-messages="form.errors.payment_method"
              :rules="[requiredValidator]"
              variant="outlined"
              density="comfortable"
              placeholder="Chọn hình thức thanh toán..."
              prepend-inner-icon="tabler-credit-card"
              hide-details="auto"
            />
          </div>
        </VCardItem>

        <!-- Action Buttons with enhanced styling -->
        <VCardActions class="pa-6 pt-0">
          <VSpacer />
          <VBtn
            variant="outlined"
            color="secondary"
            @click="onReset"
            class="me-3"
            size="large"
          >
            <VIcon icon="tabler-x" size="18" class="me-2" />
            Hủy bỏ
          </VBtn>
          <VBtn
            type="submit"
            :disabled="form.processing"
            size="large"
            class="payment-submit-btn"
            :loading="form.processing"
          >
            <VIcon icon="tabler-check" size="18" class="me-2" />
            {{ form.processing ? "Đang xử lý..." : "Thanh toán" }}
          </VBtn>
        </VCardActions>
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
const formRef = ref();

const onReset = () => {
  emit("update:isPayDialogVisible", false);
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

const defaultFormData = {
  paid: "",
  payment_method: "",
};

const form = useForm(defaultFormData);

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  form.post(route("payment.storePayment", props.paymentTargetId), {
    preserveScroll: true,
    onSuccess: () => {
      onReset();
      form.reset();
      emit("update:payment");
    },
    onError: () => {},
  });
};

watch(
  () => props.isPayDialogVisible,
  (isVisible) => {
    if (!isVisible) return;
  }
);
</script>

<style scoped>
.payment-dialog-card {
  border-radius: 16px;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #e2e8f0;
}

.payment-header {
  background: #6366f1;
  padding: 24px;
  margin: 0;
}

.amount-input {
  font-size: 1.1rem;
  font-weight: 500;
}

.amount-input :deep(.v-field__input) {
  padding: 16px;
}

.amount-input :deep(.v-field) {
  background-color: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.payment-submit-btn {
  background: #ffffff;
  color: #6366f1;
  border: 2px solid #6366f1;
  border-radius: 8px;
  font-weight: 600;
  text-transform: none;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
  transition: all 0.3s ease;
}

.amount-section,
.payment-method-section {
  position: relative;
}

.payment-method-section :deep(.v-field) {
  background-color: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

/* Animation for dialog appearance */
.v-dialog .v-card {
  animation: slideInUp 0.3s ease-out;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive adjustments */
@media (max-width: 600px) {
  .payment-header {
    padding: 16px;
  }

  .payment-header .v-card-title {
    font-size: 1.25rem;
  }
}
</style>
