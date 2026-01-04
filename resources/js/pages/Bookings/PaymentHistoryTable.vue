<template>
  <div v-if="isMobile" class="payment-cards-mobile">
    <VCard
      v-for="item in payments"
      :key="getItemKey(item)"
      variant="outlined"
      class="mb-3 payment-card-mobile"
    >
      <VCardText class="pa-4">
        <div class="d-flex justify-space-between align-center mb-3">
          <VChip
            :color="item.type === 'income' ? 'success' : 'error'"
            size="small"
            variant="flat"
          >
            <VIcon
              :icon="item.type === 'income' ? 'tabler-plus' : 'tabler-minus'"
              size="14"
              class="me-1"
            />
            #{{ item.id }}
          </VChip>
          <div class="text-h6 font-weight-bold">
            {{ item.type === "income" ? "+" : "-"
            }}{{ formatCurrency(getAmount(item)) }}
          </div>
        </div>

        <VRow>
          <VCol cols="6">
            <div class="text-caption text-medium-emphasis">Phương thức</div>
            <div class="font-weight-medium">{{ item.payment_method }}</div>
          </VCol>
          <VCol cols="6">
            <div class="text-caption text-medium-emphasis">Ngày thanh toán</div>
            <div class="font-weight-medium">{{ formatDate(item.date) }}</div>
          </VCol>
        </VRow>

        <div class="mt-2">
          <div class="text-caption text-medium-emphasis">Ghi chú</div>
          <div class="font-weight-medium">{{ item.note || "–" }}</div>
        </div>
      </VCardText>
    </VCard>
  </div>

  <VTable v-else class="modern-table">
    <thead>
      <tr>
        <th class="table-header">Mã thanh toán</th>
        <th class="table-header">Phương thức</th>
        <th class="table-header">Số tiền</th>
        <th class="table-header">Ngày thanh toán</th>
        <th class="table-header">Nhân viên</th>
        <th class="table-header">Nội dung</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="item in payments" :key="getItemKey(item)" class="table-row">
        <td>
          <VChip
            :color="item.type === 'income' ? 'success' : 'error'"
            size="small"
            variant="tonal"
          >
            #{{ item.id }}
          </VChip>
        </td>
        <td>
          <div class="d-flex align-center">
            <VIcon
              :icon="getPaymentMethodIcon(item.payment_method)"
              size="16"
              class="me-2 text-medium-emphasis"
            />
            {{ item.payment_method }}
          </div>
        </td>
        <td>
          <VChip
            :color="item.type === 'income' ? 'success' : 'error'"
            size="small"
            variant="flat"
            class="font-weight-bold"
          >
            <VIcon
              :icon="item.type === 'income' ? 'tabler-plus' : 'tabler-minus'"
              size="14"
              class="me-1"
            />
            {{ formatCurrency(getAmount(item)) }}
          </VChip>
        </td>
        <td>{{ formatDate(item.date) }}</td>
        <td>{{ item.created_by }}</td>
        <td>{{ item.note || "–" }}</td>
      </tr>
    </tbody>
  </VTable>
</template>

<script setup>
const props = defineProps({
  payments: Array,
  isMobile: Boolean,
  isPartner: Boolean,
  isOta: Boolean,
});

const getItemKey = (item) => {
  return `${props.isPartner ? "partner" : props.isOta ? "ota" : "payment"}-${
    item.id
  }`;
};

const getAmount = (item) => {
  return props.isPartner || props.isOta ? item.pivot?.amount : item.amount;
};

const getPaymentMethodIcon = (method) => {
  const iconMap = {
    "Tiền mặt": "tabler-cash",
    "Chuyển khoản": "tabler-building-bank",
    "QR Code": "tabler-qrcode",
    Momo: "tabler-brand-paypal",
    VNPay: "tabler-credit-card",
    "Thẻ tín dụng": "tabler-credit-card",
  };
  return iconMap[method] || "tabler-currency-dollar";
};
</script>

<style scoped>
.payment-card-mobile {
  border-radius: 12px;
  transition: all 0.2s ease;
}

.payment-card-mobile:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
