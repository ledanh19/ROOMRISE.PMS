<template>
  <Head title="Người dùng | Room Rise" />
  <Layout>
    <section class="user-management-page">
      <!-- 👉 Header Section - Similar to Customer Management -->
      <div class="page-header">
        <div class="d-flex align-center justify-space-between">
          <div class="header-content">
            <div class="d-flex align-center gap-3">
              <VIcon icon="tabler-users" size="28" color="white" />
              <div>
                <h1 class="page-title">Quản lý người dùng</h1>
                <p class="page-subtitle">
                  Tổng cộng {{ props.qty_user }} người dùng
                </p>
              </div>
            </div>
          </div>
          <VBtn
            color="white"
            variant="flat"
            prepend-icon="tabler-plus"
            @click="isAddNewUserDrawerVisible = true"
            class="add-btn"
          >
            Thêm Người Dùng
          </VBtn>
        </div>
      </div>

      <!-- 👉 Data Table Section -->
      <VCard class="data-table-card">
        <!-- Table Header with controls -->
        <VCardText class="table-controls">
          <div class="d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-3">
              <VIcon icon="tabler-list" color="primary" />
              <span class="table-title">Danh sách người dùng</span>
            </div>
            <div class="d-flex align-center gap-4">
              <div class="d-flex align-center gap-2">
                <span class="" style="font-size: 1.1rem">Hiển thị:</span>
                <AppSelect
                  :model-value="itemsPerPage"
                  :items="[
                    { value: 10, title: '10' },
                    { value: 25, title: '25' },
                    { value: 50, title: '50' },
                    { value: 100, title: '100' },
                    { value: -1, title: 'Tất cả' },
                  ]"
                  style="inline-size: 6.25rem"
                  @update:model-value="itemsPerPage = parseInt($event, 10)"
                />
              </div>
              <div class="search-container">
                <AppTextField
                  v-model="search"
                  placeholder="Tìm kiếm người dùng"
                  style="inline-size: 15.625rem"
                  clearable
                />
              </div>
            </div>
          </div>
        </VCardText>
        <!-- SECTION datatable -->
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items-length="userData.total"
          :items-per-page-options="[
            { value: 5, title: '5' },
            { value: 10, title: '10' },
            { value: -1, title: '$vuetify.dataFooter.itemsPerPageAll' },
          ]"
          :headers="headers"
          :items="userData.data"
          item-value="id"
          class="text-no-wrap"
          @update:options="updateOptions"
        >
          <!-- Type -->
          <template v-slot:item.type="{ item }">
            <VChip
              :color="getTypeColor(item.role)"
              size="small"
              variant="flat"
              class="text-uppercase font-weight-bold"
            >
              {{ getTypeLabel(item.role) }}
            </VChip>
          </template>

          <!-- Partner Admin -->
          <template v-slot:item.partnerAdmin="{ item }">
            <div class="d-flex align-center gap-x-3">
              <VAvatar
                size="32"
                :color="getAvatarColor(item.fullName)"
                variant="tonal"
              >
                <VImg v-if="item.avatar" :src="item.avatar" />
                <span v-else class="font-weight-bold">{{
                  getInitials(item.fullName)
                }}</span>
              </VAvatar>
              <div>
                <div class="font-weight-medium">
                  {{ item.fullName }}
                </div>
                <div class="text-xs text-medium-emphasis">{{ item.role }}</div>
              </div>
            </div>
          </template>

          <!-- Email -->
          <template v-slot:item.email="{ item }">
            <div class="">{{ item.email }}</div>
          </template>

          <!-- Phone -->
          <template v-slot:item.phone="{ item }">
            <div class="d-flex align-center gap-1">
              <VIcon icon="tabler-phone" size="14" />
              <span class="">{{ item.phone || "-" }}</span>
            </div>
          </template>

          <!-- Country -->
          <template v-slot:item.country="{ item }">
            <div class="d-flex align-center gap-1">
              <VIcon icon="tabler-map-pin" size="14" />
              <span class="">{{ getCountryName(item.country) }}</span>
            </div>
          </template>

          <!-- City -->
          <template v-slot:item.city="{ item }">
            <div class="">{{ item.city || "-" }}</div>
          </template>

          <!-- Actions -->
          <template v-slot:item.actions="{ item }">
            <div class="d-flex align-center gap-1">
              <VBtn
                icon
                size="small"
                variant="text"
                color="info"
                @click="detailUser(item.id)"
              >
                <VIcon icon="tabler-eye" size="16" />
              </VBtn>
              <VBtn
                icon
                size="small"
                variant="text"
                color="error"
                @click="deleteUser(item.id)"
              >
                <VIcon icon="tabler-trash" size="16" />
              </VBtn>
            </div>
          </template>

          <!-- pagination -->
          <template #bottom>
            <TablePagination
              v-model:page="page"
              :items-per-page="itemsPerPage"
              :total-items="userData.total"
            />
          </template>
        </VDataTableServer>
        <!-- SECTION -->
      </VCard>
      <AddNewUserDrawer
        v-model:is-drawer-open="isAddNewUserDrawerVisible"
        @user-data="addNewUser"
        @userData="fetchData"
        :roles="roles"
        :partner-group="partnerGroup"
      />
    </section>
  </Layout>
</template>
<script setup>
import { usePropertyStore } from "@/stores/usePropertyStore";
import { Head, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import Swal from "sweetalert2";
import { toast } from "vue3-toastify";
import Layout from "../../layouts/blank.vue";
import AddNewUserDrawer from "../User/AddNewUserDrawer.vue";
const propertyStore = usePropertyStore();

const users = ref([]);
const isAddNewUserDrawerVisible = ref(false);

const props = defineProps({
  userData: Object,
  filters: Object,
  roles: Object,
  qty_user: Number,
  partnerGroup: Object,
});

const headers = [
  { title: "ID", key: "id" },
  { title: "Name", key: "name" },
  { title: "Email", key: "email" },
  { title: "Role", key: "role" },
  { title: "Actions", key: "actions", sortable: false },
];

const search = ref(props.filters.search || "");
const page = ref(props.userData.current_page);
const itemsPerPage = ref(props.userData.per_page);
const property_id = ref(
  propertyStore.selectedProperty || Number(props.filters?.property_id) || null
);

const fetchData = async () => {
  router.get(
    route("users.index"),
    {
      search: search.value,
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

const deleteUser = async (id) => {
  const result = await Swal.fire({
    title: "Are you sure? <br> <i class='fa-solid fa-trash-can'></i>",
    text: "This action cannot be undone! The data will be permanently deleted!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#ea5455",
    cancelButtonColor: "#6CC9CF",
    confirmButtonText: "Yes, Proceed!",
    cancelButtonText: "Cancel",
  });

  if (result.isConfirmed) {
    try {
      const response = await axios.delete(route("users.destroy", id));

      if (response.data.success) {
        toast.success(response.data.message, {
          theme: "colored",
          type: "success",
        });
        fetchData();
      } else {
        toast.error(response.data.message, {
          theme: "colored",
          type: "error",
        });
      }
    } catch (error) {
      toast.error("Đã xảy ra lỗi khi xóa người dùng.", {
        theme: "colored",
        type: "error",
      });
    }
  }
};

const detailUser = async (id) => {
  router.visit(route("users.show", id));
};

const editlUser = async (id) => {
  router.visit(route("users.edit", id));
};

// Helper functions
const getTypeColor = (role) => {
  const colors = {
    "walk-in": "primary",
    ota: "warning",
    corporate: "success",
    admin: "error",
    manager: "info",
  };
  return colors[role?.toLowerCase()] || "secondary";
};

const getTypeLabel = (role) => {
  const labels = {
    admin: "Walk-in",
    manager: "OTA",
    user: "OTA",
    guest: "OTA",
  };
  return labels[role?.toLowerCase()] || "OTA";
};

const getAvatarColor = (name) => {
  const colors = [
    "primary",
    "secondary",
    "success",
    "info",
    "warning",
    "error",
  ];
  const index = (name?.charCodeAt(0) || 0) % colors.length;
  return colors[index];
};

const getInitials = (fullName) => {
  if (!fullName) return "";
  const names = fullName.split(" ");
  if (names.length >= 2) {
    return (names[0][0] + names[names.length - 1][0]).toUpperCase();
  }
  return fullName.substring(0, 2).toUpperCase();
};

const getCountryName = (countryCode) => {
  const countries = {
    vn: "Việt Nam",
    us: "USA",
    uk: "UK",
    de: "Germany",
    fr: "France",
  };
  return countries[countryCode?.toLowerCase()] || countryCode || "-";
};

// Legacy helper functions for compatibility
const avatarText = getInitials;
const resolveUserRoleVariant = (role) => ({ color: getTypeColor(role) });
const resolveUserStatusVariant = (status) => {
  const variants = {
    active: "success",
    inactive: "secondary",
    pending: "warning",
    banned: "error",
  };
  return variants[status?.toLowerCase()] || "secondary";
};
</script>

<style scoped>
/* Customer Management Style Layout */
.user-management-page {
  padding: 1.5rem;
  background-color: rgb(var(--v-theme-background));
  min-height: 100vh;
}

/* Header Section */
.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  color: white;
  box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.header-content {
  flex: 1;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
  color: white;
}

.page-subtitle {
  font-size: 0.875rem;
  margin: 0.25rem 0 0 0;
  opacity: 0.9;
  color: white;
}

.add-btn {
  color: #667eea !important;
  font-weight: 600;
  border-radius: 8px;
  padding: 0 1.5rem;
  text-transform: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Data Table Card */
.data-table-card {
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  overflow: hidden;
  background-color: rgb(var(--v-theme-surface));
}

.table-controls {
  border-bottom: 1px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  padding: 1.5rem 1.5rem;
}

.table-title {
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  font-size: 1.1rem;
}

.search-container :deep(.v-field) {
  background-color: rgb(var(--v-theme-surface));
  border-radius: 8px;
}

/* Data Table Styling */
:deep(.v-data-table) {
  background: transparent;
}

:deep(.v-data-table .v-data-table__wrapper) {
  border-radius: 0;
}

:deep(.v-data-table thead th) {
  color: rgba(
    var(--v-theme-on-surface),
    var(--v-high-emphasis-opacity)
  ) !important;
  font-weight: 600 !important;
  font-size: 0.75rem !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  border-bottom: 1px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity)) !important;
  height: 48px !important;
}

:deep(.v-data-table tbody tr) {
  border-bottom: 1px solid
    rgba(var(--v-theme-on-surface), var(--v-border-opacity)) !important;
  transition: background-color 0.2s ease;
}

:deep(.v-data-table tbody tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.04) !important;
}

:deep(.v-data-table tbody td) {
  padding: 0.75rem 1rem !important;
  height: auto !important;
  border-bottom: none !important;
  color: rgba(
    var(--v-theme-on-surface),
    var(--v-high-emphasis-opacity)
  ) !important;
}

/* Chip Styling */
:deep(.v-chip) {
  font-size: 0.6875rem;
  font-weight: 600;
  height: 24px;
  border-radius: 12px;
}

/* Button Styling */
:deep(.v-btn) {
  text-transform: none;
  font-weight: 500;
}

:deep(.v-btn--icon) {
  border-radius: 6px;
}

/* Avatar Styling */
:deep(.v-avatar) {
  border: 2px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Form Controls */
:deep(.v-select .v-field) {
  border-radius: 8px;
  font-size: 1rem;
  background-color: rgb(var(--v-theme-surface));
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

:deep(.v-text-field .v-field) {
  border-radius: 8px;
  font-size: 1rem;
  background-color: rgb(var(--v-theme-surface));
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

/* Responsive */
@media (max-width: 768px) {
  .user-management-page {
    padding: 1rem;
  }

  .page-header {
    padding: 1rem;
  }

  .page-title {
    font-size: 1.25rem;
  }

  .table-controls {
    padding: 1rem;
  }

  .table-controls .d-flex {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch !important;
  }

  .search-container {
    width: 100%;
  }

  .search-container input {
    width: 100% !important;
  }
}
</style>
