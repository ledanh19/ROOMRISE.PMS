<!-- src/views/reports/PaymentReportWIP.vue -->
<script setup>
import Layout from "@/layouts/blank.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
  <Head title="Báo cáo thanh toán | Room Rise" />
  <Layout>
    <div class="wip-center">
      <VCard class="wip-card">
        <div class="wip-body">
          <div class="wip-inner">
            <VIcon icon="tabler-tools" size="40" class="wip-icon" />
            <p class="wip-text">Đang phát triển</p>
            <p class="wip-sub">Chức năng sẽ sớm khả dụng.</p>
          </div>
        </div>
      </VCard>
    </div>
  </Layout>
</template>

<style scoped>
.wip-center {
}

.wip-card {
  display: grid;
  overflow: hidden;
  border: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 8%, transparent);
  border-radius: 14px;
  background: var(--v-theme-surface);
  grid-template-rows: auto 1fr;
  min-block-size: 60vh;
}

.wip-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-block-end: 1px solid
    color-mix(in oklab, var(--v-theme-on-surface) 10%, transparent);
  padding-block: 14px;
  padding-inline: 16px;
}

.wip-body {
  display: grid;
  box-sizing: border-box;
  grid-row: 1 / -1;
  min-block-size: 0;
  padding-block: 32px;
  padding-inline: 16px;
  place-items: center;
}

.wip-inner {
  display: inline-grid;
  box-sizing: border-box;
  gap: 8px;
  inline-size: 100%;
  max-inline-size: 720px;
  place-items: center center;
  text-align: center;
}

.wip-icon {
  opacity: 0.7;
}

.wip-text {
  margin: 0;
  font-weight: 600;
}

.wip-sub {
  margin: 0;
  font-size: 0.9rem;
  opacity: 0.7;
}
</style>
