<template>
  <VCard class="mt-5">
    <VCardItem>
      <VRow>
        <VCol cols="12" sm="12" md="4" lg="4">
          <div class="item">
            <div class="content">
              <p>Tổng số booking</p>
              <br />
              <h2 class="text-blue">
                {{ props.totalBookings }}
              </h2>
            </div>
            <div class="icon">
              <VIcon color="blue" icon="tabler-file-description"></VIcon>
            </div>
          </div>
        </VCol>
        <VCol cols="12" sm="12" md="4" lg="4">
          <div class="item">
            <div class="content">
              <p>Tổng doanh thu gross</p>
              <br />
              <h2 class="text-success">
                {{ formatCurrency(props.totalGross) }}
              </h2>
            </div>
            <div class="icon">
              <VIcon color="success" icon="tabler-currency-dollar"></VIcon>
            </div>
          </div>
        </VCol>
        <VCol cols="12" sm="12" md="4" lg="4">
          <div class="item">
            <div class="content">
              <p>Tổng net dự kiến</p>
              <br />
              <h2 class="text-purple">
                {{ formatCurrency(props.totalNet) }}
              </h2>
            </div>
            <div class="icon">
              <VIcon color="purple" icon="tabler-trending-up"></VIcon>
            </div>
          </div>
        </VCol>
        <VCol cols="12" sm="12" md="4" lg="4">
          <div class="item">
            <div class="content">
              <p>Tổng chênh lệch</p>
              <br />
              <h2 class="text-error">
                {{ formatCurrency(props.totalDifference) }}
              </h2>
            </div>
            <div class="icon">
              <VIcon color="error" icon="tabler-trending-down"></VIcon>
            </div>
          </div>
        </VCol>
        <VCol cols="12" sm="12" md="4" lg="4">
          <div class="item">
            <div class="content">
              <p>Đã quyết toán</p>
              <br />
              <h2 class="text-success">
                {{ props.totalSettledBookings }}
              </h2>
            </div>
            <div class="icon">
              <VIcon color="success" icon="tabler-circle-check"></VIcon>
            </div>
          </div>
        </VCol>
        <VCol cols="12" sm="12" md="4" lg="4">
          <div class="item">
            <div class="content">
              <p>Chờ quyết toán</p>
              <br />
              <h2 class="text-warning">
                {{ props.totalPendingBookings }}
              </h2>
            </div>
            <div class="icon">
              <VIcon color="warning" icon="tabler-clock-hour-4"></VIcon>
            </div>
          </div>
        </VCol>
      </VRow>
    </VCardItem>
  </VCard>
</template>
<script setup>
const props = defineProps({
  totalBookings: String,
  totalDifference: String,
  totalNet: String,
  totalGross: String,
  totalSettledBookings: String,
  totalPendingBookings: String,
});
</script>
<style scoped>
.item {
  display: flex;
  justify-content: space-between;
  border: 1px solid rgb(226, 232, 240);
  border-radius: 0.5rem;
  padding: 0.5rem;
  height: 100%;
}
</style>
