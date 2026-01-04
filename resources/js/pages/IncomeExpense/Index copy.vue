<template>
  <Head title="Quản lý thu chi | Room Rise" />
  <Layout>
    <VCard>
      <VCardItem>
        <VRow class="d-flex justify-space-between align-center">
          <VCol cols="12" sm="12" md="6" lg="6">
            <VCardTitle>
              <h2>Quản lý thu chi</h2>
              <VCardText class="d-flex gap-2 pa-0 pt-4 align-center">
                <div class="border-right pr-2">
                  <VIcon
                    icon="tabler-circle"
                    color="success"
                    class="mr-2"
                  ></VIcon
                  >Phiếu thu tự sinh
                </div>
                <div class="border-right pr-2">
                  <VIcon
                    icon="tabler-circle"
                    color="error"
                    class="mr-2"
                  ></VIcon>
                  Phiếu chi tạo tay
                </div>
                <div>
                  <VIcon
                    icon="tabler-chart-bar-popular"
                    color="info"
                    class="mr-2"
                  ></VIcon>
                  Báo cáo tổng hợp
                </div>
              </VCardText>
            </VCardTitle>
          </VCol>
          <VCol cols="12" sm="12" md="6" lg="6" class="d-flex justify-lg-end">
            <div class="d-flex gap-2 flex-wrap">
              <VBtn
                :variant="filter ? 'flat' : 'outlined'"
                color="secondary"
                @click="toggleFilter"
              >
                <VIcon icon="tabler-filter"></VIcon>
                Lọc
              </VBtn>
              <VBtn variant="outlined" color="secondary" @click="handleExport">
                Xuất Excel
              </VBtn>
              <VBtn @click="addNewItem">
                <VIcon icon="tabler-plus"></VIcon>
                Tạo phiếu chi
              </VBtn>
            </div>
          </VCol>
        </VRow>
      </VCardItem>
      <VCardItem>
        <VRow>
          <VCol cols="6" sm="6" md="6" lg="3">
            <div class="item bg_success">
              <div class="text-h5 text-success">Tổng thu</div>
              <strong class="text-h5 text-success">{{
                formatCurrency(totalIncome)
              }}</strong>
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="6" lg="3">
            <div class="item bg_error">
              <div class="text-h5 text-error">Tổng chi</div>
              <strong class="text-h5 text-error">{{
                formatCurrency(totalExpense)
              }}</strong>
            </div>
          </VCol>

          <VCol cols="6" sm="6" md="6" lg="3">
            <div class="item bg_warning">
              <div class="text-h5 text-orange">Ròng</div>
              <strong class="text-h5 text-orange">{{
                formatCurrency(netAmount)
              }}</strong>
            </div>
          </VCol>
          <VCol cols="6" sm="6" md="6" lg="3">
            <div class="item bg_purple">
              <div class="text-h5 text-purple">Tổng phiếu</div>
              <strong class="text-h5 text-purple"> {{ count }} </strong>
            </div>
          </VCol>
        </VRow>
      </VCardItem>
      <VCardItem>
        <VBtn
          @click="selectedType = 'Tất cả'"
          class="ms-2"
          :variant="selectedType === 'Tất cả' ? 'flat' : 'outlined'"
          color="info"
        >
          <VIcon class="mr-2" icon="tabler-clipboard-list"></VIcon>
          Tất cả
        </VBtn>
        <VBtn
          @click="selectedType = 'income'"
          class="ms-2"
          :variant="selectedType === 'income' ? 'flat' : 'outlined'"
          color="success"
        >
          <VIcon class="mr-2" icon="tabler-circle"></VIcon>
          Thu
        </VBtn>
        <VBtn
          class="ms-2"
          :variant="selectedType === 'expense' ? 'flat' : 'outlined'"
          color="error"
          @click="selectedType = 'expense'"
        >
          <VIcon class="mr-2" icon="tabler-circle"></VIcon>
          Chi
        </VBtn>
        <VBtn
          v-if="filter"
          class="ms-2"
          variant="text"
          color="secondary"
          @click="resetFilter"
        >
          <VIcon icon="tabler-x"></VIcon>
          Xóa bộ lọc
        </VBtn>
      </VCardItem>
      <VCardItem v-if="filter">
        <VCardTitle>Bộ lọc chi tiết</VCardTitle>
        <VRow>
          <VCol cols="6" sm="6" md="3" lg="3">
            <AppDateTimePicker
              v-model="range_date"
              label="Ngày"
              placeholder="Ngày"
              :config="{ mode: 'range' }"
            />
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="3">
            <AppSelect
              v-model="selectedCategory"
              :items="categoriesOptions"
              label="Danh mục"
            />
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="3">
            <AppSelect
              v-model="selectedPayment"
              :items="paymentOptions"
              label="Hình thức thanh toán"
            />
          </VCol>
          <VCol cols="6" sm="6" md="3" lg="3">
            <AppTextField
              v-model="selectedCreatedBy"
              type="text"
              label="Tạo bởi"
            />
          </VCol>
        </VRow>
      </VCardItem>

      <VCardItem>
        <div class="d-flex gap-2 align-center">
          <p class="text-body-1 mb-0">Hiển thị</p>
          <AppSelect
            :model-value="itemsPerPage"
            :items="[
              { value: 5, title: '5' },
              { value: 25, title: '25' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
              { value: -1, title: 'Tất cả' },
            ]"
            style="inline-size: 7.5rem"
            @update:model-value="itemsPerPage = parseInt($event, 10)"
          />
        </div>
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items-length="data.total"
          :items-per-page-options="[
            { value: 5, title: '5' },
            { value: 10, title: '10' },
            { value: -1, title: '$vuetify.dataFooter.itemsPerPageAll' },
          ]"
          :headers="headers"
          :items="data.data"
          item-value="id"
          class="text-no-wrap"
          @update:options="updateOptions"
          show-select
          v-model="selectedIds"
        >
          <template #item.type="{ item }">
            <VChip
              v-if="item.type == 'income'"
              color="success"
              variant="elevated"
            >
              Thu (Auto)
            </VChip>
            <VChip
              v-if="item.type == 'expense'"
              color="error"
              variant="elevated"
            >
              Chi (Tay)
            </VChip>
          </template>
          <template #item.category="{ item }">
            <div class="text-high-emphasis text-body-1 pa-3">
              {{ item.category }}
              <br />
              <p class="opacity-60">{{ item.subcategory }}</p>
            </div>
          </template>
          <template #item.amount="{ item }">
            <div
              v-if="item.type == 'income'"
              class="text-high-emphasis text-body-1 pa-3 text-success"
            >
              + {{ formatCurrency(item.amount) }}
            </div>
            <div
              v-if="item.type == 'expense'"
              class="text-high-emphasis text-body-1 pa-3 text-error"
            >
              - {{ formatCurrency(item.amount) }}
            </div>
          </template>
          <template #item.payment_status="{ item }">
            <VChip
              color="success"
              v-if="item.payment_status == 'Đã thanh toán'"
              >{{ item.payment_status }}</VChip
            >
            <VChip
              color="warning"
              v-if="item.payment_status == 'Chờ thanh toán'"
              >{{ item.payment_status }}</VChip
            >
          </template>

          <template #item.booking_id="{ item }">
            <template v-if="item.partner_bookings?.length">
              <span
                class="border rounded-lg pa-1"
                v-for="(booking, index) in item.partner_bookings.slice(0, 2)"
                :key="booking.id"
              >
                {{ booking.id }}
                <span
                  v-if="index === 0 && item.partner_bookings.length > 1"
                ></span>
              </span>
              <span
                class="border rounded-lg pa-1"
                v-if="item.partner_bookings.length > 2"
              >
                +{{ item.partner_bookings.length - 2 }}
              </span>
            </template>
            <template v-if="item.settlement">
              <span
                class="border rounded-lg pa-1"
                v-for="(
                  booking, index
                ) in item.settlement.settlement_bookings.slice(0, 2)"
                :key="booking.id"
              >
                {{ booking.booking_id }}
                <span
                  v-if="
                    index === 0 &&
                    item.settlement.settlement_bookings.length > 1
                  "
                ></span>
              </span>
              <span
                class="border rounded-lg pa-1"
                v-if="item.settlement.settlement_bookings.length > 2"
              >
                +{{ item.settlement.settlement_bookings.length - 2 }}
              </span>
            </template>
            <template v-else>
              <div
                class="border text-center rounded-lg pa-1"
                v-if="item.booking_id"
              >
                {{ item.booking_id }}
              </div>
            </template>
          </template>

          <template #item.business_type="{ item }">
            <VChip
              v-if="item.business_type"
              :color="getBusinessTypeColor(item.business_type)"
              size="small"
              class="text-white"
            >
              {{ item.business_type }}
            </VChip>
          </template>

          <template #item.room_payment_method="{ item }">
            <v-tooltip location="top">
              <template #activator="{ props }">
                <span v-bind="props">{{ item.room_payment_method }}</span>
              </template>

              <div class="text-sm space-y-1">
                <template
                  v-if="item.partner_bookings && item.partner_bookings.length"
                >
                  <div>Chi tiết PTTT:</div>
                  <div
                    v-for="booking in item.partner_bookings"
                    :key="'partner-' + booking.id"
                  >
                    {{ booking.id }} :
                    {{ booking.payment_type }}
                  </div>
                </template>
                <template v-else-if="item.booking">
                  <div>Chi tiết PTTT:</div>
                  <div>
                    {{ item.booking.id }} :
                    {{ item.booking.payment_type }}
                  </div>
                </template>
                <template v-else>
                  <div class="text-gray-500 italic">Không có booking</div>
                </template>
              </div>
            </v-tooltip>
          </template>

          <template #item.actions="{ item }">
            <VBtn
              icon
              size="small"
              color="medium-emphasis"
              variant="text"
              @click="showDetail(item)"
            >
              <VIcon icon="tabler-eye" />
            </VBtn>
            <template v-if="item.type === 'expense'">
              <VBtn
                icon
                size="small"
                color="medium-emphasis"
                variant="text"
                @click="editDetail(item)"
              >
                <VIcon icon="tabler-edit" />
              </VBtn>

              <VBtn
                icon
                size="small"
                color="medium-emphasis"
                variant="text"
                @click="deleteItem(item)"
              >
                <VIcon icon="tabler-trash" />
              </VBtn>
            </template>

            <template v-else>
              <VBtn icon size="small" color="grey" variant="text" disabled>
                <VIcon icon="tabler-lock" />
              </VBtn>
              <VBtn icon size="small" color="grey" variant="text" disabled>
                <VIcon icon="tabler-lock" />
              </VBtn>
            </template>
          </template>

          <template #bottom>
            <TablePagination
              v-model:page="page"
              :items-per-page="itemsPerPage"
              :total-items="data.total"
            />
          </template>
        </VDataTableServer>
      </VCardItem>
    </VCard>
    <IncomeExpenseFormDialog
      v-model:is-dialog-visible="isFormDialogVisible"
      :partner-group="partnerGroup"
      @update-data="handleUpdateData"
    />
    <Detail
      v-model:is-detail-dialog-visible="isDetailDialogVisible"
      :data="selectedData"
      @update-data="handleUpdateData"
    />
    <IncomeExpenseFormDialog
      v-model:is-dialog-visible="isFormEditDialogVisible"
      :data="dataExpense"
      @update-data="handleUpdateData"
    />
    <AppConfirmDialog @on-submit="handleDelete" />
  </Layout>
</template>

<script setup>
import IncomeExpenseFormDialog from "@/Components/incomeexpense/IncomeExpenseFormDialog.vue";
import Detail from "./Detail.vue";
import Layout from "@/layouts/blank.vue";
import { Head, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { ref, watch, onMounted } from "vue";
import BarChart from "./BarChart.vue";
import PieChart from "./PieChart.vue";
import {
  isOpenAppConfirmDialog,
  loadingAppConfirmDialog,
} from "@/composables/dialog";
import AppConfirmDialog from "@/Components/shared/AppConfirmDialog.vue";
const props = defineProps({
  data: Object,
  filters: Object,
  totalIncome: Number,
  totalExpense: String,
  netAmount: Number,
  count: Number,
  partnerGroup: Object,
});
const isFormDialogVisible = ref(false);
const isFormEditDialogVisible = ref(false);
const isDetailDialogVisible = ref(false);
const range_date = ref("");
const selectedData = ref();
const currentTab = ref("0");
const currentSubTab = ref("0");
const filter = ref(false);
const pieChartRef = ref(null);
const barChartRef = ref(null);
const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);
const selectedType = ref("Tất cả");
const selectedIds = ref([]);

const selectedCategory = ref("Tất cả danh mục");
const selectedPayment = ref("Tất cả hình thức");
const selectedCreatedBy = ref("");

const headers = [
  { title: "Mã", key: "id", sortable: false },
  { title: "Ngày", key: "date", sortable: false },
  { title: "Loại", key: "type", sortable: false },
  { title: "Danh mục", key: "category", sortable: false },
  { title: "PTTT Room", key: "room_payment_method", sortable: false },
  { title: "Hình thức", key: "payment_method", sortable: false },
  { title: "Nguồn thanh toán", key: "payment_source", sortable: false },
  { title: "Đối tượng", key: "payment_object", sortable: false },
  { title: "Booking", key: "booking_id", sortable: false },
  { title: "Nghiệp vụ", key: "business_type", sortable: false },
  { title: "Số tiền", key: "amount", sortable: false },
  { title: "TT", key: "payment_status", sortable: false },
  { title: "Tạo bởi", key: "created_by", sortable: false },
  { title: "Thao tác", key: "actions", sortable: false },
];

const categoriesOptions = [
  "Tất cả danh mục",
  "Doanh thu",
  "Thu nhập khác",
  "Chi phí vận hành",
  "Chi phí nhân sự",
  "Hoa hồng & Marketing",
  "Chi phí cố định",
];
const paymentOptions = [
  "Tất cả hình thức",
  "Tiền mặt",
  "Chuyển khoản",
  "QR Code",
  "9Pay",
  "Momo",
  "VNPay",
  "Thẻ tín dụng",
];
const chartJsCustomColors = {
  white: "#fff",
  yellow: "#ffe802",
  primary: "#836af9",
  areaChartBlue: "#2c9aff",
  barChartYellow: "#ffcf5c",
  polarChartGrey: "#4f5d70",
  polarChartInfo: "#299aff",
  lineChartYellow: "#d4e157",
  polarChartGreen: "#28dac6",
  lineChartPrimary: "#9e69fd",
  lineChartWarning: "#ff9800",
  horizontalBarInfo: "#26c6da",
  polarChartWarning: "#ff8131",
  scatterChartGreen: "#28c76f",
  warningShade: "#ffbd1f",
  areaChartBlueLight: "#84d0ff",
  areaChartGreyLight: "#edf1f4",
  scatterChartWarning: "#ff9f43",
};

const fetchData = () => {
  router.get(
    route("income-and-expense.index"),
    {
      page: page.value,

      range_date: range_date.value,
      category:
        selectedCategory.value !== "Tất cả danh mục"
          ? selectedCategory.value
          : null,
      payment:
        selectedPayment.value !== "Tất cả hình thức"
          ? selectedPayment.value
          : null,
      staff: selectedCreatedBy.value || null,
      type: selectedType.value !== "Tất cả" ? selectedType.value : null,
      paginate: itemsPerPage.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};
const handleUpdateData = () => {
  pieChartRef.value?.updateData?.();
  barChartRef.value?.updateData?.();
  fetchData();
};
onMounted(fetchData);
watch(selectedType, debounce(fetchData, 300));

watch(range_date, debounce(fetchData, 300));
watch(selectedCategory, debounce(fetchData, 300));
watch(selectedPayment, debounce(fetchData, 300));
watch(selectedCreatedBy, debounce(fetchData, 300));
watch([page, itemsPerPage], fetchData);

const handleExport = () => {
  const exportUrl = route("income-and-expense.export", {
    selectedType: selectedType.value !== "Tất cả" ? selectedType.value : null,
    range_date: range_date.value,
    selectedCategory:
      selectedCategory.value === "Tất cả danh mục"
        ? null
        : selectedCategory.value,
    selectedPayment:
      selectedPayment.value === "Tất cả hình thức"
        ? null
        : selectedPayment.value,
    selectedCreatedBy: selectedCreatedBy.value,
    selectedIds: selectedIds.value.length ? selectedIds.value : null,
  });

  window.location.href = exportUrl;
};

const updateOptions = (options) => {
  page.value = options.page;
  itemsPerPage.value = options.itemsPerPage;
};
const addNewItem = () => {
  isFormDialogVisible.value = true;
};
const showDetail = (item) => {
  selectedData.value = item;
  isDetailDialogVisible.value = true;
};
const dataExpense = ref();
const editDetail = (item) => {
  isFormEditDialogVisible.value = true;
  dataExpense.value = item;
};
const deleteItem = async (item) => {
  selectedData.value = item;
  isOpenAppConfirmDialog.value = true;
};
const handleDelete = () => {
  router.delete(
    route("income-and-expense.destroy", { id: selectedData.value.id }),
    {
      onStart: () => {
        loadingAppConfirmDialog.value = true;
      },
      onFinish: () => {
        loadingAppConfirmDialog.value = false;
        isOpenAppConfirmDialog.value = false;
      },
    }
  );
};
const resetFilter = () => {
  selectedType.value = "Tất cả";
  range_date.value = "";
  selectedCategory.value = "Tất cả danh mục";
  selectedPayment.value = "Tất cả hình thức";
  selectedCreatedBy.value = "";
  fetchData();
};
const toggleFilter = () => {
  filter.value = !filter.value;
};
const getBusinessTypeColor = (type) => {
  switch (type) {
    case "Đặt phòng":
      return "info";
    case "Đối soát đối tác":
      return "warning";
    case "Nhập tay":
      return "secondary";
    case "Quyết toán OTA":
      return "primary";
    case "Hủy đặt phòng":
      return "error";
    default:
      return "default";
  }
};
</script>
<style scoped>
.v-slide-group__prev,
.v-slide-group__next {
  display: none;
}
.text-orange {
  color: rgb(255, 68, 0);
}
.text-success {
  color: green;
}
.text-error {
  color: red;
}
.text-purple {
  color: purple;
}
.item {
  padding: 1rem;
  border: 1px solid #9e9e9e;
  border-radius: 0.5rem;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.bg_blue {
  border-radius: 0.5rem;
  background-color: #eff6ff;
}
.bg_success {
  border: none;
  border-radius: 0.5rem;
  background-color: #d1fae5;
}
.bg_error {
  border: none;
  border-radius: 0.5rem;
  background-color: #fee2e2;
}

.bg_warning {
  border: none;
  border-radius: 0.5rem;
  background-color: #fffbeb;
}
.bg_purple {
  border: none;
  border-radius: 0.5rem;
  background-color: #f3e8ff;
}
.pd-3 {
  padding: 3rem;
}
.item-chart {
  border: 1px solid #f2f2f2;
  border-radius: 0.5rem;
  padding: 1rem;
  height: 100%;
}
.border-right {
  border-right: 1px solid #a6a6a6;
}
</style>
