<script setup>
import aeIcon from '@images/icons/payments/ae-icon.png'
import mastercardIcon from '@images/icons/payments/mastercard-icon.png'
import visaIcon from '@images/icons/payments/visa-icon.png'

const lastTransitions = [
  {
    cardImg: visaIcon,
    lastDigit: 'Visa',
    trend: '300,000,000 VND',
  },
  {
    cardImg: mastercardIcon,
    lastDigit: 'Chuyển khoản',
    trend: '28,000,000 VND',
  },
  {
    cardImg: aeIcon,
    lastDigit: 'Momo',
    trend: '62,000,000 VND',
  },
  {
    cardImg: visaIcon,
    lastDigit: '9Pay',
    trend: '12,000,000 VND',
  },
  {
    cardImg: visaIcon,
    lastDigit: 'Tiền Mặt',
    trend: '6,000,000 VND',
  },
]

const resolveStatus = {
  Verified: 'success',
  Rejected: 'error',
  Pending: 'secondary',
}

const moreList = [
  {
    title: 'Refresh',
    value: 'refresh',
  },
  {
    title: 'Download',
    value: 'Download',
  },
  {
    title: 'View All',
    value: 'View All',
  },
]

const getPaddingStyle = index => index ? 'padding-block-end: 1.25rem;' : 'padding-block: 1.25rem;'
</script>

<template>
  <VCard title="Doanh Thu Theo Phương Thức Thanh Toán"
         subtitle="Tổng Quan Doanh Số Theo Tháng">
    <template #append>
      <div class="me-n2">
        <MoreBtn
          size="small"
          :menu-list="moreList"
        />
      </div>
    </template>

    <VDivider />
    <VTable class="text-no-wrap transaction-table">
      <thead>
        <tr>
          <th>Phương Thức</th>
          <th>
            TREND
          </th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="(transition, index) in lastTransitions"
          :key="index"
        >
          <td
            :style="getPaddingStyle(index)"
            style="padding-inline-end: 1.5rem;"
          >
            <div class="d-flex align-center">
              <div class="me-4">
                <VImg
                  :src="transition.cardImg"
                  width="50"
                />
              </div>
              <div>
                <p class="text-base mb-0 text-high-emphasis">
                  {{ transition.lastDigit }}
                </p>
                <p class="text-sm mb-0">
                  {{ transition.cardType }}
                </p>
              </div>
            </div>
          </td>
          <td
            :style="getPaddingStyle(index)"
            style="padding-inline-end: 1.5rem;"
          >
            <div class="text-high-emphasis text-base">
              {{ transition.trend }}
            </div>
          </td>
        </tr>
      </tbody>
    </VTable>
  </VCard>
</template>

<style lang="scss">
.transaction-table {
  &.v-table .v-table__wrapper > table > tbody > tr:not(:last-child) > td,
  &.v-table .v-table__wrapper > table > tbody > tr:not(:last-child) > th {
    border-block-end: none !important;
  }
}
</style>
