<template>
  <Head title="Loại Phòng | Room Rise" />
  <Layout>
    <RequiredSpecificProperty>
      <template #default>
        <!-- Header Section với gradient và shadow -->
        <VCard
          class="mb-6 border-0 shadow-lg"
          style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
        >
          <VCardItem class="pa-6">
            <div class="d-flex align-center justify-space-between mb-4">
              <div>
                <h1 class="text-h4 text-white font-weight-bold mb-2">
                  <VIcon icon="tabler-bed" class="me-3" size="32" />
                  Quản lý loại phòng
                </h1>
                <p class="text-white mb-0">
                  Tổng cộng {{ data.total }} loại phòng
                </p>
              </div>
              <VBtn
                size="large"
                color="white"
                variant="elevated"
                prepend-icon="tabler-plus"
                @click="addNewItem"
                class="font-weight-bold"
              >
                Thêm loại phòng
              </VBtn>
            </div>
          </VCardItem>
        </VCard>
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
                  >Danh sách loại phòng</span
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
                  placeholder="Tìm kiếm loại phòng..."
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
                    icon="tabler-bed"
                    color="primary"
                    size="20"
                    class="me-2"
                  />
                  <span class="font-weight-bold text-primary text-body-1">{{
                    item.name
                  }}</span>
                </div>
              </template>
              <template #item.property_name="{ item }">
                <div class="d-flex align-center">
                  <VIcon
                    icon="tabler-building"
                    size="16"
                    class="me-2 text-info"
                  />
                  <span class="font-weight-bold">{{
                    item.property_name || "-"
                  }}</span>
                </div>
              </template>
              <template #item.quantity="{ item }">
                <VChip
                  color="info"
                  variant="tonal"
                  size="small"
                  class="font-weight-medium"
                >
                  <VIcon icon="tabler-hash" size="16" class="me-1" />
                  {{ item.quantity || "-" }}
                </VChip>
              </template>
              <template #item.adults="{ item }">
                <VChip
                  color="success"
                  variant="tonal"
                  size="small"
                  class="font-weight-medium"
                >
                  <VIcon icon="tabler-user" size="16" class="me-1" />
                  {{ item.adults || "-" }}
                </VChip>
              </template>
              <template #item.children="{ item }">
                <VChip
                  color="warning"
                  variant="tonal"
                  size="small"
                  class="font-weight-medium"
                >
                  <VIcon icon="tabler-user" size="16" class="me-1" />
                  {{ item.children || "-" }}
                </VChip>
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
      </template>
    </RequiredSpecificProperty>
    <RoomFormDialog
      v-model:is-dialog-visible="isFormDialogVisible"
      :property_id="filtersData.property_id"
      :data="selectedData"
    />
    <AppConfirmDialog @on-submit="handleDelete" />
  </Layout>
</template>

<script setup>
import TablePagination from "@/@core/components/TablePagination.vue";
import RequiredSpecificProperty from "@/Components/properties/RequiredSpecificProperty.vue";
import RoomFormDialog from "@/Components/rooms/RoomFormDialog.vue";
import AppConfirmDialog from "@/Components/shared/AppConfirmDialog.vue";
import {
  isOpenAppConfirmDialog,
  loadingAppConfirmDialog,
} from "@/composables/dialog";
import Layout from "@/layouts/blank.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { PAGINATION_OPTIONS } from "@/utils/constants";
import { Head, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { onMounted, ref, watch } from "vue";

const propertyStore = usePropertyStore();

const props = defineProps({
  data: Object,
  filters: Object,
  properties: Array,
});

// ✅ Ưu tiên lấy từ store để đồng bộ giữa các trang
// const filtersData = ref({
//   property_id:
//     propertyStore.selectedProperty ||
//     Number(props.filters?.) ||
//     null,
// });

const filtersData = ref({
  property_id: propertyStore.selectedProperty || null,
});

const headers = [
  { title: "Tên loại phòng", key: "name", sortable: false },
  { title: "Chỗ nghỉ", key: "property_name", sortable: false },
  { title: "Số lượng", key: "quantity", sortable: false },
  { title: "Người lớn", key: "adults", sortable: false },
  { title: "Trẻ em", key: "children", sortable: false },
  { title: "Thao tác", key: "actions", sortable: false },
];

const search = ref(props.filters.search || "");
const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);

const fetchData = () => {
  console.log(filtersData.value);
  router.get(
    route("room-types.index"),
    {
      search: search.value,
      page: page.value,
      paginate: itemsPerPage.value,
      ...filtersData.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

// ✅ Khi user đổi property ở trang này → update store + fetch
// watch(
//   () => filtersData.value.property_id,
//   (val) => {
//     propertyStore.setProperty(val);
//     fetchData();
//   }
// );

// ✅ Khi store đổi (Navbar chọn) → update filtersData + fetch
watch(
  () => propertyStore.selectedProperty,
  (val) => {
    console.log(val);
    if (filtersData.value.property_id !== val) {
      filtersData.value.property_id = val;
      fetchData();
    }
  }
);

// ✅ Gọi API khi vào trang lần đầu theo property hiện tại trong store
onMounted(() => {
  filtersData.value.property_id =
    propertyStore.selectedProperty ||
    Number(props.filters?.property_id) ||
    null;
  fetchData();
});

watch(search, debounce(fetchData, 300));
watch([page, itemsPerPage], fetchData);

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
    route("room-types.destroy", { room_type: selectedData.value.id }),
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
