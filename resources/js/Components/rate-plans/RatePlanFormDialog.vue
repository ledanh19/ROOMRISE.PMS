<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 1000"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-2 pa-sm-10">
      <VCardText>
        <h4 class="text-h4 text-center mb-6">
          {{ isNewForm ? "Thêm mới" : "Cập nhật" }} giá
        </h4>

        <VForm ref="formRef" @submit.prevent="onSubmit">
          <VRow>
            <VCol cols="12 pb-2">
              <h6 class="text-h5 pb-0">Nguồn đặt phòng</h6>
            </VCol>
            <VCol>
              <template v-if="isNewForm">
                <AppSelect
                  v-model="form.booking_source_ids"
                  :error-messages="form.errors.booking_source_ids"
                  :items="bookingSourceOptions"
                  item-title="label"
                  item-value="value"
                  placeholder="Chọn nguồn đặt phòng"
                  multiple
                  chips
                  closable-chips
                />
              </template>
              <template v-else>
                <div class="d-flex flex-wrap gap-2">
                  <VChip
                    v-for="id in form.booking_source_ids"
                    :key="id"
                    color="primary"
                    class="ma-1"
                    label
                  >
                    {{
                      bookingSourceOptions.find((opt) => opt.value === id)
                        ?.label || id
                    }}
                  </VChip>
                </div>
              </template>
            </VCol>
          </VRow>
          <VRow>
            <!-- Basic Information -->
            <VCol cols="12">
              <h6 class="text-h5">Thông tin cơ bản</h6>
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField
                v-model="form.title"
                :error-messages="form.errors.title"
                :rules="[requiredValidator]"
                label="Tên giá"
                placeholder="VD: Giá ngày thường"
              />
            </VCol>

            <VCol cols="12" md="6">
              <AppSelect
                v-model="form.meal_type"
                :error-messages="form.errors.meal_type"
                :items="MEAL_TYPE_OPTIONS"
                item-title="label"
                item-value="value"
                label="Loại bữa ăn"
                placeholder="Chọn loại bữa ăn"
              />
            </VCol>

            <!-- Sell Mode -->
            <VCol cols="12">
              <VCard flat>
                <VTabs
                  v-model="form.sell_mode"
                  :disabled="!isNewForm || !form.booking_source_ids.length"
                >
                  <VTab value="per_room">Theo Phòng</VTab>
                  <VTab value="per_person">Theo Sô Người</VTab>
                </VTabs>

                <VCardText>
                  <VWindow v-model="form.sell_mode">
                    <!-- Per Room Tab -->
                    <VWindowItem value="per_room">
                      <VRow>
                        <VCol cols="12" md="6">
                          <AppTextField
                            v-model="form.price"
                            :error-messages="form.errors.price"
                            :rules="perRoomValidationRules"
                            label="Giá"
                            :suffix="roomInfo?.currency"
                            placeholder="VD: 200000"
                            type="number"
                            min="0"
                          />
                        </VCol>
                      </VRow>
                    </VWindowItem>

                    <!-- Per Person Tab -->
                    <VWindowItem value="per_person">
                      <!-- Rate Mode -->
                      <VRow>
                        <VCol cols="12">
                          <VTabs
                            v-model="form.rate_mode"
                            class="mb-4"
                            :disabled="!isNewForm"
                          >
                            <VTab value="manual">Thủ Công</VTab>
                            <VTab value="auto">Tự Động</VTab>
                          </VTabs>

                          <VCardText class="pa-0">
                            <VWindow v-model="form.rate_mode">
                              <!-- Auto Mode -->
                              <VWindowItem value="auto">
                                <VRow>
                                  <VCol cols="12" md="6">
                                    <AppTextField
                                      v-model="form.primary_occupancy"
                                      :error-messages="
                                        form.errors['primary_occupancy']
                                      "
                                      :rules="perPersonAutoValidationRules"
                                      label="Số lượng người chính"
                                      type="number"
                                      min="1"
                                      placeholder="VD: 2"
                                      :disabled="!isNewForm"
                                    />
                                  </VCol>
                                  <VCol cols="12" md="6">
                                    <AppTextField
                                      v-model="form.default_rate"
                                      :error-messages="
                                        form.errors['default_rate']
                                      "
                                      :rules="perPersonAutoValidationRules"
                                      label="Giá mặc định"
                                      :suffix="roomInfo?.currency"
                                      type="number"
                                      min="0"
                                      placeholder="VD: 500000"
                                    />
                                  </VCol>
                                  <VCol cols="12" md="6">
                                    <VRow no-gutters>
                                      <VCol cols="8">
                                        <AppTextField
                                          v-model="
                                            form.auto_rate_settings
                                              .increase_value
                                          "
                                          :error-messages="
                                            form.errors[
                                              'auto_rate_settings.increase_value'
                                            ]
                                          "
                                          :rules="perPersonAutoValidationRules"
                                          label="Giá trị tăng"
                                          type="number"
                                          min="0"
                                          placeholder="VD: 10"
                                        />
                                      </VCol>
                                      <VCol cols="4" class="pl-1">
                                        <AppSelect
                                          v-model="
                                            form.auto_rate_settings
                                              .increase_mode
                                          "
                                          :items="['$', '%']"
                                          label="Chế độ"
                                        />
                                      </VCol>
                                    </VRow>
                                  </VCol>
                                  <VCol cols="12" md="6">
                                    <VRow no-gutters>
                                      <VCol cols="8">
                                        <AppTextField
                                          v-model="
                                            form.auto_rate_settings
                                              .decrease_value
                                          "
                                          :error-messages="
                                            form.errors[
                                              'auto_rate_settings.decrease_value'
                                            ]
                                          "
                                          :rules="perPersonAutoValidationRules"
                                          label="Giá trị giảm"
                                          type="number"
                                          min="0"
                                          placeholder="VD: 30"
                                        />
                                      </VCol>
                                      <VCol cols="4" class="pl-1">
                                        <AppSelect
                                          v-model="
                                            form.auto_rate_settings
                                              .decrease_mode
                                          "
                                          :items="['$', '%']"
                                          label="Chế độ"
                                        />
                                      </VCol>
                                    </VRow>
                                  </VCol>
                                </VRow>
                              </VWindowItem>

                              <!-- Manual Mode -->
                              <VWindowItem value="manual">
                                <VRow>
                                  <VCol cols="12">
                                    <h6 class="text-subtitle-2 mb-3">
                                      Thiết lập giá cho từng số lượng người
                                    </h6>
                                    <div
                                      v-for="(
                                        option, index
                                      ) in form.occupancy_options"
                                      :key="option.occupancy"
                                      class="mb-4"
                                    >
                                      <VRow>
                                        <VCol cols="12">
                                          <AppTextField
                                            v-model="option.rate"
                                            :label="`Giá cho ${option.occupancy} người`"
                                            :suffix="roomInfo?.currency"
                                            type="number"
                                            min="0"
                                            :rules="
                                              getOccupancyValidationRules(
                                                option.occupancy
                                              )
                                            "
                                            placeholder="VD: 200000"
                                          />
                                        </VCol>
                                      </VRow>
                                    </div>
                                  </VCol>
                                </VRow>
                              </VWindowItem>
                            </VWindow>
                          </VCardText>
                        </VCol>
                      </VRow>

                      <!-- Fees Section -->
                      <VRow>
                        <VCol cols="12" md="6">
                          <AppTextField
                            v-model="form.children_fee"
                            :error-messages="form.errors.children_fee"
                            label="Children Fee"
                            :suffix="roomInfo?.currency"
                            type="number"
                            min="0"
                            placeholder="VD: 100000"
                          />
                        </VCol>
                        <VCol cols="12" md="6">
                          <AppTextField
                            v-model="form.infant_fee"
                            :error-messages="form.errors.infant_fee"
                            label="Infant Fee"
                            :suffix="roomInfo?.currency"
                            type="number"
                            min="0"
                            placeholder="VD: 200000"
                          />
                        </VCol>
                      </VRow>
                    </VWindowItem>
                  </VWindow>
                </VCardText>
              </VCard>
            </VCol>

            <!-- Restrictions Section -->
            <VCol cols="12" v-if="form.booking_source_ids.length">
              <VExpansionPanels variant="accordion">
                <VExpansionPanel>
                  <VExpansionPanelTitle>
                    <h6 class="text-h6 mb-0">Thiết lập hạn chế</h6>
                  </VExpansionPanelTitle>
                  <VExpansionPanelText>
                    <VCard variant="outlined" class="pa-4">
                      <VRow>
                        <VCol cols="12" md="4">
                          <AppTextField
                            v-model="form.min_stay_arrival"
                            :error-messages="form.errors.min_stay_arrival"
                            label="Lưu trú tối thiểu khi đến"
                            type="number"
                            min="0"
                            placeholder="VD: 1"
                          />
                        </VCol>
                        <VCol cols="12" md="4">
                          <AppTextField
                            v-model="form.min_stay_through"
                            :error-messages="form.errors.min_stay_through"
                            label="Lưu trú tối thiểu xuyên suốt"
                            type="number"
                            min="0"
                            placeholder="VD: 1"
                          />
                        </VCol>
                        <VCol cols="12" md="4">
                          <AppTextField
                            v-model="form.max_stay"
                            :error-messages="form.errors.max_stay"
                            label="Lưu trú tối đa"
                            type="number"
                            min="0"
                            placeholder="VD: 0"
                          />
                        </VCol>
                      </VRow>
                      <VRow>
                        <VCol cols="12">
                          <h6 class="text-subtitle-2 mb-2">
                            Đóng cửa nhận phòng
                          </h6>
                          <div class="d-flex gap-2">
                            <VCheckbox
                              v-for="(day, index) in weekDays"
                              :key="day"
                              v-model="form.closed_to_arrival[index]"
                              :label="day"
                              color="primary"
                            />
                          </div>
                        </VCol>
                        <VCol cols="12">
                          <h6 class="text-subtitle-2 mb-2">
                            Đóng cửa trả phòng
                          </h6>
                          <div class="d-flex gap-2">
                            <VCheckbox
                              v-for="(day, index) in weekDays"
                              :key="day"
                              v-model="form.closed_to_departure[index]"
                              :label="day"
                              color="primary"
                            />
                          </div>
                        </VCol>
                        <VCol cols="12">
                          <h6 class="text-subtitle-2 mb-2">Ngừng bán</h6>
                          <div class="d-flex gap-2">
                            <VCheckbox
                              v-for="(day, index) in weekDays"
                              :key="day"
                              v-model="form.stop_sell[index]"
                              :label="day"
                              color="primary"
                            />
                          </div>
                        </VCol>
                      </VRow>
                    </VCard>
                  </VExpansionPanelText>
                </VExpansionPanel>
              </VExpansionPanels>
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
import { requiredValidator } from "@/@core/utils/validators";
import { MEAL_TYPE_OPTIONS } from "@/utils/constants";
import { useForm } from "@inertiajs/vue3";
import { computed, nextTick, ref, watch } from "vue";

const props = defineProps({
  isDialogVisible: Boolean,
  roomInfo: Object,
  data: Object,
  bookingSources: Array,
});

const bookingSourceOptions = computed(() => {
  return (
    props.bookingSources?.map((source) => ({
      label: `${source.name}`,
      value: source.id,
    })) || []
  );
});

const isNewForm = computed(() => !props.data);
const emit = defineEmits(["update:isDialogVisible"]);
const formRef = ref();
const isFormInitialized = ref(false);
const isFormLoaded = ref(false);
const isInitializing = ref(false);

const weekDays = ["Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"];

const initialData = computed(() => {
  const maxPeople = props.roomInfo?.max_people || 1;
  const occupancyOptions = [];

  // Generate occupancy options based on max_people
  for (let i = 1; i <= maxPeople; i++) {
    occupancyOptions.push({
      occupancy: i,
      rate: 0,
      is_primary: false, // Will be set by Channex response
    });
  }

  return isNewForm.value
    ? {
        room_id: props.roomInfo?.room_id ?? null,
        booking_source_ids: [],
        title: "",
        price: null,
        meal_type: "none",
        sell_mode: "per_room",
        rate_mode: "manual",
        children_fee: 0,
        infant_fee: 0,
        min_stay_arrival: 1,
        min_stay_through: 1,
        max_stay: 0,
        closed_to_arrival: [false, false, false, false, false, false, false],
        closed_to_departure: [false, false, false, false, false, false, false],
        stop_sell: [false, false, false, false, false, false, false],
        auto_rate_settings: {
          increase_mode: "VND",
          decrease_mode: "VND",
          increase_value: null,
          decrease_value: null,
        },
        primary_occupancy: 1,
        default_rate: 0,
        occupancy_options: occupancyOptions,
      }
    : {
        room_id: props.roomInfo?.room_id ?? null,
        booking_source_ids: props.data.booking_sources.map((item) => item.id),
        title: props.data.title,
        price: Number(props.data.price),
        meal_type: props.data.meal_type,
        sell_mode: props.data.sell_mode || "per_room",
        rate_mode: props.data.rate_mode || "manual",
        children_fee: Number(props.data.children_fee || 0),
        infant_fee: Number(props.data.infant_fee || 0),
        min_stay_arrival: Array.isArray(props.data.min_stay_arrival)
          ? props.data.min_stay_arrival[0]
          : Number(props.data.min_stay_arrival || 1),
        min_stay_through: Array.isArray(props.data.min_stay_through)
          ? props.data.min_stay_through[0]
          : Number(props.data.min_stay_through || 1),
        max_stay: Array.isArray(props.data.max_stay)
          ? props.data.max_stay[0]
          : Number(props.data.max_stay || 0),
        closed_to_arrival: props.data.closed_to_arrival || [
          false,
          false,
          false,
          false,
          false,
          false,
          false,
        ],
        closed_to_departure: props.data.closed_to_departure || [
          false,
          false,
          false,
          false,
          false,
          false,
          false,
        ],
        stop_sell: props.data.stop_sell || [
          false,
          false,
          false,
          false,
          false,
          false,
          false,
        ],
        auto_rate_settings: {
          increase_mode: props.data.auto_rate_settings?.increase_mode || "$",
          decrease_mode: props.data.auto_rate_settings?.decrease_mode || "$",
          increase_value: props.data.auto_rate_settings?.increase_value || null,
          decrease_value: props.data.auto_rate_settings?.decrease_value || null,
        },
        primary_occupancy: props.data.primary_occupancy || null,
        default_rate: props.data.price || null,
        occupancy_options:
          props.data.occupancy_options?.length > 0
            ? props.data.occupancy_options
            : occupancyOptions,
      };
});

const form = useForm({});

const onReset = () => {
  emit("update:isDialogVisible", false);
  form.clearErrors();
};

const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  // Prepare data before submission
  const submitData = { ...form.data() };

  // Ensure all required fields are present
  submitData.primary_occupancy = form.primary_occupancy;

  // Map default_rate to price for auto mode
  if (
    submitData.sell_mode === "per_person" &&
    submitData.rate_mode === "auto"
  ) {
    submitData.price = Number(submitData.default_rate) || 0;
  }

  // Set primary_occupancy and price based on sell_mode
  if (submitData.sell_mode === "per_room") {
    // Per room: primary_occupancy = room's max_people, price = giá nhập
    submitData.primary_occupancy = props.roomInfo?.max_people || 1;
    // price đã được set từ form.price
  } else if (submitData.sell_mode === "per_person") {
    if (submitData.rate_mode === "manual") {
      // Per person manual: primary_occupancy = room's max_people, price = giá từ occupancy đầu tiên
      submitData.primary_occupancy = props.roomInfo?.max_people || 1;
      if (
        submitData.occupancy_options &&
        submitData.occupancy_options.length > 0
      ) {
        submitData.price = Number(submitData.occupancy_options[0].rate) || 0;
      }
    } else if (submitData.rate_mode === "auto") {
      // Per person auto: primary_occupancy = user input, price đã được set từ default_rate ở trên
      submitData.primary_occupancy = Number(submitData.primary_occupancy) || 1;
    }
  }

  // Update form data
  Object.keys(submitData).forEach((key) => {
    form[key] = submitData[key];
  });

  if (isNewForm.value) {
    form.post(route("rate-plans.store"), {
      onSuccess: () => {
        onReset();
      },
    });
  } else {
    form.put(route("rate-plans.update", props.data?.id), {
      onSuccess: () => {
        onReset();
      },
    });
  }
};

watch(
  () => props.isDialogVisible,
  (isVisible) => {
    if (isVisible) {
      isInitializing.value = true;
      form.clearErrors();
      form.defaults(initialData.value);
      form.reset();

      // Debug: Log occupancy options
      if (!isNewForm.value) {
        console.log("Edit mode - props.data:", props.data);

        console.log(
          "Edit mode - occupancy_options:",
          props.data.occupancy_options
        );
        console.log(
          "Form occupancy_options after reset:",
          form.occupancy_options
        );

        // Ensure occupancy_options is set correctly for edit mode
        if (
          props.data.occupancy_options &&
          props.data.occupancy_options.length > 0
        ) {
          form.occupancy_options = props.data.occupancy_options;
        }

        // Ensure rate_mode is set correctly for edit mode
        if (props.data.rate_mode) {
          nextTick(() => {
            form.rate_mode = props.data.rate_mode;
          });
        }
      }

      // Mark form as initialized after a short delay to avoid watcher conflicts
      nextTick(() => {
        isFormInitialized.value = true;
        isFormLoaded.value = true;
        isInitializing.value = false;
      });
    } else {
      // Reset flag when dialog closes
      isFormInitialized.value = false;
      isFormLoaded.value = false;
      isInitializing.value = false;
    }
  },
  { immediate: true }
);

// Watch for sell_mode changes to reset rate_mode and occupancy options
watch(
  () => form.sell_mode,
  (newMode, oldMode) => {
    console.log("sell_mode watcher triggered:", {
      newMode,
      oldMode,
      isFormInitialized: isFormInitialized.value,
      isNewForm: isNewForm.value,
      isInitializing: isInitializing.value,
    });

    // Don't trigger during initialization
    if (isInitializing.value) return;

    // Only reset if form is initialized and not during initial load
    if (!isFormInitialized.value) return;

    // Don't trigger reset during initial form load in edit mode
    if (!isNewForm.value && !isFormLoaded.value) return;

    if (isNewForm.value) {
      if (newMode === "per_room") {
        // Reset rate_mode to manual when switching to per_room
        form.rate_mode = "manual";
      } else if (newMode === "per_person") {
        // Reset rate_mode to manual when switching to per_person
        form.rate_mode = "manual";
        // Initialize occupancy options based on room max_people
        const maxPeople = props.roomInfo?.max_people || 1;
        const occupancyOptions = [];
        for (let i = 1; i <= maxPeople; i++) {
          occupancyOptions.push({
            occupancy: i,
            rate: null,
            is_primary: false,
          });
        }
        form.occupancy_options = occupancyOptions;
      }
    } else {
      // For edit mode, don't reset occupancy_options when switching modes
      // Keep the existing data from props
      if (newMode === "per_room") {
        form.rate_mode = "manual";
      } else if (newMode === "per_person") {
        // Don't reset rate_mode in edit mode, keep the original value from props
        // Don't reset occupancy_options in edit mode, keep existing data
      }
    }

    // Reset form fields based on new mode
    resetFormByMode();
  }
);

// Watch for rate_mode changes to handle auto settings
watch(
  () => form.rate_mode,
  (newMode, oldMode) => {
    // Only reset if form is initialized and not during initial load
    if (!isFormInitialized.value) return;

    // Reset form fields based on new rate mode
    resetFormByMode();
  }
);

const roomMaxPeople = computed(() => {
  return props.roomInfo?.max_people || 1;
});

// Dynamic validation rules based on form mode
const perRoomValidationRules = computed(() => {
  return form.sell_mode === "per_room" ? [requiredValidator] : [];
});

const perPersonManualValidationRules = computed(() => {
  return form.sell_mode === "per_person" && form.rate_mode === "manual"
    ? [requiredValidator]
    : [];
});

const perPersonAutoValidationRules = computed(() => {
  return form.sell_mode === "per_person" && form.rate_mode === "auto"
    ? [requiredValidator]
    : [];
});

// Function to get validation rules for occupancy options
const getOccupancyValidationRules = (occupancy) => {
  return form.sell_mode === "per_person" && form.rate_mode === "manual"
    ? [requiredValidator]
    : [];
};

// Function to reset form based on current mode
const resetFormByMode = () => {
  // Clear all validation errors
  form.clearErrors();

  // Only reset fields in new form mode, not in edit mode
  if (isNewForm.value) {
    console.log("Resetting form for new form mode");
    if (form.sell_mode === "per_room") {
      // Reset per room fields
      form.price = null;
      form.primary_occupancy = null;
      form.default_rate = null;
      form.auto_rate_settings = {
        increase_mode: "$",
        decrease_mode: "$",
        increase_value: null,
        decrease_value: null,
      };
      if (form.occupancy_options) {
        form.occupancy_options.forEach((option) => {
          option.rate = null;
        });
      }
    } else if (form.sell_mode === "per_person") {
      if (form.rate_mode === "manual") {
        // Reset manual mode fields
        form.price = null;
        form.primary_occupancy = null;
        form.default_rate = null;
        form.auto_rate_settings = {
          increase_mode: "$",
          decrease_mode: "$",
          increase_value: null,
          decrease_value: null,
        };
        if (form.occupancy_options) {
          form.occupancy_options.forEach((option) => {
            option.rate = null;
          });
        }
      } else if (form.rate_mode === "auto") {
        // Reset auto mode fields
        form.price = null;
        form.primary_occupancy = null;
        form.default_rate = null;
        form.auto_rate_settings = {
          increase_mode: "$",
          decrease_mode: "$",
          increase_value: null,
          decrease_value: null,
        };
        if (form.occupancy_options) {
          form.occupancy_options.forEach((option) => {
            option.rate = null;
          });
        }
      }
    }
  } else {
    console.log("Edit mode - not resetting form fields");
  }
};
</script>
