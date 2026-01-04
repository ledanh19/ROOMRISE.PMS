<template>
  <VDialog
    :model-value="props.isCheckInDialogVisible"
    @update:model-value="onReset"
    width="1200"
  >
    <DialogCloseBtn @click="onReset" />
    <VCard class="pa-6">
      <VCardItem class="pa-0 text-h5 font-weight-medium"
        >Khách nhận phòng</VCardItem
      >
      <VForm ref="formRef" @submit.prevent="onSubmit">
        <VCardItem
          class="border mt-5 rounded-lg pa-4"
          title="Thông tin khách hàng"
        >
          <VRow class="mt-5">
            <VCol cols="6">
              <VRow
                class="font-weight-medium text-high-emphasis d-flex align-center"
              >
                <VCol cols="4"><div>Tên</div></VCol>
                <VCol cols="8">
                  <AppTextField
                    v-model="form.full_name"
                    :error-messages="form.errors.full_name"
                    type="text"
                  />
                </VCol>
              </VRow>
              <VRow
                class="font-weight-medium text-high-emphasis d-flex align-center"
              >
                <VCol cols="4"><div>Email</div></VCol>
                <VCol cols="8">
                  <AppTextField
                    v-model="form.email"
                    :error-messages="form.errors.email"
                    type="email"
                  />
                </VCol>
              </VRow>
              <VRow
                class="font-weight-medium text-high-emphasis d-flex align-center"
              >
                <VCol cols="4"><div>SĐT</div></VCol>
                <VCol cols="8">
                  <AppTextField
                    v-model="form.phone"
                    :error-messages="form.errors.phone"
                    type="text"
                  />
                </VCol>
              </VRow>
              <VRow
                class="font-weight-medium text-high-emphasis d-flex align-center"
              >
                <VCol cols="4"><div>Sinh nhật</div></VCol>
                <VCol cols="8">
                  <AppTextField
                    v-model="form.dob"
                    :error-messages="form.errors.dob"
                    type="date"
                  />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="6">
              <VRow
                class="font-weight-medium text-high-emphasis d-flex align-center"
              >
                <VCol cols="4"><div>Địa chỉ</div></VCol>
                <VCol cols="8">
                  <AppTextField
                    v-model="form.address"
                    :error-messages="form.errors.address"
                    type="text"
                  />
                </VCol>
              </VRow>
              <VRow
                class="font-weight-medium text-high-emphasis d-flex align-center"
              >
                <VCol cols="4"><div>Thành phố</div></VCol>
                <VCol cols="8">
                  <AppTextField
                    v-model="form.country"
                    :error-messages="form.errors.country"
                    type="text"
                  />
                </VCol>
              </VRow>
              <VRow
                class="font-weight-medium text-high-emphasis d-flex align-center"
              >
                <VCol cols="4"><div>Quốc gia</div></VCol>
                <VCol cols="8">
                  <AppTextField
                    v-model="form.nationality"
                    :error-messages="form.errors.nationality"
                    type="text"
                  />
                </VCol>
              </VRow>
            </VCol>
          </VRow>
        </VCardItem>
        <VCardItem class="border mt-5 rounded-lg pa-4" title="CCCD/Passport">
          <VRow class="mt-5">
            <VCol cols="6">
              <VRow
                class="font-weight-medium text-high-emphasis d-flex align-center"
              >
                <VCol cols="4"><div>Số định danh</div></VCol>
                <VCol cols="8">
                  <AppTextField
                    v-model="form.id_number"
                    :error-messages="form.errors.id_number"
                    type="text"
                  />
                </VCol>
              </VRow>
              <VRow
                class="font-weight-medium text-high-emphasis d-flex align-center"
              >
                <VCol cols="4"><div>Ngày phát hành</div></VCol>
                <VCol cols="8">
                  <AppTextField
                    v-model="form.issue_date"
                    :error-messages="form.errors.issue_date"
                    type="date"
                  />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="6">
              <VRow
                class="font-weight-medium text-high-emphasis d-flex align-center"
              >
                <VCol cols="4"> <div>Ảnh CCCD / Passport</div></VCol>
                <VCol cols="8">
                  <div class="flex">
                    <div class="w-full h-auto relative">
                      <div
                        ref="dropZoneRef"
                        class="cursor-pointer"
                        @click="() => open()"
                      >
                        <div
                          v-if="fileData.length === 0"
                          class="d-flex flex-column justify-center align-center gap-y-2 pa-8 drop-zone rounded"
                        >
                          <div class="text-h6">Kéo thả tệp của bạn hoặc</div>
                          <VBtn variant="tonal" size="small"> Tìm kiếm </VBtn>
                        </div>

                        <div
                          v-else
                          class="d-flex justify-center align-center gap-3 pa-8 drop-zone flex-wrap"
                        >
                          <VRow class="match-height w-100">
                            <template
                              v-for="(item, index) in fileData"
                              :key="index"
                            >
                              <VCol cols="12" sm="12">
                                <VCard :ripple="false">
                                  <VCardText
                                    class="d-flex flex-column"
                                    @click.stop
                                  >
                                    <VImg
                                      :src="item.url"
                                      width="200px"
                                      height="150px"
                                      class="w-100 mx-auto"
                                    />
                                    <div class="mt-2">
                                      <span class="clamp-text text-wrap">
                                        {{ item.file.name }}
                                      </span>
                                      <span>
                                        {{ item.file.size / 1000 }} KB
                                      </span>
                                    </div>
                                  </VCardText>
                                  <VCardActions>
                                    <VBtn
                                      variant="text"
                                      block
                                      @click.stop="fileData.splice(index, 1)"
                                    >
                                      Xóa
                                    </VBtn>
                                  </VCardActions>
                                </VCard>
                              </VCol>
                            </template>
                          </VRow>
                        </div>
                      </div>
                    </div>
                  </div>
                </VCol>
              </VRow>
            </VCol>
          </VRow>
        </VCardItem>
        <VCardText class="d-flex pl-0 mt-4 flex-wrap gap-3">
          <VBtn type="submit" :loading="form.processing">Lưu</VBtn>
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
import { useDropZone, useFileDialog, useObjectUrl } from "@vueuse/core";
import { ref, watch } from "vue";
const props = defineProps({
  isCheckInDialogVisible: {
    type: Boolean,
    required: true,
  },
  booking: Object,
  customer: Object,
  roomId: Number,
});
const emit = defineEmits([
  "update:isCheckInDialogVisible",
  "update:customerCheckIn",
]);
const onReset = () => {
  emit("update:isCheckInDialogVisible", false);
};

const formRef = ref();
const defaultFormData = {
  user_id: null,
  full_name: "",
  email: "",
  phone: "",
  dob: "",
  note: "",
  address: "",
  country: "",
  nationality: "",
  id_number: "",
  issue_date: "",
  image: "",
};
const form = useForm(defaultFormData);

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  form.user_id = props.customer.id;
  form.post(route("bookings.customerCheckIn", props.roomId), {
    preserveScroll: true,
    onSuccess: () => {
      onReset();
      form.reset();
      emit("update:customerCheckIn");
    },
    onError: () => {},
  });
};

watch(
  () => props.isCheckInDialogVisible,
  (visible) => {
    if (!visible) {
      return;
    }
    form.clearErrors();
    if (!props.customer) {
      form.defaults(defaultFormData);
      form.reset();
    } else {
      form.defaults(props.customer);
      form.reset();
    }
  }
);

const dropZoneRef = ref();
const fileData = ref([]);
const { open, onChange } = useFileDialog({ accept: "image/*" });
function onDrop(droppedFiles) {
  droppedFiles?.forEach((file) => {
    if (file.type.slice(0, 6) !== "image/") {
      alert("Only image files are allowed");
      return;
    }
    const objectUrl = useObjectUrl(file).value ?? "";
    fileData.value.push({
      file,
      url: objectUrl,
    });
    form.image = file;
  });
}

onChange((selectedFiles) => {
  if (!selectedFiles) return;
  for (const file of selectedFiles) {
    const objectUrl = useObjectUrl(file).value ?? "";
    fileData.value.push({
      file,
      url: objectUrl,
    });
    form.image = file;
  }
});

useDropZone(dropZoneRef, onDrop);
</script>

<style scoped>
.drop-zone {
  border: 1px dashed rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}
</style>
