<template>
  <Head title="Nguồn đặt phòng | Room Rise" />
  <Layout>
    <VCard>
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

        <div class="d-flex align-center gap-4 flex-wrap">
          <AppTextField
            v-model="search"
            placeholder="Tìm kiếm nguồn đặt phòng"
            style="inline-size: 15.625rem"
          />
          <VBtn
            density="default"
            prepend-icon="tabler-plus"
            @click="addNewItem"
          >
            Thêm nguồn đặt phòng
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items-length="data.total"
        :headers="headers"
        :items="data.data"
        item-value="id"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.is_default="{ item }">
          <VChip :color="item.is_default ? 'success' : 'default'" size="small">
            {{ item.is_default ? "Mặc định" : "Không" }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <VBtn
            icon
            size="small"
            color="medium-emphasis"
            variant="text"
            @click="editItem(item)"
          >
            <VIcon size="22" icon="tabler-edit" />
          </VBtn>
          <VBtn
            icon
            size="small"
            color="medium-emphasis"
            variant="text"
            @click="deleteItem(item)"
          >
            <VIcon size="22" icon="tabler-trash" />
          </VBtn>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="data.total"
          />
        </template>
      </VDataTableServer>
    </VCard>
    <BookingSourceFormDialog
      v-model:is-dialog-visible="isFormDialogVisible"
      :data="selectedData"
    />
    <AppConfirmDialog @on-submit="handleDelete" />
  </Layout>
</template>

<script setup>
import BookingSourceFormDialog from "@/Components/booking-sources/BookingSourceFormDialog.vue";

import TablePagination from "@/@core/components/TablePagination.vue";
import AppConfirmDialog from "@/Components/shared/AppConfirmDialog.vue";
import {
  isOpenAppConfirmDialog,
  loadingAppConfirmDialog,
} from "@/composables/dialog";
import Layout from "@/layouts/blank.vue";
import { PAGINATION_OPTIONS } from "@/utils/constants";
import { Head, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { ref, watch } from "vue";

const props = defineProps({
  data: Object,
  filters: Object,
});

const headers = [
  { title: "Tên nguồn đặt phòng", key: "name", sortable: false },
  { title: "Phần trăm giá", key: "price_percentage", sortable: false },
  { title: "Thao tác", key: "actions", sortable: false },
];

const search = ref(props.filters.search || "");
const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);

const fetchData = () => {
  router.get(
    route("booking-sources.index"),
    {
      search: search.value,
      page: page.value,
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
    route("booking-sources.destroy", { booking_source: selectedData.value.id }),
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
