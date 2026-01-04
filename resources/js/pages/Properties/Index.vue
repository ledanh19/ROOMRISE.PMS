<template>
  <Head title="Chỗ nghỉ | Room Rise" />
  <Layout>
    <!-- Header Section với gradient và shadow -->
    <VCard
      class="mb-6 border-0 shadow-lg"
      style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
    >
      <VCardItem class="pa-6">
        <div class="d-flex align-center justify-space-between mb-4">
          <div>
            <h1 class="text-h4 text-white font-weight-bold mb-2">
              <VIcon icon="tabler-building" class="me-3" size="32" />
              Quản lý chỗ nghỉ
            </h1>
            <p class="text-white mb-0">Tổng cộng {{ data.total }} chỗ nghỉ</p>
          </div>
          <!-- <VBtn
            size="large"
            color="white"
            variant="elevated"
            prepend-icon="tabler-plus"
            @click="addNewItem"
            class="font-weight-bold"
          >
            Thêm chỗ nghỉ
          </VBtn> -->
        </div>
      </VCardItem>
    </VCard>

    <!-- Filter Section (nếu có filter) -->
    <!--
    <VCard class="mb-6 border-0 shadow-sm" elevation="2">
      <VCardItem class="pa-6">
        <div class="d-flex align-center mb-4">
          <VIcon icon="tabler-filter" class="me-2" color="primary" />
          <h2 class="text-h6 font-weight-bold text-primary">Bộ lọc tìm kiếm</h2>
        </div>
        <VRow>
          ... filter controls ...
        </VRow>
      </VCardItem>
    </VCard>
    -->

    <!-- Data Table Section với enhanced styling -->
    <VCard class="border-0 shadow-lg" elevation="4">
      <VCardText class="pa-6">
        <!-- Toolbar Section -->
        <div
          class="d-flex align-center justify-space-between flex-wrap gap-4 mb-4"
        >
          <div class="d-flex gap-2 align-center">
            <VIcon icon="tabler-list" color="primary" />
            <span class="text-h6 font-weight-bold text-primary"
              >Danh sách chỗ nghỉ</span
            >
          </div>
          <div class="d-flex align-center gap-4 flex-wrap">
            <div class="d-flex gap-2 align-center">
              <span class="text-medium-emphasis">Hiển thị</span>
              <AppSelect
                :model-value="itemsPerPage"
                :items="PAGINATION_OPTIONS"
                style="inline-size: 5.5rem"
                @update:model-value="itemsPerPage = parseInt($event, 10)"
              />
            </div>
            <AppTextField
              v-model="search"
              placeholder="Tìm kiếm chỗ nghỉ..."
              style="inline-size: 18rem"
              clearable
              color="primary"
            />
          </div>
        </div>
        <VDivider class="mb-4" />
        <!-- Enhanced Data Table -->
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items-length="data.total"
          :headers="headers"
          :items="data.data"
          item-value="id"
          class="text-no-wrap custom-data-table"
          @update:options="updateOptions"
          hover
        >
          <template #item.name="{ item }">
            <div class="d-flex align-center">
              <VIcon
                icon="tabler-building"
                color="primary"
                size="20"
                class="me-2"
              />
              <span class="font-weight-bold text-primary text-body-1">{{
                item.name
              }}</span>
            </div>
          </template>
          <template #item.type="{ item }">
            <VChip
              :color="getTypeColor(item.type)"
              variant="tonal"
              size="small"
              class="font-weight-medium"
            >
              {{ item.type || "-" }}
            </VChip>
          </template>
          <template #item.country="{ value }">
            <div class="d-flex align-center">
              <VIcon icon="tabler-world" size="16" class="me-2 text-primary" />
              <span class="font-weight-bold">{{
                countriesMap[value] || "-"
              }}</span>
            </div>
          </template>
          <template #item.deposit_amount="{ item }">
            <div class="d-flex align-center">
              <VIcon
                icon="tabler-currency-dollar"
                size="16"
                class="me-2 text-success"
              />
              <span class="font-weight-bold text-success">
                {{
                  item.deposit_amount
                    ? formatCurrency(item.deposit_amount, item.currency)
                    : "-"
                }}
              </span>
            </div>
          </template>
          <template #item.phone="{ item }">
            <div class="d-flex align-center">
              <VIcon
                icon="tabler-phone"
                size="16"
                class="me-2 text-medium-emphasis"
              />
              <span>{{ item.phone || "-" }}</span>
            </div>
          </template>
          <template #item.email="{ item }">
            <div class="d-flex align-center">
              <VIcon
                icon="tabler-mail"
                size="16"
                class="me-2 text-medium-emphasis"
              />
              <span>{{ item.email || "-" }}</span>
            </div>
          </template>
          <template #item.roles="{ item }">
            <div class="d-flex gap-2">
              <VChip
                v-for="text in item.roles"
                :key="text"
                label
                size="small"
                color="info"
                class="font-weight-medium"
              >
                {{ text }}
              </VChip>
            </div>
          </template>
          <template #item.created_at="{ item }">
            <div class="text-body-2 text-medium-emphasis">
              {{ formatDate(item.created_at) || "-" }}
            </div>
          </template>
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <VBtn
                icon
                size="small"
                color="primary"
                variant="tonal"
                @click="editItem(item)"
              >
                <VIcon size="20" icon="tabler-edit" />
                <VTooltip activator="parent" location="top">
                  Chỉnh sửa
                </VTooltip>
              </VBtn>
              <VBtn
                icon
                size="small"
                color="error"
                variant="tonal"
                @click="deleteItem(item)"
              >
                <VIcon size="20" icon="tabler-trash" />
                <VTooltip activator="parent" location="top"> Xóa </VTooltip>
              </VBtn>
            </div>
          </template>
          <template #bottom>
            <TablePagination
              v-model:page="page"
              :items-per-page="itemsPerPage"
              :total-items="data.total"
            />
          </template>
        </VDataTableServer>
      </VCardText>
    </VCard>
    <PropertyFormDialog
      v-model:is-dialog-visible="isFormDialogVisible"
      :data="selectedData"
      :partner-group="partnerGroup"
      :booking-sources="bookingSources"
    />
    <AppConfirmDialog @on-submit="handleDelete" />
  </Layout>
</template>

<script setup>
import TablePagination from "@/@core/components/TablePagination.vue";
import PropertyFormDialog from "@/Components/properties/PropertyFormDialog.vue";
import AppConfirmDialog from "@/Components/shared/AppConfirmDialog.vue";
import {
  isOpenAppConfirmDialog,
  loadingAppConfirmDialog,
} from "@/composables/dialog";
import Layout from "@/layouts/blank.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { PAGINATION_OPTIONS } from "@/utils/constants";
import { countriesMap } from "@/utils/country";
import { formatCurrency, formatDate } from "@/utils/formatters";
import { Head, router, usePage } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { computed, onMounted, ref, watch } from "vue";
const propertyStore = usePropertyStore();

const props = defineProps({
  data: Object,
  filters: Object,
  // partnerAdmins: Array,
  partnerGroup: Object,
  bookingSources: {
    type: Array,
    required: true,
    default: () => [],
  },
});

const role = computed(() => usePage().props?.auth?.user?.role);

const headers = [
  { title: "Tên", key: "name", sortable: false },
  { title: "Loại", key: "type", sortable: false },
  { title: "Thành phố", key: "city", sortable: false },
  { title: "Địa chỉ", key: "address", sortable: false },
  { title: "Quốc gia", key: "country", sortable: false },
  { title: "Tiền tệ", key: "currency", sortable: false },
  { title: "Tiền đặt cọc", key: "deposit_amount", sortable: false },
  { title: "Điện thoại", key: "phone", sortable: false },
  { title: "Email", key: "email", sortable: false },
  { title: "Thao tác", key: "actions", sortable: false },
];
const property_id = ref(
  propertyStore.selectedProperty || Number(props.filters?.property_id) || null
);
const search = ref(props.filters.search || "");
const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);

const fetchData = () => {
  router.get(
    route("properties.index"),
    {
      search: search.value,
      page: page.value,
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

watch(search, debounce(fetchData, 300));
watch([page, itemsPerPage], fetchData);
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
    route("properties.destroy", { property: selectedData.value.id }),
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

// Helper function for type colors
const getTypeColor = (type) => {
  const colorMap = {
    Hotel: "primary",
    Hostel: "info",
    Resort: "success",
    Apartment: "warning",
    apartment: "warning",
    Villa: "purple",
    Homestay: "secondary",
  };
  return colorMap[type] || "default";
};
</script>

<style scoped>
.custom-data-table {
  border-radius: 12px;
  overflow: hidden;
}

.custom-data-table :deep(.v-data-table__th) {
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface-variant));
}

.custom-data-table :deep(.v-data-table__tr:hover) {
  background-color: rgb(var(--v-theme-primary), 0.04) !important;
}

.custom-data-table :deep(.v-data-table__td) {
  padding: 16px 12px;
  vertical-align: middle;
}
</style>
