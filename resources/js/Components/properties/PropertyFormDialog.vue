<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-6">
          {{ isNewForm ? "Thêm mới" : "Cập nhật" }} chỗ nghỉ
        </h4>

        <VForm ref="formRef" @submit.prevent="onSubmit">
          <VRow>
            <VCol cols="12">
              <VRow>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <AppTextField
                    v-model="form.name"
                    :error-messages="form.errors.name"
                    :rules="[requiredValidator]"
                    label="Tên"
                    type="text"
                    placeholder="Tên"
                  />
                </VCol>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <AppSelect
                    :disabled="!isNewForm"
                    v-model="form.type"
                    :error-messages="form.errors.type"
                    :items="PROPERTY_TYPE_OPTIONS"
                    :rules="[requiredValidator]"
                    item-title="label"
                    item-value="value"
                    label="Loại hình chỗ nghỉ"
                    placeholder="Loại"
                  />
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12">
              <VRow>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <!-- <AppTextField
                    v-model="form.city"
                    :error-messages="form.errors.city"
                    label="Thành phố"
                    type="text"
                    placeholder="Thành phố"
                  /> -->
                  <AppSelect
                    v-model="form.city"
                    :items="vietnamCities"
                    :error-messages="form.errors.city"
                    label="Thành phố"
                  />
                </VCol>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <AppTextField
                    v-model="form.address"
                    :error-messages="form.errors.address"
                    label="Địa chỉ"
                    type="text"
                    placeholder="Địa chỉ"
                  />
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12">
              <VRow>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <AppSelect
                    v-model="form.country"
                    :items="CountriesOptions()"
                    :error-messages="form.errors.country"
                    label="Quốc gia"
                  />
                </VCol>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <AppSelect
                    v-model="form.currency"
                    :error-messages="form.errors.currency"
                    :items="CURRENCIES"
                    :rules="[requiredValidator]"
                    item-title="text"
                    item-value="value"
                    label="Tiền tệ"
                    placeholder="Chọn loại tiền tệ"
                  />
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12">
              <VRow>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <AppTextField
                    v-model="form.deposit_amount"
                    :error-messages="form.errors.deposit_amount"
                    label="Tiền đặt cọc"
                    type="number"
                    placeholder="Nhập số tiền đặt cọc"
                    step="0.01"
                    min="0"
                  />
                </VCol>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <!-- Placeholder for future fields -->
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12">
              <VRow>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <AppTextField
                    v-model="form.email"
                    :error-messages="form.errors.email"
                    :rules="[emailValidator]"
                    label="Email"
                    type="email"
                    placeholder="Nhập email"
                  />
                </VCol>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <AppTextField
                    v-model="form.phone"
                    :error-messages="form.errors.phone"
                    label="Điện thoại"
                    type="text"
                    placeholder="Nhập số điện thoại"
                  />
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12">
              <VRow>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <AppTextField
                    v-model="form.checkin_from_time"
                    :error-messages="form.errors.checkin_from_time"
                    label="Giờ check-in mặc định"
                    type="time"
                    placeholder="Chọn giờ check-in"
                  />
                </VCol>
                <VCol cols="12" sm="12" md="6" lg="6">
                  <AppTextField
                    v-model="form.checkout_to_time"
                    :error-messages="form.errors.checkout_to_time"
                    label="Giờ check-out mặc định"
                    type="time"
                    placeholder="Chọn giờ check-out"
                  />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12">
              <VCheckbox
                v-model="form.is_sync_enabled"
                label="Cho phép đồng bộ dữ liệu bên ngoài"
                :error-messages="form.errors.is_sync_enabled"
                color="primary"
                hide-details="auto"
              />
            </VCol>

            <!-- Partner Admin assignment fields - only for new properties -->
            <template v-if="isNewForm">
              <VCol cols="12">
                <VDivider class="mb-4" />
                <h6 class="text-h6">Thông tin Partner</h6>
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="form.partner_group_id"
                  :error-messages="form.errors.partner_group_id"
                  :items="partnerGroup"
                  item-title="name"
                  item-value="id"
                  label="Chọn Partner Admin"
                  placeholder="Chọn Partner Admin"
                />
              </VCol>
            </template>

            <!-- Room limits and OTA settings - for both new and edit -->
            <!-- TODO: change to permission -->
            <template v-if="!partnerUser">
              <VCol cols="12">
                <VDivider class="mb-4" />
                <h6 class="text-h6">
                  {{ isNewForm ? "Thiết lập" : "Cập nhật" }} giới hạn phòng
                </h6>
              </VCol>

              <VCol cols="12">
                <VRow>
                  <VCol cols="6">
                    <AppTextField
                      v-model="form.max_room_types"
                      :error-messages="form.errors.max_room_types"
                      :rules="[requiredValidator, positiveValidator]"
                      label="Số loại phòng tối đa"
                      type="number"
                      placeholder="Nhập số lượng"
                    />
                  </VCol>
                  <VCol cols="6">
                    <AppTextField
                      v-model="form.max_rooms"
                      :error-messages="form.errors.max_rooms"
                      :rules="[
                        requiredValidator,
                        positiveValidator,
                        maxRoomsValidator,
                      ]"
                      label="Số phòng tối đa"
                      type="number"
                      placeholder="Nhập số lượng"
                    />
                  </VCol>
                </VRow>
              </VCol>
            </template>
            <VCol cols="12">
              <VDivider class="mb-4" />
              <h6 class="text-h6">Các OTAs liên kết</h6>
              <div class="text-body-2 text-medium-emphasis">
                Chọn các OTAs mà chỗ nghỉ này sẽ sử dụng
              </div>
            </VCol>
            <VCol cols="12 mb-4">
              <VRow dense>
                <VCol
                  v-for="source in bookingSources"
                  :key="source.id"
                  cols="12"
                  sm="6"
                  md="3"
                >
                  <VCheckbox
                    v-model="form.booking_source_ids"
                    :value="source.id"
                    :label="source.name"
                    :error-messages="form.errors.booking_source_ids"
                    color="primary"
                    hide-details="auto"
                  >
                    <template v-slot:append>
                      <VChip
                        v-if="source.is_default"
                        size="small"
                        color="success"
                        variant="tonal"
                      >
                        Mặc định
                      </VChip>
                      <VChip
                        v-if="source.price_percentage > 0"
                        size="small"
                        color="warning"
                        variant="tonal"
                      >
                        +{{ source.price_percentage }}%
                      </VChip>
                    </template>
                  </VCheckbox>
                </VCol>
              </VRow>
            </VCol>

            <!-- Submit button -->
          </VRow>
          <VRow class="ga-2 justify-center">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="onReset"
              :disabled="form.processing"
              >Hủy</VBtn
            >
            <VBtn type="submit" :loading="form.processing">Submit</VBtn>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { CURRENCIES, PROPERTY_TYPE_OPTIONS } from "@/utils/constants";
import { CountriesOptions } from "@/utils/country";
import { useForm, usePage } from "@inertiajs/vue3";
import { provinces } from "vietnam-provinces";
import { computed, ref, watch } from "vue";

const vietnamCities = provinces.map((province) => ({
  title: province.name,
  value: province.code,
}));
const partnerUser = computed(() => usePage().props?.auth?.user?.group_id);

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  data: {
    type: Object,
    required: false,
  },
  partnerGroup: {
    type: Array,
    required: true,
    default: () => [],
  },
  bookingSources: {
    type: Array,
    required: true,
    default: () => [],
  },
});

const isNewForm = computed(() => !props.data);

const emit = defineEmits(["update:isDialogVisible"]);

const defaultFormValues = {
  name: "",
  type: "",
  city: "",
  address: "",
  country: "",
  currency: "",
  phone: "",
  email: "",
  checkin_from_time: "14:00",
  checkout_to_time: "12:00",
  is_sync_enabled: false,
  partner_group_id: "",
  max_room_types: 1,
  max_rooms: 1,
  deposit_amount: null,
  booking_source_ids: [],
};

const formRef = ref();

const form = useForm(defaultFormValues);

// Validators
const positiveValidator = (value) => {
  return Number(value) > 0 ? true : "Giá trị phải lớn hơn 0";
};

const maxRoomsValidator = (value) => {
  const maxRoomTypes = Number(form.max_room_types);
  const maxRooms = Number(value);
  if (maxRooms < maxRoomTypes) {
    return "Số phòng tối đa phải lớn hơn hoặc bằng số loại phòng";
  }
  return true;
};

// Partner Admin options computed from props
// const partnerAdminOptions = computed(() => {
//   return props.partnerAdmins.map((admin) => ({
//     id: admin.id,
//     name: `${admin.name} (${admin.email})`,
//   }));
// });

const onReset = () => {
  emit("update:isDialogVisible", false);
};

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();

  if (!valid) return;

  if (isNewForm.value) {
    form.post(route("properties.store"), {
      onSuccess: () => {
        onReset();
      },
      onError: () => {},
    });
  } else {
    form.put(route("properties.update", { property: props.data.id }), {
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
    if (!props.data) {
      form.defaults(defaultFormValues);
      form.reset();
    } else {
      // For edit form, include booking source IDs
      const editData = {
        ...props.data,
        booking_source_ids:
          props.data.booking_sources?.map((source) => source.id) || [],
      };
      form.defaults(editData);
      form.reset();
    }
  }
);
</script>
