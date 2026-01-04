<template>
  <VDialog
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
    max-width="600px"
    persistent
  >
    <DialogCloseBtn @click="onReset" />
    <VCard class="pa-2 pa-sm-10">
      <VCardTitle class="text-h4 text-center">
        {{ isNewForm ? "Thêm nguồn đặt phòng" : "Cập nhật nguồn đặt phòng" }}
      </VCardTitle>

      <VCardText>
        <VForm ref="formRef">
          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="form.name"
                label="Tên nguồn đặt phòng"
                :error-messages="form.errors.name"
                required
              />
            </VCol>
          </VRow>
          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="form.price_percentage"
                label="Phần trăm giá"
                :error-messages="form.errors.price_percentage"
                required
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VCardActions class="justify-center">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="onReset"
          :disabled="form.processing"
        >
          Hủy
        </VBtn>
        <VBtn
          color="primary"
          variant="flat"
          @click="onSubmit"
          :loading="form.processing"
        >
          {{ isNewForm ? "Thêm" : "Cập nhật" }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, watch } from "vue";

const props = defineProps({
  isDialogVisible: Boolean,
  data: Object,
});

const defaultFormData = {
  name: "",
  is_default: false,
  price_percentage: 0,
};

const emit = defineEmits(["update:isDialogVisible"]);

const formRef = ref();

const isNewForm = computed(() => !props.data);
const form = useForm({
  name: "",
  is_default: false,
});

const onReset = () => {
  emit("update:isDialogVisible", false);
};

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  if (isNewForm.value) {
    form.post(route("booking-sources.store"), {
      onSuccess: () => {
        onReset();
      },
      onError: () => {},
    });
  } else {
    form.put(route("booking-sources.update", props.data.id), {
      onSuccess: () => {
        onReset();
      },
      onError: () => {},
    });
  }
};

watch(
  () => props.isDialogVisible,
  (isVisible) => {
    if (!isVisible) return;

    form.clearErrors();

    if (isNewForm.value) {
      form.defaults(defaultFormData);
      form.reset();
    } else {
      form.defaults(props.data);
      form.reset();
    }
  }
);
</script>
