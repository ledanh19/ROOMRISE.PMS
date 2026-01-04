<template>
  <VNavigationDrawer
    data-allow-mismatch
    temporary
    :width="1600"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCardText>
        <VTabs
          v-model="currentTab"
          class="border custom-tabs-radius v-tabs-pill"
          :show-arrows="true"
          slider-color="primary"
        >
          <VTab class="responsive-tab">
            <VIcon
              :size="$vuetify.display.smAndDown ? 20 : 28"
              icon="tabler-book"
            />
            <span class="d-none d-sm-inline ms-2">Thông tin phòng</span>
            <span class="d-inline d-sm-none ms-1">Phòng</span>
          </VTab>
          <VTab class="responsive-tab">
            <VIcon
              :size="$vuetify.display.smAndDown ? 20 : 28"
              icon="tabler-users-group"
            />
            <span class="d-none d-sm-inline ms-2">Khách lưu trú</span>
            <span class="d-inline d-sm-none ms-1">Khách</span>
          </VTab>
          <VTab class="responsive-tab">
            <VIcon
              :size="$vuetify.display.smAndDown ? 20 : 28"
              icon="tabler-stars"
            />
            <span class="ms-2">Dịch vụ</span>
          </VTab>
          <VTab class="responsive-tab">
            <VIcon
              :size="$vuetify.display.smAndDown ? 20 : 28"
              icon="tabler-coin"
            />
            <span class="d-none d-sm-inline ms-2">Thông tin thanh toán</span>
            <span class="d-inline d-sm-none ms-1">Thanh toán</span>
          </VTab>
        </VTabs>

        <VCardText class="custom-border">
          <VWindow v-model="currentTab">
            <VWindowItem>
              <VRow>
                <VCol cols="12" sm="12" md="8" lg="8">
                  <VCard
                    elevation="0"
                    class="border rounded-lg"
                    style="border-color: #ddd"
                  >
                    <VCardTitle class="d-flex align-center mb-4 border-b">
                      <strong class="text-h5">Thông tin phòng</strong>
                    </VCardTitle>
                    <VCardItem>
                      <VRow>
                        <VCol cols="12" sm="12" md="6" lg="6">
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Loại phòng</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                {{ room?.room?.name ?? "" }}
                              </div>
                            </VCol>
                          </VRow>
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Nhận phòng</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                {{ formatDate(room.check_in_date) }}
                                ({{
                                  Math.max(
                                    dayjs(room?.check_out_date).diff(
                                      dayjs(room?.check_in_date),
                                      "day"
                                    ),
                                    1
                                  )
                                }}
                                đêm)
                              </div>
                            </VCol>
                          </VRow>
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Trả phòng</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                {{ formatDate(room.check_out_date) }}
                              </div>
                            </VCol>
                          </VRow>
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Mã đặt phòng</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                {{ booking.id }}
                              </div>
                            </VCol>
                          </VRow>
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Số khách</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                {{
                                  totalGuests(
                                    booking.adults,
                                    booking.children,
                                    booking.newborn
                                  )
                                }}
                                khách ( {{ booking.adults }} người lớn
                                <template
                                  v-if="
                                    booking.children && booking.children > 0
                                  "
                                >
                                  , {{ booking.children }} trẻ em
                                </template>
                                <template
                                  v-if="booking.newborn && booking.newborn > 0"
                                >
                                  , {{ booking.newborn }} trẻ sơ sinh
                                </template>
                                )
                              </div>
                            </VCol>
                          </VRow>
                        </VCol>
                        <VCol cols="12" sm="12" md="6" lg="6">
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Phòng</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                {{ room?.room_unit?.name ?? "" }}
                              </div>
                            </VCol>
                          </VRow>
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Giờ đến</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                {{ room.check_in_time }}
                              </div>
                            </VCol>
                          </VRow>
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Giờ đến</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                {{ room.check_out_time }}
                              </div>
                            </VCol>
                          </VRow>
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Tình trạng</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                <VChip
                                  v-if="room.room_status"
                                  :color="statusColor(room.room_status)"
                                  variant="flat"
                                  class="text-white"
                                  size="small"
                                >
                                  {{ room.room_status }}
                                </VChip>
                                <span v-else>-</span>
                              </div>
                            </VCol>
                          </VRow>
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Ghi chú</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">{{ booking.note }}</div>
                            </VCol>
                          </VRow>
                        </VCol>
                      </VRow>
                    </VCardItem>
                  </VCard>
                  <VCard
                    elevation="0"
                    class="border rounded-lg mt-5"
                    style="border-color: #ddd"
                  >
                    <VCardTitle class="d-flex align-center mb-4 border-b">
                      <strong class="text-h5">Kênh</strong>
                    </VCardTitle>
                    <VCardItem>
                      <VRow>
                        <VCol cols="12" sm="12" md="6" lg="6">
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">TT thanh toán</div>
                            </VCol>
                            <VCol cols="6">
                              <VChip
                                v-if="booking.payment_status"
                                :color="statusColor(booking.payment_status)"
                                variant="flat"
                                class="text-white"
                                size="small"
                              >
                                {{ booking.payment_status }}
                              </VChip>
                              <span v-else>-</span>
                            </VCol>
                          </VRow>
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Ngày đặt</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                {{ formatDate(booking.created_at) }}
                              </div>
                            </VCol>
                          </VRow>
                        </VCol>
                        <VCol cols="12" sm="12" md="6" lg="6">
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Nguồn</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                {{ booking.ota_name }}
                              </div>
                            </VCol>
                          </VRow>
                          <VRow>
                            <VCol cols="6">
                              <div class="text-h6">Phí hoa hồng</div>
                            </VCol>
                            <VCol cols="6">
                              <div class="text-h6">
                                {{ booking.commission_fee }}
                              </div>
                            </VCol>
                          </VRow>
                        </VCol>
                      </VRow>
                    </VCardItem>
                  </VCard>
                </VCol>
                <VCol cols="12" sm="12" md="4" lg="4">
                  <VCard
                    elevation="0"
                    class="border rounded-lg"
                    style="border-color: #ddd"
                  >
                    <VCardTitle class="d-flex align-center mb-4 border-b">
                      <VIcon class="me-2" icon="tabler-users"></VIcon>
                      <strong class="text-h5">Khách hàng</strong>
                    </VCardTitle>
                    <VRow class="pa-4">
                      <VCol cols="12">
                        <strong>Tên đầy đủ:</strong>
                        <span class="text-primary font-weight-medium ms-2">
                          {{ customer?.full_name || "–" }}
                        </span>
                      </VCol>
                      <VCol cols="12">
                        <strong>SĐT:</strong>
                        <span class="ms-2">{{ customer?.phone || "–" }}</span>
                      </VCol>
                      <VCol cols="12">
                        <strong>Email:</strong>
                        <span class="ms-2">{{ customer?.email || "–" }}</span>
                      </VCol>
                      <VCol cols="12">
                        <strong>Quốc gia:</strong>
                        <span class="ms-2">{{ customer?.country || "–" }}</span>
                      </VCol>
                      <VCol cols="12">
                        <strong>Loại giấy tờ :</strong>
                        <span class="ms-2">{{ customer?.id_type || "–" }}</span>
                      </VCol>
                      <VCol cols="12">
                        <strong>Số định danh:</strong>
                        <span class="ms-2">{{
                          customer?.id_number || "–"
                        }}</span>
                      </VCol>
                    </VRow>
                  </VCard>
                </VCol>
              </VRow>
            </VWindowItem>
            <VWindowItem>
              <VCard
                elevation="0"
                class="border rounded-lg mt-5"
                style="border-color: #ddd"
              >
                <VCardTitle class="d-flex align-center mb-4 border-b">
                  <strong class="text-h5">Khách lưu trú</strong>
                </VCardTitle>

                <VCardItem v-if="$vuetify.display.smAndDown" class="pa-0">
                  <VCardText
                    v-for="item in bookingCustomers"
                    :key="item.id"
                    class="pa-3"
                  >
                    <div class="mb-2">
                      <div class="font-weight-bold text-primary">
                        {{ item.full_name }}
                      </div>
                    </div>

                    <div class="mb-2">
                      <strong>SĐT:</strong>
                      <span class="ms-2">{{ item.phone || "-" }}</span>
                    </div>

                    <div class="mb-2">
                      <strong>Email:</strong>
                      <span class="ms-2">{{ item.email || "-" }}</span>
                    </div>

                    <div class="mb-2">
                      <strong>Giới tính:</strong>
                      <span class="ms-2">{{ item.gender || "-" }}</span>
                    </div>

                    <div class="mb-2">
                      <strong>Số định danh:</strong>
                      <span class="ms-2">{{ item.id_number || "-" }}</span>
                    </div>
                  </VCardText>
                </VCardItem>
                <VCardItem v-else>
                  <VTable>
                    <thead>
                      <tr>
                        <th>Tên</th>
                        <th>SĐT</th>
                        <th>Email</th>
                        <th>Giới tính</th>
                        <th>Số định danh</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in bookingCustomers" :key="item.id">
                        <td>{{ item.full_name }}</td>
                        <td>{{ item.phone }}</td>
                        <td>{{ item.email }}</td>
                        <td>{{ item.gender }}</td>
                        <td>{{ item.id_number }}</td>
                      </tr>
                    </tbody>
                  </VTable>
                </VCardItem>
              </VCard>
            </VWindowItem>
            <VWindowItem>
              <VCard
                elevation="0"
                class="border rounded-lg mt-5"
                style="border-color: #ddd"
              >
                <VCardTitle class="d-flex align-center mb-4 border-b">
                  <strong class="text-h5">Dịch vụ</strong>
                </VCardTitle>
                <VCardItem> Chưa có dịch vụ nào được thêm vào </VCardItem>
              </VCard>
            </VWindowItem>
            <VWindowItem>
              <VCard
                elevation="0"
                class="border rounded-lg mt-5"
                style="border-color: #ddd"
              >
                <VCardTitle class="d-flex align-center mb-4 border-b">
                  <VIcon class="me-2" icon="tabler-info-circle"></VIcon>
                  <strong class="text-h5">Thông tin thanh toán</strong>
                </VCardTitle>
                <VCardItem>
                  <VRow>
                    <VCol cols="6" sm="6" md="3" lg="3">
                      <div class="mb-2">Tiền phòng</div>
                      <div class="font-weight-medium2">
                        {{ formatCurrency(booking.total_amount) }}
                      </div>
                    </VCol>
                    <VCol cols="6" sm="6" md="3" lg="3">
                      <div class="mb-2">Phí hoa hồng:</div>
                      <div class="">
                        {{ formatCurrency(booking.commission_fee) }}
                      </div>
                    </VCol>
                    <VCol cols="6" sm="6" md="3" lg="3">
                      <div class="mb-2">Giảm giá phòng</div>
                      <div class="">
                        {{ formatCurrency(totalDiscount) }}
                      </div>
                    </VCol>
                    <VCol cols="6" sm="6" md="3" lg="3">
                      <div class="mb-2">PPTT tiền phòng</div>
                      <div class="">{{ booking.room_payment_method }}</div>
                    </VCol>
                  </VRow>
                  <VRow>
                    <VCol cols="6" sm="6" md="3" lg="3">
                      <div class="mb-2">Tiền phòng (khách thanh toán)</div>
                      <div class="">
                        {{ formatCurrency(booking.customer_payment_amount) }}
                      </div>
                    </VCol>
                    <VCol cols="6" sm="6" md="3" lg="3">
                      <div class="mb-2">Tiền dịch vụ:</div>
                      <div class="">{{ "-" }}</div>
                    </VCol>
                  </VRow>
                  <VRow>
                    <VCol cols="6" sm="6" md="3" lg="3">
                      <div class="mb-2">Tổng số tiền:</div>
                      <div class="">
                        {{ formatCurrency(booking.customer_payment_amount) }}
                      </div>
                    </VCol>
                    <VCol cols="6" sm="6" md="3" lg="3">
                      <div class="mb-2">Trạng thái thanh toán:</div>
                      <div class="">
                        <VChip
                          v-if="booking.payment_status"
                          :color="statusColor(booking.payment_status)"
                          variant="flat"
                          class="text-white"
                          size="small"
                        >
                          {{ booking.payment_status }}
                        </VChip>
                        <span v-else>-</span>
                      </div>
                    </VCol>
                    <VCol cols="6" sm="6" md="3" lg="3">
                      <div class="mb-2">Đã thanh toán:</div>
                      <div class="">{{ formatCurrency(booking.paid) }}</div>
                    </VCol>
                    <VCol cols="6" sm="6" md="3" lg="3">
                      <div class="mb-2">Còn lại:</div>
                      <div class="">
                        {{ formatCurrency(booking.remaining) }}
                      </div>
                    </VCol>
                  </VRow>
                </VCardItem>
              </VCard>

              <VCard
                elevation="0"
                class="border rounded-lg mt-5"
                style="border-color: #ddd"
              >
                <VCardTitle class="d-flex align-center mb-4 border-b">
                  <VIcon class="me-2" icon="tabler-cash"></VIcon>
                  <strong class="text-h5">Lịch sử thanh toán</strong>
                </VCardTitle>
                <VCardItem v-if="$vuetify.display.smAndDown" class="pt-0">
                  <template v-for="item in paymentHistories" :key="item.id">
                    <VCardText class="pa-0 pt-0">
                      <div
                        class="d-flex justify-space-between align-center mb-2"
                      >
                        <div>
                          <div class="font-weight-bold">Mã thanh toán:</div>
                          <div>{{ item.id }}</div>
                        </div>
                        <div>
                          <div class="font-weight-bold">Số tiền:</div>

                          <div>
                            {{ item.type === "income" ? "+" : "-"
                            }}{{ formatCurrency(item.amount) }}
                          </div>
                        </div>
                      </div>

                      <div
                        class="d-flex justify-space-between align-center mb-2"
                      >
                        <div>
                          <div class="font-weight-bold">Phương thức:</div>
                          <div>{{ item.payment_method }}</div>
                        </div>
                        <div>
                          <div class="font-weight-bold">Ngày thanh toán:</div>
                          <div>{{ formatDate(item.date) }}</div>
                        </div>
                      </div>

                      <div
                        class="d-flex justify-space-between align-center mb-2"
                      >
                        <div>
                          <div class="font-weight-bold">Nhân viên:</div>
                          <div>{{ item.created_by }}</div>
                        </div>
                        <div class="">
                          <div class="font-weight-bold">Nội dung:</div>
                          <div>{{ item.note }}</div>
                        </div>
                      </div>
                    </VCardText>
                    <VDivider class="my-2" />
                  </template>
                </VCardItem>
                <VCardItem v-else>
                  <VTable>
                    <thead>
                      <tr>
                        <th>Mã thanh toán</th>
                        <th>Phương thức</th>
                        <th>Số tiền</th>
                        <th>Ngày thanh toán</th>
                        <th>Nhân viên</th>
                        <th>Nội dung</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in paymentHistories" :key="item.id">
                        <td>{{ item.id }}</td>
                        <td>{{ item.payment_method }}</td>
                        <td>
                          {{ item.type === "income" ? "+" : "-"
                          }}{{ formatCurrency(item.amount) }}
                        </td>
                        <td>{{ formatDate(item.date) }}</td>
                        <td>{{ item.created_by }}</td>
                        <td>{{ item.note }}</td>
                      </tr>
                    </tbody>
                  </VTable>
                </VCardItem>
              </VCard>
              <VCard
                elevation="0"
                class="border rounded-lg mt-5"
                style="border-color: #ddd"
              >
                <VCardTitle class="d-flex align-center mb-4 border-b">
                  <VIcon class="me-2" icon="tabler-cash"></VIcon>
                  <strong class="text-h5">Công nợ</strong>
                </VCardTitle>
                <VCardItem v-if="$vuetify.display.smAndDown" class="pt-0">
                  <template
                    v-for="incomeExpense in paymentHistoriesPartner"
                    :key="'partner-' + incomeExpense.id"
                  >
                    <VCardText class="pa-0 pt-0">
                      <div
                        class="d-flex justify-space-between align-center mb-2"
                      >
                        <div>
                          <div class="font-weight-bold">Mã thanh toán:</div>
                          <div>{{ incomeExpense.id }}</div>
                        </div>
                        <div>
                          <div class="font-weight-bold">Số tiền:</div>
                          <div>
                            {{ formatCurrency(incomeExpense.pivot.amount) }}
                          </div>
                        </div>
                      </div>

                      <div
                        class="d-flex justify-space-between align-center mb-2"
                      >
                        <div>
                          <div class="font-weight-bold">Phương thức:</div>
                          <div>{{ incomeExpense.payment_method }}</div>
                        </div>
                        <div>
                          <div class="font-weight-bold">Ngày thanh toán:</div>
                          <div>{{ formatDate(incomeExpense.date) }}</div>
                        </div>
                      </div>

                      <div
                        class="d-flex justify-space-between align-center mb-2"
                      >
                        <div>
                          <div class="font-weight-bold">Nhân viên:</div>
                          <div>{{ incomeExpense.created_by }}</div>
                        </div>
                        <div class="">
                          <div class="font-weight-bold">Nội dung:</div>
                          <div>{{ incomeExpense.note }}</div>
                        </div>
                      </div>
                    </VCardText>
                    <VDivider class="my-2" />
                  </template>
                </VCardItem>
                <VCardItem v-else>
                  <VTable>
                    <thead>
                      <tr>
                        <th>Mã thanh toán</th>
                        <th>Phương thức</th>
                        <th>Số tiền</th>
                        <th>Ngày thanh toán</th>
                        <th>Nhân viên</th>
                        <th>Nội dung</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template
                        v-for="incomeExpense in paymentHistoriesPartner"
                        :key="'partner-' + incomeExpense.id"
                      >
                        <tr
                          v-if="incomeExpense.pivot?.booking_id === booking.id"
                        >
                          <td>{{ incomeExpense.id }}</td>
                          <td>{{ incomeExpense.payment_method }}</td>
                          <td>
                            {{ formatCurrency(incomeExpense.pivot.amount) }}
                          </td>
                          <td>{{ formatDate(incomeExpense.date) }}</td>
                          <td>{{ incomeExpense.created_by }}</td>
                          <td>{{ incomeExpense.note }}</td>
                        </tr>
                      </template>
                    </tbody>
                  </VTable>
                </VCardItem>
              </VCard>
              <VCol cols="12">
                <VCard
                  elevation="0"
                  class="border rounded-lg mt-5"
                  style="border-color: #ddd"
                >
                  <VCardTitle class="d-flex align-center mb-4 border-b">
                    <VIcon class="me-2" icon="tabler-cash"></VIcon>
                    <strong class="text-h5">OTA</strong>
                  </VCardTitle>

                  <VCardItem v-if="$vuetify.display.smAndDown" class="pt-0">
                    <template
                      v-for="incomeExpense in otaHistories"
                      :key="'partner-' + incomeExpense.id"
                    >
                      <VCardText class="pa-0 pt-0">
                        <div
                          class="d-flex justify-space-between align-center mb-2"
                        >
                          <div>
                            <div class="font-weight-bold">Mã thanh toán:</div>
                            <div>{{ incomeExpense.id }}</div>
                          </div>
                          <div>
                            <div class="font-weight-bold">Số tiền:</div>
                            <div>
                              {{ formatCurrency(incomeExpense.pivot.amount) }}
                            </div>
                          </div>
                        </div>

                        <div
                          class="d-flex justify-space-between align-center mb-2"
                        >
                          <div>
                            <div class="font-weight-bold">Phương thức:</div>
                            <div>{{ incomeExpense.payment_method }}</div>
                          </div>
                          <div>
                            <div class="font-weight-bold">Ngày thanh toán:</div>
                            <div>{{ formatDate(incomeExpense.date) }}</div>
                          </div>
                        </div>

                        <div
                          class="d-flex justify-space-between align-center mb-2"
                        >
                          <div>
                            <div class="font-weight-bold">Nhân viên:</div>
                            <div>{{ incomeExpense.created_by }}</div>
                          </div>
                          <div class="">
                            <div class="font-weight-bold">Nội dung:</div>
                            <div>{{ incomeExpense.note }}</div>
                          </div>
                        </div>
                      </VCardText>
                      <VDivider class="my-2" />
                    </template>
                  </VCardItem>
                  <VCardItem v-else>
                    <VTable>
                      <thead>
                        <tr>
                          <th>Mã thanh toán</th>
                          <th>Phương thức</th>
                          <th>Số tiền</th>
                          <th>Ngày thanh toán</th>
                          <th>Nhân viên</th>
                          <th>Nội dung</th>
                        </tr>
                      </thead>
                      <tbody>
                        <template
                          v-for="incomeExpense in otaHistories"
                          :key="'partner-' + incomeExpense.id"
                        >
                          <tr
                            v-if="
                              incomeExpense.pivot?.booking_id === booking.id
                            "
                          >
                            <td>{{ incomeExpense.id }}</td>
                            <td>{{ incomeExpense.payment_method }}</td>
                            <td>
                              {{ formatCurrency(incomeExpense.pivot.amount) }}
                            </td>
                            <td>{{ formatDate(incomeExpense.date) }}</td>
                            <td>{{ incomeExpense.created_by }}</td>
                            <td>{{ incomeExpense.note }}</td>
                          </tr>
                        </template>
                      </tbody>
                    </VTable>
                  </VCardItem>
                </VCard>
              </VCol>
            </VWindowItem>
          </VWindow>
        </VCardText>

        <VCardItem>
          <div class="d-flex gap-2 flex-wrap justify-center justify-md-start">
            <div
              class="d-flex gap-2 flex-wrap justify-center w-100"
              v-if="$vuetify.display.smAndDown"
            >
              <VBtn
                @click="isCheckInDialogVisible = true"
                v-if="room.room_status == 'Chưa nhận phòng'"
                color="primary"
                size="small"
              >
                <VIcon size="18" icon="tabler-book-download" class="mr-1" />
                Nhận phòng
              </VBtn>

              <VBtn
                @click="isCheckOutDialogVisible = true"
                v-if="room.room_status == 'Đã nhận phòng'"
                color="primary"
                size="small"
              >
                <VIcon size="18" icon="tabler-book-upload" class="mr-1" />
                Trả phòng
              </VBtn>
              <VBtn
                @click="isPayDialogVisible = true"
                v-if="
                  booking.status !== 'Hủy' &&
                  booking.payment_status !== 'Đã thanh toán'
                "
                color="primary"
                size="small"
              >
                <VIcon size="18" icon="tabler-coin" class="mr-1" />
                Thanh toán
              </VBtn>
              <VBtn variant="outlined" size="small">
                <VIcon icon="tabler-dots-vertical" />
                <VMenu activator="parent">
                  <VList>
                    <VListItem
                      @click="isUndoCheckOutDialogVisible = true"
                      v-if="room.room_status == 'Đã trả phòng'"
                    >
                      <template #prepend>
                        <VIcon icon="tabler-arrow-back-up" size="20" />
                      </template>
                      <VListItemTitle>Hoàn tác trả phòng</VListItemTitle>
                    </VListItem>

                    <VListItem
                      @click="isUndoCheckInDialogVisible = true"
                      v-if="room.room_status == 'Đã nhận phòng'"
                    >
                      <template #prepend>
                        <VIcon icon="tabler-arrow-back-up" size="20" />
                      </template>
                      <VListItemTitle>Hoàn tác nhận phòng</VListItemTitle>
                    </VListItem>

                    <VListItem
                      @click="isAddDialogVisible = true"
                      v-if="
                        booking.status !== 'Hủy' &&
                        booking.status !== 'Hoàn thành'
                      "
                    >
                      <template #prepend>
                        <VIcon icon="tabler-edit" size="20" />
                      </template>
                      <VListItemTitle>Đổi ngày/Phòng</VListItemTitle>
                    </VListItem>

                    <VListItem @click="isAddCustomerDialogVisible = true">
                      <template #prepend>
                        <VIcon icon="tabler-users-plus" size="20" />
                      </template>
                      <VListItemTitle>Quản lý khách</VListItemTitle>
                    </VListItem>

                    <VListItem
                      @click="isCancelDialogVisible = true"
                      v-if="
                        booking.status !== 'Hủy' &&
                        booking.status !== 'Hoàn thành' &&
                        booking.payment_type !== 'OTA Collect'
                      "
                    >
                      <template #prepend>
                        <VIcon icon="tabler-circle-x" size="20" />
                      </template>
                      <VListItemTitle>Hủy đặt phòng</VListItemTitle>
                    </VListItem>
                  </VList>
                </VMenu>
              </VBtn>

              <VBtn
                @click="closeNavigationDrawer"
                variant="outlined"
                color="secondary"
                size="small"
              >
                Đóng
              </VBtn>
            </div>
            <div class="d-flex gap-3 flex-wrap" v-else>
              <VBtn
                @click="isUndoCheckOutDialogVisible = true"
                v-if="room.room_status == 'Đã trả phòng'"
              >
                <VIcon size="22" class="mr-2" icon="tabler-arrow-back-up" />
                Hoàn tác trả phòng
              </VBtn>

              <VBtn
                @click="isUndoCheckInDialogVisible = true"
                v-if="room.room_status == 'Đã nhận phòng'"
              >
                <VIcon size="22" class="mr-2" icon="tabler-arrow-back-up" />
                Hoàn tác nhận phòng
              </VBtn>

              <VBtn
                @click="isAddDialogVisible = true"
                v-if="
                  booking.status !== 'Hủy' && booking.status !== 'Hoàn thành'
                "
              >
                <VIcon size="22" class="mr-2" icon="tabler-edit" />
                Đổi ngày/Phòng
              </VBtn>

              <VBtn @click="isAddCustomerDialogVisible = true">
                <VIcon size="22" class="mr-2" icon="tabler-users-plus" />
                Khách
              </VBtn>

              <VBtn
                @click="isCheckInDialogVisible = true"
                v-if="room.room_status == 'Chưa nhận phòng'"
              >
                <VIcon size="22" class="mr-2" icon="tabler-book-download" />
                Nhận phòng
              </VBtn>

              <VBtn
                @click="isCheckOutDialogVisible = true"
                v-if="room.room_status == 'Đã nhận phòng'"
              >
                <VIcon size="22" class="mr-2" icon="tabler-book-upload" />
                Trả phòng
              </VBtn>

              <VBtn
                @click="isPayDialogVisible = true"
                v-if="
                  booking.status !== 'Hủy' &&
                  booking.payment_status !== 'Đã thanh toán'
                "
              >
                <VIcon size="22" icon="tabler-coin" class="mr-2" />
                Thanh toán
              </VBtn>

              <VBtn
                @click="isCancelDialogVisible = true"
                v-if="
                  booking.status !== 'Hủy' &&
                  booking.status !== 'Hoàn thành' &&
                  booking.payment_type !== 'OTA Collect'
                "
                variant="outlined"
                color="secondary"
              >
                <VIcon size="22" class="mr-2" icon="tabler-circle-x" />
                Hủy đặt phòng
              </VBtn>

              <VBtn
                @click="closeNavigationDrawer"
                variant="outlined"
                color="secondary"
              >
                Đóng
              </VBtn>
            </div>
          </div>
        </VCardItem>
      </VCardText>
    </PerfectScrollbar>
  </VNavigationDrawer>

  <AddNewCustomer
    v-model:is-add-customer-dialog-visible="isAddCustomerDialogVisible"
    :booking="props.booking"
    :bookingCustomers="props.bookingCustomers"
  />
  <UpdatePayment
    v-model:is-pay-dialog-visible="isPayDialogVisible"
    :booking="props.booking"
  />
  <CustomerCheckIn
    v-model:is-check-in-dialog-visible="isCheckInDialogVisible"
    :customer="props.customer"
    :booking="props.booking"
    :roomId="room.id"
    @update:customerCheckIn="closeNavigationDrawer"
  />
  <FormAddDialog
    v-model:is-add-dialog-visible="isAddDialogVisible"
    :data="props.room"
    mode="changeDate"
    @update:updateBooking="closeNavigationDrawer"
  />
  <CancelBooking
    v-model:is-cancel-dialog-visible="isCancelDialogVisible"
    :booking="props.booking"
  />

  <UpdatePayment
    v-model:is-pay-dialog-visible="isCheckOutDialogVisible"
    :booking="props.booking"
    checkout="checkout"
    @update:Payment="closeNavigationDrawer"
  />

  <VDialog v-model="isUndoCheckInDialogVisible" class="v-dialog-sm">
    <DialogCloseBtn @click="isUndoCheckInDialogVisible = false" />

    <VCard>
      <VCardText class="d-flex align-center justify-center flex-column">
        <VBtn
          variant="tonal"
          color="info"
          size="40"
          icon="tabler-alert-triangle"
        />
        <div class="text-h4 mt-3 font-weigth-bold">Hoàn tác nhận phòng</div>
        <div class="text-h6">Bạn có chắc chắn khi thực hiện thao tác này ?</div>
        <div class="d-flex gap-3 mt-5 w-100">
          <VBtn
            @click="isUndoCheckInDialogVisible = false"
            class="w-50"
            variant="outlined"
            color="secondary"
            >Hủy bỏ</VBtn
          >
          <VBtn class="w-50" @click="UndoCheckIn">Xác nhận</VBtn>
        </div>
      </VCardText>
    </VCard>
  </VDialog>

  <VDialog v-model="isUndoCheckOutDialogVisible" class="v-dialog-sm">
    <DialogCloseBtn @click="isUndoCheckOutDialogVisible = false" />

    <VCard>
      <VCardText class="d-flex align-center justify-center flex-column">
        <VBtn
          variant="tonal"
          color="info"
          size="40"
          icon="tabler-alert-triangle"
        />
        <div class="text-h4 mt-3 font-weigth-bold">Hoàn tác trả phòng</div>
        <div class="text-h6">Bạn có chắc chắn khi thực hiện thao tác này ?</div>
        <div class="d-flex gap-3 mt-5 w-100">
          <VBtn
            @click="isUndoCheckOutDialogVisible = false"
            class="w-50"
            variant="outlined"
            color="secondary"
            >Hủy bỏ</VBtn
          >
          <VBtn class="w-50" @click="UndoCheckOut">Xác nhận</VBtn>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>
<script setup>
import EventBus from "@/pages/Events/eventBus";
import avatar1 from "@images/avatars/avatar-1.png";
import axios from "axios";
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { toast } from "vue3-toastify";
import { VForm } from "vuetify/components/VForm";
import AddNewCustomer from "./AddNewCustomer.vue";
import UpdatePayment from "./UpdatePayment.vue";
import CustomerCheckIn from "./CustomerCheckIn.vue";
import FormAddDialog from "./FormAddDialog.vue";
import CancelBooking from "./CancelBooking.vue";
import dayjs from "dayjs";
const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  booking: Object,
  customer: Object,
  paymentHistories: Object,
  paymentHistoriesPartner: Object,
  otaHistories: Object,
  room: Object,
  bookingCustomers: Object,
});
const totalDiscount = computed(() => {
  return (props.booking.booking_rooms || []).reduce((total, item) => {
    return total + (Number(item.discount) || 0);
  }, 0);
});
const currentTab = ref(0);
const isAddCustomerDialogVisible = ref(false);
const isPayDialogVisible = ref(false);
const isCheckInDialogVisible = ref(false);
const isCheckOutDialogVisible = ref(false);
const isAddDialogVisible = ref(false);
const isUndoCheckInDialogVisible = ref(false);
const isUndoCheckOutDialogVisible = ref(false);
const isCancelDialogVisible = ref(false);
const emit = defineEmits(["update:isDrawerOpen", "update:undoCheck"]);
const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false);
  currentTab.value = 0;
};
const handleDrawerModelValueUpdate = (val) => {
  emit("update:isDrawerOpen", val);
  currentTab.value = 0;
};
function totalGuests(adults, children, newborn) {
  return Number(adults || 0) + Number(children || 0) + Number(newborn || 0);
}
const UndoCheckIn = async () => {
  const res = await axios.post(
    route("bookings.undoCheckIn", { id: props.room.id })
  );
  toast.success(res.data.message, { theme: "colored" });
  closeNavigationDrawer();
  emit("update:undoCheck");
  isUndoCheckInDialogVisible.value = false;
};

const UndoCheckOut = async () => {
  const res = await axios.post(
    route("bookings.undoCheckOut", {
      booking: props.booking.id,
      room: props.room.id,
    })
  );

  toast.success(res.data.message, { theme: "colored" });
  closeNavigationDrawer();
  emit("update:undoCheck");
  isUndoCheckOutDialogVisible.value = false;
};

const statusColor = (status) => {
  switch (status) {
    case "Mới":
      return "primary";
    case "Xác nhận":
    case "Đã thanh toán":
    case "Đã nhận phòng":
      return "success";
    case "Yêu cầu":
    case "Đã cọc":
    case "Chưa nhận phòng":
      return "warning";

    case "Hoàn thành":
    case "Chờ thanh toán":
      return "secondary";
    case "Chưa thanh toán":
    case "Hủy":
      return "error";
    case "Đã trả phòng":
      return "info";
    default:
      return "grey";
  }
};
</script>
<style scoped>
.v-tabs .v-btn .v-icon {
  font-size: 2rem !important;
}
.custom-tabs-radius {
  border-top-left-radius: 1rem;
  border-top-right-radius: 1rem;
}

.custom-border {
  border-bottom-left-radius: 1rem;
  border-bottom-right-radius: 1rem;
  border: 1px solid rgba(47, 43, 61, 0.12);
  border-top: 0;
}

.v-tabs.v-tabs-pill.v-slide-group {
  padding: 1rem;
  margin: 0rem;
}
.v-tabs.v-tabs-pill .v-slide-group__container {
  padding: 0;
  margin: 0;
}
</style>
