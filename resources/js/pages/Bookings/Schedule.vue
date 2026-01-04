<template>
  <Head title="Lịch | Room Rise" />
  <Layout>
    <div class="container-xxl flex-grow-1 container-p-y">
      <VCard>
        <VCardText>
          <VRow>
            <VCol cols="6" md="6" sm="3" lg="3">
              <AppSelect
                v-model="filters.property_id"
                :items="[
                  { name: 'Tất cả Property', id: null },
                  ...propertyOptions,
                ]"
                item-title="name"
                item-value="id"
                label="Chỗ nghỉ"
              />
            </VCol>

            <VCol cols="6" md="6" sm="3" lg="3">
              <AppSelect
                v-model="filters.room_id"
                :items="[{ name: 'Tất cả', id: null }, ...roomOptions]"
                item-title="name"
                item-value="id"
                label="Loại phòng"
              />
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <v-row class="mt-5">
        <v-col cols="12">
          <v-card class="pa-4">
            <FullCalendar ref="calendarRef" :options="calendarOptions">
              <template #eventContent="{ event }">
                <div
                  :title="`Check-in: ${formatDate(
                    event.extendedProps?.start
                  )}\nCheck-out: ${formatDate(
                    event.extendedProps?.origin_end
                  )}`"
                >
                  <div
                    class="text-body-2 fc-event-custom px-2 py-1"
                    :class="{
                      'event-ota': event.extendedProps?.ota_name,
                      'event-local': !event.extendedProps?.ota_name,
                      'month-view':
                        calendarRef?.getApi()?.view?.type ===
                        'resourceTimelineMonth',
                    }"
                    :style="
                      event.extendedProps?.ota_name
                        ? getOtaEventStyle(event.extendedProps.ota_name)
                        : {}
                    "
                  >
                    <div class="event-header">
                      <div class="event-title">
                        {{ event.title }}
                      </div>
                    </div>
                    <div class="event-subtitle">
                      <img
                        v-if="
                          event.extendedProps?.ota_name &&
                          getOtaConfig(event.extendedProps.ota_name)?.logo
                        "
                        :src="getOtaConfig(event.extendedProps.ota_name).logo"
                        :alt="event.extendedProps.ota_name"
                        class="ota-logo"
                      />
                      {{ event.extendedProps?.ota_name || "Cục bộ" }}
                    </div>
                  </div>
                </div>
              </template>
              <template #resourceLabelContent="{ resource }">
                <div class="d-inline-flex gap-2 ml-2">
                  <img
                    v-if="resource.id.startsWith('room_')"
                    width="24"
                    height="24"
                    src="/images/room_icon.png"
                  />
                  <span>{{ resource.title }}</span>
                </div>
                <div
                  v-if="resource.extendedProps?.note"
                  class="ps-2 pt-2 text-caption"
                >
                  {{ resource.extendedProps.note }}
                </div>
              </template>
            </FullCalendar>
          </v-card>
        </v-col>
      </v-row>
    </div>
    <BookingDetailDialog :booking-id="selectedBookingId" />
    <BookingFormDialog
      v-model:is-dialog-visible="isFormDialogVisible"
      :data="bookingData"
    />
  </Layout>
</template>

<script setup>
import BookingDetailDialog from "@/Components/bookings/BookingDetailDialog.vue";
import BookingFormDialog from "@/Components/bookings/BookingFormDialog.vue";
import { isVisiableBookingDetailDialog } from "@/composables/booking";
import { usePropertyStore } from "@/stores/usePropertyStore"; // ✅ Import store
import { formatDate } from "@/utils/formatters";
import viLocale from "@fullcalendar/core/locales/vi";
import interactionPlugin from "@fullcalendar/interaction";
import resourceTimelinePlugin from "@fullcalendar/resource-timeline";
import FullCalendar from "@fullcalendar/vue3";
import { Head, router, useForm } from "@inertiajs/vue3";
import { reactive, watch } from "vue";
import { toast } from "vue3-toastify";
import Layout from "../../layouts/blank.vue";

// OTA Logo và màu sắc mapping
const otaConfig = {
  bookingcom: {
    logo: "/images/bookingcom.png",
    colors: {
      primary: "#003580",
      secondary: "#0071c2",
      gradient: "linear-gradient(135deg, #003580 0%, #0071c2 100%)",
      border: "#003580",
      shadow: "rgba(0, 53, 128, 0.4)",
    },
  },
  ctrip: {
    logo: "/images/ctrip.png",
    colors: {
      primary: "#ff6b35",
      secondary: "#ff8c42",
      gradient: "linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%)",
      border: "#e55a2b",
      shadow: "rgba(255, 107, 53, 0.4)",
    },
  },
  expedia: {
    logo: "/images/expedia.png",
    colors: {
      primary: "#00a3e0",
      secondary: "#0078d7",
      gradient: "linear-gradient(135deg, #00a3e0 0%, #0078d7 100%)",
      border: "#0078d7",
      shadow: "rgba(0, 163, 224, 0.4)",
    },
  },
  airbnb: {
    logo: "/images/airbnb.png",
    colors: {
      primary: "#ff5a5f",
      secondary: "#ff385c",
      gradient: "linear-gradient(135deg, #ff5a5f 0%, #ff385c 100%)",
      border: "#e31c5f",
      shadow: "rgba(255, 90, 95, 0.4)",
    },
  },
  agoda: {
    logo: "/images/agoda.png",
    colors: {
      primary: "#facc15",
      secondary: "#eab308",
      gradient: "linear-gradient(135deg, #facc15 0%, #eab308 100%)",
      border: "#ca8a04",
      shadow: "rgba(250, 204, 21, 0.4)",
    },
  },
};

// Hàm lấy config cho OTA
const getOtaConfig = (otaName) => {
  if (!otaName) return null;

  const normalizedName = otaName.toLowerCase().replace(/\s+/g, "");
  // console.log(
  //   "getOtaConfig - Original:",
  //   otaName,
  //   "Normalized:",
  //   normalizedName
  // );

  const config = otaConfig[normalizedName];
  // console.log("getOtaConfig - Found config:", config);

  return config || null;
};

// Hàm tạo style cho OTA event
const getOtaEventStyle = (otaName) => {
  const config = getOtaConfig(otaName);

  if (!config) return {};

  const style = {
    background: `${config.colors.gradient} !important`,
    boxShadow: `0 4px 15px ${config.colors.shadow} !important`,
    borderLeft: `4px solid ${config.colors.border} !important`,
  };

  // console.log("Generated style:", style);
  return style;
};

const propertyStore = usePropertyStore();
const {
  filters: filterProps,
  resourceTree,
  bookings,
  unscheduledBookings,
  unitRoomMap,
  existingEventsByUnit,
  propertyOptions,
} = defineProps({
  roomOptions: Array,
  propertyOptions: Array,
  filters: Object,
  resourceTree: Array,
  bookings: Array,
  unscheduledBookings: Array,
  unitRoomMap: Object,
  existingEventsByUnit: Object,
});

const assignRoomUnitForm = useForm({
  room_unit_id: null,
});

const calendarRef = ref(null);
const selectedBookingId = ref(null);

const calendarOptions = reactive({
  plugins: [resourceTimelinePlugin, interactionPlugin],
  schedulerLicenseKey: "0133043170-fcs-16964811720",

  initialView: "resourceTimelineWeek",
  droppable: true,
  editable: true,
  eventDurationEditable: false,

  selectable: true,
  selectMirror: true,
  select: handleCellClick,

  resourceGroupField: "group",
  resourceAreaHeaderContent: "Phòng",

  locale: viLocale,

  customButtons: {
    prevCustom: {
      text: "< Trước",
      click() {
        calendarRef.value.getApi().prev();
      },
    },
    nextCustom: {
      text: "Sau >",
      click() {
        calendarRef.value.getApi().next();
      },
    },
  },
  headerToolbar: {
    left: "prevCustom today nextCustom",
    center: "title",
    right: "resourceTimelineDay,resourceTimelineWeek,resourceTimelineMonth",
  },

  views: {
    resourceTimelineDay: {
      slotDuration: "01:00",
      slotLabelFormat: [{ hour: "2-digit" }],
      slotLabelClassNames: "fc-multirow-header",
    },
    resourceTimelineWeek: {
      type: "resourceTimeline",
      duration: { days: 7 },
      slotDuration: { days: 1 },
      slotLabelFormat: [
        { year: "numeric", month: "long" },
        { day: "numeric" },
        { weekday: "short" },
      ],
      slotLabelClassNames: "fc-multirow-header",
    },
    resourceTimelineMonth: {
      type: "resourceTimelineMonth",
      slotLabelFormat: [
        { year: "numeric", month: "long" },
        { day: "numeric" },
        { weekday: "short" },
      ],
      slotLabelClassNames: "fc-multirow-header",
      slotMinWidth: 80,
      slotWidth: 80,
    },
  },

  buttonText: {
    today: "Hôm nay",
    resourceTimelineDay: "Ngày",
    resourceTimelineWeek: "Tuần",
    resourceTimelineMonth: "Tháng",
  },

  resources: resourceTree,

  eventAllow: function (dropInfo, draggedEvent) {
    const dropResource = dropInfo.resource || null;
    if (!dropResource.id) {
      return false;
    }

    const { room_id: dropRoomID, room_unit_id: dropUnitID } =
      dropResource.extendedProps;
    const { room_id: dragRoomID, room_unit_id: dragUnitID } =
      draggedEvent.extendedProps;

    console.log("Resource info:", {
      dropRoomID,
      dropUnitID,
      dragRoomID,
      dragUnitID,
    });

    // Chỉ cho phép kéo thả vào room unit hoặc room type (để bỏ gắn)
    if (
      !dropResource.id.startsWith("unit_") &&
      !dropResource.id.startsWith("room_")
    ) {
      console.log("Invalid resource type:", dropResource.id);
      return false;
    }

    // Kiểm tra room_id phải giống nhau
    if (dropRoomID !== dragRoomID) {
      console.log("Room ID mismatch:", dropRoomID, dragRoomID);
      return false;
    }

    // Nếu kéo vào ô loại phòng (room type) thì cho phép luôn
    if (dropResource.id.startsWith("room_")) {
      return true;
    }

    // Nếu kéo vào room unit thì kiểm tra conflict về thời gian
    const targetResourceId = dropResource.id;

    // Fallback nếu existingEventsByUnit không có dữ liệu
    if (!existingEventsByUnit || typeof existingEventsByUnit !== "object") {
      console.log("existingEventsByUnit is not available, allowing drop");
      return true;
    }

    const existingEvents = existingEventsByUnit[targetResourceId] || [];

    console.log(
      "Existing events for target:",
      targetResourceId,
      existingEvents
    );

    const bookingStart = new Date(draggedEvent.extendedProps.start);
    const bookingEnd = new Date(draggedEvent.extendedProps.end);

    for (const ev of existingEvents) {
      if (ev.id === draggedEvent.id) continue;

      const evStart = new Date(ev.start);
      const evEnd = new Date(ev.end);

      if (
        bookingStart.getTime() < evEnd.getTime() &&
        bookingEnd.getTime() > evStart.getTime()
      ) {
        console.log("Time conflict detected");
        return false;
      }
    }

    console.log("Event allowed");
    return true;
  },

  eventDrop: (info) => {
    const { booking_id, booking_room_id } = info.event.extendedProps;
    const { room_unit_id: new_unit_id } = info.newResource.extendedProps;

    if (!booking_id) {
      info.revert();
      return;
    }

    // Nếu kéo vào ô loại phòng (room type) thì bỏ gắn room unit
    if (info.newResource.id.startsWith("room_")) {
      assignRoomUnitForm.room_unit_id = null;
    } else {
      // Nếu kéo vào room unit thì gắn room unit mới
      assignRoomUnitForm.room_unit_id = new_unit_id;
    }

    assignRoomUnitForm.post(
      route("booking-rooms.assign-room-unit", booking_room_id),
      {
        onError: (error) => {
          toast(error.msg || "Có lỗi xảy ra, vui lòng thử lại sau", {
            theme: "colored",
            type: "error",
          });
          info.revert(); // Revert nếu có lỗi
        },
        onSuccess: () => {
          toast("Cập nhật phòng thành công!", {
            theme: "colored",
            type: "success",
          });
        },
      }
    );
  },

  eventClick: function (info) {
    selectedBookingId.value = info.event?.extendedProps?.booking_id || null;
    isVisiableBookingDetailDialog.value = true;
  },
});

// const filters = ref({
//   property_id: Number(filterProps?.property_id) || null,
//   room_id: Number(filterProps?.room_id) || null,
// });

// watch(
//   () => filters.value.property_id,
//   () => {
//     filters.value.room_id = null;
//   }
// );

// watch(
//   filters,
//   (newFilters) => {
//     router.get(route("bookings.schedule"), newFilters, {
//       preserveScroll: true,
//     });
//   },
//   {
//     deep: true,
//   }
// );

const filters = ref({
  property_id:
    propertyStore.selectedProperty || Number(filterProps?.property_id) || null,
  room_id: Number(filterProps?.room_id) || null,
});

// ✅ Khi property_id thay đổi -> lưu vào store và reset room_id
watch(
  () => filters.value.property_id,
  (val) => {
    propertyStore.setProperty(val);
    filters.value.room_id = null;
  }
);

// ✅ Khi store thay đổi -> cập nhật filters.property_id
watch(
  () => propertyStore.selectedProperty,
  (val) => {
    filters.value.property_id = val;
  }
);

// ✅ Gọi API khi filters thay đổi
watch(
  filters,
  (newFilters) => {
    router.get(route("bookings.schedule"), newFilters, {
      preserveScroll: true,
    });
  },
  {
    deep: true,
  }
);

const isFormDialogVisible = ref(false);

const bookingData = ref(null);
function handleCellClick(info) {
  const resourceId = info.resource.id;
  if (!resourceId) return;

  const { property_id, room_id, room_unit_id } = info.resource.extendedProps;

  const checkInDateTime = new Date(info.startStr);
  const checkOutDateTime = new Date(info.endStr);

  const property = propertyOptions.find((p) => p.id === property_id);

  let checkInTime, checkOutTime;

  const currentView = calendarRef.value.getApi().view.type;

  if (currentView === "resourceTimelineDay") {
    checkInTime = checkInDateTime.toTimeString().slice(0, 5);
    checkOutTime = checkOutDateTime.toTimeString().slice(0, 5);
  } else {
    checkInTime = property?.checkin_from_time || "14:00";
    checkOutTime = property?.checkout_to_time || "12:00";
  }

  bookingData.value = {
    status: "",
    status_2: "",
    property_id,
    room_id,
    room_unit_id: room_unit_id,
    room_price_at_booking: 0,
    check_in_date: checkInDateTime.toISOString().slice(0, 10),
    check_out_date: checkOutDateTime.toISOString().slice(0, 10),
    check_in_time: checkInTime,
    check_out_time: checkOutTime,
    customer_type: "",
    customer_id: "",
  };
  isFormDialogVisible.value = true;
}

watch(
  () => bookings,
  async (newBookings) => {
    await nextTick();
    const calendarApi = calendarRef.value?.getApi();
    if (calendarApi) {
      calendarApi.removeAllEvents();

      calendarApi.addEventSource(newBookings);
    }
  },
  { immediate: true, deep: true }
);
</script>

<style lang="scss">
// Base event styling
.fc-event {
  padding: 0 !important;
  border: none !important;
  border-radius: 8px !important;
  overflow: hidden !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
  min-height: 50px !important;
}

.fc-event:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

// Event content container
.fc-event-custom {
  padding: 8px 12px !important;
  border-radius: 8px !important;
  position: relative !important;
  overflow: hidden !important;
  height: 100% !important;
  display: flex !important;
  flex-direction: column !important;
  justify-content: center !important;
  min-height: 50px !important;
}

// Month view specific styling - reduce horizontal padding
.fc-resourceTimelineMonth .fc-event-custom {
  padding: 6px 4px !important;
}

.fc-resourceTimelineMonth .event-title {
  font-size: 0.75rem !important;
}

.fc-resourceTimelineMonth .event-subtitle {
  font-size: 0.65rem !important;
}

.fc-resourceTimelineMonth .ota-logo {
  width: 14px !important;
  height: 14px !important;
}

// Alternative selector for month view
.fc-resourceTimelineMonth .fc-event .fc-event-custom {
  padding: 6px 4px !important;
}

// More specific selector for month view events
.fc-resourceTimelineMonth .fc-timeline-event-harness .fc-event-custom {
  padding: 6px 4px !important;
}

// Force override padding for month view
.fc-resourceTimelineMonth .fc-event-custom {
  padding: 6px 4px !important;
}

// Additional month view selectors
.fc-resourceTimelineMonth .fc-event .fc-event-custom,
.fc-resourceTimelineMonth .fc-timeline-event .fc-event-custom,
.fc-resourceTimelineMonth .fc-timeline-event-harness .fc-event-custom {
  padding: 6px 4px !important;
}

// Override with higher specificity
.fc-resourceTimelineMonth .fc-event-custom[style*="padding"] {
  padding: 6px 4px !important;
}

// Highest priority override for month view
.fc-resourceTimelineMonth .fc-event-custom {
  padding: 6px 4px !important;
}

// Force override with !important and higher specificity
.fc-resourceTimelineMonth .fc-event .fc-event-custom {
  padding: 6px 4px !important;
}

// Additional override for month view
.fc-resourceTimelineMonth .fc-timeline-event-harness .fc-event-custom {
  padding: 6px 4px !important;
}

// Month view class styling - simple and effective
.month-view.fc-event-custom {
  padding: 6px 4px !important;
}

.month-view .event-title {
  font-size: 0.75rem !important;
}

.month-view .event-subtitle {
  font-size: 0.65rem !important;
}

.month-view .ota-logo {
  width: 14px !important;
  height: 14px !important;
}

// OTA booking styles with dynamic colors
.event-ota {
  background: linear-gradient(
    135deg,
    #34d399 0%,
    #059669 100%
  ); /* xanh lá nhạt → xanh lá đậm */
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); /* bóng màu xanh */
  border-left: 4px solid #047857; /* xanh lá đậm */

  &::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
      135deg,
      rgba(255, 255, 255, 0.1) 0%,
      rgba(255, 255, 255, 0.05) 100%
    );
    pointer-events: none;
  }
}

// Local booking style
.event-local {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
  border-left: 4px solid #3b82f6;

  &::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
      135deg,
      rgba(255, 255, 255, 0.1) 0%,
      rgba(255, 255, 255, 0.05) 100%
    );
    pointer-events: none;
  }
}

// Event header with logo and title
.event-header {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
  margin-bottom: 4px !important;
  min-height: 20px !important;
  width: 100% !important;
}

// OTA logo styling
.ota-logo {
  width: 16px !important;
  height: 16px !important;
  object-fit: contain !important;
  border-radius: 2px !important;
  background: rgba(255, 255, 255, 0.2) !important;
  padding: 1px !important;
  flex-shrink: 0 !important;
  display: block !important;
}

// Event title styling
.event-title {
  font-weight: 600 !important;
  color: white !important;
  font-size: 0.875rem !important;
  line-height: 1.2 !important;
  margin-bottom: 2px !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  max-width: 100% !important;
  flex: 1 !important;
  display: block !important;
}

// Event subtitle styling
.event-subtitle {
  display: flex;
  align-items: center;
  gap: 4px;
  color: rgba(255, 255, 255, 0.9) !important;
  font-size: 0.75rem !important;
  line-height: 1.1 !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  max-width: 100% !important;
}

// FullCalendar specific styling
.fc .fc-timeline-event-harness,
.fc .fc-timeline-more-link {
  padding: 3px !important;
}

.fc-datagrid-cell-main {
  font-weight: bold;
}

.fc .fc-scrollgrid-liquid {
  thead {
    // background: linear-gradient(180deg, #eeeeee 0% 100%);
    background: linear-gradient(
      180deg,
      rgb(var(--v-theme-grey-200)) 0%,
      rgb(var(--v-theme-grey-200)) 100%
    );
    tr {
      padding: 6px !important;
      .fc-datagrid-cell-frame {
        font-size: 28px;
        padding: 8px !important;
        font-weight: 500 !important;
        color: rgb(var(--v-theme-primary));
      }
    }
  }
}

.fc .fc-scrollgrid,
.fc .fc-timegrid-slot {
  border-radius: 12px !important;
  overflow: hidden !important;
}

// Button styling
.fc .fc-button,
.fc .fc-button-primary,
.fc .fc-today-button {
  background-color: rgb(var(--v-theme-primary)) !important;
  color: #fff !important;
  border: none !important;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3) !important;
  border-radius: 8px !important;
  padding: 8px 16px !important;
  font-weight: 500 !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.fc .fc-button:hover,
.fc .fc-button-primary:hover,
.fc .fc-today-button:hover {
  background-color: rgb(var(--v-theme-primary)) !important;
  color: #fff !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4) !important;
}

.fc .fc-button.fc-button-active,
.fc .fc-button-primary.fc-button-active,
.fc .fc-today-button.fc-button-active {
  color: #fff !important;
  opacity: 0.8;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.5) !important;
}

// Spacing between day/week/month view buttons
.fc .fc-button-group .fc-button {
  margin-right: 4px !important;
}

.fc .fc-button-group .fc-button:last-child {
  margin-right: 0 !important;
}

// Alternative spacing for view buttons
.fc .fc-toolbar-chunk:last-child .fc-button {
  margin-left: 4px !important;
}

.fc .fc-toolbar-chunk:last-child .fc-button:first-child {
  margin-left: 0 !important;
}

// Toolbar and text styling
.fc-toolbar,
.fc-timeline-slot-frame {
  text-transform: capitalize !important;
}

// Weekend highlighting
.fc-day-sat[colspan="1"],
.fc-day-sun[colspan="1"] {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
  position: relative !important;

  &::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
      135deg,
      rgba(251, 191, 36, 0.1) 0%,
      rgba(245, 158, 11, 0.1) 100%
    );
    pointer-events: none;
  }
}

// Resource area styling
.fc-resource-timeline-divider {
  background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%) !important;
}

.fc-resource-timeline-header {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
  border-bottom: 2px solid #e2e8f0 !important;
}

.fc-resource-timeline-header th {
  padding: 12px 8px !important;
  font-weight: 600 !important;
  color: #334155 !important;
  border-right: 1px solid #e2e8f0 !important;
}

// Timeline grid styling
.fc-timeline-slot {
  border-right: 1px solid #f1f5f9 !important;
}

.fc-timeline-slot-frame {
  padding: 8px !important;
  color: #64748b !important;
  font-weight: 500 !important;
}

// Resource area styling
.fc-resource-area {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
  border-right: 2px solid #e2e8f0 !important;
}

.fc-resource-area-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  color: white !important;
  font-weight: 600 !important;
  padding: 12px !important;
  text-align: center !important;
}

.fc-resource-area-header th {
  border: none !important;
  color: white !important;
}

// .fc-resource-timeline-divider {
//   background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
//   width: 3px !important;
// }

// Resource group styling
.fc-resource-group {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%) !important;
  border-bottom: 1px solid #cbd5e1 !important;
  font-weight: 600 !important;
  color: #475569 !important;
  padding: 8px 12px !important;
}

// Property row styling - different color from header
.fc-resource-group:first-child {
  background: linear-gradient(
    180deg,
    rgb(96, 211, 252) 0%,
    rgb(var(--v-theme-primary), 0.4) 100%
  ) !important;
  color: #fff !important;
  font-weight: 700 !important;
  padding: 12px 16px !important;
  font-size: 1rem !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
}

// Grid lines styling for property row only
.fc-resource-group:first-child .fc-timeline-slot {
  border-right: 1px solid rgba(255, 255, 255, 0.3) !important;
  border: none;
}

.fc-resource-group:first-child .fc-timeline-slot-frame {
  border-right: 1px solid rgba(255, 255, 255, 0.2) !important;
}

// Alternative selector for grid lines in property row
.fc-resource-group:first-child td {
  border-right: 1px solid rgba(255, 255, 255, 0.25) !important;
}

// Room type subgroup styling
.fc-resource-group:not(:first-child) {
  background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%) !important;
  color: white !important;
  font-weight: 600 !important;
  padding: 10px 20px !important;
  font-size: 0.9rem !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
  border-bottom: 1px solid #475569 !important;
}

.fc-resource {
  border-bottom: 1px solid #f1f5f9 !important;
  transition: background-color 0.2s ease !important;
}

.fc-resource:hover {
  background-color: rgba(102, 126, 234, 0.05) !important;
}

.fc-resource td {
  padding: 10px 12px !important;
  color: #334155 !important;
  font-weight: 500 !important;
}

.fc .fc-resource-timeline-header th {
  padding: 16px 20px !important;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
  border-bottom: 2px solid #e2e8f0 !important;
  border-right: 1px solid #e2e8f0 !important;
  color: #334155 !important;
  font-weight: 600 !important;
}

// Responsive design
@media (max-width: 768px) {
  .fc .fc-toolbar.fc-header-toolbar {
    flex-direction: column !important;
    gap: 12px !important;
  }

  .fc-event-custom {
    padding: 6px 8px !important;
  }

  .event-title {
    font-size: 0.8rem !important;
  }

  .event-subtitle {
    font-size: 0.7rem !important;
  }
}

.fc-h-event {
  background-color: transparent !important;
}
</style>
