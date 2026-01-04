<template>
  <Head title="Khách hàng | Room Rise" />
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
              <VIcon icon="tabler-users" class="me-3" size="32" />
              Quản lý khách hàng
            </h1>
            <p class="text-white mb-0">Tổng cộng {{ data.total }} khách hàng</p>
          </div>
          <VBtn
            size="large"
            color="white"
            variant="elevated"
            prepend-icon="tabler-plus"
            @click="addNewItem"
            class="font-weight-bold"
          >
            Thêm khách hàng
          </VBtn>
        </div>
      </VCardItem>
    </VCard>

    <!-- Filter Section với modern design -->
    <VCard class="mb-6 border-0 shadow-sm" elevation="2">
      <VCardItem class="pa-6">
        <div class="d-flex align-center mb-4">
          <VIcon icon="tabler-filter" class="me-2" color="primary" />
          <h2 class="text-h6 font-weight-bold text-primary">Bộ lọc tìm kiếm</h2>
        </div>

        <VRow>
          <VCol
            cols="12"
            v-if="['Super Admin', 'Admin'].includes(user.role)"
            md="3"
          >
            <div class="filter-item">
              <AppSelect
                item-title="name"
                item-value="id"
                v-model="partner_group_id"
                :items="props.partnerGroup"
                label="Chọn Partner Admin"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="tabler-building"
                color="primary"
              />
            </div>
          </VCol>
          <VCol cols="12" md="3">
            <div class="filter-item">
              <AppSelect
                item-title="name"
                item-value="id"
                v-model="type"
                :items="typeOptions"
                label="Loại khách hàng"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="tabler-category"
                color="primary"
              />
            </div>
          </VCol>
          <VCol cols="12" md="3">
            <div class="filter-item">
              <AppSelect
                item-title="name"
                item-value="id"
                v-model="country"
                :items="props.countries"
                label="Quốc gia"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="tabler-world"
                color="primary"
              />
            </div>
          </VCol>
          <VCol cols="12" md="3">
            <div class="filter-item">
              <AppSelect
                item-title="name"
                item-value="id"
                v-model="city"
                :items="props.cities"
                label="Thành phố"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="tabler-map-pin"
                color="primary"
              />
            </div>
          </VCol>
          <VCol cols="12" class="d-flex justify-end">
            <VBtn
              color="warning"
              variant="outlined"
              prepend-icon="tabler-refresh"
              @click="resetFilters"
              class="font-weight-medium"
            >
              Reset bộ lọc
            </VBtn>
          </VCol>
        </VRow>
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
              >Danh sách khách hàng</span
            >
          </div>

          <div class="d-flex align-center gap-4 flex-wrap">
            <div class="d-flex gap-2 align-center">
              <span class="text-body-2 text-medium-emphasis">Hiển thị</span>
              <AppSelect
                :model-value="itemsPerPage"
                :items="PAGINATION_OPTIONS"
                style="inline-size: 5.5rem"
                variant="outlined"
                density="compact"
                @update:model-value="itemsPerPage = parseInt($event, 10)"
              />
            </div>

            <AppTextField
              v-model="search"
              placeholder="Tìm kiếm khách hàng..."
              style="inline-size: 18rem"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="tabler-search"
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
          <template #item.full_name="{ item }">
            <div class="d-flex align-center">
              <VAvatar size="32" color="primary" variant="tonal" class="me-3">
                <VIcon icon="tabler-user" />
              </VAvatar>
              <div>
                <span class="font-weight-medium">{{ item.full_name }}</span>
              </div>
            </div>
          </template>

          <template #item.type="{ item }">
            <VChip
              :color="getTypeColor(item.type)"
              variant="tonal"
              size="small"
              class="font-weight-medium"
            >
              {{ item.type }}
            </VChip>
          </template>

          <template #item.partner_admin="{ item }">
            <VChip
              color="info"
              variant="outlined"
              size="small"
              v-if="item?.partner_group?.name"
            >
              {{ item?.partner_group?.name }}
            </VChip>
            <span v-else class="text-medium-emphasis">-</span>
          </template>

          <template #item.email="{ item }">
            <div class="d-flex align-center">
              <VIcon
                icon="tabler-mail"
                size="16"
                class="me-2 text-medium-emphasis"
              />
              {{ item.email }}
            </div>
          </template>

          <template #item.phone="{ item }">
            <div class="d-flex align-center">
              <VIcon
                icon="tabler-phone"
                size="16"
                class="me-2 text-medium-emphasis"
              />
              {{ item.phone }}
            </div>
          </template>

          <template #item.country="{ item }">
            <div class="d-flex align-center">
              <VIcon
                icon="tabler-world"
                size="16"
                class="me-2 text-medium-emphasis"
              />
              {{ item.country }}
            </div>
          </template>

          <template #item.city="{ item }">
            <div class="d-flex align-center">
              <VIcon
                icon="tabler-map-pin"
                size="16"
                class="me-2 text-medium-emphasis"
              />
              {{ item.city }}
            </div>
          </template>

          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <Link :href="route('customers.show', { customer: item.id })">
                <VBtn icon size="small" color="info" variant="tonal">
                  <VIcon size="20" icon="tabler-eye" />
                  <VTooltip activator="parent" location="top"> Xem </VTooltip>
                </VBtn>
              </Link>
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

    <CustomerFormDialog
      v-model:is-dialog-visible="isFormDialogVisible"
      :data="selectedData"
      :partner-group="partnerGroup"
    />
    <AppConfirmDialog @on-submit="handleDelete" />
  </Layout>
</template>

<script setup>
import TablePagination from "@/@core/components/TablePagination.vue";
import CustomerFormDialog from "@/Components/customers/CustomerFormDialog.vue";
import AppConfirmDialog from "@/Components/shared/AppConfirmDialog.vue";
import {
  isOpenAppConfirmDialog,
  loadingAppConfirmDialog,
} from "@/composables/dialog";
import Layout from "@/layouts/blank.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { PAGINATION_OPTIONS } from "@/utils/constants";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { computed, onMounted, ref, watch } from "vue";
const propertyStore = usePropertyStore();

const pageUser = usePage();
const user = computed(() => pageUser.props.auth.user);
const role = computed(() => user.value.role);
const isSuperAdmin = computed(
  () => role.value === "Super Admin" || role.value === "Admin"
);
const partner_group_id = ref("");
const type = ref("");
const country = ref("");
const city = ref("");
const props = defineProps({
  data: Object,
  filters: Object,
  partnerGroup: Object,
  countries: Object,
  cities: Object,
});

const baseHeaders = [
  { title: "Khách hàng", key: "full_name", sortable: false },
  { title: "Loại", key: "type", sortable: false },
  { title: "Email", key: "email", sortable: false },
  { title: "Điện thoại", key: "phone", sortable: false },
  { title: "Quốc gia", key: "country", sortable: false },
  { title: "Thành phố", key: "city", sortable: false },
  { title: "Thao tác", key: "actions", sortable: false },
];
const property_id = ref(
  propertyStore.selectedProperty || Number(props.filters?.property_id) || null
);

const headers = computed(() => {
  if (isSuperAdmin.value) {
    const newHeaders = [...baseHeaders];
    newHeaders.splice(2, 0, {
      title: "Partner Admin",
      key: "partner_admin",
      sortable: false,
    });
    return newHeaders;
  }
  return baseHeaders;
});

// Helper function for type colors
const getTypeColor = (type) => {
  const colorMap = {
    Sale: "success",
    "Sale TA": "info",
    OTA: "warning",
    Social: "purple",
    "Walk-in": "primary",
  };
  return colorMap[type] || "default";
};

const search = ref(props.filters.search || "");
const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);

const fetchData = () => {
  router.get(
    route("customers.index"),
    {
      search: search.value,
      partner_group_id: partner_group_id.value,
      type: type.value,
      country: country.value,
      city: city.value,
      page: page.value,
      paginate: itemsPerPage.value,
      property_id: property_id.value !== null ? property_id.value : null,
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
watch(search, debounce(fetchData, 300));
watch(partner_group_id, debounce(fetchData, 300));
watch(type, debounce(fetchData, 300));
watch(country, debounce(fetchData, 300));
watch(city, debounce(fetchData, 300));
watch([page, itemsPerPage], fetchData);
onMounted(() => {
  if (property_id.value) {
    fetchData();
  }
});
const typeOptions = ref([
  { id: "Sale", name: "Sale" },
  { id: "Sale TA", name: "Sale TA" },
  { id: "OTA", name: "OTA" },
  { id: "Social", name: "Social" },
  { id: "Walk-in", name: "Walk-in" },
]);

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

const viewItem = (itemData) => {
  console.log("Viewing customer:", itemData);
};

const deleteItem = async (itemData) => {
  selectedData.value = itemData;
  isOpenAppConfirmDialog.value = true;
};

const handleDelete = () => {
  router.delete(
    route("customers.destroy", { customer: selectedData.value.id }),
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

const resetFilters = () => {
  partner_group_id.value = "";
  type.value = "";
  country.value = "";
  city.value = "";
  search.value = "";
  page.value = 1;
};
</script>

<style scoped>
.filter-item {
  transition: all 0.3s ease;
}

.filter-item:hover {
  transform: translateY(-2px);
}

.custom-data-table {
  border-radius: 12px;
  overflow: hidden;
}

.custom-data-table :deep(.v-data-table__th) {
  /* background-color: rgb(var(--v-theme-surface-variant)); */
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface-variant));
}

.custom-data-table :deep(.v-data-table__tr:hover) {
  background-color: rgb(var(--v-theme-primary), 0.04);
}

.custom-data-table :deep(.v-data-table__td) {
  padding: 16px 12px;
}
</style>
