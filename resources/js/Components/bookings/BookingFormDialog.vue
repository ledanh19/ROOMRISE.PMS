<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 1000"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />
    <VCard>
      <VCardText>
        <VCard class="border">
          <AppStepperBooking
            v-model:current-step="currentStep"
            :items="iconsSteps"
            align="center"
          />
        </VCard>
      </VCardText>
      <VCardText>
        <VForm ref="formRef" @submit.prevent="onSubmit">
          <VWindow v-model="currentStep" class="disable-tab-transition">
            <VWindowItem>
              <VCard class="border">
                <VCardItem>
                  <VRow>
                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Nhận phòng</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.check_in_date"
                            :error-messages="form.errors.check_in_date"
                            type="date"
                        /></VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Giờ đến</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.check_in_time"
                            :error-messages="form.errors.check_in_time"
                            type="time"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Trả phòng</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.check_out_date"
                            :error-messages="form.errors.check_out_date"
                            type="date"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Giờ đi</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.check_out_time"
                            :error-messages="form.errors.check_out_time"
                            type="time"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Nguồn</div></VCol>
                        <VCol cols="8">
                          <AppSelect
                            v-model="form.ota_name"
                            :error-messages="form.errors.ota_name"
                            :items="otaChannels"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>PPTT tiền phòng</div></VCol>
                        <VCol cols="8">
                          <AppSelect
                            v-model="form.room_payment_method"
                            :error-messages="form.errors.room_payment_method"
                            :items="roomPaymentMethod"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Chỗ nghỉ</div></VCol>
                        <VCol cols="8">
                          <AppSelect
                            v-model="form.property_id"
                            :items="propertyOptions"
                            item-title="name"
                            item-value="id"
                            :error-messages="form.errors.property_id"
                          />
                        </VCol>
                      </VRow>
                    </VCol>

                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Người lớn</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.adults"
                            :error-messages="form.errors.adults"
                            type="number"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Trẻ em</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.children"
                            :error-messages="form.errors.children"
                            type="number"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Trạng thái</div></VCol>
                        <VCol cols="8">
                          <AppSelect
                            v-model="form.status"
                            :items="statusOptions"
                            :error-messages="form.errors.status"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                  </VRow>
                  <VRow class="mt-4">
                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Trẻ sơ sinh</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.newborn"
                            :error-messages="form.errors.newborn"
                            type="number"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" lg="6" md="6" sm="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Ghi chú</div></VCol>
                        <VCol cols="8">
                          <AppTextarea
                            label="Ghi chú"
                            placeholder="Ghi chú"
                            v-model="form.note"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                  </VRow>
                </VCardItem>
              </VCard>
              <VCard class="mt-5">
                <VCardItem>
                  <VRow v-for="(room, idx) in form.booking_rooms" :key="idx">
                    <VCol cols="12" sm="6" md="3" lg="3">
                      <AppSelect
                        v-model="room.room_id"
                        :items="roomOptions"
                        item-title="name"
                        item-value="id"
                        label="Loại phòng "
                        :error-messages="
                          form.errors[`booking_rooms.${idx}.room_id`]
                        "
                        @update:model-value="(val) => onRoomChange(idx, val)"
                      />
                    </VCol>
                    <VCol cols="12" sm="6" md="3" lg="3">
                      <AppSelect
                        v-model="room.room_unit_id"
                        :items="unitOptions[room.room_id] || []"
                        item-title="name"
                        item-value="id"
                        label="Phòng"
                        :error-messages="
                          form.errors[`booking_rooms.${idx}.room_unit_id`]
                        "
                      />
                    </VCol>
                    <VCol cols="12" sm="6" md="3" lg="3">
                      <AppSelect
                        v-model="room.rate_plan_id"
                        :items="ratePlanOptions[room.room_id] || []"
                        item-title="title"
                        item-value="id"
                        label="Loại giá"
                        :error-messages="
                          form.errors[`booking_rooms.${idx}.rate_plan_id`]
                        "
                        @update:model-value="
                          (val) => onRatePlanChange(idx, val)
                        "
                      />
                    </VCol>
                    <VCol
                      cols="12"
                      sm="6"
                      md="3"
                      lg="3"
                      class="d-flex align-center h-100"
                    >
                      <AppTextField
                        v-model.number="room.room_price_at_booking"
                        type="number"
                        label="Giá/đêm"
                        :suffix="
                          propertyOptions.find(
                            (option) => option.id === form.property_id
                          )?.currency || null
                        "
                        :error-messages="
                          form.errors[
                            `booking_rooms.${idx}.room_price_at_booking`
                          ]
                        "
                      />
                      <VBtn
                        icon
                        variant="text"
                        color="error"
                        @click="removeRoom(idx)"
                        v-if="form.booking_rooms.length > 1"
                      >
                        <VIcon icon="tabler-trash"></VIcon>
                      </VBtn>
                    </VCol>
                  </VRow>
                  <div class="d-flex align-center justify-center mt-5 w-100">
                    <VBtn color="secondary" variant="outlined" @click="addRoom">
                      Thêm
                    </VBtn>
                  </div>
                </VCardItem>
              </VCard>
              <div v-if="messageErrorBooking" class="mt-5 text-h6 text-error">
                {{ messageErrorBooking }}
              </div>
            </VWindowItem>

            <VWindowItem>
              <VCard>
                <VCardItem title="Tìm khách hàng">
                  <AppTextField
                    v-model="search_customer"
                    class="mt-2"
                    append-inner-icon="tabler-search"
                    placeholder="Nhập số điện thoại hoặc email"
                  />
                </VCardItem>
              </VCard>
              <VCard class="mt-5">
                <VCardItem title="Thông tin khách hàng">
                  <VRow class="mt-3">
                    <VCol cols="12" sm="12" md="6" lg="6">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Tên</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.customer[0].full_name"
                            :error-messages="
                              form.errors['customer.0.full_name']
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
                            v-model="form.customer[0].phone"
                            :error-messages="form.errors['customer.0.phone']"
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
                            v-model="form.customer[0].email"
                            :error-messages="form.errors['customer.0.email']"
                            type="email"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" sm="12" md="6" lg="6">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"> <div>Quốc gia</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.customer[0].country"
                            :error-messages="form.errors['customer.0.country']"
                            type="text"
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
                            v-model="form.customer[0].id_number"
                            :error-messages="
                              form.errors['customer.0.id_number']
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
                        <VCol cols="4"> <div>Ngày phát hành</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model="form.customer[0].issue_date"
                            :error-messages="
                              form.errors['customer.0.issue_date']
                            "
                            type="date"
                          />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12" sm="12" md="6" lg="6">
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
                                  <div class="text-h6">
                                    Kéo thả tệp của bạn hoặc
                                  </div>
                                  <VBtn variant="tonal" size="small">
                                    Tìm kiếm
                                  </VBtn>
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
                                              <span
                                                class="clamp-text text-wrap"
                                              >
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
                                              @click.stop="
                                                fileData.splice(index, 1)
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
              </VCard>
              <VCard class="mt-5">
                <VCardItem title="Phân loại khách hàng">
                  <VRow>
                    <VCol cols="12">
                      <AppSelect
                        class="mt-3"
                        v-model="form.customer[0].type"
                        :items="customerTypeOptions"
                        @update:model-value="onCustomerTypeChange"
                      />
                    </VCol>
                    <template
                      v-if="['Sale', 'Sale TA'].includes(form.customer[0].type)"
                    >
                      <VCol cols="12" sm="12" md="9" lg="9">
                        <AppSelect
                          class="mt-3"
                          item-title="name"
                          item-value="id"
                          v-model="form.customer[0].partner_id"
                          :items="partnerOptions"
                          label="Đối tác"
                        />
                      </VCol>
                      <VCol cols="12" sm="12" md="3" lg="3">
                        <div class="d-flex align-end h-100">
                          <VBtn @click="partnerDialog = true" class="w-100"
                            >Thêm đối tác</VBtn
                          >
                        </div>
                      </VCol>
                    </template>
                  </VRow>
                </VCardItem>
              </VCard>
            </VWindowItem>

            <VWindowItem>
              <VCard>
                <VCardItem title="Khách đặt cọc">
                  <VRow class="mt-3">
                    <VCol cols="12" sm="12" md="4" lg="4">
                      <AppSelect
                        v-model="form.payment_method"
                        :items="paymentMethodOptions"
                        label="Phương thức"
                        :error-messages="form.errors.payment_method"
                      />
                    </VCol>
                    <VCol cols="12" sm="12" md="4" lg="4">
                      <AppTextField
                        v-model="form.paid"
                        :error-messages="form.errors.paid"
                        type="number"
                        label="Số tiền"
                      />
                    </VCol>
                    <VCol cols="12" sm="12" md="4" lg="4">
                      <AppTextField
                        v-model="form.payment_content"
                        :error-messages="form.errors.payment_content"
                        type="text"
                        label="Nội dung"
                      />
                    </VCol>
                  </VRow>
                </VCardItem>
              </VCard>
            </VWindowItem>
          </VWindow>
          <div
            class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"
          >
            <VBtn
              color="secondary"
              variant="tonal"
              :disabled="currentStep === 0"
              @click="currentStep--"
            >
              <VIcon icon="tabler-arrow-left" start class="flip-in-rtl" />
              Quay lại
            </VBtn>

            <VBtn
              v-if="iconsSteps.length - 1 === currentStep"
              color="success"
              @click="onSubmit"
            >
              Đặt phòng
            </VBtn>

            <VBtn v-else @click="handleNextStep">
              Tiếp theo
              <VIcon icon="tabler-arrow-right" end class="flip-in-rtl" />
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
  <FormDialog
    v-model:is-dialog-visible="partnerDialog"
    @update:partner="loadData"
  />
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import AppStepperBooking from "./AppStepperBooking.vue";
import { debounce } from "lodash";
import { useToast } from "vue-toastification";
import FormDialog from "./FormDialog.vue";
const toast = useToast();

const iconsSteps = [
  {
    title: "Thông tin đặt phòng",
    icon: "tabler-book",
    size: "40",
  },
  {
    title: "Khách hàng",
    icon: "tabler-user",
    size: "40",
  },
  {
    title: "Đặt cọc",
    icon: "tabler-coin",
    size: "40",
  },
];
const partnerDialog = ref(false);
const currentStep = ref(0);
const isPasswordVisible = ref(false);
const isCPasswordVisible = ref(false);
const search_customer = ref("");
const props = defineProps({
  isDialogVisible: Boolean,
  data: Object,
});

const emit = defineEmits(["update:isDialogVisible"]);
const partnerOptions = ref([]);
const isNewForm = computed(() => !props.data || !props.data.id);
const isInitializing = ref(false);

const propertyOptions = ref([]);
const roomOptions = ref([]);
const unitOptions = ref({}); // { room_id: [unit1, ...] }
const ratePlanOptions = ref({}); // { room_id: [rateplan1, ...] }
const otaChannels = ["Walk-in", "Từ đối tác"];
const roomPaymentMethod = ["Thu tại KS", "Thu bởi đối tác"];
const statusOptions = ["Mới"];
const status2Options = [
  "Hành động bắt buộc",
  "Phân bổ",
  "Bị khách hủy",
  "Bị host hủy",
  "Không có chương trình",
  "Danh sách chờ",
  "Khách vãng lai",
];
const customerTypeOptions = ["Sale", "Sale TA", "OTA", "Social", "Walk-in"];
const customerOptions = ref([]);
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
const defaultRoom = () => ({
  room_id: "",
  room_unit_id: "",
  rate_plan_id: "",
  room_price_at_booking: 0,
});
const defaultCustomer = () => ({
  user_id: "",
  full_name: "",
  phone: "",
  email: "",
  country: "",
  id_number: "",
  issue_date: "",
  image: "",
  type: "",
  partner_id: "",
});
const defaultFormData = {
  //step 1
  check_in_date: "",
  check_out_date: "",
  check_in_time: "",
  check_out_time: "",
  ota_name: "",
  room_payment_method: "",
  property_id: "",
  adults: "",
  children: "",
  newborn: "",
  payment_content: "",
  status: "Mới",
  booking_rooms: [defaultRoom()],
  // status_2: "",
  //step 2
  customer: [defaultCustomer()],
  customer_type: "",
  customer_id: "",
  //step 3
  paid: "",
  payment_method: "",
  note: "",
};

const form = useForm(defaultFormData);

form.transform((data) => {
  const {
    check_in_date,
    check_out_date,
    check_in_time,
    check_out_time,
    booking_rooms,
  } = data;
  return {
    ...data,
    booking_rooms: booking_rooms.map((room) => ({
      ...room,
      check_in_date,
      check_out_date,
      check_in_time,
      check_out_time,
      rate_plan_id: room.rate_plan_id || null,
    })),
  };
});

function addRoom() {
  form.booking_rooms.push(defaultRoom());
}
function removeRoom(idx) {
  form.booking_rooms.splice(idx, 1);
}

async function onRoomChange(idx, roomId) {
  if (roomId) {
    // Load units
    if (!unitOptions.value[roomId]) {
      const res = await axios.get(
        route("rooms.units.options", { room: roomId })
      );
      unitOptions.value[roomId] = res.data;
    }
    // Load rate plans
    if (!ratePlanOptions.value[roomId]) {
      const res = await axios.get(route("rateplans.options", { room: roomId }));
      ratePlanOptions.value[roomId] = res.data;
    }
  }
  form.booking_rooms[idx].room_unit_id = "";
  form.booking_rooms[idx].rate_plan_id = "";
  form.booking_rooms[idx].room_price_at_booking = 0;
}

function onRatePlanChange(idx, ratePlanId) {
  const roomId = form.booking_rooms[idx].room_id;
  const ratePlan = (ratePlanOptions.value[roomId] || []).find(
    (rp) => rp.id === ratePlanId
  );

  if (ratePlan) {
    form.booking_rooms[idx].room_price_at_booking = ratePlan.price || 0;
    form.booking_rooms[idx].rate_plan_id = ratePlanId;
  }
}

const onReset = () => {
  emit("update:isDialogVisible", false);
  form.reset();
  fileData.value = [];
  currentStep.value = 0;
  search_customer.value = "";
};

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  if (isNewForm.value) {
    form.post(route("bookings.store"), {
      onSuccess: onReset,
      onError: setActiveOnError,
    });
  } else {
    form.put(route("bookings.update", { booking: props.data.id }), {
      onSuccess: onReset,
      onError: setActiveOnError,
    });
  }
};

watch(
  () => props.isDialogVisible,
  (visible) => {
    if (!visible) {
      isInitializing.value = false;
      return;
    }
    isInitializing.value = true;
    form.clearErrors();
    loadProperties();
    getPartnerByType();

    if (!props.data) {
      form.defaults(defaultFormData);
      form.reset();
    } else {
      form.defaults(props.data);
      form.reset();
    }
  }
);
onMounted(() => {
  loadProperties();
  getPartnerByType();
});

// watch(
//   () => form.customer[0].type,
//   async (val) => {
//     if (["Sale", "Sale TA"].includes(val)) {
//       getPartnerByType(val);
//       // form.customer[0].partner_id = null;
//     } else {
//       partnerOptions.value = [];
//       form.customer[0].partner_id = null;
//     }
//   }
// );
const onCustomerTypeChange = async (newType) => {
  if (["Sale", "Sale TA"].includes(newType)) {
    await getPartnerByType(newType);
    // Reset partner_id khi user chủ động đổi type
    form.customer[0].partner_id = null;
  } else {
    partnerOptions.value = [];
    form.customer[0].partner_id = null;
  }
};
async function getPartnerByType(val) {
  const res = await axios.get(route("partner.getPartnerByType"), {
    params: { type: val },
  });
  partnerOptions.value = res.data;
}
async function loadProperties() {
  const res = await axios.get(route("properties.options"));
  propertyOptions.value = res.data;
  // Load room options nếu có property_id
  if (form.property_id) {
    const res2 = await axios.get(
      route("rooms.options", { property: form.property_id })
    );
    roomOptions.value = res2.data;
  }
}
const loadData = () => {
  getPartnerByType();
};

watch(
  () => form.property_id,
  async (id) => {
    if (!id) {
      roomOptions.value = [];
      return;
    }
    const res = await axios.get(route("rooms.options", { property: id }));
    roomOptions.value = res.data;
    // Lấy giờ nhận/trả mặc định từ property
    const property = propertyOptions.value.find((p) => p.id === id);
    if (property) {
      form.check_in_time = property.checkin_from_time || "14:00";
      form.check_out_time = property.checkout_to_time || "12:00";
    }
  }
);

watch(
  () => form.customer_type,
  async (type) => {
    if (!type) {
      customerOptions.value = [];
      form.customer_id = null;
      return;
    }
    const res = await axios.get(route("customers.by-type", { type }));
    customerOptions.value = res.data;
    form.customer_id = null;
  }
);

const fetchCustomer = async (keyword) => {
  if (!keyword) return;

  try {
    const res = await axios.get(
      route("customers.by-search", {
        customer: keyword,
        property_id: form.property_id,
      })
    );
    const { found, customer } = res.data;

    if (found && customer) {
      if (["Sale", "Sale TA"].includes(customer.type)) {
        await getPartnerByType(customer.type);
      }
      form.customer[0] = {
        user_id: customer.id || "",
        full_name: customer.full_name || "",
        phone: customer.phone || "",
        email: customer.email || "",
        country: customer.country || "",
        id_number: customer.id_number || "",
        issue_date: customer.issue_date || "",
        image: null,
        type: customer.type || "",
        partner_id: customer.partner_id || "",
      };
      form.customer_id = customer.id;
    } else {
      form.customer[0] = defaultCustomer();
      form.customer_id = "";
    }
  } catch (error) {
    console.error("Lỗi khi tìm khách hàng:", error);
  }
};
const debouncedFetchCustomer = debounce(fetchCustomer, 300);

watch(
  () => search_customer.value,
  (value) => {
    debouncedFetchCustomer(value);
  }
);
const messageErrorBooking = ref("");
const handleNextStep = async () => {
  try {
    const res = await axios.post(route("rooms.check-availability"), {
      booking_rooms: form.booking_rooms,
      check_in_date: form.check_in_date,
      check_out_date: form.check_out_date,
      check_in_time: form.check_in_time,
      check_out_time: form.check_out_time,
    });

    if (res.data.available) {
      currentStep.value++;
      messageErrorBooking.value = "";
    } else {
      const err = res.data.error;
      messageErrorBooking.value = err.message;
    }
  } catch (error) {
    toast.error("Không thể kết nối máy chủ.");
  }
};

const checkRoomAvailable = async () => {
  try {
    const res = await axios.post(route("rooms.check-availability"), {
      booking_rooms: form.booking_rooms,
    });

    return res.data.available;
  } catch (err) {
    const message = err.response?.data?.error?.message ?? "Lỗi kiểm tra phòng.";
    toast.error(message);
    return false;
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
    form.customer[0].image = file;
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
    form.customer[0].image = file;
  }
});

useDropZone(dropZoneRef, onDrop);

const setActiveOnError = () => {
  const step1Errors = [
    form.errors.check_in_date,
    form.errors.check_out_date,
    form.errors.check_in_time,
    form.errors.check_out_time,
    form.errors.status,
    form.errors.ota_name,
    form.errors.room_payment_method,
    form.errors.property_id,
    form.errors.adults,
    ...(form.errors["booking_rooms.0.room_id"] ? [true] : []),
    ...(form.errors["booking_rooms.0.rate_plan_id"] ? [true] : []),
    ...(form.errors["booking_rooms.0.room_price_at_booking"] ? [true] : []),
  ].some((e) => e);

  const step2Errors = [
    form.errors["customer.0.full_name"],
    form.errors["customer.0.phone"],
    form.errors["customer.0.email"],
  ].some((e) => e);

  const step3Errors = [
    form.errors.paid,
    form.errors.payment_method,
    form.errors.note,
  ].some((e) => e);

  if (step1Errors) {
    currentStep.value = 0;
    next.value = false;
  } else if (step2Errors) {
    currentStep.value = 1;
    next.value = false;
  } else if (step3Errors) {
    currentStep.value = 2;
    next.value = false;
  } else {
    currentStep.value = 0;
  }
};
</script>
<style scoped>
.drop-zone {
  border: 1px dashed rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}
</style>
