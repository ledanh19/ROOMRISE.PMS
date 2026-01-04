<script setup>
const vehicleData = ref([]);
const availablePercentage = ref("");
const bookedPercentage = ref("");
import { ref, onMounted } from "vue";
const loadVehicleData = async () => {
  const res = await axios.get(route("bookings.getVehicleData"));

  vehicleData.value = res.data.data;
  availablePercentage.value = res.data.availablePercentage;
  bookedPercentage.value = res.data.bookedPercentage;
};
onMounted(loadVehicleData);
</script>

<template>
  <VCard>
    <VCardItem title="Dữ Liệu Lấp Đầy Phòng">
      <template #append>
        <MoreBtn />
      </template>
    </VCardItem>
    <VCardText>
      <div class="d-flex justify-space-between">
        <div
          class="vehicle-progress-label position-relative mb-6 text-body-1 d-none d-sm-block"
        >
          Còn trống
        </div>
        <div
          class="vehicle-progress-label position-relative mb-6 text-body-1 d-none d-sm-block"
        >
          Đã đặt
        </div>
      </div>
      <div class="d-flex mb-6">
        <div :style="`inline-size: ${availablePercentage}%`">
          <VProgressLinear
            color="rgba(var(--v-theme-on-surface), var(--v-hover-opacity))"
            model-value="100"
            height="46"
            class="rounded-e-0 rounded-lg"
          >
            <div class="text-start text-sm font-weight-medium">
              {{ availablePercentage }}%
            </div>
          </VProgressLinear>
        </div>
        <div
          v-if="bookedPercentage"
          :style="`inline-size: ${bookedPercentage}%`"
        >
          <VProgressLinear
            color="rgb(var(--v-tooltip-background))"
            model-value="100"
            height="46"
            class="rounded-s-0 rounded-lg"
          >
            <div class="text-sm text-surface font-weight-medium text-start">
              {{ bookedPercentage }}%
            </div>
          </VProgressLinear>
        </div>
      </div>
      <VTable class="text-no-wrap">
        <tbody>
          <tr v-for="(vehicle, index) in vehicleData" :key="index">
            <td width="70%" style="padding-inline-start: 0 !important">
              <div class="d-flex align-center gap-x-2">
                <VIcon
                  :icon="vehicle.icon"
                  size="24"
                  class="text-high-emphasis"
                />
                <div class="text-body-1 text-high-emphasis">
                  {{ vehicle.title }}
                </div>
              </div>
            </td>
            <td>
              <h6 class="text-h6">
                {{ vehicle.time }}
              </h6>
            </td>
            <td>
              <div class="text-body-1">{{ vehicle.percentage }}%</div>
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.vehicle-progress-label {
  padding-block-end: 1rem;

  &::after {
    position: absolute;
    display: inline-block;
    background-color: rgba(var(--v-theme-on-surface), var(--v-border-opacity));
    block-size: 10px;
    content: "";
    inline-size: 2px;
    inset-block-end: 0;
    inset-inline-start: 0;

    [dir="rtl"] & {
      inset-inline: unset 0;
    }
  }
}
</style>

<style lang="scss">
.v-progress-linear__content {
  justify-content: start;
  padding-inline-start: 1rem;
}

#shipment-statistics .apexcharts-legend-series {
  padding-inline: 16px;
}

@media (max-width: 1080px) {
  #shipment-statistics .apexcharts-legend-series {
    padding-inline: 12px;
  }

  .v-progress-linear__content {
    padding-inline-start: 0.75rem !important;
  }
}

@media (max-width: 576px) {
  #shipment-statistics .apexcharts-legend-series {
    padding-inline: 8px;
  }

  .v-progress-linear__content {
    padding-inline-start: 0.125rem !important;
  }
}
</style>
