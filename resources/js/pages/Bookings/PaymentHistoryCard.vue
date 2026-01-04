<template>
  <VCard class="modern-card" elevation="2">
    <VCardTitle class="card-header">
      <VAvatar size="40" :color="color" variant="tonal" class="me-3">
        <VIcon :icon="icon" />
      </VAvatar>
      <div>
        <div class="text-h5 font-weight-bold">{{ title }}</div>
        <div class="text-body-2 text-medium-emphasis">
          {{ payments?.length || 0 }} giao dịch
        </div>
      </div>
      <VSpacer />
      <VChip :color="color" variant="tonal" size="small">
        {{ payments?.length || 0 }} giao dịch
      </VChip>
    </VCardTitle>

    <VCardText v-if="payments?.length">
      <!-- Mobile Cards -->
      <div v-if="$vuetify.display.smAndDown" class="payment-cards">
        <VCard
          v-for="payment in payments"
          :key="getPaymentKey(payment)"
          variant="outlined"
          class="payment-card mb-3"
        >
          <VCardText>
            <div class="d-flex justify-space-between align-center mb-3">
              <VChip :color="color" size="small" variant="tonal">
                #{{ payment.id }}
              </VChip>
              <div class="text-h6 font-weight-bold">
                {{ formatCurrency(getAmount(payment)) }}
              </div>
            </div>

            <VRow>
              <VCol cols="6">
                <div class="payment-detail">
                  <div class="payment-detail-label">Phương thức</div>
                  <div class="payment-detail-value">
                    {{ payment.payment_method }}
                  </div>
                </div>
              </VCol>
              <VCol cols="6">
                <div class="payment-detail">
                  <div class="payment-detail-label">Ngày</div>
                  <div class="payment-detail-value">
                    {{ formatDate(payment.date) }}
                  </div>
                </div>
              </VCol>
            </VRow>

            <div class="payment-detail mt-2">
              <div class="payment-detail-label">Ghi chú</div>
              <div class="payment-detail-value">{{ payment.note || "–" }}</div>
            </div>
          </VCardText>
        </VCard>
      </div>

      <!-- Desktop Table -->
      <VTable v-else class="modern-table">
        <thead>
          <tr>
            <th class="table-header">Mã thanh toán</th>
            <th class="table-header">Phương thức</th>
            <th class="table-header">Số tiền</th>
            <th class="table-header">Ngày</th>
            <th class="table-header">Nhân viên</th>
            <th class="table-header">Ghi chú</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="payment in payments"
            :key="getPaymentKey(payment)"
            class="table-row"
          >
            <td>
              <VChip :color="color" size="small" variant="tonal">
                #{{ payment.id }}
              </VChip>
            </td>
            <td>{{ payment.payment_method }}</td>
            <td>
              <div class="text-h6 font-weight-bold">
                {{ formatCurrency(getAmount(payment)) }}
              </div>
            </td>
            <td>{{ formatDate(payment.date) }}</td>
            <td>{{ payment.created_by }}</td>
            <td>{{ payment.note || "–" }}</td>
          </tr>
        </tbody>
      </VTable>
    </VCardText>

    <VCardText v-else class="text-center py-8">
      <VIcon :icon="icon" size="64" class="text-disabled mb-4" />
      <div class="text-h6 text-disabled mb-2">Chưa có giao dịch nào</div>
      <div class="text-body-2 text-medium-emphasis">
        Lịch sử giao dịch sẽ được hiển thị ở đây
      </div>
    </VCardText>
  </VCard>
</template>

<script setup>
const props = defineProps({
  title: String,
  payments: Array,
  icon: String,
  color: String,
  isPartner: Boolean,
  isOta: Boolean,
});

const getPaymentKey = (payment) => {
  const prefix = props.isPartner ? "partner" : props.isOta ? "ota" : "payment";
  return `${prefix}-${payment.id}`;
};

const getAmount = (payment) => {
  return props.isPartner || props.isOta
    ? payment.pivot?.amount
    : payment.amount;
};
</script>

<style scoped>
.payment-cards {
  display: grid;
  gap: 12px;
}

.payment-card {
  border-radius: 12px;
  transition: all 0.2s ease;
}

.payment-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.payment-detail {
  margin-bottom: 8px;
}

.payment-detail-label {
  font-size: 0.75rem;
  color: rgb(var(--v-theme-on-surface-variant));
  margin-bottom: 2px;
}

.payment-detail-value {
  font-weight: 500;
  font-size: 0.875rem;
}
</style>
