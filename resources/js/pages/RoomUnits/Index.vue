<template>
  <Head title="Phòng | Room Rise" />
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
              <VIcon icon="tabler-door" size="32" />
              Quản lý phòng
            </h1>
            <!-- <p class="text-white mb-0">Tổng cộng {{ data.total }} phòng</p> -->
          </div>
        </div>
      </VCardItem>
    </VCard>
    <RequiredSpecificProperty>
      <VCard class="border-0 shadow-lg" elevation="4">
        <VCardText class="pa-6">
          <!-- Toolbar Section -->
          <div
            class="d-flex align-center justify-space-between flex-wrap gap-4 mb-4"
          >
            <div class="d-flex gap-2 align-center">
              <VIcon icon="tabler-list" color="primary" />
              <span class="text-h6 font-weight-bold text-primary"
                >Danh sách phòng</span
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
                placeholder="Tìm kiếm phòng..."
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
            v-model:expanded="expanded"
            show-expand
            item-value="id"
            class="text-no-wrap custom-data-table"
            @update:options="updateOptions"
            hover
          >
            <template #item.name="{ value, item }">
              <div class="d-flex align-center">
                <VIcon
                  icon="tabler-door"
                  color="primary"
                  size="20"
                  class="me-2"
                />
                <span class="font-weight-bold text-primary text-body-1">{{
                  value
                }}</span>
              </div>
            </template>

            <template #expanded-row="{ item }">
              <tr v-for="(unit, i) in item.room_units" :key="i">
                <td class="font-weight-bold text-primary">
                  <div class="ml-5">
                    <VIcon
                      icon="tabler-door"
                      size="16"
                      class="me-1 text-primary"
                    />
                    {{ unit.name }}
                  </div>
                </td>
                <td class="font-weight-medium">
                  <VIcon icon="tabler-note" size="14" class="me-1 text-info" />
                  {{ unit.note || "-" }}
                </td>
                <td>
                  <VChip
                    :color="getStatusColor(unit.status)"
                    variant="tonal"
                    size="small"
                    class="font-weight-medium status-chip"
                  >
                    <VIcon
                      icon="tabler-check"
                      size="14"
                      class="me-1"
                      v-if="isStatusActive(unit.status)"
                    />
                    <VIcon icon="tabler-x" size="14" class="me-1" v-else />
                    {{ unit.status || "-" }}
                  </VChip>
                </td>
                <td class="pl-1">
                  <VBtn
                    icon
                    size="small"
                    color="primary"
                    variant="tonal"
                    @click="editItem(unit)"
                  >
                    <VIcon size="20" icon="tabler-edit" />
                    <VTooltip activator="parent" location="top">
                      Chỉnh sửa
                    </VTooltip>
                  </VBtn>
                </td>
              </tr>
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
    </RequiredSpecificProperty>
    <RoomUnitFormDialog
      v-model:is-dialog-visible="isFormDialogVisible"
      :data="selectedData"
    />
  </Layout>
</template>

<script setup>
import TablePagination from "@/@core/components/TablePagination.vue";
import RoomUnitFormDialog from "@/Components/room-units/RoomUnitFormDialog.vue";
import Layout from "@/layouts/blank.vue";
import { PAGINATION_OPTIONS } from "@/utils/constants";
import { Head, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { onMounted, ref, watch } from "vue";

import RequiredSpecificProperty from "@/Components/properties/RequiredSpecificProperty.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
const propertyStore = usePropertyStore();

const props = defineProps({
  data: Object,
  propertyOptions: Array,
  filters: Object,
});

const headers = [
  { title: "Tên phòng", key: "name", sortable: false },
  { title: "Ghi Chú", key: "note", sortable: false },
  { title: "Trạng Thái", key: "status", sortable: false },
];

const search = ref(props.filters.search || "");
const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);
const expanded = ref(props.data.data.map((item) => item.id));

// const filtersData = ref({
//   property_id: Number(props.filters?.property_id) || null,
// });
const filtersData = ref({
  property_id: propertyStore.selectedProperty || null,
});

watch(
  () => filtersData.value.property_id,
  (val) => {
    propertyStore.setProperty(val);
    fetchData();
  }
);

watch(
  () => propertyStore.selectedProperty,
  (val) => {
    if (filtersData.value.property_id !== val) {
      filtersData.value.property_id = val;
      fetchData(); // ✅ gọi API reload
    }
  }
);

const fetchData = () => {
  router.get(
    route("rooms.index"),
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

watch(search, debounce(fetchData, 300));
watch([page, itemsPerPage], fetchData);
watch(
  filtersData,
  (newFilters) => {
    router.get(route("rooms.index"), newFilters, {
      preserveScroll: true,
      replace: true,
    });
  },
  {
    deep: true,
  }
);

onMounted(() => {
  filtersData.value.property_id = propertyStore.selectedProperty || null;
  fetchData();
});
const updateOptions = (options) => {
  page.value = options.page;
  itemsPerPage.value = options.itemsPerPage;
};
const isFormDialogVisible = ref(false);
const selectedData = ref();

const editItem = (itemData) => {
  selectedData.value = itemData;
  isFormDialogVisible.value = true;
};

// Helper function for status color
const getStatusColor = (status) => {
  if (isStatusActive(status)) return "success";
  if (isStatusInactive(status)) return "error";
  return "default";
};
const isStatusActive = (status) => {
  return (
    status === "active" ||
    status === "Sẵn sàng" ||
    status === "san-sang" ||
    status === "ready"
  );
};
const isStatusInactive = (status) => {
  return (
    status === "inactive" ||
    status === "Không sẵn sàng" ||
    status === "khong-san-sang" ||
    status === "not-ready"
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

.status-chip {
  letter-spacing: 0.5px;
  font-size: 0.875rem !important;
  font-weight: 600 !important;
}

/* Success status chip with gradient */
.custom-data-table
  :deep(.status-chip.v-chip--variant-tonal.v-chip--color-success) {
  background: linear-gradient(90deg, #28c76f 60%, #43e97b 100%) !important;
  color: #fff !important;
}

/* Error status chip with gradient */
.custom-data-table
  :deep(.status-chip.v-chip--variant-tonal.v-chip--color-error) {
  background: linear-gradient(90deg, #ff4c51 60%, #ff8884 100%) !important;
  color: #fff !important;
}
</style>
