<template>
  <Head title="Cài đặt giá | Room Rise" />
  <Layout>
    <!-- <VCard class="mb-4">
      <VCardText>
        <VRow>
          <VCol cols="3">
            <AppSelect
              v-model="filtersData.property_id"
              :items="propertyOptions"
              item-title="name"
              item-value="id"
              label="Chỗ nghỉ"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard> -->
    <!-- Header Section với gradient và shadow -->
    <VCard
      class="mb-6 border-0 shadow-lg rounded-lg"
      style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
    >
      <VCardItem class="pa-6">
        <div class="d-flex align-center justify-space-between mb-4">
          <div>
            <h1 class="text-h4 text-white font-weight-bold mb-2">
              <VIcon icon="tabler-currency-dollar" class="me-3" size="32" />
              Quản lý giá phòng
            </h1>
            <p class="text-white mb-0">Tổng cộng {{ data.total }} giá phòng</p>
          </div>
        </div>
      </VCardItem>
    </VCard>
    <RequiredSpecificProperty>
      <VCard class="border-0 shadow-lg rounded-xl" elevation="4">
        <VCardText
          class="pa-6 d-flex align-center justify-space-between flex-wrap gap-4"
        >
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
            placeholder="Tìm giá..."
            style="inline-size: 18rem"
            clearable
            color="primary"
          />
        </VCardText>
        <VDivider class="mb-4" />
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items-length="data.total"
          :headers="headers"
          :items="data.data"
          v-model:expanded="expanded"
          show-expand
          item-value="room_id"
          class="text-no-wrap"
          @update:options="updateOptions"
        >
          <template #item.name="{ value, item }">
            <div>
              <span class="font-weight-bold text-primary">{{ value }}</span>
              <VBtn
                @click="addRatePlan(item)"
                class="ml-2"
                variant="primary"
                size="x-small"
                icon="tabler-plus"
              />
            </div>
          </template>
          <template #expanded-row="{ item }">
            <template v-if="item.ratePlans?.length">
              <tr v-for="(ratePlan, i) in item.ratePlans" :key="i">
                <td class="">
                  {{ ratePlan.title }}
                </td>
                <td class="">
                  {{ formatCurrency(ratePlan.price, item.currency) || "-" }}
                </td>
                <td>
                  {{ getMealTypeLabel(ratePlan.meal_type) }}
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1">
                    <VChip
                      v-for="bookingSource in ratePlan.booking_sources"
                      :key="bookingSource.id"
                      size="small"
                      color="primary"
                      variant="tonal"
                    >
                      {{ bookingSource.name }}
                    </VChip>
                    <span
                      v-if="!ratePlan.booking_sources?.length"
                      class="text-caption text-medium-emphasis"
                    >
                      Không có
                    </span>
                  </div>
                </td>
                <td class="pl-1">
                  <VBtn
                    icon
                    size="small"
                    color="medium-emphasis"
                    variant="text"
                    @click="editItem(ratePlan)"
                  >
                    <VIcon size="22" icon="tabler-edit" />
                  </VBtn>
                  <VBtn
                    icon
                    size="small"
                    color="medium-emphasis"
                    variant="text"
                    @click="deleteItem(ratePlan)"
                  >
                    <VIcon size="22" icon="tabler-trash" />
                  </VBtn>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="12" class="text-center text-caption">
                Không có dữ liệu
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
      </VCard>
    </RequiredSpecificProperty>
    <RatePlanFormDialog
      v-model:is-dialog-visible="isFormDialogVisible"
      :roomInfo="roomInfo"
      :data="selectedRatePlan"
      :booking-sources="bookingSources"
    />
    <AppConfirmDialog @on-submit="handleDelete" />
  </Layout>
</template>

<script setup>
import TablePagination from "@/@core/components/TablePagination.vue";
import RequiredSpecificProperty from "@/Components/properties/RequiredSpecificProperty.vue";
import RatePlanFormDialog from "@/Components/rate-plans/RatePlanFormDialog.vue";
import AppConfirmDialog from "@/Components/shared/AppConfirmDialog.vue";
import {
  isOpenAppConfirmDialog,
  loadingAppConfirmDialog,
} from "@/composables/dialog";
import Layout from "@/layouts/blank.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { getMealTypeLabel, PAGINATION_OPTIONS } from "@/utils/constants";
import { formatCurrency } from "@/utils/formatters";
import { Head, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { onMounted, ref, watch } from "vue";

const propertyStore = usePropertyStore();

const props = defineProps({
  data: Object,
  propertyOptions: Array,
  filters: Object,
  bookingSources: Array,
});

const headers = [
  { title: "Tên", key: "name", sortable: false },
  { title: "Giá", key: "note", sortable: false },
  { title: "Loại Bữa Ăn", key: "meal_type", sortable: false },
  { title: "Nguồn đặt phòng", key: "booking_sources", sortable: false },
];

const search = ref(props.filters.search || "");
const page = ref(props.data.current_page);
const itemsPerPage = ref(props.data.per_page);
const expanded = ref(props.data.data.map((item) => item.room_id));

// ✅ Lấy property từ store để đồng bộ
const filtersData = ref({
  property_id: propertyStore.selectedProperty || null,
});

const fetchData = () => {
  router.get(
    route("rate-plans.index"),
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

// ✅ Khi user đổi property trong trang này → update store + fetch
watch(
  () => filtersData.value.property_id,
  (val) => {
    propertyStore.setProperty(val);
    fetchData();
  }
);

// ✅ Khi store đổi (Navbar chọn) → update filtersData + fetch
watch(
  () => propertyStore.selectedProperty,
  (val) => {
    if (filtersData.value.property_id !== val) {
      filtersData.value.property_id = val;
      fetchData();
    }
  }
);

// ✅ Gọi API khi load trang lần đầu theo store
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
const roomInfo = ref();
const selectedRatePlan = ref();

const addRatePlan = (item) => {
  console.log("roomInfo", item);
  roomInfo.value = item;
  selectedRatePlan.value = null;
  isFormDialogVisible.value = true;
};

const editItem = (item) => {
  console.log("roomInfo", item);

  roomInfo.value = item;
  selectedRatePlan.value = item;
  isFormDialogVisible.value = true;
};

const deleteItem = async (item) => {
  selectedRatePlan.value = item;
  isOpenAppConfirmDialog.value = true;
};

const handleDelete = () => {
  router.delete(route("rate-plans.destroy", selectedRatePlan.value.id), {
    onStart: () => {
      loadingAppConfirmDialog.value = true;
    },
    onFinish: () => {
      loadingAppConfirmDialog.value = false;
      isOpenAppConfirmDialog.value = false;
    },
  });
};
</script>
