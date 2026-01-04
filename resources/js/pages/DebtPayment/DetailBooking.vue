<template>
  <VDialog
    :model-value="props.isDetailDialogVisible"
    @update:model-value="onReset"
    width="1500"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-6">
      <div class="text-h4 font-weight-medium">
        Chi tiết công nợ - {{ data.name }}
      </div>
      <VCardText class="border mt-3 rounded-lg bg-cae4ff">
        <div class="text-h5 text-blue">Thông tin tổng quan</div>
        <VRow class="mt-5">
          <VCol cols="6" sm="6" md="3" lg="3" class="text-center">
            <div class="text-blue text-h6 font-weight-medium">Vai trò</div>
            <div>
              <VChip color="info" v-if="data.type == 'Sale'">
                <VIcon icon="tabler-user"></VIcon> {{ data.type }}
              </VChip>
              <VChip color="primary" v-if="data.type == 'Sale TA'">
                <VIcon icon="tabler-building"></VIcon> {{ data.type }}
              </VChip>
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="3" class="text-center">
            <div class="text-blue text-h6 font-weight-medium">Số booking</div>
            <div class="text-blue font-weight-bold text-h4">
              {{ data.total_bookings }}
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="3" class="text-center">
            <div class="text-blue text-h6 font-weight-medium">
              Tổng doanh thu
            </div>
            <div class="text-success font-weight-medium text-h5">
              {{ formatCurrency(data.total_revenue) }}
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="3" class="text-center">
            <div class="text-blue text-h6 font-weight-medium">Net công nợ</div>
            <div class="text-warning font-weight-medium text-h5">
              {{ formatCurrency(data.total_net_debt) }}
            </div>
          </VCol>
        </VRow>
      </VCardText>
      <VCardText
        class="mt-5 border rounded-lg pa-6"
        style="padding-block-start: 24px !important"
      >
        <div class="d-flex align-center justify-space-between">
          <VCheckbox
            ref="selectAllRef"
            v-model="selectAllBooking"
            class="text-h5 font-weight-medium"
            :label="`Chọn tất cả booking (${data.filtered_bookings})`"
            @update:model-value="onSelectAllChange"
          />

          <div class="text-body-1">
            Đã chọn: {{ selected_bookings?.length || 0 }} booking
          </div>
        </div>
      </VCardText>
      <VCardText
        class="mt-5 border rounded-lg pa-6"
        style="padding-block-start: 24px !important"
        v-if="selected_bookings.length > 0"
      >
        <div class="text-h5 font-weight-medium">
          Thống kê booking đã được chọn ({{ selected_bookings.length }})
        </div>
        <VRow class="mt-3">
          <VCol cols="6" sm="6" md="2" lg="2">
            <div class="text-warning text-center">Tổng số booking</div>
            <div class="text-warning text-center font-weight-bold text-h4">
              {{ selected_bookings.length }}
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="3">
            <div class="text-warning text-center">Tổng giá trị</div>
            <div class="text-warning text-center font-weight-bold text-h4">
              {{
                formatCurrency(
                  selected_bookings.reduce(
                    (sum, booking) =>
                      sum + Number(booking.customer_payment_amount || 0),
                    0
                  )
                )
              }}
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="2">
            <div class="text-warning text-center">Đã xử lý</div>
            <div class="text-success text-center font-weight-bold text-h4">
              {{ formatCurrency(totalProcessedAmount) }}
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="2" lg="2">
            <div class="text-warning text-center">Hoa hồng</div>
            <div class="text-success text-center font-weight-bold text-h4">
              {{ formatCurrency(data.total_commission) }}
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="3">
            <div class="text-warning text-center">Còn lại</div>
            <div class="text-blue text-center font-weight-bold text-h4">
              {{ formatCurrency(totalNetAmount) }}
            </div>
          </VCol>
        </VRow>
        <VRow class="mt-3">
          <VCol cols="12" md="6">
            <VCardText class="border text-center rounded-lg bg_error">
              <div class="text-h5 text-error">Tổng phải thu</div>
              <div class="text-h5 text-error font-weight-bold">
                {{
                  totalNetAmount > 0
                    ? formatCurrency(totalNetAmount)
                    : formatCurrency(0)
                }}
              </div>
            </VCardText>
          </VCol>

          <VCol cols="12" md="6">
            <VCardText class="border text-center rounded-lg">
              <div class="text-h5 text-warning">Tổng phải trả</div>
              <div class="text-h5 text-warning font-weight-bold">
                {{
                  totalNetAmount < 0
                    ? formatCurrency(Math.abs(totalNetAmount))
                    : formatCurrency(0)
                }}
              </div>
            </VCardText>
          </VCol>

          <VCol cols="12" class="text-center mt-4">
            <VBtn
              color="success"
              class="mr-2"
              :disabled="totalNetAmount <= 0"
              @click="submitIncomeExpense('income')"
            >
              Tạo phiếu thu ({{
                formatCurrency(totalNetAmount > 0 ? totalNetAmount : 0)
              }})
            </VBtn>

            <VBtn
              color="error"
              :disabled="totalNetAmount >= 0"
              @click="submitIncomeExpense('expense')"
            >
              Tạo phiếu chi ({{
                formatCurrency(
                  totalNetAmount < 0 ? Math.abs(totalNetAmount) : 0
                )
              }})
            </VBtn>
          </VCol>
          <VCol cols="12" md="4" offset-md="4" class="text-center">
            <AppSelect
              :rules="[requiredValidator]"
              label="Hình thức thanh toán"
              v-model="payment_method"
              :items="paymentOptions"
            />
            <div class="text-error mt-2" v-if="payment_method_error">
              {{ payment_method_error }}
            </div>
            <AppTextarea
              class="mt-2"
              label="Ghi chú"
              placeholder="Ghi chú"
              v-model="note"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VCardText
        class="mt-5 border rounded-lg pa-6"
        style="padding-block-start: 24px !important"
      >
        <div class="text-h5 font-weight-medium">Danh sách booking</div>

        <VDataTable
          :headers="headers"
          :items="bookings"
          item-value="id"
          return-object
          show-select
          v-model="selected_bookings"
          :items-per-page="-1"
          class="text-no-wrap"
          hide-default-footer
        >
          <template #item.property_id="{ item }">
            {{ item?.property?.name }}
          </template>
          <template #item.room="{ item }">
            {{ item.room_names }} ({{ item.unit_names }})
          </template>
          <template #item.customer_payment_amount="{ item }">
            <div class="font-weight-medium">
              {{ formatCurrency(item.customer_payment_amount) }}
            </div>
          </template>

          <template #item.net_estimate="{ item }">
            <div class="text-error">
              {{
                item.payment_type === "Partner Collect"
                  ? formatCurrency(netEstimateAmount(item))
                  : "-"
              }}
            </div>
          </template>

          <template #item.processed="{ item }">
            <div class="text-success font-weight-medium">
              {{
                item.income_expenses && item.income_expenses.length
                  ? formatCurrency(
                      item.income_expenses.reduce(
                        (sum, ie) => sum + Number(ie.amount || 0),
                        0
                      )
                    )
                  : "0"
              }}
            </div>
          </template>

          <template #item.total_commission="{ item }">
            <div class="text-warning font-weight-medium">
              {{
                item.commission_fee ? formatCurrency(item.commission_fee) : "0"
              }}
            </div>
          </template>

          <template #item.payment_method="{ item }">
            {{
              item.payment_method
                ? item.payment_method
                : item.income_expenses?.length
                ? item.income_expenses[item.income_expenses.length - 1]
                    .payment_method
                : ""
            }}
          </template>
          <template #item.check_in_date="{ item }">
            <div class="text-high-emphasis text-body-1 pa-3">
              <div>
                {{ formatDate(item.check_in_date) }}
              </div>
            </div>
          </template>
          <template #item.check_out_date="{ item }">
            <div class="text-high-emphasis text-body-1 pa-3">
              <div>
                {{ formatDate(item.check_out_date) }}
              </div>
            </div>
          </template>
          <template #item.payment_status="{ item }">
            <VChip
              :color="item.income_expense_id ? 'success' : 'warning'"
              variant="tonal"
              size="small"
            >
              {{ item.income_expense_id ? "Đã thanh toán" : "Chờ thanh toán" }}
            </VChip>
          </template>
        </VDataTable>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3 mt-5">
        <VBtn variant="tonal" color="secondary" @click="onReset"> Hủy bỏ </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { ref, watch, onMounted, computed } from "vue";
import { formatCurrency, formatDate } from "@/utils/formatters";
const props = defineProps({
  isDetailDialogVisible: Boolean,
  data: Object,
});

const selectAllRef = ref(null);
const selectAllBooking = ref(false);
const selected_bookings = ref([]);
const payment_method = ref("");
const note = ref("");
const bookings = computed(() => {
  if (!props.data?.customers) return [];

  return props.data.customers.flatMap((customer) => {
    return (customer.bookings || []).map((booking) => {
      booking.full_name = customer.full_name;

      const rooms = booking.booking_rooms || [];

      booking.room_names = rooms
        .map((br) => br.room?.name)
        .filter(Boolean)
        .join(", ");

      booking.unit_names = rooms
        .map((br) => br.room_unit?.name)
        .filter(Boolean)
        .join(", ");

      return booking;
    });
  });
});

const onSelectAllChange = (val) => {
  if (val) {
    selected_bookings.value = bookings.value.slice();
  } else {
    selected_bookings.value = [];
  }
};

watch(
  () => selected_bookings.value,
  (newVal) => {
    const allSelected =
      newVal.length === bookings.value.length && bookings.value.length > 0;

    if (selectAllBooking.value !== allSelected) {
      selectAllBooking.value = allSelected;
    }
  },
  { deep: true }
);

const emit = defineEmits(["update:isDetailDialogVisible"]);
const defaultFormData = {
  adults: "",
  children: "",
  newborn: "",
};

const formRef = ref();
const form = useForm(defaultFormData);

const onReset = () => {
  emit("update:isDetailDialogVisible", false);
  selected_bookings.value = [];
  payment_method.value = "";
  payment_method_error.value = "";
};

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
const headers = [
  { title: "Mã booking", key: "id", sortable: false },
  { title: "Chỗ nghỉ", key: "property_id", sortable: false },
  { title: "Tên khách", key: "full_name", sortable: false },
  { title: "Loại phòng", key: "room", sortable: false },
  { title: "Ngày nhận", key: "check_in_date", sortable: false },
  { title: "Ngày trả", key: "check_out_date", sortable: false },
  { title: "Giá trị", key: "customer_payment_amount", sortable: false },
  { title: "PTTT", key: "room_payment_method", sortable: false },
  { title: "Đã xử lý", key: "processed", sortable: false },
  { title: "Phải thu", key: "net_estimate", sortable: false },
  { title: "Hoa hồng", key: "total_commission", sortable: false },
  { title: "Thanh toán", key: "payment_method", sortable: false },
  { title: "Trạng thái", key: "payment_status", sortable: false },
];
const paymentOptions = [
  "Tiền mặt",
  "Chuyển khoản",
  "QR Code",
  "9Pay",
  "Momo",
  "VNPay",
  "Thẻ tín dụng",
];
const totalProcessedAmount = computed(() => {
  return selected_bookings.value.reduce((total, booking) => {
    return (
      total +
      (booking.income_expenses || [])
        .filter((ie) => ie.type === "income")
        .reduce((sum, ie) => sum + Number(ie.amount || 0), 0)
    );
  }, 0);
});

const totalNetEstimate = computed(() => {
  return selected_bookings.value.reduce((sum, booking) => {
    return booking.payment_type === "Partner Collect"
      ? sum + Number(booking.customer_payment_amount || 0)
      : sum;
  }, 0);
});

const totalPayoutReceived = computed(() => {
  return selected_bookings.value.reduce((sum, booking) => {
    return sum + Number(booking.commission_fee || 0);
  }, 0);
});

const totalNetAmount = computed(() => {
  return (
    totalNetEstimate.value -
    totalPayoutReceived.value -
    totalProcessedAmount.value
  );
});

watch(bookings, () => {
  selectAllBooking.value = false;
  selected_bookings.value = [];
});
const payment_method_error = ref("");
const submitIncomeExpense = (type) => {
  const amount =
    type === "income" ? totalNetAmount.value : Math.abs(totalNetAmount.value);
  if (
    (type === "income" && amount <= 0) ||
    (type === "expense" && amount <= 0) ||
    !payment_method.value
  ) {
    payment_method_error.value = "Phải chọn hình thức thanh toán";
    return;
  }

  router.post(
    route("debtPayment.storeIncomeExpenses"),
    {
      type,
      partner_id: props.data.id,
      booking_ids: selected_bookings.value.map((b) => b.id),
      amount,
      payment_method: payment_method.value,
      note: note.value,
    },
    {
      onSuccess: () => {
        onReset();
      },
    }
  );
};

const netEstimateAmount = computed(() => {
  return (item) => {
    if (item.payment_type === "Partner Collect") {
      const remaining = Number(item.remaining || 0);
      const commissionFee = Number(item.commission_fee || 0);
      return remaining - commissionFee;
    }
    return 0;
  };
});
</script>
<style scoped>
.text-blue {
  color: #4970f5;
}
.bg-cae4ff {
  background-color: #cae4ff;
}
.v-card-text + .v-card-text {
  padding-block-start: unset !important;
}
.bg_warning {
  background-color: #fff3cd !important;
}
.text-warning {
  color: #856404 !important;
}
</style>
