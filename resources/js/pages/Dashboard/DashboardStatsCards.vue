<template>
  <div class="stats-container">
    <VRow class="match-height">
      <!-- Doanh thu hôm nay -->
      <VCol cols="12" sm="6" lg="3">
        <VCard class="stat-card stat-card--primary" elevation="0">
          <VCardText class="stat-content">
            <div class="stat-header">
              <VAvatar
                size="56"
                color="primary"
                variant="tonal"
                class="stat-avatar"
              >
                <VIcon icon="tabler-currency-dollar" size="28" />
              </VAvatar>
              <VChip
                size="small"
                :color="stats.revenueChange >= 0 ? 'success' : 'error'"
                variant="elevated"
                class="stat-trend"
                :prepend-icon="
                  stats.revenueChange >= 0
                    ? 'tabler-trending-up'
                    : 'tabler-trending-down'
                "
              >
                {{ stats.revenueChange >= 0 ? "+" : ""
                }}{{ stats.revenueChange }}%
              </VChip>
            </div>

            <div class="stat-body">
              <div class="">Doanh thu hôm nay</div>
              <div class="stat-value primary--text">
                {{ formatCurrency(stats.todayRevenue) }}
              </div>
              <div class="">
                <VIcon
                  icon="tabler-check-circle"
                  size="14"
                  class="me-1 text-success"
                />
                Đã thu: {{ formatCurrency(stats.todayCollected) }}
              </div>
            </div>

            <VProgressLinear
              :model-value="
                getCollectionProgress(stats.todayCollected, stats.todayRevenue)
              "
              color="primary"
              height="4"
              rounded
              class="stat-progress"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- Doanh thu dự kiến tháng sau -->
      <VCol cols="12" sm="6" lg="3">
        <VCard class="stat-card stat-card--success" elevation="0">
          <VCardText class="stat-content">
            <div class="stat-header">
              <VAvatar
                size="56"
                color="success"
                variant="tonal"
                class="stat-avatar"
              >
                <VIcon icon="tabler-chart-line" size="28" />
              </VAvatar>
              <VChip
                size="small"
                :color="stats.nextMonthIncrease >= 0 ? 'success' : 'error'"
                variant="elevated"
                class="stat-trend"
                :prepend-icon="
                  stats.nextMonthIncrease >= 0
                    ? 'tabler-trending-up'
                    : 'tabler-trending-down'
                "
              >
                {{ stats.nextMonthIncrease >= 0 ? "+" : ""
                }}{{ stats.nextMonthIncrease }}%
              </VChip>
            </div>

            <div class="stat-body">
              <div class="">Doanh thu dự kiến tháng sau</div>
              <div class="stat-value success--text">
                {{ formatCurrency(stats.nextMonthRevenue) }}
              </div>
              <div class="">
                <VIcon
                  :icon="
                    stats.nextMonthIncrease >= 0
                      ? 'tabler-arrow-up'
                      : 'tabler-arrow-down'
                  "
                  size="14"
                  :class="
                    stats.nextMonthIncrease >= 0 ? 'text-success' : 'text-error'
                  "
                  class="me-1"
                />
                {{ stats.nextMonthIncrease >= 0 ? "Tăng" : "Giảm" }}
                {{ Math.abs(stats.nextMonthIncrease) }}% so với tháng này
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Tỷ lệ lấp đầy -->
      <VCol cols="12" sm="6" lg="3">
        <VCard class="stat-card stat-card--info" elevation="0">
          <VCardText class="stat-content">
            <div class="stat-header">
              <VAvatar
                size="56"
                color="info"
                variant="tonal"
                class="stat-avatar"
              >
                <VIcon icon="tabler-building" size="28" />
              </VAvatar>
              <VChip
                size="small"
                :color="getOccupancyColor(stats.occupancyRate)"
                variant="elevated"
                class="stat-trend"
                :prepend-icon="
                  stats.occupancyChange >= 0
                    ? 'tabler-trending-up'
                    : 'tabler-trending-down'
                "
              >
                {{ stats.occupancyChange >= 0 ? "+" : ""
                }}{{ stats.occupancyChange }}%
              </VChip>
            </div>

            <div class="stat-body">
              <div class="">Tỷ lệ lấp đầy</div>
              <div class="stat-value info--text">
                {{ stats.occupancyRate }}%
              </div>
              <div class="">
                <VIcon icon="tabler-door" size="14" class="me-1 text-info" />
                {{ stats.occupiedRooms }}/{{ stats.totalRooms }} phòng
              </div>
            </div>

            <VProgressLinear
              :model-value="stats.occupancyRate"
              :color="getOccupancyColor(stats.occupancyRate)"
              height="4"
              rounded
              class="stat-progress"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- Đặt phòng mới -->
      <VCol cols="12" sm="6" lg="3">
        <VCard class="stat-card stat-card--warning" elevation="0">
          <VCardText class="stat-content">
            <div class="stat-header">
              <VAvatar
                size="56"
                color="warning"
                variant="tonal"
                class="stat-avatar"
              >
                <VIcon icon="tabler-calendar-plus" size="28" />
              </VAvatar>
              <VChip
                size="small"
                :color="stats.newBookingsChange >= 0 ? 'success' : 'error'"
                variant="elevated"
                class="stat-trend"
                :prepend-icon="
                  stats.newBookingsChange >= 0
                    ? 'tabler-trending-up'
                    : 'tabler-trending-down'
                "
              >
                {{ stats.newBookingsChange >= 0 ? "+" : ""
                }}{{ stats.newBookingsChange }}
              </VChip>
            </div>

            <div class="stat-body">
              <div class="">Đặt phòng mới</div>
              <div class="stat-value warning--text">
                {{ stats.newBookings }}
              </div>
              <div class="">
                <VIcon
                  icon="tabler-clock"
                  size="14"
                  class="me-1 text-warning"
                />
                Booking mới hôm nay
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Khách Check-in -->
      <VCol cols="12" sm="6" lg="3">
        <VCard class="stat-card stat-card--secondary" elevation="0">
          <VCardText class="stat-content">
            <div class="stat-header">
              <VAvatar
                size="56"
                color="secondary"
                variant="tonal"
                class="stat-avatar"
              >
                <VIcon icon="tabler-login" size="28" />
              </VAvatar>
              <VChip
                size="small"
                :color="stats.checkinChange >= 0 ? 'success' : 'error'"
                variant="elevated"
                class="stat-trend"
                :prepend-icon="
                  stats.checkinChange >= 0
                    ? 'tabler-trending-up'
                    : 'tabler-trending-down'
                "
              >
                {{ stats.checkinChange >= 0 ? "+" : ""
                }}{{ stats.checkinChange }}
              </VChip>
            </div>

            <div class="stat-body">
              <div class="">Khách Check-in</div>
              <div class="stat-value secondary--text">
                {{ stats.todayCheckins }}
              </div>
              <div class="">
                <VIcon
                  icon="tabler-user-check"
                  size="14"
                  class="me-1 text-secondary"
                />
                Đã đến hôm nay
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Khách Check-out -->
      <VCol cols="12" sm="6" lg="3">
        <VCard class="stat-card stat-card--purple" elevation="0">
          <VCardText class="stat-content">
            <div class="stat-header">
              <VAvatar
                size="56"
                color="purple"
                variant="tonal"
                class="stat-avatar"
              >
                <VIcon icon="tabler-logout" size="28" />
              </VAvatar>
              <VChip
                size="small"
                :color="stats.checkoutChange >= 0 ? 'info' : 'warning'"
                variant="elevated"
                class="stat-trend"
                :prepend-icon="
                  stats.checkoutChange >= 0
                    ? 'tabler-trending-up'
                    : 'tabler-trending-down'
                "
              >
                {{ stats.checkoutChange >= 0 ? "+" : ""
                }}{{ stats.checkoutChange }}
              </VChip>
            </div>

            <div class="stat-body">
              <div class="">Khách Check-out</div>
              <div class="stat-value purple--text">
                {{ stats.todayCheckouts }}
              </div>
              <div class="">
                <VIcon
                  icon="tabler-user-minus"
                  size="14"
                  class="me-1 text-purple"
                />
                Đã rời đi hôm nay
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Chưa gán phòng -->
      <VCol cols="12" sm="6" lg="3">
        <VCard class="stat-card stat-card--error" elevation="0">
          <VCardText class="stat-content">
            <div class="stat-header">
              <VAvatar
                size="56"
                color="error"
                variant="tonal"
                class="stat-avatar"
              >
                <VIcon icon="tabler-alert-triangle" size="28" />
              </VAvatar>
              <VChip
                size="small"
                color="error"
                variant="elevated"
                class="stat-trend"
                prepend-icon="tabler-exclamation-mark"
              >
                {{ stats.unassignedChange >= 0 ? "+" : "-" }}
                {{ Math.abs(stats.unassignedChange) }}
              </VChip>
            </div>

            <div class="stat-body">
              <div class="">Chưa gán phòng</div>
              <div class="stat-value error--text">
                {{ stats.unassignedRooms }}
              </div>
              <div class="">
                <VIcon
                  icon="tabler-alert-circle"
                  size="14"
                  class="me-1 text-error"
                />
                Cần xử lý ngay
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Chưa thu tiền -->
      <VCol cols="12" sm="6" lg="3">
        <VCard class="stat-card stat-card--orange" elevation="0">
          <VCardText class="stat-content">
            <div class="stat-header">
              <VAvatar
                size="56"
                color="orange"
                variant="tonal"
                class="stat-avatar"
              >
                <VIcon icon="tabler-cash-off" size="28" />
              </VAvatar>
              <VChip
                size="small"
                :color="stats.unpaidChange <= 0 ? 'success' : 'warning'"
                variant="elevated"
                class="stat-trend"
                :prepend-icon="
                  stats.unpaidChange <= 0
                    ? 'tabler-trending-down'
                    : 'tabler-trending-up'
                "
              >
                {{ stats.unpaidChange >= 0 ? "+" : "-" }}
                {{ Math.abs(stats.unpaidChange) }}
              </VChip>
            </div>

            <div class="stat-body">
              <div class="">Chưa thu tiền</div>
              <div class="stat-value orange--text">
                {{ stats.unpaidBookings }}
              </div>
              <div class="">
                <VIcon icon="tabler-cash" size="14" class="me-1 text-orange" />
                {{ formatCurrency(stats.unpaidAmount) }}
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<script setup>
import { computed } from "vue";

// Props nhận từ component cha
const props = defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
      todayRevenue: 0,
      todayCollected: 0,
      revenueChange: 0,
      nextMonthRevenue: 0,
      nextMonthIncrease: 0,
      occupancyRate: 0,
      occupiedRooms: 0,
      totalRooms: 0,
      occupancyChange: 0,
      newBookings: 0,
      newBookingsChange: 0,
      todayCheckins: 0,
      checkinChange: 0,
      todayCheckouts: 0,
      checkoutChange: 0,
      unassignedRooms: 0,
      unassignedChange: 0,
      unpaidBookings: 0,
      unpaidAmount: 0,
      unpaidChange: 0,
    }),
  },
});

// Emit events to parent component
const emit = defineEmits(["handle-unassigned-rooms", "handle-unpaid-bookings"]);

// Format currency
const formatCurrency = (value) => {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(value);
};

// Get collection progress percentage
const getCollectionProgress = (collected, total) => {
  if (!total || total === 0) return 0;
  return Math.min((collected / total) * 100, 100);
};

// Get occupancy color based on rate
const getOccupancyColor = (rate) => {
  if (rate >= 90) return "success";
  if (rate >= 70) return "info";
  if (rate >= 50) return "warning";
  return "error";
};

// Handle unassigned rooms action
const handleUnassignedRooms = () => {
  emit("handle-unassigned-rooms");
};

// Handle unpaid bookings action
const handleUnpaidBookings = () => {
  emit("handle-unpaid-bookings");
};
</script>

<style scoped>
.stats-container {
  padding: 0;
}

.stat-card {
  border-radius: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(var(--v-border-color), 0.08);
  background: rgb(var(--v-theme-surface));
  height: 100%;
}

.stat-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.stat-card--primary::before {
  background: linear-gradient(
    90deg,
    transparent,
    rgb(var(--v-theme-primary)),
    transparent
  );
}

.stat-card--success::before {
  background: linear-gradient(
    90deg,
    transparent,
    rgb(var(--v-theme-success)),
    transparent
  );
}

.stat-card--info::before {
  background: linear-gradient(
    90deg,
    transparent,
    rgb(var(--v-theme-info)),
    transparent
  );
}

.stat-card--warning::before {
  background: linear-gradient(
    90deg,
    transparent,
    rgb(var(--v-theme-warning)),
    transparent
  );
}

.stat-card--secondary::before {
  background: linear-gradient(
    90deg,
    transparent,
    rgb(var(--v-theme-secondary)),
    transparent
  );
}

.stat-card--purple::before {
  background: linear-gradient(
    90deg,
    transparent,
    rgb(156, 39, 176),
    transparent
  );
}

.stat-card--error::before {
  background: linear-gradient(
    90deg,
    transparent,
    rgb(var(--v-theme-error)),
    transparent
  );
}

.stat-card--orange::before {
  background: linear-gradient(
    90deg,
    transparent,
    rgb(255, 152, 0),
    transparent
  );
}

.stat-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
}

.stat-card:hover::before {
  opacity: 1;
}

.stat-content {
  padding: 24px;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.stat-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 20px;
}

.stat-avatar {
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-card:hover .stat-avatar {
  transform: scale(1.1) rotate(5deg);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.stat-trend {
  opacity: 0;
  transform: translateX(10px);
  transition: all 0.3s ease;
  font-weight: 600;
}

.stat-card:hover .stat-trend {
  opacity: 1;
  transform: translateX(0);
}

.stat-body {
  flex-grow: 1;
  margin-bottom: 16px;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  line-height: 1.2;
  margin-bottom: 8px;
}

.stat-progress {
  margin-top: 16px;
  opacity: 0;
  transform: scaleX(0);
  transition: all 0.5s ease;
  transform-origin: left;
}

.stat-card:hover .stat-progress {
  opacity: 1;
  transform: scaleX(1);
}

.stat-action {
  margin-top: 12px;
  opacity: 0;
  transform: translateY(10px);
  transition: all 0.3s ease;
}

.stat-card:hover .stat-action {
  opacity: 1;
  transform: translateY(0);
}

/* Color variants */
.primary--text {
  color: rgb(var(--v-theme-primary)) !important;
}

.success--text {
  color: rgb(var(--v-theme-success)) !important;
}

.info--text {
  color: rgb(var(--v-theme-info)) !important;
}

.warning--text {
  color: rgb(var(--v-theme-warning)) !important;
}

.secondary--text {
  color: rgb(var(--v-theme-secondary)) !important;
}

.purple--text {
  color: rgb(156, 39, 176) !important;
}

.error--text {
  color: rgb(var(--v-theme-error)) !important;
}

.orange--text {
  color: rgb(255, 152, 0) !important;
}

.text-purple {
  color: rgb(156, 39, 176) !important;
}

.text-orange {
  color: rgb(255, 152, 0) !important;
}

/* Responsive */
@media (max-width: 960px) {
  .stat-content {
    padding: 20px;
  }

  .stat-value {
    font-size: 1.75rem;
  }

  .stat-avatar {
    width: 48px !important;
    height: 48px !important;
  }
}

@media (max-width: 600px) {
  .stat-content {
    padding: 16px;
  }

  .stat-value {
    font-size: 1.5rem;
  }

  .stat-header {
    margin-bottom: 16px;
  }

  .stat-body {
    margin-bottom: 12px;
  }
}

/* Animation keyframes */
@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stat-card {
  animation: slideInUp 0.6s ease-out;
}

.stat-card:nth-child(1) {
  animation-delay: 0.1s;
}
.stat-card:nth-child(2) {
  animation-delay: 0.2s;
}
.stat-card:nth-child(3) {
  animation-delay: 0.3s;
}
.stat-card:nth-child(4) {
  animation-delay: 0.4s;
}
.stat-card:nth-child(5) {
  animation-delay: 0.5s;
}
.stat-card:nth-child(6) {
  animation-delay: 0.6s;
}
.stat-card:nth-child(7) {
  animation-delay: 0.7s;
}
.stat-card:nth-child(8) {
  animation-delay: 0.8s;
}
</style>
