<template>
  <VDialog
    :model-value="props.isAddCustomerDialogVisible"
    @update:model-value="onReset"
    width="1200"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard title="Quản lý khách lưu trú">
      <VForm ref="formRef" @submit.prevent="onSubmit">
        <VCardItem>
          <VCard class="border pa-6">
            <VRow>
              <VCol cols="12" sm="12" md="4" lg="4">
                <AppTextField
                  label="Người lớn"
                  v-model="form.adults"
                  :error-messages="form.errors.adults"
                  type="number"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" sm="12" md="4" lg="4">
                <AppTextField
                  label="Trẻ em"
                  v-model="form.children"
                  :error-messages="form.errors.children"
                  type="number"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" sm="12" md="4" lg="4">
                <AppTextField
                  label="Trẻ sơ sinh"
                  v-model="form.newborn"
                  :error-messages="form.errors.newborn"
                  type="number"
                  :rules="[requiredValidator]"
                />
              </VCol>
            </VRow>
          </VCard>
          <VCard class="mt-5">
            <VCardItem
              class="border rounded-lg mb-5"
              v-for="(customer, index) in form.customers"
              :key="index"
            >
              <VRow class="mt-3">
                <VCol cols="12" class="text-right">
                  <VBtn
                    color="error"
                    icon="tabler-trash"
                    rounded
                    v-if="index !== 0"
                    @click="removeCustomer(index)"
                  />
                </VCol>
                <VCol cols="12" sm="12" md="8" lg="8">
                  <VRow>
                    <VCol cols="12" sm="12" md="6" lg="6">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Tên</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            :rules="[requiredValidator]"
                            v-model="form.customers[index].full_name"
                            :error-messages="
                              form.errors[`customers.${index}.full_name`]
                            "
                            type="text"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" sm="12" md="6" lg="6">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>SĐT</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.customers[index].phone"
                            :error-messages="
                              form.errors[`customers.${index}.phone`]
                            "
                            type="text"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" sm="12" md="6" lg="6">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Email</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.customers[index].email"
                            :error-messages="
                              form.errors[`customers.${index}.email`]
                            "
                            type="email"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" sm="12" md="6" lg="6">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Số định danh</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.customers[index].id_number"
                            :error-messages="
                              form.errors[`customers.${index}.id_number`]
                            "
                            type="text"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" sm="12" md="6" lg="6">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Phân loại khách hàng</div></VCol>
                        <VCol cols="8">
                          <AppSelect
                            class="mt-3"
                            v-model="form.customers[index].type"
                            :items="customerTypeOptions"
                            :rules="[requiredValidator]"
                            @update:model-value="
                              (value) => onCustomerTypeChange(index, value)
                            "
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol
                      cols="12"
                      sm="12"
                      md="6"
                      lg="6"
                      v-if="
                        ['Sale', 'Sale TA'].includes(form.customers[index].type)
                      "
                    >
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Đối tác</div></VCol>
                        <VCol cols="8">
                          <AppSelect
                            class="mt-3"
                            item-title="name"
                            item-value="id"
                            v-model="form.customers[index].partner_id"
                            :items="partnerOptions"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                  </VRow>
                </VCol>
                <VCol cols="12" sm="12" md="4" lg="4">
                  <VRow class="font-weight-medium text-high-emphasis d-flex">
                    <VCol cols="4"> <div>Ảnh CCCD / Passport</div></VCol>
                    <VCol cols="8">
                      <div class="flex">
                        <div class="w-full h-auto relative">
                          <div
                            :ref="(el) => (dropZoneRefs[index] = el)"
                            class="cursor-pointer"
                            @click="handleOpenUpload(index)"
                          >
                            <div
                              v-if="
                                !fileData[index] || fileData[index].length === 0
                              "
                              class="d-flex flex-column justify-center align-center gap-y-2 pa-8 drop-zone rounded"
                            >
                              <div class="text-h6">
                                Kéo thả tệp của bạn hoặc
                              </div>
                              <VBtn variant="tonal" size="small">Tìm kiếm</VBtn>
                            </div>

                            <div
                              v-else
                              class="d-flex justify-center align-center gap-3 pa-4 drop-zone flex-wrap"
                            >
                              <VRow class="match-height w-100">
                                <template
                                  v-for="(item, imgIndex) in fileData[index]"
                                  :key="imgIndex"
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
                                          <span class="clamp-text text-wrap">{{
                                            item.file?.name
                                          }}</span>
                                          <span
                                            >{{
                                              item.file?.size / 1000
                                            }}
                                            KB</span
                                          >
                                        </div>
                                      </VCardText>
                                      <VCardActions>
                                        <VBtn
                                          variant="text"
                                          block
                                          @click.stop="
                                            fileData[index].splice(imgIndex, 1)
                                          "
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

            <div class="d-flex align-center justify-center mt-5 w-100">
              <VBtn color="secondary" variant="outlined" @click="addCustomer">
                Thêm Khách
              </VBtn>
            </div>
          </VCard>
        </VCardItem>
        <VCardText class="d-flex justify-end flex-wrap gap-3">
          <VBtn type="submit" :disabled="form.processing" variant="tonal">
            Lưu
          </VBtn>
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
import { ref, watch, onMounted, nextTick } from "vue";
import { useDropZone, useFileDialog, useObjectUrl } from "@vueuse/core";
const props = defineProps({
  isAddCustomerDialogVisible: Boolean,
  booking: Object,
  bookingCustomers: Object,
});
const emit = defineEmits([
  "update:isAddCustomerDialogVisible",
  "update:payment",
]);
const defaultCustomer = () => ({
  user_id: null,
  full_name: "",
  phone: "",
  email: "",
  id_number: "",
  image: "",
  type: "",
  partner_id: null,
});
const defaultFormData = {
  adults: "",
  children: "",
  newborn: "",
  customers: [defaultCustomer()],
};

const formRef = ref();
const form = useForm(defaultFormData);
const customerTypeOptions = ["Sale", "Sale TA", "OTA", "Social", "Walk-in"];
const onReset = () => {
  emit("update:isAddCustomerDialogVisible", false);
};
const dropZoneRefs = ref([]);
const fileData = ref([]);
const currentUploadIndex = ref(null);
const { open, onChange } = useFileDialog({ accept: "image/*" });
const onDrop = (files, index) => {
  const fileArray = Array.isArray(files) ? files : Array.from(files || []);
  if (!fileArray.length) return;
  fileData.value[index] = [];
  fileArray.forEach((file) => {
    if (!file.type.startsWith("image/")) {
      alert("Chỉ chấp nhận ảnh");
      return;
    }
    const url = useObjectUrl(file).value ?? "";
    fileData.value[index].push({ file, url });
    if (form.customers[index]) {
      form.customers[index].image = file;
    }
  });
};
onChange((files) => {
  if (currentUploadIndex.value !== null) {
    onDrop(files, currentUploadIndex.value);
    currentUploadIndex.value = null;
  }
});
const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  form.customers.forEach((customer, index) => {
    const firstImage = fileData.value[index]?.[0]?.file;
    if (firstImage) customer.image = firstImage;
  });
  form.post(route("bookings.addNewCustomer", props.booking.id), {
    forceFormData: true,
    onSuccess: () => {
      onReset();
      form.reset();
      emit("update:payment");
    },
    onError: () => {},
  });
};
watch(
  () => props.isAddCustomerDialogVisible,
  (visible) => {
    if (!visible) return;
    try {
      form.clearErrors();
      if (!props.booking) {
        form.defaults(defaultFormData);
        form.reset();
        fileData.value = [[]];
        dropZoneRefs.value = [null];
      } else {
        const customers = Array.isArray(props.bookingCustomers)
          ? props.bookingCustomers.map((cus) => ({
              user_id: cus.id ?? "",
              full_name: cus.full_name ?? "",
              phone: cus.phone ?? "",
              email: cus.email ?? "",
              id_number: cus.id_number ?? "",
              type: cus.type ?? "",
              partner_id: cus.partner_id ?? "",
              image: cus.image ?? "",
            }))
          : [defaultCustomer()];

        form.defaults({
          adults: props.booking.adults ?? "",
          children: props.booking.children ?? "",
          newborn: props.booking.newborn ?? "",
          customers,
        });
        form.reset();

        fileData.value = customers.map((cus) =>
          cus.image ? [{ file: null, url: cus.image }] : []
        );
        dropZoneRefs.value = customers.map(() => null);

        // Chỉ load partners nếu có khách hàng Sale hoặc Sale TA
        const saleCustomers = customers.filter(
          (c) => c.type === "Sale" || c.type === "Sale TA"
        );

        if (saleCustomers.length > 0) {
          // Load partners theo type của khách hàng Sale đầu tiên
          const firstSaleType = saleCustomers[0].type;
          getPartnerByType(firstSaleType);
        }
      }
    } catch (err) {
      console.error("Lỗi khi khởi tạo form khách hàng:", err);
    }
  }
);
const addCustomer = () => {
  form.customers.push(defaultCustomer());
  fileData.value.push([]);
  dropZoneRefs.value.push(null);
};
const removeCustomer = (index) => {
  if (index !== 0) {
    form.customers.splice(index, 1);
    fileData.value.splice(index, 1);
    dropZoneRefs.value.splice(index, 1);
  }
};
const partnerOptions = ref([]);

// Thay thế getPartner bằng getPartnerByType
async function getPartnerByType(val) {
  try {
    const res = await axios.get(route("partner.getPartnerByType"), {
      params: { type: val },
    });
    partnerOptions.value = res.data;
  } catch (error) {
    console.error("Lỗi khi lấy danh sách đối tác:", error);
    partnerOptions.value = [];
  }
}

// Function xử lý khi customer type thay đổi
const onCustomerTypeChange = (index, newType) => {
  // Reset partner_id khi đổi type
  form.customers[index].partner_id = null;

  // Chỉ load partners cho Sale và Sale TA
  if (newType === "Sale" || newType === "Sale TA") {
    getPartnerByType(newType);
  }
};

onMounted(() => {
  nextTick(() => {
    dropZoneRefs.value.forEach((el, index) => {
      if (el) useDropZone(el, (files) => onDrop(files, index));
    });
  });
});

const handleOpenUpload = (index) => {
  currentUploadIndex.value = index;
  open();
};
</script>

<style scoped>
.drop-zone {
  border: 1px dashed rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}
</style>
