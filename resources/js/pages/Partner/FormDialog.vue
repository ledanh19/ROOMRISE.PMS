<template>
  <VDialog
    :model-value="props.isDialogVisible || props.isEditDialogVisible"
    @update:model-value="
      (val) => {
        if (!val) {
          emit('update:isDialogVisible', false);
          emit('update:isEditDialogVisible', false);
        }
      }
    "
    width="900"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard :title="isEditMode ? 'Chỉnh sửa đối tác' : 'Thêm đối tác mới '">
      <VForm ref="formRef" @submit.prevent="onSubmit">
        <VCardItem>
          <VRow>
            <VCol cols="12" sm="12" md="6" lg="6" class="pb-0">
              <AppTextField
                v-model="form.name"
                :error-messages="form.errors.paid"
                type="text"
                label="Tên đối tác"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6" class="pb-0">
              <AppTextField
                v-model="form.email"
                :error-messages="form.errors.email"
                type="email"
                label="Email"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6" class="pb-0">
              <AppTextField
                v-model="form.phone"
                :error-messages="form.errors.phone"
                type="number"
                label="Số điện thoại"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6" class="pb-0">
              <AppSelect
                :rules="[requiredValidator]"
                label="Loại đối tác"
                v-model="form.type"
                :items="typeOptions"
                :error-messages="form.errors.type"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6" class="pb-0">
              <AppTextField
                v-model="form.commission"
                :error-messages="form.errors.commission"
                type="number"
                label="Hoa hồng (%)"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6" class="pb-0">
              <AppSelect
                :rules="[requiredValidator]"
                label="Hình thức thanh toán"
                v-model="form.payment_method"
                :items="paymentMethodOptions"
                :error-messages="form.errors.payment_method"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6" class="pb-0">
              <AppTextField
                v-model="form.internal_code"
                :error-messages="form.errors.internal_code"
                type="text"
                label="Mã nội bộ"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6" class="pb-0">
              <AppSelect
                :rules="[requiredValidator]"
                label="Trạng thái"
                v-model="form.status"
                :items="statusOptions"
                :error-messages="form.errors.status"
              />
            </VCol>
            <VCol cols="12" class="pb-0">
              <AppTextField
                v-model="form.address"
                :error-messages="form.errors.address"
                type="text"
                label="Địa chỉ"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6" class="pb-0">
              <AppTextField
                v-model="form.city"
                :error-messages="form.errors.city"
                type="text"
                label="Thành phố"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6" class="pb-0">
              <AppTextField
                v-model="form.country"
                :error-messages="form.errors.country"
                type="text"
                label="Quốc gia"
              />
            </VCol>
            <VCol cols="12" v-if="['Super Admin', 'Admin'].includes(user.role)">
              <AppSelect
                item-title="name"
                item-value="id"
                v-model="form.partner_group_id"
                :items="props.partnerGroup"
                :error-messages="form.errors.partner_group_id"
                label="Chọn Partner Admin"
              />
            </VCol>

            <VCol cols="12">
              <AppTextarea
                v-model="form.internal_note"
                :error-messages="form.errors.internal_note"
                type="text"
                label="Ghi chú nội bộ"
              />
            </VCol>
          </VRow>
        </VCardItem>
        <VCardText class="d-flex justify-end flex-wrap gap-3">
          <VBtn variant="tonal" color="secondary" @click="onReset"> Hủy </VBtn>
          <VBtn type="submit" :disabled="form.processing">
            {{ isEditMode ? "Chỉnh sửa đối tác" : "Thêm đối tác mới" }}
          </VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>
</template>
<script setup>
import { useForm, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const page = usePage();
const user = computed(() => page.props.auth.user);

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  isEditDialogVisible: {
    type: Boolean,
    required: true,
  },
  partnerId: Number,
  partnerGroup: Object,
});
const emit = defineEmits([
  "update:isDialogVisible",
  "update:isEditDialogVisible",
  "update:partner",
]);
const formRef = ref();
const partner = ref({});
const onReset = () => {
  emit("update:isDialogVisible", false);
  emit("update:isEditDialogVisible", false);
};

const defaultFormData = {
  name: "",
  email: "",
  phone: "",
  type: "",
  commission: "",
  payment_method: "Công nợ",
  internal_code: "",
  status: "Hoạt động",
  address: "",
  city: "",
  country: "",
  internal_note: "",
  partner_group_id: "",
};
const form = useForm(defaultFormData);
const isEditMode = computed(() => props.isEditDialogVisible && props.partnerId);
const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  if (isEditMode.value) {
    form.put(route("partner.update", { id: props.partnerId }), {
      preserveScroll: true,
      onSuccess: () => {
        onReset();
        form.reset();
        emit("update:partner");
      },
      onError: () => {},
    });
  } else {
    form.post(route("partner.store"), {
      preserveScroll: true,
      onSuccess: () => {
        onReset();
        form.reset();
        emit("update:partner");
      },
      onError: () => {},
    });
  }
};

const typeOptions = ["Sale", "Sale TA"];
const paymentMethodOptions = ["Thanh toán ngay", "Công nợ"];
const statusOptions = ["Hoạt động", "Không hoạt động"];
watch(
  () => props.isDialogVisible,
  (isVisible) => {
    if (isVisible) {
      form.reset();
      if (!["Super Admin", "Admin"].includes(user.value.role)) {
        form.partner_group_id = user.value.partner_group_id;
      }
    }
  }
);

watch(
  [() => props.isEditDialogVisible, () => props.partnerId],
  async ([editVisible, id]) => {
    if (!editVisible || !id) return;

    const res = await axios.get(route("partner.getPartnerById", { id }));
    partner.value = res.data;

    if (partner.value) {
      form.name = partner.value.name;
      form.email = partner.value.email;
      form.phone = partner.value.phone;
      form.type = partner.value.type;
      form.commission = partner.value.commission;
      form.payment_method = partner.value.payment_method;
      form.internal_code = partner.value.internal_code;
      form.status = partner.value.status;
      form.address = partner.value.address;
      form.city = partner.value.city;
      form.country = partner.value.country;
      form.internal_note = partner.value.internal_note;
      form.partner_group_id = partner.value.partner_group_id;
    }
  }
);
</script>
