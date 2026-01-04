<template>
  <VCard class="mt-5">
    <VCardItem>
      <VCardTitle> Hóa đơn </VCardTitle>
      <VDataTableServer
        :headers="headers"
        :items="invoiceHistories"
        item-value="id"
        class="text-no-wrap"
      >
        <template #item.partner_id="{ item }">
          {{ item?.partner ? item.partner?.name : "" }}
        </template>
        <template #item.total_amount="{ item }">
          {{ formatCurrency(item.total_amount) }}
        </template>
        <template #item.issued_date="{ item }">
          {{ formatDate(item.issued_date) }}
        </template>
        <template #item.actions="{ item }">
          <VIcon @click="viewItem(item)" icon="tabler-eye" />
        </template>

        <template #bottom> </template>
      </VDataTableServer>
    </VCardItem>
  </VCard>
  <VDialog v-model="isDialogVisible" width="500">
    <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />
    <VCard title="Chi tiết thanh toán">
      <VTable>
        <thead>
          <tr>
            <th>Booking ID</th>
            <th>Số tiền</th>
            <th>Ngày thanh toán</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in selectedInvoice?.histories || []" :key="item.id">
            <td>{{ item.booking_id }}</td>
            <td>{{ formatCurrency(item.total_amount) }}</td>
            <td>{{ formatDate(selectedInvoice.issued_date) }}</td>
          </tr>
        </tbody>
      </VTable>
    </VCard>
  </VDialog>
</template>
<script setup>
const props = defineProps({
  invoiceHistories: Array,
});
import { ref } from "vue";
const selectedInvoice = ref(null);
const isDialogVisible = ref(false);
const headers = [
  { title: "invoice ID", key: "id", sortable: false },
  { title: "Đối tác", key: "partner_id", sortable: false },
  { title: "Đã thanh toán", key: "total_amount", sortable: false },
  { title: "Ngày thanh toán", key: "issued_date", sortable: false },
  { title: "Chi tiết", key: "actions", sortable: false },
];
const viewItem = (item) => {
  selectedInvoice.value = item;
  isDialogVisible.value = true;
};
</script>
