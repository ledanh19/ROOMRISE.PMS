<script setup>
const currentTab = ref("New");

const tabsData = ["Check-in", "Check-out"];
const checkIn = ref([]);
const checkOut = ref([]);
import { ref, onMounted } from "vue";
const loadCheckInData = async () => {
  const res = await axios.get(route("bookings.getCheckInData")); 
  checkIn.value = res.data;
};
const loadCheckOutData = async () => {
  const res = await axios.get(route("bookings.getCheckOutData"));
  checkOut.value = res.data;
};
onMounted(() => {
  loadCheckInData();
  loadCheckOutData();
});
</script>

<template>
  <VCard class="country-order-card">
    <VCardItem title="Check-in/Check-out hôm nay"> </VCardItem>

    <VTabs v-model="currentTab" grow class="disable-tab-transition">
      <VTab v-for="(tab, index) in tabsData" :key="index">
        {{ tab }}
      </VTab>
    </VTabs>

    <VCardText>
      <VWindow v-model="currentTab">
        <VWindowItem>
          <VList class="card-list">
            <VListItem v-for="(data, index) in checkIn" :key="index">
              <VListItemTitle class="me-2 font-weight-bold">
                {{ data.title }}
              </VListItemTitle>

              <VListItemSubtitle>
                <div class="d-flex align-center gap-x-1">
                  <div>{{ data.desc }}</div>
                </div>
              </VListItemSubtitle>

              <template #append>
                <span
                  :class="data.color ? 'bg-' + data.color : 'bg-error'"
                  class="text-body-1 font-weight-medium px-4 py-4"
                >
                  {{ data.value }}
                </span>
              </template>
            </VListItem>
          </VList>
        </VWindowItem>

        <VWindowItem>
          <VList class="card-list">
            <VListItem v-for="(data, index) in checkOut" :key="index">
              <VListItemTitle class="me-2 font-weight-bold">
                {{ data.title }}
              </VListItemTitle>

              <VListItemSubtitle>
                <div class="d-flex align-center gap-x-1">
                  <div>{{ data.desc }}</div>
                </div>
              </VListItemSubtitle>

              <template #append>
                <span
                  :class="data.color ? 'bg-' + data.color : 'bg-error'"
                  class="text-body-1 font-weight-medium px-4 py-4"
                >
                  {{ data.value }}
                </span>
              </template>
            </VListItem>
          </VList>
        </VWindowItem>
      </VWindow>
    </VCardText>
  </VCard>
</template>

<style lang="scss">
.country-order-card {
  .v-timeline .v-timeline-divider__dot .v-timeline-divider__inner-dot {
    box-shadow: none !important;
  }
}
</style>
