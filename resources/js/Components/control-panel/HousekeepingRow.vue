<template>
  <VCard class="room-row pa-4 mb-3">
    <VRow class="align-center" dense>
      <VCol cols="12" md="4">
        <div
          class="text-caption mt-1 text-medium-emphasis d-flex gap-3 flex-wrap"
        >
          <div class="room-type">{{ safeRoom.roomType }}</div>
        </div>
        <div class="room-link" :title="safeRoom.name">
          {{ safeRoom.name }}
        </div>
      </VCol>

      <VCol cols="12" md="4">
        <div class="d-flex flex-column align-center justify-center gap-2">
          <VChip
            size="small"
            density="comfortable"
            :color="availabilityColor(safeRoom.isAvailable)"
            variant="tonal"
            :title="`Availability: ${
              safeRoom.isAvailable ? 'Có sẵn' : 'Không sẵn'
            }`"
          >
            {{ safeRoom.isAvailable ? "Có sẵn" : "Không sẵn" }}
          </VChip>

          <VChip
            size="small"
            density="comfortable"
            :color="statusColor(roomUiStatus)"
            :variant="roomUiStatus === 'Đóng' ? 'flat' : 'tonal'"
            :title="`Trạng thái: ${roomUiStatus}`"
          >
            {{ roomUiStatus }}
          </VChip>
        </div>
      </VCol>

      <VCol cols="12" md="2" class="d-flex align-center justify-end gap-3">
        <span class="text-medium-emphasis">
          {{ safeRoom.notes || "-" }}
        </span>
      </VCol>

      <VCol cols="12" md="2" class="d-flex align-center justify-end gap-3">
        <VBtn
          variant="tonal"
          color="primary"
          class="icon-square"
          :ripple="false"
          aria-label="Edit housekeeping"
          @click="onEdit"
        >
          <VIcon icon="tabler-edit" size="20" />
        </VBtn>
      </VCol>
    </VRow>
  </VCard>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  room: { type: Object, required: true },
});
const emit = defineEmits(["edit"]);

const norm = (v) =>
  String(v ?? "")
    .trim()
    .toLowerCase();

const safeRoom = computed(() => ({
  id: props.room?.id ?? "",
  name: props.room?.name ?? "N/A",
  roomType: props.room?.roomType ?? props.room?.type ?? "",
  isAvailable: !!props.room?.isAvailable,
  status: props.room?.status ?? props.room?.hkStatus ?? "",
  notes: props.room?.notes ?? null,
}));

const roomUiStatus = computed(() => {
  const s = safeRoom.value.status;
  if (s === "dirty" || s === "Phòng bẩn") return "Phòng bẩn";
  if (
    s === "in progress" ||
    s === "in-progress" ||
    s === "working" ||
    s === "Đang dọn"
  )
    return "đang dọn";
  if (s === "unavailable" || s === "blocked" || s === "Đóng") return "Đóng";
  if (s === "clean" || s === "cleaned" || s === "inspected" || s === "Sẵn sàng")
    return "Sẵn sàng";
  return "Sẵn sàng";
});

function availabilityColor(isAvailable) {
  return isAvailable ? "success" : "grey";
}

function statusColor(label) {
  const key = norm(label);
  const map = {
    [norm("Sẵn sàng")]: "success",
    [norm("Phòng bẩn")]: "error",
    [norm("Đang dọn")]: "warning",
    [norm("Đóng")]: "grey",
  };
  return map[key] || "primary";
}

function onEdit() {
  emit("edit", safeRoom.value);
}
</script>

<style scoped>
.room-row {
  border: 1px solid rgba(0, 0, 0, 12%);
  border-radius: 12px;
}

.room-link {
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
  text-decoration: underline;
}

.room-type {
  color: rgba(var(--v-theme-on-surface), 0.6);
  font-size: 0.95rem;
  margin-block-start: 2px;
}

.icon-square {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border-radius: 10px;
  block-size: 36px;
  inline-size: 36px;
  min-block-size: 36px;
  min-inline-size: 36px;
}

.drag-handle {
  all: unset;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: grab;
}

@media (max-width: 960px) {
  .icon-square {
    margin-inline-start: auto;
  }
}
</style>
