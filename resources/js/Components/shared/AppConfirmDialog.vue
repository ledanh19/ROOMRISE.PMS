<template>
  <!-- v-model="show" -->
  <VDialog max-width="400" v-model="isOpenAppConfirmDialog" persistent>
    <template #default="{ isActive }">
      <VCard rounded="lg" class="py-4 px-12">
        <div class="d-flex justify-center">
          <v-icon
            class="mb-6"
            color="warning"
            icon="tabler-alert-circle"
            size="128"
          ></v-icon>
        </div>
        <div
          v-if="title"
          class="text-h5 text-medium-emphasis font-weight-bold text-center text my-3"
        >
          {{ title }}
        </div>
        <div>
          <slot> </slot>
        </div>
        <div class="my-2 d-flex ga-4 justify-center">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isActive.value = false"
            :disabled="loadingAppConfirmDialog"
            >Hủy</VBtn
          >

          <VBtn
            :loading="loadingAppConfirmDialog"
            :disabled="loadingAppConfirmDialog || isDisabled"
            @click="emit('onSubmit')"
            >Đồng ý</VBtn
          >
        </div>
      </VCard>
    </template>
  </VDialog>
</template>

<script setup>
defineProps({
  isDisabled: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: "Bạn có muốn xóa dòng này không?",
  },
});

import {
  isOpenAppConfirmDialog,
  loadingAppConfirmDialog,
} from "@/composables/dialog";

const emit = defineEmits(["onSubmit"]);
</script>
