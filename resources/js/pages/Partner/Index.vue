<template>
  <Head title="Đối tác | Room Rise" />
  <Layout>
    <!-- Header Section -->
    <div class="header-section mb-6">
      <VCard class="gradient-header">
        <VCardItem>
          <div class="d-flex justify-space-between align-center">
            <div class="header-content">
              <VIcon icon="tabler-users" size="48" color="white" class="mb-3" />
              <h2 class="text-white mb-2 font-weight-bold">Quản lý đối tác</h2>
              <p class="text-white mb-0">
                Quản lý các thông tin đối tác và hợp tác kinh doanh
              </p>
            </div>
            <div class="header-stats">
              <VCard class="stats-card">
                <VCardText class="text-center stats-card-content">
                  <VIcon
                    icon="tabler-briefcase"
                    size="24"
                    color="primary"
                    class="mb-2"
                  />
                  <div class="font-weight-bold stats-value">
                    {{ totalPartners }}
                  </div>
                  <div class="stats-label">Tổng đối tác</div>
                </VCardText>
              </VCard>
            </div>
          </div>
        </VCardItem>
      </VCard>
    </div>

    <!-- Quick Stats -->
    <VRow class="mb-6">
      <VCol cols="12" md="6">
        <VCard class="stats-overview">
          <VCardText class="text-center">
            <VAvatar color="primary" variant="tonal" size="48" class="mb-3">
              <VIcon icon="tabler-chart-line" size="24" />
            </VAvatar>
            <div class="font-weight-bold text-primary">
              {{ salePartners }}
            </div>
            <div class="text-medium-emphasis">Đối tác Sale</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard class="stats-overview">
          <VCardText class="text-center">
            <VAvatar color="success" variant="tonal" size="48" class="mb-3">
              <VIcon icon="tabler-plane" size="24" />
            </VAvatar>
            <div class="font-weight-bold text-success">
              {{ travelAgents }}
            </div>
            <div class="text-medium-emphasis">Travel Agent</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Tabs Section -->
    <VCard class="tabs-container">
      <VCardItem>
        <VTabs v-model="currentTab" class="modern-tabs" grow>
          <VTab value="0" class="tab-item">
            <VIcon icon="tabler-chart-line" class="me-2" />
            Đối tác Sale
            <VChip size="small" color="primary" variant="tonal" class="ms-2">
              {{ salePartners }}
            </VChip>
          </VTab>
          <VTab value="1" class="tab-item">
            <VIcon icon="tabler-plane" class="me-2" />
            Travel Agent
            <VChip size="small" color="success" variant="tonal" class="ms-2">
              {{ travelAgents }}
            </VChip>
          </VTab>
        </VTabs>
      </VCardItem>
    </VCard>

    <!-- Content Window -->
    <VWindow v-model="currentTab" class="mt-6">
      <VWindowItem value="0">
        <DataSale
          :currentTab="currentTab"
          :partnerGroup="partnerGroup"
          :property_id="property_id"
          @update:stats="updateStats"
        />
      </VWindowItem>
      <VWindowItem value="1">
        <DataSaleTA
          :currentTab="currentTab"
          :partnerGroup="partnerGroup"
          :property_id="property_id"
          @update:stats="updateStats"
        />
      </VWindowItem>
    </VWindow>
  </Layout>
</template>

<script setup>
import Layout from "@/layouts/blank.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import { computed, ref, watch } from "vue";
import DataSale from "./DataSale.vue";
import DataSaleTA from "./DataSaleTA.vue";
const propertyStore = usePropertyStore();

const currentTab = ref("0");

const props = defineProps({
  partnerGroup: Object,
});

// Sử dụng computed để theo dõi thay đổi property_id từ store
const property_id = computed(() => propertyStore.selectedProperty);

// Watch để theo dõi thay đổi property_id

// Stats data
const stats = ref({
  salePartners: 0,
  travelAgents: 0,
});

// Computed values
const totalPartners = computed(
  () => stats.value.salePartners + stats.value.travelAgents
);
const salePartners = computed(() => stats.value.salePartners);
const travelAgents = computed(() => stats.value.travelAgents);

// Load stats
const loadStats = async () => {
  try {
    const params = {
      type: "Sale",
      page: 1,
      paginate: 999,
    };

    // Thêm property_id vào params nếu có
    if (property_id.value) {
      params.property_id = property_id.value;
    }

    const [saleResponse, taResponse] = await Promise.all([
      axios.get(route("partner.loadDataSale"), { params }),
      axios.get(route("partner.loadDataSaleTA"), {
        params: { ...params, type: "Sale TA" },
      }),
    ]);

    const saleData = saleResponse.data.data || [];
    const taData = taResponse.data.data || [];

    stats.value = {
      salePartners: saleData.length,
      travelAgents: taData.length,
    };
  } catch (error) {
    console.error("Lỗi khi tải thống kê:", error);
  }
};

const updateStats = () => {
  loadStats();
};
watch(
  property_id,
  (newValue) => {
    console.log("Property ID changed:", newValue);
    // Reload stats khi property_id thay đổi
    loadStats();
  },
  { immediate: true }
);
</script>
<style scoped>
/* Header Gradient */
.gradient-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px !important;
  position: relative;
  overflow: hidden;
}

.gradient-header::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.header-content {
  position: relative;
  z-index: 2;
}

.header-stats {
  position: relative;
  z-index: 2;
}

.stats-card {
  background: rgba(var(--v-theme-surface), 0.95);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  min-width: 120px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}

.stats-card-content {
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}

.stats-value {
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  font-size: 1.5rem;
}

.stats-label {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 0.875rem;
}

/* Stats Overview Cards */
.stats-overview {
  border-radius: 12px;
  transition: all 0.3s ease;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.05);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.stats-overview:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

/* Modern Tabs */
.tabs-container {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.05);
}

.modern-tabs {
  background: transparent;
}

.modern-tabs .v-tab {
  border-radius: 8px;
  margin: 0 4px;
  transition: all 0.3s ease;
  text-transform: none;
  font-weight: 500;
}

.modern-tabs .v-tab:hover {
  background-color: rgba(var(--v-theme-primary), 0.04);
}

.modern-tabs .v-tab--selected {
  background-color: rgba(var(--v-theme-primary), 0.08);
  color: rgb(var(--v-theme-primary));
}

.tab-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* Slide Group Navigation Hidden */
.v-slide-group__prev,
.v-slide-group__next {
  display: none;
}

/* Status Colors */
.text-blue {
  color: rgb(var(--v-theme-primary));
}

.text-success {
  color: rgb(var(--v-theme-success));
}

.text-error {
  color: rgb(var(--v-theme-error));
}

/* Card Animations */
.v-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Responsive Design */
@media (max-width: 768px) {
  .header-section .d-flex {
    flex-direction: column;
    gap: 1rem;
  }

  .header-stats {
    align-self: stretch;
  }

  .stats-card {
    width: 100%;
  }
}

/* Loading States */
.v-skeleton-loader {
  border-radius: 12px;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: rgba(var(--v-theme-on-surface), 0.05);
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-on-surface), 0.2);
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(var(--v-theme-on-surface), 0.3);
}
</style>
