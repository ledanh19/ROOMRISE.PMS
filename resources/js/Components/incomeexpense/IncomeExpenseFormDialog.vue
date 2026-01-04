<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-2 pa-sm-10">
      <div class="text-h4 d-flex align-center">
        <VIcon class="mr-2" icon="tabler-plus"></VIcon>Tạo phiếu chi mới
      </div>
      <VCardText class="border bg-cae4ff rounded-lg mt-5">
        <VRow>
          <VCol cols="1" sm="1" md="1" lg="1">
            <VIcon color="blue" size="24" icon="tabler-info-circle"></VIcon>
          </VCol>
          <VCol cols="11" sm="11" md="11" lg="11">
            <div class="text-h5 text-primary font-weight-bold">
              <VIcon class="mr-2" icon="tabler-clipboard-list"></VIcon> Lưu ý
              quan trọng
            </div>
            <ul class="pa-0 text-blue">
              <li>Bạn chỉ có thể tạo <strong>phiếu chi</strong> thủ công</li>
              <li>
                Phiếu thu được <strong>tự động sinh</strong> từ: Booking
                <VIcon icon="tabler-arrow-narrow-right"></VIcon> Quyết toàn OTA
                <VIcon icon="tabler-arrow-narrow-right"></VIcon>
                Đối soát đối tác
              </li>
              <li>
                Nguồn thanh toán tự động được gắn là <strong>"Công ty"</strong>
              </li>
            </ul>
          </VCol>
        </VRow>
      </VCardText>
      <VForm ref="formRef" @submit.prevent="onSubmit">
        <VCardText class="mt-5 border rounded-lg">
          <div class="text-h5">Thông tin phiếu chi</div>
          <VRow class="mt-3">
            <VCol cols="12" sm="12" md="6" lg="6">
              <label for="">Loại phiếu</label>
              <VRadioGroup v-model="form.type" inline>
                <VRadio
                  color="error"
                  label="Phiếu Chi ( Tạo tay )"
                  value="expense"
                />
              </VRadioGroup>
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6">
              <AppTextField
                v-model="form.date"
                :error-messages="form.errors.date"
                type="date"
                label="Ngày thực hiện *"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6">
              <AppTextField
                v-model="form.amount"
                :error-messages="form.errors.amount"
                type="number"
                label="Số tiền (VND) *"
                placeholder="0"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6">
              <AppSelect
                v-model="form.payment_status"
                :items="paymentStatusOptions"
                :error-messages="form.errors.payment_status"
                :rules="[requiredValidator]"
                label="Tình trạng thanh toán *"
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
          </VRow>
        </VCardText>

        <VCardText
          class="mt-5 border rounded-lg pa-6"
          style="padding-block-start: 24px !important"
        >
          <div class="text-h5">Phân loại chi phí</div>
          <VRow class="mt-3">
            <VCol cols="12" sm="12" md="6" lg="6">
              <AppSelect
                v-model="form.category"
                :items="categoryList"
                item-text="title"
                item-value="value"
                :error-messages="form.errors.category"
                :rules="[requiredValidator]"
                label="Danh mục chi phí*"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6">
              <AppSelect
                v-model="form.subcategory"
                :items="subcategoryList"
                :error-messages="form.errors.subcategory"
                item-text="title"
                item-value="value"
                :rules="[requiredValidator]"
                label="Hạng mục chi phí *"
                :disabled="!form.category"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText
          class="mt-5 border rounded-lg pa-6"
          style="padding-block-start: 24px !important"
        >
          <div class="text-h5">Chi tiết thanh toán</div>
          <VRow class="mt-3">
            <VCol cols="12" sm="12" md="6" lg="6">
              <AppSelect
                v-model="form.payment_method"
                :items="paymentOptions"
                :error-messages="form.errors.payment_method"
                :rules="[requiredValidator]"
                label="Hình thức thanh toán *"
              />
            </VCol>
            <VCol cols="12" sm="12" md="6" lg="6">
              <label for="">Nguồn thanh toán</label>
              <VRadioGroup v-model="form.payment_source" inline>
                <VRadio
                  color="info"
                  label="Công ty ( Tự động gắn cho phiếu chi)"
                  value="Công ty"
                />
              </VRadioGroup>
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="form.payment_object"
                :error-messages="form.errors.payment_object"
                type="text"
                label="Đối tượng thanh toán *"
                placeholder="Nhà cung cấp, đối tác, nhân viên"
                :rules="[requiredValidator]"
              />
              <span class="mt-1 d-block">Người/Tổ chức nhận tiền</span>
            </VCol>
          </VRow>
        </VCardText>
        <VCardText
          class="mt-5 border rounded-lg pa-6"
          style="padding-block-start: 24px !important"
        >
          <div class="text-h5">Thông tin bổ sung</div>
          <VRow class="mt-3">
            <VCol cols="12">
              <AppTextarea
                v-model="form.note"
                label="Ghi chú"
                placeholder="Mô tả lý do chi tiền nội dung chi tiết"
              />
            </VCol>
            <VCol cols="12">
              <div class="v-label mb-1 text-body-2 text-wrap">
                Đính kèm file( tùy chọn )
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
                              <VCardText class="d-flex flex-column" @click.stop>
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
                                  <span> {{ item.file.size / 1000 }} KB </span>
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

          <VRow class="ga-2 justify-end mt-4">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="onReset"
              :disabled="form.processing"
            >
              Hủy
            </VBtn>
            <VBtn type="submit" :loading="form.processing">
              <VIcon icon="tabler-plus"></VIcon>
              {{ isNewForm ? "Tạo phiếu chi" : "Cập nhật phiếu chi" }}
            </VBtn>
          </VRow>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>
</template>

<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
  isDialogVisible: Boolean,
  data: Object,
  partnerGroup: Object,
});
const page = usePage();
const user = computed(() => page.props.auth.user);
const emit = defineEmits(["update:isDialogVisible", "update-data"]);
const isNewForm = computed(() => !props.data || !props.data.id);

const defaultFormData = {
  type: "expense",
  category: "",
  subcategory: "",
  payment_method: "",
  amount: "",
  date: "",
  note: "",
  payment_status: "",
  payment_source: "Công ty",
  payment_object: "",
  file: "",
  partner_group_id: null,
};
const formRef = ref();
const form = useForm(defaultFormData);

const categoryData = {
  income: [
    {
      id: 1,
      name: "Doanh thu",
      subcategories: [
        "Đặt phòng",
        "Phụ thu",
        "Upsell dịch vụ",
        "Đặt cọc",
        "Thanh toán phần còn lại",
      ],
    },
    {
      id: 2,
      name: "Thu nhập khác",
      subcategories: ["Thu hộ khách", "Phạt hư hỏng", "Thu nhập phụ"],
    },
  ],
  expense: [
    {
      id: 1,
      name: "Chi phí vận hành",
      subcategories: [
        "Dọn phòng",
        "Vật tư tiêu hao",
        "Điện nước",
        "Internet",
        "Bảo trì sữa chữa",
      ],
    },
    {
      id: 2,
      name: "Chi phí nhân sự",
      subcategories: ["Lương nhân viên", "Thưởng", "Bảo hiểm", "Phúc lợi"],
    },
    {
      id: 3,
      name: "Hoa hồng & Marketing",
      subcategories: [
        "Hoa hồng OTA",
        "Quảng cáo",
        "Marketing",
        "Hoa hồng đại lý",
      ],
    },
    {
      id: 4,
      name: "Chi phí cố định",
      subcategories: [
        "Thuê mặt bằng",
        "Bảo hiểm",
        "Phí ngân hàng",
        "Phí phần mềm",
      ],
    },
  ],
};

const categoryList = computed(() => {
  return categoryData[form.type].map((cat) => ({
    title: cat.name,
    value: cat.name,
  }));
});
const selectedCategory = computed(() =>
  categoryData[form.type].find((cat) => cat.name === form.category)
);

const subcategoryList = computed(() =>
  (selectedCategory.value?.subcategories || []).map((name) => ({
    title: name,
    value: name,
  }))
);
const paymentStatusOptions = ["Chờ thanh toán", "Đã thanh toán"];
const paymentOptions = [
  "Tiền mặt",
  "Chuyển khoản",
  "QR Code",
  "9Pay",
  "Momo",
  "VNPay",
  "Thẻ tín dụng",
];

watch(
  () => form.category,
  () => {
    form.subcategory = null;
  }
);
watch(
  () => form.type,
  () => {
    form.category = null;
    form.subcategory = null;
  }
);

const onReset = () => {
  emit("update:isDialogVisible", false);
  emit("update-data");
  form.reset();
};

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  if (isNewForm.value) {
    form.post(route("income-and-expense.store"), {
      onSuccess: onReset,
    });
  } else {
    form.post(
      route("income-and-expense.postUpdate", {
        incomeandexpense: props.data.id,
      }),
      {
        onSuccess: onReset,
      }
    );
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
    form.file = file;
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
    form.file = file;
  }
});

useDropZone(dropZoneRef, onDrop);

watch(
  () => props.isDialogVisible,
  (visible) => {
    if (!visible) {
      return;
    }

    form.clearErrors();
    if (!props.data) {
      form.defaults(defaultFormData);
      form.reset();
    } else {
      form.defaults(props.data);
      form.reset();
      nextTick(() => {
        if (props.data.category) form.category = props.data.category;
        if (props.data.subcategory) form.subcategory = props.data.subcategory;
      });
    }
  }
);
</script>
<style scoped>
.text-blue {
  color: #4970f5;
}
.bg-cae4ff {
  background-color: #cae4ff;
}
.drop-zone {
  border: 1px dashed rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}
</style>
