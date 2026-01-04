<script setup>
import { ref, watch, onMounted } from "vue";
import {
  VDialog,
  VCard,
  VCardTitle,
  VCardText,
  VCardActions,
  VBtn,
  VSpacer,
  VCheckbox,
  VIcon,
} from "vuetify/components";
import draggable from "vuedraggable";

const props = defineProps({
  modelValue: Boolean, // hiển thị dialog
  headers: {
    type: Array,
    required: true,
  },
});

const emit = defineEmits(["update:modelValue", "update:headers"]);

const STORAGE_KEY_HEADERS = "hotel_collect_custom_headers";
const STORAGE_KEY_VISIBLE = "hotel_collect_visible_columns";

const allHeaders = ref([...props.headers]);
const selectedColumns = ref(props.headers.map((h) => h.key));

// Khôi phục từ localStorage
onMounted(() => {
  try {
    const savedHeaders = JSON.parse(localStorage.getItem(STORAGE_KEY_HEADERS));
    const savedVisible = JSON.parse(localStorage.getItem(STORAGE_KEY_VISIBLE));

    if (Array.isArray(savedHeaders) && savedHeaders.every((h) => h.key)) {
      allHeaders.value = savedHeaders;
    }

    if (Array.isArray(savedVisible)) {
      selectedColumns.value = savedVisible;
    }

    emitVisibleHeaders();
  } catch (e) {
    console.warn("Lỗi đọc localStorage:", e);
  }
});

const emitVisibleHeaders = () => {
  const visible = allHeaders.value.filter((h) =>
    selectedColumns.value.includes(h.key)
  );
  emit("update:headers", visible);
};

// Lưu & emit khi thay đổi
watch(
  allHeaders,
  (val) => {
    localStorage.setItem(STORAGE_KEY_HEADERS, JSON.stringify(val));
    emitVisibleHeaders();
  },
  { deep: true }
);

watch(
  selectedColumns,
  (val) => {
    localStorage.setItem(STORAGE_KEY_VISIBLE, JSON.stringify(val));
    emitVisibleHeaders();
  },
  { deep: true }
);
</script>

<template>
  <VDialog
    :model-value="modelValue"
    @update:modelValue="$emit('update:modelValue', $event)"
    max-width="500px"
  >
    <VCard>
      <VCardTitle class="text-h6">Tùy chỉnh hiển thị và sắp xếp cột</VCardTitle>
      <VCardText>
        <draggable
          v-model="allHeaders"
          item-key="key"
          handle=".drag-handle"
          animation="200"
        >
          <template #item="{ element }">
            <div class="d-flex align-center mb-2">
              <VIcon icon="tabler-menu-2" class="mr-2 drag-handle" />
              <VCheckbox
                :label="element.title || 'Hành động'"
                :value="element.key"
                v-model="selectedColumns"
                hide-details
                density="compact"
                class="flex-grow-1"
              />
            </div>
          </template>
        </draggable>
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn color="primary" @click="$emit('update:modelValue', false)"
          >Đóng</VBtn
        >
      </VCardActions>
    </VCard>
  </VDialog>
</template>
