<template>
  <!-- Action Header -->
  <VCard class="action-header">
    <VCardItem>
      <div class="d-flex justify-space-between align-center flex-wrap gap-4">
        <div class="content">
          <div class="d-flex align-center gap-3">
            <VAvatar color="success" variant="tonal" size="48">
              <VIcon icon="tabler-plane" size="24" />
            </VAvatar>
            <div>
              <h3 class="font-weight-bold">Đối tác Travel Agent</h3>
              <p class="text-medium-emphasis mb-0">
                Quản lý danh sách đối tác lữ hành và thông tin liên hệ
              </p>
            </div>
          </div>
        </div>
        <VBtn
          @click="addNewItem"
          color="success"
          class="add-button"
          size="large"
        >
          <VIcon icon="tabler-plus" class="me-2" size="20" />
          Thêm đối tác
        </VBtn>
      </div>
    </VCardItem>
  </VCard>

  <!-- Data Table -->
  <VCard class="mt-4 data-table-card">
    <VCardItem>
      <div class="d-flex justify-space-between align-center mb-4">
        <h3 class="font-weight-bold">
          Danh sách đối tác Travel Agent
          <VChip size="small" color="success" variant="tonal" class="ms-2">
            {{ saleTAData.total }} đối tác
          </VChip>
        </h3>
      </div>

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items-length="saleTAData.total"
        :headers="headers"
        :items="saleTAData.data"
        :loading="loading"
        item-value="id"
        class="modern-data-table"
        @update:options="updateOptions"
      >
        <template #loading>
          <VSkeletonLoader type="table-row@5" />
        </template>

        <template #[`item.name`]="{ item }">
          <div class="d-flex align-center gap-3">
            <VAvatar
              :color="getAvatarColor(item.name)"
              size="32"
              variant="tonal"
            >
              {{ getInitials(item.name) }}
            </VAvatar>
            <div>
              <div class="font-weight-medium">{{ item.name }}</div>
            </div>
          </div>
        </template>

        <template #[`item.email`]="{ item }">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-mail" size="22" class="text-medium-emphasis" />
            <a :href="`mailto:${item.email}`" class="text-decoration-none">
              {{ item.email }}
            </a>
          </div>
        </template>

        <template #[`item.phone`]="{ item }">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-phone" size="22" class="text-medium-emphasis" />
            <a :href="`tel:${item.phone}`" class="text-decoration-none">
              {{ item.phone }}
            </a>
          </div>
        </template>

        <template #[`item.commission`]="{ item }">
          <VChip :color="getCommissionColor(item.commission)" variant="tonal">
            {{ item.commission }}%
          </VChip>
        </template>

        <template #[`item.status`]="{ item }">
          <VChip
            :color="item.status === 'Hoạt động' ? 'success' : 'error'"
            variant="tonal"
          >
            <VIcon
              :icon="item.status === 'Hoạt động' ? 'tabler-check' : 'tabler-x'"
              size="14"
              class="me-1"
            />
            {{ item.status }}
          </VChip>
        </template>

        <template #[`item.actions`]="{ item }">
          <div class="d-flex gap-1">
            <VBtn
              icon
              variant="text"
              color="primary"
              @click="editPartner(item.id)"
            >
              <VIcon size="22" icon="tabler-edit" />
              <VTooltip activator="parent" location="top"> Chỉnh sửa </VTooltip>
            </VBtn>
            <VBtn
              icon
              variant="text"
              color="error"
              @click="deleteItem(item.id)"
            >
              <VIcon size="22" icon="tabler-trash" />
              <VTooltip activator="parent" location="top"> Xóa </VTooltip>
            </VBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="saleTAData.total"
          />
        </template>

        <template #no-data>
          <div class="text-center py-8">
            <VIcon
              icon="tabler-database-off"
              size="48"
              class="text-medium-emphasis mb-4"
            />
            <div class="text-medium-emphasis">Không có dữ liệu</div>
            <div class="text-medium-emphasis mb-4">
              Chưa có đối tác Travel Agent nào được thêm vào hệ thống
            </div>
            <VBtn @click="addNewItem" color="success">
              <VIcon icon="tabler-plus" class="me-2" />
              Thêm đối tác đầu tiên
            </VBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCardItem>
  </VCard>

  <!-- Dialogs -->

  <FormDialog
    v-model:is-dialog-visible="isFormDialogVisible"
    @update:partner="loadData"
    :partnerGroup="props.partnerGroup"
  />
  <FormDialog
    v-model:is-edit-dialog-visible="isFormEditDialogVisible"
    :partnerId="partnerId"
    @update:partner="loadData"
    :partnerGroup="props.partnerGroup"
  />
  <AppConfirmDialog @on-submit="handleDelete" />
</template>

<script setup>
import axios from "axios";
import { ref, watch, onMounted } from "vue";
import { Head, router } from "@inertiajs/vue3";
import FormDialog from "./FormDialog.vue";
import AppConfirmDialog from "@/Components/shared/AppConfirmDialog.vue";
import {
  isOpenAppConfirmDialog,
  loadingAppConfirmDialog,
} from "@/composables/dialog";
const props = defineProps({
  currentTab: [String, Number],
  partnerGroup: Object,
  property_id: Number,
});

const emit = defineEmits(["update:stats"]);
const isFormDialogVisible = ref(false);
const isFormEditDialogVisible = ref(false);
const page = ref(1);
const itemsPerPage = ref(10);
const saleTAData = ref({ data: [], total: 0 });
const loading = ref(false);
const partnerId = ref();
const headers = [
  { title: "Tên đối tác", key: "name", sortable: false },
  { title: "Email", key: "email", sortable: false },
  { title: "Số điện thoại", key: "phone", sortable: false },
  { title: "Chiết khấu (%)", key: "commission", sortable: false },
  { title: "Trạng thái", key: "status", sortable: false },
  { title: "Thao tác", key: "actions", sortable: false },
];
const fetchData = async () => {
  // Chỉ fetch khi đang ở tab "1" (Travel Agent)
  if (props.currentTab !== "1" && props.currentTab !== 1) return;

  loading.value = true;
  try {
    const params = {
      type: "Sale TA",
      page: page.value,
      paginate: itemsPerPage.value,
    };

    // Chỉ thêm property_id vào params nếu có giá trị
    if (props.property_id !== null && props.property_id !== undefined) {
      params.property_id = props.property_id;
    }

    const res = await axios.get(route("partner.loadDataSaleTA"), { params });

    saleTAData.value = {
      data: res.data.data,
      total: res.data.total,
    };

    emit("update:stats");
  } catch (error) {
    console.error("Lỗi khi tải dữ liệu Sale TA:", error);
  } finally {
    loading.value = false;
  }
};
watch(
  () => props.property_id,
  (val) => {
    fetchData();
  },
  { immediate: true }
);

const updateOptions = (options) => {
  page.value = options.page;
  itemsPerPage.value = options.itemsPerPage;
};
const addNewItem = () => {
  isFormDialogVisible.value = true;
};
const editPartner = (id) => {
  isFormEditDialogVisible.value = true;
  partnerId.value = id;
};
const selectedData = ref();
const deleteItem = (id) => {
  selectedData.value = id;
  isOpenAppConfirmDialog.value = true;
};
const loadData = () => {
  fetchData();
};

const handleDelete = () => {
  router.delete(route("partner.destroy", { id: selectedData.value }), {
    onStart: () => {
      loadingAppConfirmDialog.value = true;
    },
    onFinish: () => {
      loadingAppConfirmDialog.value = false;
      isOpenAppConfirmDialog.value = false;
      fetchData();
    },
  });
};

// Helper methods
const getInitials = (name) => {
  if (!name) return "";
  return name
    .split(" ")
    .map((word) => word[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
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
  const index = name ? name.length % colors.length : 0;
  return colors[index];
};

const getCommissionColor = (commission) => {
  const value = parseFloat(commission);
  if (value < 5) return "error";
  if (value <= 10) return "warning";
  return "success";
};

watch(
  () => props.currentTab,
  (val) => {
    if (val === "1" || val === 1) {
      fetchData();
    }
  }
);
watch([page, itemsPerPage], () => {
  if ((props.currentTab === "1" || props.currentTab === 1) && !loading.value) {
    fetchData();
  }
});
</script>

<style scoped>
/* Action Header */
.action-header {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.05);
}

.add-button {
  box-shadow: 0 4px 12px rgba(var(--v-theme-success), 0.3);
  transition: all 0.3s ease;
}

.add-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(var(--v-theme-success), 0.4);
}

/* Data Table Card */
.data-table-card {
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.05);
  overflow: hidden;
}

/* Modern Data Table */
.modern-data-table {
  border-radius: 12px;
  overflow: hidden;
}

.modern-data-table :deep(.v-data-table__wrapper) {
  border-radius: 12px;
}

.modern-data-table :deep(.v-data-table-header) {
  background: rgba(var(--v-theme-surface-variant), 0.3);
}

.modern-data-table :deep(.v-data-table-header__content) {
  font-weight: 600;
}

.modern-data-table :deep(.v-data-table__tr:hover) {
  background: rgba(var(--v-theme-success), 0.02);
}

/* Avatar Styles */
.v-avatar {
  transition: all 0.3s ease;
}

.v-avatar:hover {
  transform: scale(1.1);
}

/* Chip Styles */
.v-chip {
  font-weight: 500;
  transition: all 0.3s ease;
}

.v-chip:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Button Styles */
.v-btn {
  transition: all 0.3s ease;
}

.v-btn:hover {
  transform: translateY(-1px);
}

/* Link Styles */
a {
  color: rgb(var(--v-theme-primary));
  transition: color 0.3s ease;
}

a:hover {
  color: rgb(var(--v-theme-primary-darken-1));
}

/* Loading State */
.v-skeleton-loader {
  border-radius: 8px;
}

/* Responsive */
@media (max-width: 768px) {
  .action-header .d-flex {
    flex-direction: column;
    gap: 1rem;
  }

  .add-button {
    width: 100%;
  }
}

/* Animation for new items */
@keyframes slideInUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.v-data-table__tr {
  animation: slideInUp 0.3s ease;
}

/* Tooltip styles */
.v-tooltip .v-overlay__content {
  background: rgba(var(--v-theme-surface-variant), 0.95);
  backdrop-filter: blur(10px);
  border-radius: 8px;
  font-size: 0.75rem;
  padding: 4px 8px;
}
</style>
