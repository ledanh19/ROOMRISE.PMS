<script setup>
const deliveryData = ref([]);
import { ref, onMounted } from "vue";

const loadRecentBookings = async () => {
  const res = await axios.get(route("bookings.getRecentBookings"));
  deliveryData.value = res.data;
};

onMounted(() => {
  loadRecentBookings();
});
</script>

<template>
  <VCard>
    <VCardItem title="Đặt Phòng Gần Đây"></VCardItem>

    <VCardText>
      <VList class="card-list">
        <VListItem v-for="(data, index) in deliveryData" :key="index">
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
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 1.5rem;
}
</style>
