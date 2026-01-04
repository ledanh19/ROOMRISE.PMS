<template>
  <VDialog
    :model-value="props.isAddDialogVisible"
    @update:model-value="onReset"
    width="1200"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard
      :title="props.mode == 'changeDate' ? 'Đổi ngày/Phòng' : 'Thêm phòng'"
    >
      <VForm ref="formRef" @submit.prevent="onSubmit">
        <VCardItem>
          <VCard class="border pa-6">
            <VRow>
              <VCol cols="12" sm="12" md="6" lg="4">
                <VRow
                  class="font-weight-medium text-high-emphasis d-flex align-center"
                >
                  <VCol cols="4"><div>Nhận phòng</div></VCol>
                  <VCol cols="8">
                    <AppTextField
                      v-model="form.check_in_date"
                      :error-messages="form.errors.check_in_date"
                      type="date"
                    />
                  </VCol>
                </VRow>
                <VRow
                  class="font-weight-medium text-high-emphasis d-flex align-center"
                >
                  <VCol cols="4"><div>Trả phòng</div></VCol>
                  <VCol cols="8">
                    <AppTextField
                      v-model="form.check_out_date"
                      :error-messages="form.errors.check_out_date"
                      type="date"
                    />
                  </VCol>
                </VRow>
              </VCol>

              <VCol cols="12" sm="12" md="6" lg="4">
                <VRow
                  class="font-weight-medium text-high-emphasis d-flex align-center"
                >
                  <VCol cols="4"><div>Giờ đến</div></VCol>
                  <VCol cols="8">
                    <AppTextField
                      v-model="form.check_in_time"
                      :error-messages="form.errors.check_in_time"
                      type="time"
                    />
                  </VCol>
                </VRow>
                <VRow
                  class="font-weight-medium text-high-emphasis d-flex align-center"
                >
                  <VCol cols="4"><div>Giờ đi</div></VCol>
                  <VCol cols="8">
                    <AppTextField
                      v-model="form.check_out_time"
                      :error-messages="form.errors.check_out_time"
                      type="time"
                    />
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="4">
                <AppTextarea
                  v-model="form.note"
                  rows="3"
                  label="Ghi chú"
                  placeholder="Ghi chú"
                />
              </VCol>
            </VRow>
          </VCard>
        </VCardItem>
        <VCardItem>
          <VCard class="border pa-6">
            <VRow>
              <VCol cols="12" sm="12" md="6" lg="3">
                <AppSelect
                  v-model="form.room_id"
                  :items="roomOptions"
                  item-title="name"
                  item-value="id"
                  label="Loại phòng"
                  :rules="[requiredValidator]"
                  :error-messages="form.errors.room_id"
                  @update:model-value="onRoomChange"
                />
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="3">
                <AppSelect
                  v-model="form.room_unit_id"
                  :items="unitOptions[form.room_id] || []"
                  item-title="name"
                  item-value="id"
                  :rules="[requiredValidator]"
                  label="Phòng"
                  :error-messages="form.errors.room_unit_id"
                />
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="3">
                <AppSelect
                  v-model="form.rate_plan_id"
                  :items="ratePlanOptions[form.room_id] || []"
                  item-title="title"
                  item-value="id"
                  label="Loại giá"
                  :error-messages="form.errors.rate_plan_id"
                  @update:model-value="onRatePlanChange"
                />
              </VCol>
              <VCol
                cols="12"
                sm="12"
                md="6"
                lg="3"
                class="d-flex align-center h-100"
              >
                <AppTextField
                  v-model.number="form.room_price_at_booking"
                  type="number"
                  label="Giá/đêm"
                  :suffix="
                    propertyOptions.find(
                      (option) => option.id === form.property_id
                    )?.currency || null
                  "
                  :rules="[requiredValidator]"
                  :error-messages="form.errors.room_price_at_booking"
                  readonly
                />
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="6">
                <VCardItem class="border rounded-lg">
                  <VTable>
                    <thead>
                      <tr>
                        <th style="border-bottom: none">Ngày</th>
                        <th style="border-bottom: none">Giá</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(rate, index) in nightlyRates"
                        :key="rate.date"
                      >
                        <td class="d-flex align-center justify-space-between">
                          <div>{{ rate.date }}</div>
                          <VIcon icon="tabler-calendar-event"></VIcon>
                        </td>
                        <td>
                          <AppTextField
                            v-model.number="rate.price"
                            type="number"
                            placeholder="Nhập giá"
                          />
                        </td>
                      </tr>
                      <tr>
                        <td class="text-right font-weight-medium">
                          Áp dụng cho tất cả
                        </td>
                        <td>
                          <AppTextField
                            v-model.number="bulkPrice"
                            type="number"
                            placeholder="Nhập giá"
                            @blur="applyBulkPrice"
                            @keyup.enter="applyBulkPrice"
                          />
                        </td>
                      </tr>
                    </tbody>
                  </VTable>
                </VCardItem>
              </VCol>
              <VCol cols="12" sm="12" md="6" lg="6">
                <VCardItem title="Giảm giá" class="border rounded-sm">
                  <VRow>
                    <VCol cols="12">
                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"><div>Số tiền giảm</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model.number="discountAmount"
                            :suffix="'₫'"
                            @blur="onDiscountAmountBlur"
                          />
                        </VCol>
                      </VRow>

                      <VRow
                        class="font-weight-medium text-high-emphasis d-flex align-center"
                      >
                        <VCol cols="4"><div>Giảm theo %</div></VCol>
                        <VCol cols="8">
                          <AppTextField
                            v-model.number="discountPercent"
                            :suffix="'%'"
                            :rules="[
                              (v) =>
                                (v >= 0 && v <= 100) || 'Phải từ 0 đến 100',
                            ]"
                            @blur="onDiscountPercentBlur"
                          />
                        </VCol>
                      </VRow>

                      <VRow class="mt-2">
                        <VCol cols="4"><div>Tổng sau giảm:</div></VCol>
                        <VCol cols="8">
                          <strong class="text-success">
                            {{ formatCurrency(totalNightlyPrice) }}
                            -
                            {{ formatCurrency(discountAmount) }}
                            =
                            {{ formatCurrency(finalTotal) }}
                          </strong>
                        </VCol>
                      </VRow>
                    </VCol>
                  </VRow>
                </VCardItem>
              </VCol>
            </VRow>
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
import { computed, ref, watch } from "vue";
import dayjs from "dayjs";
import { debounce } from "lodash";
const props = defineProps({
  isAddDialogVisible: {
    type: Boolean,
    required: true,
  },
  data: Object,
  mode: String,
});
const emit = defineEmits(["update:isAddDialogVisible", "update:updateBooking"]);

const onReset = () => {
  emit("update:isAddDialogVisible", false);
  form.reset();
  form.clearErrors();
  bulkPrice.value = null;
  nightlyRates.value = [];
  discountAmount.value = 0;
  discountPercent.value = 0;
};

const defaultFormData = {
  check_in_date: "",
  check_out_date: "",
  check_in_time: "",
  check_out_time: "",
  note: "",
  property_id: "",
  room_id: "",
  room_unit_id: "",
  rate_plan_id: "",
  room_price_at_booking: "",
  total: "",
  discount: "",
  prices: [],
};
const bulkPrice = ref(null);
const formRef = ref();
const form = useForm(defaultFormData);
function pickValidFormFields(data, template) {
  return Object.fromEntries(
    Object.keys(template).map((key) => [key, data[key] ?? template[key]])
  );
}
watch(
  () => props.isAddDialogVisible,
  async (isVisible) => {
    if (!isVisible) return;

    form.clearErrors();

    if (!props.data) {
      form.defaults(defaultFormData);
      form.reset();
      nightlyRates.value = [];
    } else {
      const filtered = pickValidFormFields(props.data, defaultFormData);
      form.defaults(filtered);
      form.reset();
      await loadProperties();
      const roomId = filtered.room_id;
      if (roomId) {
        if (!unitOptions.value[roomId]) {
          const res = await axios.get(
            route("rooms.units.options", { room: roomId })
          );
          unitOptions.value[roomId] = res.data;
        }

        if (!ratePlanOptions.value[roomId]) {
          const res = await axios.get(
            route("rateplans.options", { room: roomId })
          );
          ratePlanOptions.value[roomId] = res.data;
        }
        const ratePlan = (ratePlanOptions.value[roomId] || []).find(
          (rp) => rp.id === filtered.rate_plan_id
        );
        if (ratePlan && !props.data?.price_date) {
          form.room_price_at_booking = ratePlan.rate || 0;
        }
      }

      if (props.data.price_date) {
        const parsed = props.data.price_date.split(",").map((item) => {
          const [date, price] = item.split(":");
          return { date, price: Number(price) };
        });
        nightlyRates.value = parsed;
        form.prices = [...parsed];

        const total = parsed.reduce((sum, rate) => sum + (rate.price || 0), 0);
        discountAmount.value = Number(props.data.discount || 0);
        if (total > 0 && discountAmount.value > 0) {
          discountPercent.value = Math.round(
            (discountAmount.value / total) * 100
          );
        }
      }
    }
    // console.log(form);
  }
);

const roomOptions = ref([]);
const unitOptions = ref({});
const propertyOptions = ref([]);
const ratePlanOptions = ref({});
async function loadProperties() {
  const res = await axios.get(route("properties.options"));
  propertyOptions.value = res.data;
  if (form.property_id) {
    const res2 = await axios.get(
      route("rooms.options", { property: form.property_id })
    );
    roomOptions.value = res2.data;
  }
}
async function onRoomChange(roomId) {
  if (roomId) {
    if (!unitOptions.value[roomId]) {
      const res = await axios.get(
        route("rooms.units.options", { room: roomId })
      );
      unitOptions.value[roomId] = res.data;
    }
    if (!ratePlanOptions.value[roomId]) {
      const res = await axios.get(route("rateplans.options", { room: roomId }));
      ratePlanOptions.value[roomId] = res.data;
    }
    form.room_unit_id = "";
    form.rate_plan_id = "";
    form.room_price_at_booking = 0;
  }
}
function onRatePlanChange(ratePlanId) {
  const roomId = form.room_id;
  const ratePlan = (ratePlanOptions.value[roomId] || []).find(
    (rp) => rp.id === ratePlanId
  );

  if (ratePlan) {
    form.room_price_at_booking = ratePlan.price || 0;
    form.rate_plan_id = ratePlanId;
  }
}

const nightlyRates = ref([]);
watch(
  () => [form.check_in_date, form.check_out_date],
  ([checkIn, checkOut]) => {
    if (!checkIn || !checkOut) {
      nightlyRates.value = [];
      return;
    }

    const start = dayjs(checkIn);
    const end = dayjs(checkOut);

    if (!end.isAfter(start)) {
      nightlyRates.value = [];
      return;
    }
    const days = [];
    let current = start;

    while (current.isBefore(end)) {
      days.push({
        date: current.format("YYYY-MM-DD"),
        price: 0,
      });
      current = current.add(1, "day");
    }
    const oldRatesMap = Object.fromEntries(
      nightlyRates.value.map((rate) => [rate.date, rate.price])
    );

    const updatedDays = days.map((day) => {
      return {
        date: day.date,
        price: oldRatesMap[day.date] ?? form.room_price_at_booking ?? 0,
      };
    });

    nightlyRates.value = updatedDays;
    form.prices = [...updatedDays];
  },
  { immediate: false }
);
watch(nightlyRates, (rates) => {
  const total = rates.reduce((sum, rate) => sum + (rate.price || 0), 0);
  const discount = discountAmount.value;
  if (total > 0 && discount > 0) {
    discountPercent.value = Math.round((discount / total) * 100);
  } else {
    discountPercent.value = 0;
  }
});

watch(
  () => form.room_price_at_booking,
  (newPrice) => {
    if (!newPrice || nightlyRates.value.length === 0) return;
    if (!props.data || !props.data.price_date) {
      const updatedRates = nightlyRates.value.map((rate) => ({
        ...rate,
        price: newPrice,
      }));

      nightlyRates.value = updatedRates;
      form.prices = [...updatedRates];
    }
  }
);

const totalNightlyPrice = computed(() => {
  return nightlyRates.value.reduce(
    (sum, rate) => sum + Number(rate.price || 0),
    0
  );
});
const discountAmount = ref(0);
const discountPercent = ref(0);

const finalTotal = computed(() => {
  const original = totalNightlyPrice.value;
  return Math.max(original - discountAmount.value, 0);
});

function onDiscountAmountBlur() {
  const original = totalNightlyPrice.value;
  if (original > 0) {
    discountPercent.value = Math.round((discountAmount.value / original) * 100);
  }
}

function onDiscountPercentBlur() {
  const original = totalNightlyPrice.value;
  if (discountPercent.value >= 0 && discountPercent.value <= 100) {
    discountAmount.value = Math.round((discountPercent.value / 100) * original);
  }
}
function applyBulkPrice() {
  if (bulkPrice.value != null) {
    nightlyRates.value = nightlyRates.value.map((rate) => ({
      ...rate,
      price: bulkPrice.value,
    }));
    form.prices = [...nightlyRates.value];
  }
}
watch(totalNightlyPrice, (newTotal) => {
  if (newTotal > 0 && discountAmount.value > 0) {
    discountPercent.value = Math.round((discountAmount.value / newTotal) * 100);
  } else {
    discountPercent.value = 0;
  }
});
const isEditMode = !!props.data?.id;
const onSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  form.total = totalNightlyPrice.value;
  form.discount = discountAmount.value;

  const isEditMode = !!props.data?.id && !!props.data?.booking_id;

  const routeName = isEditMode
    ? "bookings.rooms.update"
    : "bookings.rooms.store";

  const routeParams = isEditMode
    ? {
        booking: props.data.booking_id,
        room: props.data.id,
      }
    : {
        booking: props.data?.booking_id || props.data?.id,
      };

  if (isEditMode) {
    form.put(route(routeName, routeParams), {
      onSuccess: () => {
        onReset();
        form.reset();
        emit("update:updateBooking");
      },
      onError: () => {
        console.log("Có lỗi khi cập nhật phòng");
      },
    });
  } else {
    form.post(route(routeName, routeParams), {
      onSuccess: () => {
        onReset();
        form.reset();
      },
      onError: () => {
        console.log("Có lỗi khi tạo phòng");
      },
    });
  }
};
</script>
