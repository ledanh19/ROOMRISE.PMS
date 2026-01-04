<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    :model-value="props.isDetailDialogVisible"
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-2 pa-sm-10">
      <div class="text-h4 d-flex align-center">
        <VIcon class="mr-2" icon="tabler-file-description"></VIcon>Chi tiết
        phiếu
      </div>

      <VCardText class="mt-5 border rounded-lg">
        <div class="d-flex align-center justify-space-between">
          <div class="text-h4">Thông tin phiếu</div>
          <div>
            <VChip
              v-if="data.type"
              :color="data.type == 'expense' ? 'error' : 'success'"
              class="mr-3"
              >{{ data.type == "expense" ? "Phiếu chi" : "Phiếu thu" }}</VChip
            >
            <VChip
              v-if="data.payment_status"
              :color="
                data.payment_status == 'Đã thanh toán' ? 'success' : 'warning'
              "
              >{{ data.payment_status }}</VChip
            >
          </div>
        </div>
        <VRow class="mt-3">
          <VCol cols="6">
            <div>Mã phiếu</div>
            <div class="text-h5 font-weight-bold">{{ data.id }}</div>
          </VCol>
          <VCol cols="6">
            <div>Ngày tạo</div>
            <div class="text-h5 font-weight-bold">
              {{ formatDate(data.date) }}
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VCardText
        class="mt-5 border rounded-lg pa-6"
        style="padding-block-start: 24px !important"
      >
        <div class="text-h4">Chi tiết số tiền</div>
        <div
          class="text-h4 text-center"
          :class="data.type == 'expense' ? 'text-error' : 'text-success'"
        >
          <span>
            {{ data.type == "expense" ? "-" : "+" }}
          </span>
          {{ formatCurrency(data.amount) }}
        </div>
        <span class="text-center d-block">
          {{ data.type == "expense" ? "Số tiền chi ra" : "Số tiền thu được" }}
        </span>
      </VCardText>

      <VCardText
        class="mt-5 border rounded-lg pa-6"
        style="padding-block-start: 24px !important"
      >
        <div class="text-h5">Phân loại</div>
        <VRow class="mt-3">
          <VCol cols="6">
            <div>Danh mục</div>
            <div class="font-weight-bold">{{ data.category }}</div>
          </VCol>
          <VCol cols="6">
            <div>Hạng mục</div>
            <div class="font-weight-bold">
              {{ data.subcategory }}
            </div>
          </VCol>
        </VRow>
      </VCardText>
      <VCardText
        class="mt-5 border rounded-lg pa-6"
        style="padding-block-start: 24px !important"
      >
        <div class="text-h5">Thông tin thanh toán</div>
        <VRow class="mt-3">
          <VCol cols="6" v-if="data.type == 'income'">
            <div>PTTT tiền phòng</div>
            <div class="font-weight-bold">{{ data.room_payment_method }}</div>
          </VCol>
          <VCol cols="6">
            <div>Hình thức thanh toán</div>
            <div class="font-weight-bold">{{ data.payment_method }}</div>
          </VCol>
          <VCol cols="6">
            <div>Nguồn thanh toán</div>
            <div class="font-weight-bold">
              {{ data.payment_source }}
            </div>
          </VCol>
          <VCol cols="6">
            <div>Đối tượng</div>
            <div class="font-weight-bold">
              {{ data.payment_object }}
            </div>
          </VCol>
        </VRow>
      </VCardText>
      <VCardText
        class="mt-5 border rounded-lg pa-6"
        style="padding-block-start: 24px !important"
      >
        <div class="text-h5">Booking liên quan</div>
        <VRow class="mt-3">
          <VCol cols="6">
            <!-- Nếu có nhiều bookings từ partner -->
            <div v-if="data.partner_bookings && data.partner_bookings.length">
              <template
                v-for="booking in data.partner_bookings"
                :key="'partner-' + booking.id"
              >
                <Link
                  :href="route('bookings.show', booking.id)"
                  class="d-flex align-center text-high-emphasis border rounded-lg pa-2 mb-2"
                >
                  {{ booking.id }}
                  <VIcon
                    class="ml-2"
                    size="20"
                    icon="tabler-external-link"
                  ></VIcon>
                </Link>
              </template>
            </div>

            <!-- Nếu chỉ có 1 booking -->
            <div v-else-if="data.booking">
              <Link
                :href="route('bookings.show', data.booking.id)"
                class="d-flex align-center text-high-emphasis border rounded-lg pa-2"
              >
                {{ data.booking.id }}
                <VIcon
                  class="ml-2"
                  size="20"
                  icon="tabler-external-link"
                ></VIcon>
              </Link>
            </div>

            <!-- Không có booking -->
            <div v-else class="text-grey">Không có booking</div>
          </VCol>
        </VRow>
      </VCardText>
      <VCardText
        class="mt-5 border rounded-lg pa-6"
        style="padding-block-start: 24px !important"
      >
        <div class="text-h5">Thông tin nghiệp vụ</div>
        <VRow class="mt-3">
          <VCol cols="6">
            <div>Tạo từ nghiệp vụ</div>
            <div class="font-weight-bold">{{ data.business_type }}</div>
          </VCol>
          <VCol cols="6">
            <div>Người tạo</div>
            <div class="font-weight-bold">
              {{ data.created_by }}
            </div>
          </VCol>
          <VCol cols="6">
            <div>Loại nghiệp vụ nguồn</div>
            <div class="font-weight-bold">{{ data.source_business_type }}</div>
          </VCol>
          <VCol cols="12">
            <div>Mã nghiệp vụ nguồn</div>
            <div class="font-weight-bold">{{ data.source_business_code }}</div>
          </VCol>
          <VCol cols="12">
            <div>Ghi chú</div>
            <div class="font-weight-bold">{{ data.note }}</div>
          </VCol>
        </VRow>
      </VCardText>

      <VCardText
        class="mt-5 border rounded-lg pa-6"
        style="padding-block-start: 24px !important"
      >
        <div class="text-h5">Lịch sử kiểm toán</div>

        <VRow class="mt-3" v-for="log in data.audit_logs" :key="log.id">
          <VCol cols="12" class="bg-secondary rounded-lg">
            <div class="d-flex align-center justify-space-between">
              <div>Tạo phiếu</div>
              <div class="font-weight-bold">
                {{ log.performed_by }} -
                {{ formatDate(log.performed_at) }}
              </div>
            </div>
          </VCol>

          <VCol cols="12" class="bg-success rounded-lg mt-3">
            <div class="d-flex align-start justify-space-between">
              <div>Xác nhận thanh toán</div>
              <div class="font-weight-bold text-right">
                {{ log.performed_by }} - {{ formatDate(log.performed_at) }}
              </div>
            </div>
          </VCol>

          <VCol cols="12" class="bg-info rounded-lg mt-3">
            <div class="d-flex align-center justify-space-between">
              <div>Sinh từ {{ data.source_business_type }}</div>
              <div class="font-weight-bold">
                {{ getSourceLabel(log.source_type) }} -
                {{ formatDate(log.performed_at) }}
              </div>
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VCardText
        class="mt-5 border rounded-lg pa-6"
        style="padding-block-start: 24px !important"
      >
        <div class="text-h5 mb-2">Cấu trúc dữ liệu JSON (Dev)</div>
        <VRow class="mt-3">
          <VCol cols="12" class="bg-grey-lighten-4 rounded-lg pa-4">
            <AppTextarea
              auto-grow
              rows="3"
              :model-value="JSON.stringify(data, null, 2)"
              readonly
              class="monospace"
            />
          </VCol>
        </VRow>
      </VCardText>

      <div class="ga-2 d-flex justify-space-between gap-3 mt-4">
        <div class="">
          <VBtn
            class="mr-3"
            color="primary"
            :href="route('income-expense.export-pdf', data.id)"
            target="_blank"
          >
            <VIcon size="24" class="mr-2" icon="tabler-download"></VIcon> Xuất
            PDF
          </VBtn>
          <VBtn color="secondary" @click="printReceipt">
            <VIcon
              size="24"
              class="mr-2"
              icon="tabler-file-description"
            ></VIcon>
            In phiếu
          </VBtn>
        </div>
        <VBtn color="secondary" variant="tonal" @click="onReset"> Đóng </VBtn>
      </div>
    </VCard>
  </VDialog>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { formatCurrency, formatDate } from "@/utils/formatters";
const props = defineProps({
  isDetailDialogVisible: Boolean,
  data: Object,
});

const emit = defineEmits(["update:isDetailDialogVisible", "update-data"]);

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
};
const formRef = ref();
const form = useForm(defaultFormData);
const printArea = ref(null);
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
  emit("update:isDetailDialogVisible", false);
  emit("update-data");
  form.reset();
};

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  form.post(route("income-and-expense.store"), {
    onSuccess: onReset,
  });
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

const getSourceLabel = (sourceType) => {
  switch (sourceType) {
    case "manual":
      return "Thủ công";
    case "auto":
      return "Tự động";
    default:
      return "Không xác định";
  }
};

function printReceipt() {
  const url = route("income-expense.print", props.data.id);
  const printWindow = window.open(url, "_blank", "width=800,height=600");
}
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
.monospace textarea {
  font-family: monospace;
  font-size: 13px;
  white-space: pre-wrap;
}
.printable {
  display: none;
}

@media print {
  .printable {
    display: block;
  }
}
</style>
