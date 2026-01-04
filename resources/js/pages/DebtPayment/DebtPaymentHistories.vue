<template>
  <VDialog
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
    class="v-dialog-sm"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard title="Lịch sử thanh toán">
      <VCardText>
        <VTable>
          <thead>
            <tr>
              <th>Số tiền</th>
              <th>Ngày thanh toán</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in histories" :key="item.id">
              <td>{{ formatCurrency(item.paid) }}</td>
              <td>{{ formatDate(item.created_at) }}</td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn variant="tonal" color="secondary" @click="onReset"> Close </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
<script setup>
import { computed, ref, watch } from "vue";
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  historyTargetId: Number,
});
const histories = ref([]);
const emit = defineEmits(["update:isDialogVisible"]);
const onReset = () => {
  emit("update:isDialogVisible", false);
};

watch(
  () => props.isDialogVisible,

  (isVisible) => {
    if (!isVisible) return;
  }
);
watch(
  () => props.historyTargetId,
  async (id) => {
    if (!id) {
      histories.value = [];
      return;
    }
    const res = await axios.get(route("payment.historiesData", { id: id }));
    histories.value = res.data;
  }
);
</script>
