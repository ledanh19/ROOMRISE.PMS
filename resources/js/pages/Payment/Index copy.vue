<template>
  <Head title="Phiếu thu - Hotel Collect | Room Rise" />
  <Layout>
    <VCard>
      <VCardItem>
        <div class="d-flex justify-space-between align-center">
          <VCardTitle>
            <h3>Phiếu thu Hotel Collect</h3>
            <p>Quản lý các khoản thanh toán trực tiếp tại khách sạn</p>
          </VCardTitle>
          <VBtn
            color="secondary"
            variant="outlined"
            @click="isDialogVisible = true"
          >
            Xuất dữ liệu
          </VBtn>
        </div>
      </VCardItem>
      <!-- <VCardItem>
        <VBtn
          :variant="filter ? 'flat' : 'outlined'"
          color="secondary"
          @click="toggleFilter"
        >
          <VIcon icon="tabler-filter"></VIcon>
          Bộ lọc
        </VBtn>
        <VBtn
          class="ms-2"
          variant="text"
          color="secondary"
          @click="resetFilter"
        >
          <VIcon icon="tabler-x"></VIcon>
          Xóa bộ lọc
        </VBtn>
        <VRow class="mt-3">
          <VCol cols="6" sm="6" md="4" lg="4">
            <AppDateTimePicker
              v-model="range_date"
              label="Ngày đặt"
              placeholder="Ngày đặt"
              :config="{ mode: 'range' }"
            />
          </VCol>
          <VCol cols="6" sm="6" md="4" lg="4">
            <AppDateTimePicker
              v-model="s_date"
              label="Ngày check in"
              placeholder="Ngày check in"
              :config="{ mode: 'range' }"
            />
          </VCol>
          <VCol cols="6" sm="6" md="4" lg="4">
            <AppDateTimePicker
              v-model="e_date"
              label="Ngày check out"
              placeholder="Ngày check out"
              :config="{ mode: 'range' }"
            />
          </VCol>
        </VRow>
      </VCardItem>
      <VCardItem>
        <VRow>
          <VCol cols="6" sm="6" md="3" lg="3">
            <div class="item">
              <div class="content">
                <p>Tổng booking</p>
                <h2 class>{{ props.totalBookings }}</h2>
              </div>
              <VIcon color="blue" icon="tabler-receipt-dollar"></VIcon>
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="3">
            <div class="item">
              <div class="content">
                <p>Tổng doanh thu</p>
                <h2 class="text-blue">
                  {{ formatCurrency(props.totalRevenue) }}
                </h2>
              </div>
              <VIcon color="blue" icon="tabler-currency-dollar"></VIcon>
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="3">
            <div class="item">
              <div class="content">
                <p>Đã thu</p>
                <h2 class="text-success">
                  {{ formatCurrency(props.totalPaid) }}
                </h2>
              </div>
              <VIcon color="success" icon="tabler-currency-dollar"></VIcon>
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="3">
            <div class="item">
              <div class="content">
                <p>Còn nợ</p>
                <h2 class="text-error">
                  {{ formatCurrency(props.totalRemaining) }}
                </h2>
              </div>
              <VIcon color="error" icon="tabler-currency-dollar"></VIcon>
            </div>
          </VCol>
        </VRow>
      </VCardItem> -->
    </VCard>
    <VCard class="mt-5">
      <VCardItem>
        <div class="d-flex align-center justify-space-between">
          <div class="text-h6">Bộ lọc</div>
          <VBtn variant="text" color="error" @click="resetFilter">
            Đặt lại
          </VBtn>
        </div>

        <VRow>
          <VCol cols="12" sm="12" md="3" lg="3">
            <AppSelect
              item-value="id"
              item-title="title"
              label="Chọn"
              v-model="date_type"
              :items="typeOptions"
              chips
              closable-chips
            />
          </VCol>

          <VCol cols="12" sm="12" md="3" lg="3">
            <AppDateTimePicker
              v-model="range_date"
              placeholder="Chọn ngày"
              label="Chọn ngày"
              :config="{ mode: 'range' }"
            />
          </VCol>
          <VCol cols="12" sm="12" md="3" lg="3">
            <AppSelect
              v-model="ota_name"
              :items="otaOptions"
              label="Nguồn"
              chips
              closable-chips
            />
          </VCol>
          <VCol cols="12" sm="12" md="3" lg="3">
            <AppSelect
              label="Phương thức"
              v-model="payment_method"
              :items="paymentOptions"
              chips
              closable-chips
            />
          </VCol>
        </VRow>
      </VCardItem>
    </VCard>
    <VCard class="mt-5">
      <VCardText
        class="d-flex align-center justify-space-between flex-wrap gap-4"
      >
        <div class="d-flex gap-2 align-center">
          <p class="text-body-1 mb-0">Show</p>
          <AppSelect
            :model-value="itemsPerPage"
            :items="PAGINATION_OPTIONS"
            style="inline-size: 5.5rem"
            @update:model-value="itemsPerPage = parseInt($event, 10)"
          />
        </div>

        <div class="d-flex align-center justify-end gap-2">
          <AppTextField
            v-model="search"
            placeholder="Tìm kiếm đặt phòng"
            style="inline-size: 15.625rem"
          />

          <VBtn color="primary" @click="showColumnDialog = true">
            Tùy chỉnh cột
          </VBtn>
          <VBtn
            color="secondary"
            variant="outlined"
            @click="exportSelected"
            :loading="exportingSelected"
            v-if="selected_bookings.length > 0"
          >
            Xuất dữ liệu
          </VBtn>
        </div>
      </VCardText>
      <VCardText
        v-if="selected_bookings.length > 0"
        class="d-flex align-center justify-space-between gap-3"
      >
        <div>{{ selected_bookings.length }} bản ghi đã chọn</div>
        <div class="d-flex gap-2">
          <VBtn
            variant="text"
            color="info"
            @click="selectAllBookings"
            :loading="isSelectingAll"
            :disabled="hasSelectedAll || bookingIds.length === 0"
          >
            Chọn tất cả ({{ bookingIds.length }})
          </VBtn>

          <VBtn variant="text" color="error" @click="clearAllSelection">
            Bỏ chọn tất cả
          </VBtn>
        </div>
      </VCardText>
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items-length="data.total"
        :headers="visibleHeaders"
        :items="data.data"
        :search="search"
        item-value="id"
        class="text-no-wrap"
        @update:options="updateOptions"
        show-select
        v-model="selected_bookings"
      >
        <template #item.rooms="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            <div
              v-for="(roomItem, index) in item.rooms"
              :key="index"
              class="mb-1"
            >
              {{ roomItem.room_unit?.name }}
            </div>
          </div>
        </template>
        <template #item.income_expense_id="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            {{
              [
                ...(item.partner_income_expenses ?? []),
                ...(item.income_expenses ?? []),
              ]
                .map((i) => i.id)
                .join(", ") || "-"
            }}
          </div>
        </template>

        <template #item.customer_id="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            {{ item.customer_name }}
          </div>
        </template>
        <template #item.check_in_date="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            {{ formatDate(item?.check_in_date) }}
          </div>
        </template>
        <template #item.check_out_date="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            {{ formatDate(item?.check_out_date) }}
          </div>
        </template>

        <template #item.total_amount="{ item }">
          <div class="text-high-emphasis text-body-1 pa-3">
            {{ formatCurrency(item.total_amount) }}
          </div>
        </template>

        <template #item.actions="{ item }">
          <Link
            class="text-high-emphasis"
            :href="route('payment.detailById', item.id)"
          >
            <VIcon size="22" color="secondary" icon="tabler-eye" class="mr-2" />
            Xem
          </Link>
        </template>
        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="data.total"
          />
        </template>
        <template #body.append>
          <tr>
            <td
              v-for="header in visibleHeaders"
              :key="header.key"
              class="pa-4"
              :class="
                header.key === 'booking_code'
                  ? 'text-left font-weight-bold'
                  : ''
              "
            >
              <template v-if="header.key === 'booking_code'">
                Tóm tắt
              </template>

              <template v-else-if="header.key === 'total_amount'">
                <div class="text-h6">Số tiền</div>
                <div>{{ formatCurrency(totalRevenue) }}</div>
              </template>

              <template v-else> </template>
            </td>
          </tr>
        </template>
      </VDataTableServer>
    </VCard>
    <PaymentHistories
      v-model:is-dialog-visible="isFormDialogVisible"
      :history-TargetId="historyTargetId"
    />
    <PayDialog
      v-model:is-pay-dialog-visible="isFormPayDialogVisible"
      :payment-TargetId="paymentTargetId"
      @update:payment="loadData"
    />
    <ColumnSettings
      v-model="showColumnDialog"
      :headers="defaultHeaders"
      @update:headers="visibleHeaders = $event"
    />
  </Layout>
  <VDialog v-model="isDialogVisible" width="500">
    <DialogCloseBtn @click="isDialogVisible = false" />

    <VCard title="Xác nhận xuất dữ liệu">
      <VCardText>
        Xuất dữ liệu sẽ mất khoảng 10 giây để hoàn tất. Sau khi hoàn tất, bạn sẽ
        nhận được thông báo để tải file. Vui lòng nhấp vào "Xác nhận" để bắt
        đầu!
      </VCardText>
      <VCardText class="d-flex flex-wrap gap-3">
        <VBtn
          @click="confirmSelectedExport"
          v-if="selected_bookings.length > 0"
        >
          Xác nhận
        </VBtn>
        <VBtn @click="confirmExport" v-else> Xác nhận</VBtn>

        <VBtn
          variant="tonal"
          color="secondary"
          @click="isDialogVisible = false"
        >
          Hủy bỏ
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import Layout from "@/layouts/blank.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { Head, Link, router } from "@inertiajs/vue3";
import dayjs from "dayjs";
import { debounce } from "lodash";
import { onMounted, ref, watch, computed } from "vue";
import PayDialog from "./PayDialog.vue";
import PaymentHistories from "./PaymentHistories.vue";
import { PAGINATION_OPTIONS } from "@/utils/constants";
import ColumnSettings from "./ColumnSettings.vue";

const propertyStore = usePropertyStore();
const props = defineProps({
  data: Object,
  filters: Object,
  totalBookings: Number,
  totalRevenue: String,
  totalPaid: String,
  totalRemaining: Number,
  propertyOptions: Array,
  bookingIds: Array,
});
console.log(props.bookingIds);
const search = ref("");
const filter = ref(false);
const isFormDialogVisible = ref(false);
const isFormPayDialogVisible = ref(false);
const selectedData = ref();
const range_date = ref("");
const showColumnDialog = ref(false);
const date_type = ref(null);
const payment_method = ref(null);
const isDialogVisible = ref(false);
const selected_bookings = ref([]);
const isSelectedExportDialog = ref(false);
const exportingSelected = ref(false);
const property_id = ref(
  propertyStore.selectedProperty || Number(props.filters?.property_id) || null
);
const payment_status = ref("Tất cả");
const status = ref("Tất cả trạng thái");
const paymentTargetId = ref();
const historyTargetId = ref();
const ota_name = ref(null);
const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);

const typeOptions = [
  { id: "check_in_date", title: "Ngày nhận phòng" },
  { id: "check_out_date", title: "Ngày trả phòng" },
  { id: "created_at", title: "Ngày đặt phòng" },
  { id: "date", title: "Ngày thanh toán" },
];
const otaOptions = ref([
  "Walk-in",
  "Từ đối tác",
  "BookingCom",
  "Agoda",
  "Airbnb",
  "Expedia",
  "Traveloka",
]);
const statusOptions = [
  "Tất cả trạng thái",
  "Hủy",
  "Mới",
  "Xác nhận",
  "Yêu cầu",
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
const fetchData = () => {
  router.get(
    route("payment.index"),
    {
      page: page.value,
      range_date: range_date.value,
      ota_name: ota_name.value,
      payment_method: payment_method.value,
      search: search.value,
      property_id: property_id.value !== null ? property_id.value : null,
      paginate: itemsPerPage.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};
watch(property_id, (val) => {
  propertyStore.setProperty(val);
  fetchData();
});

watch(
  () => propertyStore.selectedProperty,
  (val) => {
    property_id.value = val;
    fetchData();
  }
);
onMounted(() => {
  if (property_id.value) {
    fetchData();
  }
});
const updateOptions = (options) => {
  page.value = options.page;
  itemsPerPage.value = options.itemsPerPage;
};
const toggleFilter = () => {
  filter.value = !filter.value;
};
const showHistory = (id) => {
  isFormDialogVisible.value = true;
  historyTargetId.value = id;
};
const addNewPay = (id) => {
  isFormPayDialogVisible.value = true;
  paymentTargetId.value = id;
};
const resetFilter = () => {
  range_date.value = "";
  date_type.value = null;
  ota_name.value = null;
  payment_method.value = null;
  fetchData();
};
const loadData = () => {
  fetchData();
};
watch([page, itemsPerPage], fetchData);
watch(range_date, debounce(fetchData, 300));
watch(ota_name, debounce(fetchData, 300));
watch(payment_method, debounce(fetchData, 300));
watch(search, debounce(fetchData, 300));

function getPaymentColor(status) {
  switch (status) {
    case "Đã cọc":
      return "warning";
    case "Đã thanh toán":
      return "success";
    case "Chưa thanh toán":
      return "error";
    default:
      return "grey";
  }
}
const defaultHeaders = [
  { title: "Mã đặt phòng", key: "booking_code", sortable: false },
  { title: "Mã TT", key: "income_expense_id", sortable: false },
  { title: "Nguồn", key: "ota_name", sortable: false },
  { title: "Khách hàng", key: "customer_id", sortable: false },
  { title: "Số tiền", key: "total_amount", sortable: false },
  { title: "Phòng", key: "rooms", sortable: false },
  { title: "Phương thức", key: "payment_method", sortable: false },
  { title: "Nhận phòng", key: "check_in_date", sortable: false },
  { title: "Trả phòng", key: "check_out_date", sortable: false },
  { title: "Nội dung", key: "note", sortable: false },
  { title: "", key: "actions", sortable: false },
];

const visibleHeaders = ref([...defaultHeaders]);

const confirmExport = () => {
  const exportUrl = route("payment.exports", {
    range_date: range_date.value,
    date_type: date_type.value,
    ota_name: ota_name.value,
    payment_method: payment_method.value,
    property_id: property_id.value,
  });
  isDialogVisible.value = false;
  window.location.href = exportUrl;
};

const isSelectingAll = ref(false);
const hasSelectedAll = ref(false);

const selectAllBookings = () => {
  isSelectingAll.value = true;

  selected_bookings.value = [...props.bookingIds];
  hasSelectedAll.value = true;

  isSelectingAll.value = false;
};

const clearAllSelection = () => {
  selected_bookings.value = [];
  hasSelectedAll.value = false;
};

watch(
  [range_date, ota_name, payment_method, search, () => props.bookingIds],
  () => {
    selected_bookings.value = [];
    hasSelectedAll.value = false;
  }
);

const exportSelected = () => {
  if (selected_bookings.value.length === 0) {
    return;
  }
  isDialogVisible.value = true;
};

const confirmSelectedExport = async () => {
  if (selected_bookings.value.length === 0) return;

  exportingSelected.value = true;

  try {
    const exportUrl = route("payment.exports.selected", {
      selected_bookings: selected_bookings.value,
    });

    selected_bookings.value = [];
    hasSelectedAll.value = false;
    isDialogVisible.value = false;
    window.location.href = exportUrl;
  } catch (error) {
    console.error("Export failed:", error);
  } finally {
    exportingSelected.value = false;
  }
};
</script>
<style scoped>
.text-blue {
  color: blue;
}
.text-success {
  color: green;
}
.text-error {
  color: red;
}
.item {
  padding: 1rem;
  border: 1px solid #9e9e9e;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.v-icon {
  font-size: 3rem;
}
</style>
