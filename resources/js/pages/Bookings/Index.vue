<template>
  <Head title="Danh sách bookings | Room Rise" />
  <Layout>
    <!-- Header Section -->
    <VCard class="mb-4" elevation="2">
      <VCardItem>
        <div class="d-flex align-center justify-space-between flex-wrap gap-4">
          <div>
            <h1 class="text-h4 font-weight-bold text-primary mb-1">
              Đặt phòng
            </h1>
            <p class="text-body-2 text-medium-emphasis">
              Quản lý tất cả đặt phòng của bạn
            </p>
          </div>
          <VBtn
            color="primary"
            variant="elevated"
            size="large"
            @click="isDialogVisible = true"
          >
            <VIcon icon="tabler-file-download" class="mr-2"></VIcon>
            Xuất dữ liệu
          </VBtn>
        </div>
      </VCardItem>
    </VCard>

    <!-- Filter Section -->
    <VCard class="mb-4" elevation="2">
      <VCardItem>
        <div class="d-flex justify-space-between align-center mb-4">
          <div>
            <h3 class="text-h6 font-weight-bold text-primary mb-1">
              <VIcon icon="tabler-filter" class="mr-2"></VIcon>
              Bộ lọc
            </h3>
            <p class="text-body-2 text-medium-emphasis">
              Tìm kiếm và lọc đặt phòng
            </p>
          </div>
          <VBtn variant="outlined" color="error" @click="resetFilter">
            <VIcon icon="tabler-refresh" class="mr-2"></VIcon>
            Đặt lại
          </VBtn>
        </div>
        <VRow>
          <VCol cols="12" sm="6" md="3" lg="3">
            <AppSelect
              item-value="id"
              item-title="title"
              label="Chọn"
              v-model="date_type"
              :items="typeOptions"
            />
          </VCol>
          <VCol cols="12" sm="6" md="3" lg="3">
            <AppDateTimePicker
              v-model="range_date"
              placeholder="Chọn ngày"
              label="Chọn ngày"
              :config="{ mode: 'range' }"
            />
          </VCol>
          <VCol cols="12" sm="6" md="3" lg="3">
            <AppSelect
              label="Loại phòng"
              placeholder="Loại phòng"
              v-model="room_type"
              :items="roomTypeOptions"
              item-value="id"
              item-title="name"
            />
          </VCol>
          <VCol cols="12" sm="6" md="3" lg="3">
            <AppSelect
              label="Trạng thái"
              v-model="status"
              :items="statusOptions"
            />
          </VCol>
          <VCol cols="12" sm="6" md="3" lg="3">
            <AppSelect label="Nguồn" v-model="ota_name" :items="otaChannels" />
          </VCol>
          <VCol cols="12" sm="6" md="3" lg="3">
            <AppSelect
              label="Phân loại"
              v-model="payment_type"
              :items="paymentTypeOptions"
            />
          </VCol>
          <VCol cols="12" sm="6" md="3" lg="3">
            <AppSelect
              label="TT thanh toán"
              v-model="payment_status"
              :items="paymentStatusOptions"
            />
          </VCol>
        </VRow>
      </VCardItem>
    </VCard>

    <!-- Data Table Card -->
    <VCard class="data-table-card" elevation="2">
      <VCardItem class="table-header">
        <div class="d-flex align-center justify-space-between flex-wrap gap-4">
          <div class="d-flex gap-2 align-center">
            <p class="text-body-1 mb-0 font-weight-medium">Hiển thị</p>
            <AppSelect
              :model-value="itemsPerPage"
              :items="PAGINATION_OPTIONS"
              style="inline-size: 5.5rem"
              @update:model-value="itemsPerPage = parseInt($event, 10)"
              class="custom-input"
            />
            <span class="text-body-2 text-medium-emphasis ml-2">mục</span>
          </div>

          <div class="d-flex align-center gap-4 flex-wrap">
            <AppTextField
              v-model="search"
              placeholder="Tìm kiếm đặt phòng..."
              style="inline-size: 15.625rem"
              prepend-inner-icon="tabler-search"
              class="custom-input"
            />

            <VBtn
              color="secondary"
              variant="outlined"
              @click="showColumnDialog = true"
              class="action-btn"
            >
              <VIcon icon="tabler-settings" class="mr-2"></VIcon>
              Tùy chỉnh cột
            </VBtn>

            <VBtn
              color="primary"
              variant="elevated"
              size="large"
              prepend-icon="tabler-plus"
              @click="addNewItem"
              class="action-btn primary-btn"
            >
              Thêm đặt phòng
            </VBtn>
          </div>
        </div>
      </VCardItem>

      <VDivider />

      <VCardItem class="table-content">
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items-length="data.total"
          :headers="visibleHeaders"
          :items="data.data"
          item-value="id"
          class="text-no-wrap custom-data-table"
          @update:options="updateOptions"
        >
          <template #item.ota_name="{ item }">
            <div class="d-flex align-center gap-2">
              <img
                v-if="item?.ota_name && otaLogos[item.ota_name.toLowerCase()]"
                :src="otaLogos[item.ota_name.toLowerCase()]"
                alt="OTA Logo"
                class="h-6 logo w-auto"
              />
              <span class="font-weight-medium">{{ item?.ota_name }}</span>
            </div>
          </template>
          <template #item.room_payment_method="{ item }">
            <div class="d-flex align-center gap-2">
              <img
                v-if="item?.ota_name && otaLogos[item.ota_name.toLowerCase()]"
                :src="otaLogos[item.ota_name.toLowerCase()]"
                alt="OTA Logo"
                class="h-6 logo w-auto"
              />
              <span class="font-weight-medium">{{
                item?.room_payment_method
              }}</span>
            </div>
          </template>

          <template #item.property_id="{ item }">
            <span class="font-weight-medium property-name">{{
              item?.property
            }}</span>
          </template>
          <template #item.partner_id="{ item }">
            <span class="font-weight-medium customer-name">{{
              item.partner_name ?? ""
            }}</span>
          </template>
          <template #item.customer_name="{ item }">
            <div>
              <p class="font-weight-medium mb-1 customer-name">
                {{ item.customer_name }}
              </p>
              <div class="guest-info">
                <p
                  v-if="item.adults"
                  class="text-body-2 text-medium-emphasis mb-0"
                >
                  <VIcon icon="tabler-user" size="14" class="mr-1"></VIcon>
                  {{ item.adults }} người lớn
                </p>
                <p
                  v-if="item.children"
                  class="text-body-2 text-medium-emphasis mb-0"
                >
                  <VIcon icon="tabler-baby" size="14" class="mr-1"></VIcon>
                  {{ item.children }} trẻ em
                </p>
                <p
                  v-if="item.newborn"
                  class="text-body-2 text-medium-emphasis mb-0"
                >
                  <VIcon
                    icon="tabler-baby-bottle"
                    size="14"
                    class="mr-1"
                  ></VIcon>
                  {{ item.newborn }} trẻ sơ sinh
                </p>
              </div>
            </div>
          </template>
          <template #item.number_of_night="{ item }">
            <VChip color="info" variant="flat" size="small" class="status-chip">
              <VIcon icon="tabler-moon" size="14" class="mr-1"></VIcon>
              {{
                Math.max(
                  dayjs(item?.check_out_date).diff(
                    dayjs(item?.check_in_date),
                    "day"
                  ),
                  1
                )
              }}
              đêm
            </VChip>
          </template>
          <template #item.rooms="{ item }">
            <div>
              <div
                v-for="(roomItem, index) in item.rooms"
                :key="index"
                class="mb-1 room-info"
              >
                <VIcon
                  icon="tabler-bed"
                  size="14"
                  class="mr-1 text-primary"
                ></VIcon>
                <span class="font-weight-medium">{{
                  roomItem.room?.name
                }}</span>
                <span class="text-body-2 text-medium-emphasis"
                  >({{ roomItem.room_unit?.name }})</span
                >
              </div>
            </div>
          </template>
          <template #item.is_imported="{ item }">
            <template v-if="item.is_imported">
              <VIcon icon="tabler-file-arrow-left" size="24" class="mr-1" />
            </template>
            <template v-else>
              <span style="font-size: 18px; line-height: 1">-</span>
            </template>
          </template>
          <template #item.check_in_date="{ item }">
            <VChip
              color="success"
              variant="flat"
              size="small"
              class="status-chip"
            >
              <VIcon icon="tabler-plane-arrival" size="14" class="mr-1"></VIcon>
              {{ formatDate(item.check_in_date) }}
            </VChip>
          </template>
          <template #item.status="{ item }">
            <VChip
              :color="statusColor(item.status)"
              variant="flat"
              class="text-white status-chip"
              size="small"
            >
              <VIcon
                :icon="getStatusIcon(item.status)"
                size="14"
                class="mr-1"
              ></VIcon>
              {{ item.status }}
            </VChip>
          </template>
          <template #item.check_out_date="{ item }">
            <VChip
              color="warning"
              variant="flat"
              size="small"
              class="status-chip"
            >
              <VIcon
                icon="tabler-plane-departure"
                size="14"
                class="mr-1"
              ></VIcon>
              {{ formatDate(item.check_out_date) }}
            </VChip>
          </template>

          <template #item.payment_status="{ item }">
            <VChip
              color="error"
              variant="flat"
              v-if="item.payment_status == 'Chưa thanh toán'"
              class="status-chip"
            >
              <VIcon
                icon="tabler-credit-card-off"
                size="14"
                class="mr-1"
              ></VIcon>
              {{ item.payment_status }}
            </VChip>
            <VChip
              color="success"
              variant="flat"
              v-if="item.payment_status == 'Đã thanh toán'"
              class="status-chip"
            >
              <VIcon icon="tabler-check" size="14" class="mr-1"></VIcon>
              {{ item.payment_status }}
            </VChip>
            <VChip
              color="warning"
              variant="flat"
              v-if="item.payment_status == 'Đã cọc'"
              class="status-chip"
            >
              <VIcon icon="tabler-credit-card" size="14" class="mr-1"></VIcon>
              {{ item.payment_status }}
            </VChip>
            <VChip
              color="info"
              variant="flat"
              v-if="item.payment_status == 'Chờ thanh toán'"
              class="status-chip"
            >
              <VIcon icon="tabler-clock" size="14" class="mr-1"></VIcon>
              {{ item.payment_status }}
            </VChip>
          </template>

          <template #item.total_amount="{ item }">
            <span class="font-weight-bold amount-highlight">{{
              formatCurrency(item.total_amount)
            }}</span>
          </template>
          <template #item.paid="{ item }">
            <span class="font-weight-bold text-primary">{{
              formatCurrency(item.paid)
            }}</span>
          </template>
          <template #item.remaining="{ item }">
            <span class="font-weight-bold text-warning">{{
              formatCurrency(item.remaining)
            }}</span>
          </template>
          <template #item.actions="{ item }">
            <Link :href="route('bookings.show', item.id)">
              <VBtn
                color="primary"
                variant="text"
                size="small"
                class="action-btn"
              >
                <VIcon size="18" icon="tabler-eye" />
              </VBtn>
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
            <tr class="summary-row">
              <td
                v-for="header in visibleHeaders"
                :key="header.key"
                class="pa-4"
                :class="header.key === 'id' ? 'text-left font-weight-bold' : ''"
              >
                <template v-if="header.key === 'id'">
                  <div class="d-flex align-center">
                    <VIcon
                      icon="tabler-chart-pie"
                      class="mr-2 text-primary"
                    ></VIcon>
                    <span class="font-weight-bold">Tóm tắt</span>
                  </div>
                </template>

                <template v-else-if="header.key === 'total_amount'">
                  <div>
                    <div class="text-h6 text-success font-weight-bold">
                      Tổng số tiền
                    </div>
                    <div class="text-h5 text-success">
                      {{ formatCurrency(totalAmount) }}
                    </div>
                  </div>
                </template>

                <template v-else-if="header.key === 'paid'">
                  <div>
                    <div class="text-h6 text-primary font-weight-bold">
                      Đã thanh toán
                    </div>
                    <div class="text-h5 text-primary">
                      {{ formatCurrency(totalPaid) }}
                    </div>
                  </div>
                </template>

                <template v-else-if="header.key === 'remaining'">
                  <div>
                    <div class="text-h6 text-warning font-weight-bold">
                      Còn lại
                    </div>
                    <div class="text-h5 text-warning">
                      {{ formatCurrency(totalRemaining) }}
                    </div>
                  </div>
                </template>

                <template v-else> </template>
              </td>
            </tr>
          </template>
        </VDataTableServer>
      </VCardItem>
    </VCard>
    <BookingFormDialog
      v-model:is-dialog-visible="isFormDialogVisible"
      :data="selectedData"
    />
    <AppConfirmDialog @on-submit="handleDelete" />
    <VDialog v-model="isDialogVisible" width="500">
      <DialogCloseBtn @click="isDialogVisible = false" />

      <VCard title="Xác nhận xuất dữ liệu">
        <VCardText>
          Xuất dữ liệu sẽ mất khoảng 10 giây để hoàn tất. Sau khi hoàn tất, bạn
          sẽ nhận được thông báo để tải file. Vui lòng nhấp vào "Xác nhận" để
          bắt đầu!
        </VCardText>
        <VCardText class="d-flex justify-end flex-wrap gap-3">
          <VBtn
            variant="tonal"
            color="secondary"
            @click="isDialogVisible = false"
          >
            Hủy bỏ
          </VBtn>
          <VBtn @click="confirmExport"> Xác nhận </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
    <ColumnSettings
      v-model="showColumnDialog"
      :headers="defaultHeaders"
      @update:headers="visibleHeaders = $event"
    />
  </Layout>
</template>

<script setup>
import TablePagination from "@/@core/components/TablePagination.vue";
import BookingFormDialog from "@/Components/bookings/BookingFormDialog.vue";
import AppConfirmDialog from "@/Components/shared/AppConfirmDialog.vue";
import {
  isOpenAppConfirmDialog,
  loadingAppConfirmDialog,
} from "@/composables/dialog";
import Layout from "@/layouts/blank.vue";
import { PAGINATION_OPTIONS } from "@/utils/constants";
import { formatCurrency, formatDate } from "@/utils/formatters";
import { Head, Link, router } from "@inertiajs/vue3";
import dayjs from "dayjs";
import { debounce } from "lodash";
import { onMounted, ref, watch } from "vue";
import ColumnSettings from "./ColumnSettings.vue";

import { usePropertyStore } from "@/stores/usePropertyStore";
import { VIcon } from "vuetify/components";
const propertyStore = usePropertyStore();

const props = defineProps({
  data: Object,
  filters: Object,
  totalAmount: String,
  totalPaid: String,
  totalRemaining: String,
  propertyOptions: Array,
});

const otaLogos = {
  bookingcom: "/images/bookingcom.png",
  ctrip: "/images/ctrip.png",
  expedia: "/images/expedia.png",
  airbnb: "/images/airbnb.png",
  agoda: "/images/agoda.png",
};

const isDialogVisible = ref(false);
const date_type = ref("");
const range_date = ref("");
const status = ref("");
const payment_type = ref("");
const payment_status = ref("");
const room_type = ref("");
const ota_name = ref("");
const roomTypeOptions = ref([]);

const property_id = ref(
  propertyStore.selectedProperty || Number(props.filters?.property_id) || null
);

const typeOptions = [
  { id: "check_in_date", title: "Ngày nhận phòng" },
  { id: "check_out_date", title: "Ngày trả phòng" },
  { id: "created_at", title: "Ngày đặt phòng" },
];

// const paymentTypeOptions = ["OTA Collect", "Hotel Collect"];
const paymentStatusOptions = [
  "Đã thanh toán",
  "Chưa thanh toán",
  "Đã cọc",
  "Chờ thanh toán",
];
const paymentTypeOptions = ["OTA Collect", "Hotel Collect", "Partner Collect"];
const statusOptions = ["Hủy", "Mới", "Xác nhận", "Yêu cầu"];
const otaChannels = [
  "Walk-in",
  "Từ đối tác",
  "BookingCom",
  "Agoda",
  "Expedia",
  "Airbnb",
  "Ctrip",
];

const search = ref(props.filters.search || "");
const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);

const fetchData = () => {
  router.get(
    route("bookings.list"),
    {
      search: search.value,
      page: page.value,
      date_type: date_type.value,
      range_date: range_date.value,
      status: status.value,
      payment_type: payment_type.value,
      payment_status: payment_status.value,
      room_type: room_type.value,
      ota_name: ota_name.value,
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
  loadRoomType();
});

watch(
  () => propertyStore.selectedProperty,
  (val) => {
    property_id.value = val;
    fetchData();
    loadRoomType();
  }
);

const loadRoomType = async () => {
  const res = await axios.get(route("bookings.getRoomType"), {
    params: {
      property_id: property_id.value,
    },
  });
  roomTypeOptions.value = res.data;
};

const resetFilter = () => {
  date_type.value = "";
  range_date.value = "";
  status.value = "";
  payment_type.value = "";
  payment_status.value = "";
  room_type.value = "";
  ota_name.value = "";
};

watch(search, debounce(fetchData, 300));
watch([page, itemsPerPage], fetchData);

watch(date_type, fetchData);
watch(range_date, fetchData);
watch(status, fetchData);
watch(payment_type, fetchData);
watch(payment_status, fetchData);
watch(ota_name, fetchData);
watch(room_type, fetchData);

const updateOptions = (options) => {
  page.value = options.page;
  itemsPerPage.value = options.itemsPerPage;
};

const isFormDialogVisible = ref(false);
const selectedData = ref();

const addNewItem = () => {
  selectedData.value = null;
  isFormDialogVisible.value = true;
};

const editItem = (itemData) => {
  selectedData.value = itemData;
  isFormDialogVisible.value = true;
};

const deleteItem = async (itemData) => {
  selectedData.value = itemData;
  isOpenAppConfirmDialog.value = true;
};

const handleDelete = () => {
  router.delete(
    route("bookings.destroy", { property: selectedData.value.id }),
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

const confirmExport = () => {
  const exportUrl = route("bookings.exports", {
    date_type: date_type.value,
    range_date: range_date.value,
    status: status.value,
    payment_type: payment_type.value,
    payment_status: payment_status.value,
    room_type: room_type.value,
    property_id: property_id.value || null,
  });
  isDialogVisible.value = false;
  window.location.href = exportUrl;
};

onMounted(() => {
  loadRoomType();

  if (property_id.value) {
    fetchData();
  }
});

const defaultHeaders = [
  { title: "Mã đặt phòng", key: "booking_code" },
  { title: "Nguồn", key: "ota_name" },
  { title: "Chổ nghỉ", key: "property_id" },
  { title: "Đối tác", key: "partner_id" },
  { title: "Nhập từ OTA", key: "is_imported" },
  { title: "Khách hàng", key: "customer_name" },
  { title: "Số đêm", key: "number_of_night" },
  { title: "Loại phòng", key: "rooms" },
  { title: "Nhận phòng", key: "check_in_date" },
  { title: "Trả phòng", key: "check_out_date" },
  { title: "PPTT tiền phòng", key: "room_payment_method" },
  { title: "TT Thanh toán", key: "payment_status" },
  { title: "Tổng số tiền", key: "total_amount" },
  { title: "Đã thanh toán", key: "paid" },
  { title: "Còn lại", key: "remaining" },
  { title: "Trạng thái", key: "status" },
  { title: "", key: "actions" },
];

const visibleHeaders = ref([...defaultHeaders]);
const showColumnDialog = ref(false);

const statusColor = (status) => {
  switch (status) {
    case "Mới":
      return "primary";
    case "Xác nhận":
      return "success";
    case "Yêu cầu":
      return "warning";
    case "Hủy":
      return "error";
    case "Hoàn thành":
      return "success";
    default:
      return "grey";
  }
};

const getStatusIcon = (status) => {
  switch (status) {
    case "Mới":
      return "tabler-circle";
    case "Xác nhận":
      return "tabler-check";
    case "Yêu cầu":
      return "tabler-alert-triangle";
    case "Hủy":
      return "tabler-x";
    case "Hoàn thành":
      return "tabler-check";
    default:
      return "tabler-circle";
  }
};
</script>
<style lang="scss" scoped>
// Data Table Card styling
.data-table-card {
  overflow: hidden;
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  border-radius: 16px;
  background-color: rgb(var(--v-theme-surface));
  box-shadow: 0 4px 20px rgba(0, 0, 0, 8%);

  .table-header {
    border-block-end: 1px solid
      rgba(var(--v-theme-on-surface), var(--v-border-opacity));
    padding-block: 20px;
    padding-inline: 24px;

    .v-card-title {
      color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
      font-size: 1.25rem;
      font-weight: 600;
    }
  }

  .table-content {
    padding: 0;

    .custom-data-table {
      :deep(.v-data-table__wrapper) {
        overflow: hidden;
        border-radius: 0 0 16px 16px;
      }

      :deep(.v-data-table-header) {
        .v-data-table-header__content {
          color: rgba(
            var(--v-theme-on-surface),
            var(--v-high-emphasis-opacity)
          );
          font-size: 0.875rem;
          font-weight: 600;
          letter-spacing: 0.025em;
          text-transform: uppercase;
        }
      }

      :deep(.v-data-table__tr) {
        border-block-end: 1px solid
          rgba(var(--v-theme-on-surface), var(--v-border-opacity));
        transition: all 0.3s ease;

        &:hover {
          background: rgba(var(--v-theme-primary), 0.04);
          box-shadow: 0 2px 8px rgba(102, 126, 234, 10%);
          transform: scale(1.002);
        }

        &:last-child {
          border-block-end: none;
        }
      }

      :deep(.v-data-table__td) {
        border-block-end: 1px solid
          rgba(var(--v-theme-on-surface), var(--v-border-opacity));
        color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
        padding-block: 16px;
        padding-inline: 12px;
        vertical-align: middle;
      }

      // Summary row styling
      :deep(.summary-row) {
        background: rgba(var(--v-theme-on-surface), var(--v-hover-opacity));
        border-block-start: 2px solid rgb(var(--v-theme-primary));

        .v-data-table__td {
          border-block-end: none;
          font-weight: 600;
        }
      }
    }
  }
}

// Button styling
.action-btn {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
  font-weight: 500;
  letter-spacing: 0.025em;
  text-transform: none;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 4px 12px rgba(102, 126, 234, 25%);
    transform: translateY(-2px);
  }

  &.primary-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 30%);

    &:hover {
      box-shadow: 0 6px 20px rgba(102, 126, 234, 40%);
      transform: translateY(-3px);
    }
  }
}

// Input styling
.custom-input {
  :deep(.v-field) {
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 5%);
    transition: all 0.3s ease;

    &:hover {
      box-shadow: 0 4px 12px rgba(102, 126, 234, 15%);
    }

    &.v-field--focused {
      border-color: #667eea;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 25%);
    }
  }

  :deep(.v-field__input) {
    font-weight: 500;
  }
}

// Table cell content styling
.property-name {
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  font-size: 0.875rem;
  font-weight: 600;
}

.customer-name {
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  font-size: 0.875rem;
  font-weight: 500;
}

.amount-highlight {
  color: rgb(var(--v-theme-success));
  font-size: 1.1rem;
  font-weight: 700;
}

.guest-info {
  margin-block-start: 6px;

  p {
    display: flex;
    align-items: center;
    color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
    font-size: 0.75rem;
    margin-block-end: 2px;
  }
}

.room-info {
  display: flex;
  align-items: center;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  font-size: 0.875rem;
  margin-block-end: 4px;
}

// Status chip styling
.status-chip {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
  font-size: 0.75rem;
  font-weight: 500;
  letter-spacing: 0.025em;
  text-transform: uppercase;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 15%);
    transform: translateY(-1px);
  }

  :deep(.v-icon) {
    font-size: 14px;
  }
}

// Logo styling
.logo {
  border-radius: 6px;
  block-size: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
  inline-size: 2rem;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 15%);
    transform: scale(1.1);
  }
}

// Chip styling improvements
:deep(.v-chip) {
  &.v-chip--size-small {
    block-size: 28px;
    font-size: 0.75rem;
    padding-block: 0;
    padding-inline: 10px;
  }

  .v-chip__content {
    display: flex;
    align-items: center;
    font-weight: 500;
    gap: 4px;
  }
}

// Responsive design
@media (max-width: 768px) {
  .data-table-card {
    .table-header {
      padding: 16px;

      .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 16px;
      }
    }

    .custom-data-table {
      :deep(.v-data-table__wrapper) {
        overflow-x: auto;
      }
    }
  }

  .action-btn {
    inline-size: 100%;
    margin-block-end: 8px;
  }
}

// Animation keyframes
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.data-table-card {
  animation: fadeInUp 0.6s ease-out;
}
</style>
