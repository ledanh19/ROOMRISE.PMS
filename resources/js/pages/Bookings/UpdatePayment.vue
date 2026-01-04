<template>
  <VDialog
    :model-value="props.isPayDialogVisible"
    @update:model-value="onReset"
    width="1200"
  >
    <DialogCloseBtn @click="onReset" />
    <VCard class="pa-6">
      <VCardItem
        class="pa-0 text-h5 font-weight-medium"
        :title="
          props.checkout == 'checkout'
            ? 'Khách trả phòng'
            : 'Cập nhật thanh toán'
        "
      ></VCardItem>
      <VCardText class="border mt-5 rounded-lg pa-4">
        <VRow>
          <VCol cols="6" sm="6" md="4" lg="4">
            <div class="d-flex">
              <div class="text-h6">
                Tiền phòng <br />
                (khách thanh toán)
              </div>
              <div class="text-h6 ml-3 font-weight-bold">
                {{ formatCurrency(booking.customer_payment_amount) }}
              </div>
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="4" lg="4">
            <div class="d-flex">
              <div class="text-h6">Tiền dịch vụ <br />(khách thanh toán)</div>
              <div class="text-h6 ml-3 font-weight-bold">
                {{ formatCurrency(0) }}
              </div>
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="4" lg="4">
            <div class="d-flex">
              <div class="text-h6">PPTT tiền phòng</div>
              <div class="text-h6 ml-3 font-weight-bold">
                {{ booking.payment_method }}
              </div>
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="4" lg="4">
            <div class="d-flex">
              <div class="text-h6">Tổng số tiền</div>
              <div class="text-h6 ml-3 font-weight-bold">
                {{ formatCurrency(booking.customer_payment_amount) }}
              </div>
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="4" lg="4">
            <div class="d-flex">
              <div class="text-h6">Đã thanh toán</div>
              <div class="text-h6 ml-3 font-weight-bold">
                {{ formatCurrency(booking.paid) }}
              </div>
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="4" lg="4">
            <div class="d-flex">
              <div class="text-h6">Còn lại</div>
              <div class="text-h6 ml-3 font-weight-bold">
                {{ formatCurrency(booking.remaining) }}
              </div>
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VForm ref="formRef" @submit.prevent="onSubmit">
        <VCardText
          class="mt-5 border rounded-lg pa-4"
          v-if="booking.payment_status !== 'Đã thanh toán'"
        >
          <VRow>
            <VCol cols="6" sm="6" md="3" lg="3">
              <AppSelect
                :rules="[requiredValidator]"
                label="Hình thức thanh toán"
                v-model="form.payment_method"
                :items="paymentMethodOptions"
                :error-messages="form.errors.payment_method"
              />
            </VCol>
            <VCol cols="6" sm="6" md="3" lg="3">
              <AppTextField
                v-model="form.paid"
                :error-messages="form.errors.paid"
                type="text"
                label="Số tiền đã thanh toán"
              />
            </VCol>
            <VCol cols="6" sm="6" md="3" lg="3">
              <AppTextField
                v-model="form.date"
                :error-messages="form.errors.date"
                type="date"
                label="Ngày thanh toán"
              />
            </VCol>
            <VCol cols="6" sm="6" md="3" lg="3">
              <AppTextarea
                auto-grow
                rows="1"
                label="Ghi chú"
                placeholder="Ghi chú"
                v-model="form.note"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex pl-0 mt-4 flex-wrap gap-3">
          <VBtn type="submit" :loading="form.processing">{{
            props.checkout == "checkout" ? "Khách trả phòng" : "Lưu"
          }}</VBtn>
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
import { computed, ref, watch } from "vue";
import dayjs from "dayjs";
import { debounce } from "lodash";
const props = defineProps({
  isPayDialogVisible: {
    type: Boolean,
    required: true,
  },
  booking: Object,
  checkout: String,
});
const emit = defineEmits(["update:isPayDialogVisible", "update:Payment"]);
const onReset = () => {
  emit("update:isPayDialogVisible", false);
};
const paymentMethodOptions = [
  "Tiền mặt",
  "Chuyển khoản",
  "QR Code",
  "9Pay",
  "Momo",
  "VNPay",
  "Thẻ tín dụng",
];
const formRef = ref();
const defaultFormData = {
  payment_method: "",
  note: "",
  paid: "",
  date: "",
  checkout: "",
};
const form = useForm(defaultFormData);
const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  form.checkout = props.checkout;
  form.post(route("payment.storePayment", props.booking.id), {
    preserveScroll: true,
    onSuccess: () => {
      onReset();
      form.reset();
      emit("update:Payment");
    },
    onError: () => {},
  });
};
</script>
