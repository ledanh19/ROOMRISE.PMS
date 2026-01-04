<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 800"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-6">Cập nhật loại phòng</h4>

        <VForm ref="formRef" @submit.prevent="onSubmit">
          <VRow>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="form.name"
                :error-messages="form.errors.name"
                :rules="[requiredValidator]"
                label="Tên phòng"
                placeholder="Tên phòng"
              />
            </VCol>

            <VCol cols="12" md="6">
              <AppSelect
                v-model="form.status"
                :error-messages="form.errors.status"
                :items="ROOM_STATUS"
                :rules="[requiredValidator]"
                label="Trạng thái"
                placeholder="Trạng thái"
              />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="form.note"
                label="Ghi chí"
                placeholder="Ghi chí"
              />
            </VCol>
          </VRow>

          <VRow class="ga-2 justify-center mt-4">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="onReset"
              :disabled="form.processing"
            >
              Hủy
            </VBtn>
            <VBtn type="submit" :loading="form.processing">Lưu</VBtn>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ROOM_STATUS } from "@/utils/constants";
import { useForm } from "@inertiajs/vue3";
import { watch } from "vue";

const props = defineProps({
  isDialogVisible: Boolean,
  data: Object,
});

const emit = defineEmits(["update:isDialogVisible"]);

const formRef = ref();

const form = useForm({
  name: "",
  status: "",
  note: "",
});

const onReset = () => {
  emit("update:isDialogVisible", false);
};

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  form.put(route("rooms.update", { room: props.data.id }), {
    onSuccess: () => {
      onReset();
    },
    onError: () => {},
  });
};

watch(
  () => props.isDialogVisible,
  (isVisible) => {
    if (!isVisible) return;

    form.clearErrors();
    form.defaults(props.data);
    form.reset();
  }
);
</script>
