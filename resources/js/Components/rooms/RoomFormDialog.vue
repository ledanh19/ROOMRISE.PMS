<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 800"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-6">
          {{ isNewForm ? "Thêm mới" : "Cập nhật" }} loại phòng
        </h4>

        <VForm ref="formRef" @submit.prevent="onSubmit">
          <VRow>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="form.name"
                :error-messages="form.errors.name"
                :rules="[requiredValidator]"
                label="Tên loại phòng"
                placeholder="Nhập loại tên phòng"
              />
            </VCol>

            <!-- <VCol cols="12" md="6">
              <AppSelect
                v-if="isNewForm"
                v-model="form.property_id"
                :error-messages="form.errors.property_id"
                :items="properties"
                item-title="name"
                item-value="id"
                :rules="[requiredValidator]"
                label="Chỗ nghỉ"
                placeholder="Chọn chỗ nghỉ"
              />
            </VCol> -->

            <!-- <VCol cols="12" md="6">
              <AppTextField
                v-model="form.unit"
                :error-messages="form.errors.unit"
                label="Đơn vị"
                placeholder="VD: Phòng, Căn"
              />
            </VCol> -->

            <!-- <VCol cols="12" md="6">
              <AppTextField
                v-model="form.max_people"
                :error-messages="form.errors.max_people"
                :rules="[requiredValidator]"
                label="Tổng số người tối đa"
                type="number"
                placeholder="Tối đa người lớn + trẻ em"
              />
            </VCol> -->

            <VCol cols="12" md="6">
              <AppTextField
                v-model="form.adults"
                :error-messages="form.errors.adults"
                :rules="[requiredValidator]"
                label="Số người lớn"
                type="number"
              />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField
                v-model="form.children"
                :error-messages="form.errors.children"
                :rules="[requiredValidator]"
                label="Số trẻ em"
                type="number"
              />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField
                v-model="form.quantity"
                :error-messages="form.errors.quantity"
                :rules="[requiredValidator]"
                label="Số lượng phòng"
                type="number"
                placeholder="Nhập số lượng phòng"
              />
            </VCol>
          </VRow>
          <VRow class="border ma-0 mt-4">
            <VCol
              v-for="(unit, i) in form.room_units"
              :key="i"
              cols="12"
              md="4"
              class="py-2"
            >
              <AppTextField
                v-model="form.room_units[i].name"
                :rules="[requiredValidator]"
                :label="`Tên Unit ${i + 1}`"
                placeholder="Tên unit"
              />
            </VCol>
          </VRow>

          <!-- Nút hành động -->
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
import { useForm } from "@inertiajs/vue3";
import { computed, watch } from "vue";

const props = defineProps({
  isDialogVisible: Boolean,
  data: Object,
  property_id: Number,
});

const defaultFormData = {
  id: null,
  name: "",
  property_id: null,
  // unit: "",
  quantity: 0,
  room_units: [],
  // max_people: 1,
  adults: 1,
  children: 0,
};

const emit = defineEmits(["update:isDialogVisible"]);

const formRef = ref();

const isNewForm = computed(() => !props.data);
const form = useForm({
  id: null,
  name: "",
  property_id: null,
  // unit: "",
  quantity: 0,
  room_units: [],
  // max_people: 1,
  adults: 1,
  children: 0,
});

watch(
  () => form.quantity,
  (newQuantity) => {
    const qty = Number(newQuantity) || 0;

    if (qty > form.room_units.length) {
      for (let i = form.room_units.length; i < qty; i++) {
        form.room_units.push({
          name: i + 1 + "",
        });
      }
    } else {
      form.room_units.splice(qty);
    }
  }
);

const onReset = () => {
  emit("update:isDialogVisible", false);
};

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  if (isNewForm.value) {
    form.post(route("room-types.store"), {
      onSuccess: () => {
        onReset();
      },
      onError: () => {},
    });
  } else {
    form.put(route("room-types.update", props.data.id), {
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
      form.defaults({ ...defaultFormData, property_id: props.property_id });
      form.reset();
    } else {
      form.defaults(props.data);
      form.reset();
    }
  }
);
</script>
