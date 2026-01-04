<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 1200"
    :model-value="props.isDialogVisible"
    @update:model-value="close"
  >
    <DialogCloseBtn @click="close" />

    <VCard class="pa-2 pa-sm-8">
      <VCardTitle>
        <div class="text-h4 text-center mb-6">
          {{ data ? "Chỉnh sửa khách hàng" : "Thêm khách hàng" }}
        </div>
      </VCardTitle>
      <VCardText>
        <VForm ref="formRef" @submit.prevent="handleSubmit">
          <!-- Thông tin cơ bản -->
          <VCard elevation="0" class="pa-4 mb-4 border rounded-md">
            <h3 class="text-subtitle-3 font-weight-medium mb-4">
              Thông tin cơ bản
            </h3>
            <VRow>
              <VCol cols="12" sm="12" md="6" lg="6">
                <AppTextField
                  v-model="form.full_name"
                  :error-messages="form.errors.full_name"
                  label="Họ và tên"
                  required
                />
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="6">
                <AppSelect
                  v-model="form.type"
                  :error-messages="form.errors.type"
                  :items="['Sale', 'Sale TA', 'OTA', 'Social', 'Walk-in']"
                  label="Nguồn"
                  required
                  @update:model-value="onCustomerTypeChange"
                />
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="6">
                <AppTextField
                  v-model="form.email"
                  :error-messages="form.errors.email"
                  label="Email"
                />
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="6">
                <AppTextField
                  v-model="form.phone"
                  :error-messages="form.errors.phone"
                  label="Số điện thoại"
                />
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="6" v-if="['Sale', 'Sale TA'].includes(form.type)">
                <AppSelect
                  item-title="name"
                  item-value="id"
                  v-model="form.partner_id"
                  :items="partnerOptions"
                  label="Đối tác"
                />
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="6" v-if="['Sale', 'Sale TA'].includes(form.type)">
                <div class="d-flex h-100 align-end">
                  <VBtn @click="addPartner"> Thêm đối tác</VBtn>
                </div>
              </VCol>
              <VCol
                cols="12"
                v-if="['Super Admin', 'Admin'].includes(user.role)"
              >
                <AppSelect
                  item-title="name"
                  item-value="id"
                  v-model="form.partner_group_id"
                  :items="props.partnerGroup"
                  :error-messages="form.errors.partner_group_id"
                  label="Chọn Partner Admin"
                />
              </VCol>
            </VRow>
          </VCard>

          <!-- Thông tin định danh -->
          <VCard elevation="0" class="pa-4 mb-4 border rounded-md">
            <h3 class="text-subtitle-3 font-weight-medium mb-4">
              Thông tin định danh
            </h3>
            <VRow>
              <VCol cols="12" sm="12" md="6" lg="6">
                <AppSelect
                  v-model="form.id_type"
                  :items="['CCCD/CMND', 'Hộ chiếu', 'Bằng lái xe', 'Khác']"
                  label="Loại giấy tờ"
                />
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="6">
                <AppTextField v-model="form.id_number" label="Số giấy tờ" />
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="6">
                <AppTextField
                  v-model="form.dob"
                  label="Ngày sinh"
                  type="date"
                />
              </VCol>
              <!-- <VCol cols="12" sm="12" md="6" lg="6">
                <AppTextField v-model="form.nationality" label="Quốc tịch" />
              </VCol> -->
              <VCol cols="12" sm="12" md="6" lg="6">
                <AppTextField v-model="form.country" label="Quốc gia" />
              </VCol>
              <VCol cols="12">
                <div class="v-label mb-1 text-body-2 text-wrap">
                  Ảnh CCCD / Passport
                </div>
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
          </VCard>

          <!-- Địa chỉ -->
          <VCard elevation="0" class="pa-4 border rounded-md">
            <h3 class="text-subtitle-3 font-weight-medium mb-4">Địa chỉ</h3>
            <VRow>
              <VCol cols="12" sm="12" md="6" lg="6">
                <AppTextField v-model="form.address" label="Địa chỉ chi tiết" />
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="6">
                <AppTextField v-model="form.city" label="Thành phố" />
              </VCol>
            </VRow>
          </VCard>

          <VCard elevation="0" flat class="pa-4 mt-4">
            <VRow class="ga-2 justify-center">
              <VBtn
                color="secondary"
                variant="tonal"
                @click="close"
                :disabled="form.processing"
              >
                Hủy
              </VBtn>
              <VBtn type="submit" :loading="form.processing">Lưu</VBtn>
            </VRow>
          </VCard>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
  <FormPartnerDialog
    v-model:is-dialog-visible="partnerDialog"
    @update:partner="loadData"
  />
</template>

<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { watch } from "vue";
import FormPartnerDialog from "./FormPartnerDialog.vue";
const props = defineProps({
  isDialogVisible: Boolean,
  data: Object,
  partnerGroup: Object,
});
const page = usePage();
const user = computed(() => page.props.auth.user);
const emit = defineEmits(["update:isDialogVisible"]);
const formRef = ref();
const partnerDialog = ref(false);
const defaultData = {
  full_name: "",
  type: "",
  email: "",
  phone: "",
  id_type: "",
  id_number: "",
  dob: "",
  // nationality: "",
  country: "",
  address: "",
  city: "",
  partner_id: "",
  image: null,
  partner_group_id: "",
};
const addPartner = () => {
  partnerDialog.value = true;
};

const form = useForm(defaultData);
const partnerOptions = ref([]);
watch(
  () => props.isDialogVisible,
  (isVisible) => {
    if (!isVisible) return;

    if (!props.data) {
      form.defaults(defaultData);
      form.reset();
    } else {
      form.defaults(props.data);
      form.reset();
    }

    getPartnerByType(props.data?.type || "");
  }
);
const loadData = () => {
  getPartnerByType(props.data?.type || "");
};

const onCustomerTypeChange = async (newType) => {
  if (["Sale", "Sale TA"].includes(newType)) {
    await getPartnerByType(newType);
    form.partner_id = null;
  } else {
    partnerOptions.value = [];
    form.partner_id = null;
  }
};
async function getPartnerByType(val) {
  const res = await axios.get(route("partner.getPartnerByType"), {
    params: { type: val },
  });
  partnerOptions.value = res.data;
  // console.log("Partner options updated:", partnerOptions.value);
}
const close = () => {
  emit("update:isDialogVisible", false);
};

const handleSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  if (props.data) {
    form.post(route("customers.updateWithFile", props.data.id), {
      onSuccess: () => {
        emit("update:isDialogVisible", false);
      },
    });
  } else {
    form.post(route("customers.store"), {
      onSuccess: () => {
        emit("update:isDialogVisible", false);
      },
    });
  }
};
import { useDropZone, useFileDialog, useObjectUrl } from "@vueuse/core";

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
