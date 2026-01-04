<template>
  <Head title="Quản lý kênh | Room Rise" />
  <Layout>
    <RequiredSpecificProperty>
      <VCard class="mb-4">
        <VCardText>
          <VRow justify="space-between">
            <VCol cols="12" md="9">
              <VRow>
                <VCol cols="12" md="4">
                  <AppSelect
                    v-model="filtersData.property_id"
                    :items="propertyOptions"
                    item-title="name"
                    item-value="id"
                    label="Chỗ nghỉ"
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <AppSelect
                    v-model="filtersData.room_type_ids"
                    :items="roomTypeOptions"
                    item-title="name"
                    item-value="id"
                    label="Loại phòng"
                    placeholder="Loại phòng"
                    multiple
                    clearable
                    chips
                  />
                </VCol>
                <VCol cols="12" md="3">
                  <AppSelect
                    v-model="filtersData.rate_plan_ids"
                    :items="ratePlanOptions"
                    item-title="title"
                    item-value="id"
                    label="Gói giá"
                    placeholder="Gói giá"
                    multiple
                    clearable
                    chips
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <AppSelect
                    v-model="filtersData.restriction_types"
                    :items="restrictionTypeOptions"
                    item-title="label"
                    item-value="value"
                    label="Loại hạn chế"
                    placeholder="Loại hạn chế"
                    multiple
                    clearable
                    chips
                  />
                </VCol>
              </VRow>
            </VCol>
            <VCol
              cols="12"
              md="3"
              class="d-flex flex justify-end"
              style="position: relative"
            >
              <!-- <v-btn
                color="primary"
                variant="text"
                @click="syncChannex"
                :disabled="form.processing"
                class="pr-1"
              >
                <span :class="{ rotating: form.processing }">🔄</span>
                <span class="ml-2">
                  {{ form.processing ? "Syncing..." : "Full Sync" }}
                </span>
              </v-btn>
              <VTooltip location="bottom right">
                <template #activator="{ props }">
                  <VIcon
                    v-bind="props"
                    icon="tabler-help"
                    size="20"
                    color="primary"
                    style="cursor: pointer; margin-left: 2px"
                  />
                </template>
                <div style="width: 200px" color="green">
                  Đồng bộ 500 ngày cho số lượng phòng trống, giá và hạn chế chỗ
                  nghỉ hiện tại với các OTA.
                </div>
              </VTooltip> -->
              <v-btn
                color="primary"
                @click="isPriceDrawerOpen = true"
                class="ml-4"
              >
                <VIcon icon="tabler-currency-dollar" size="20" class="mr-2" />
                Cài đặt giá
              </v-btn>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
      <VCard>
        <VCardText>
          <div class="d-flex justify-space-between flex-mobile mb-4">
            <div class="d-flex align-center gap-4">
              <VBtn
                variant="tonal"
                icon="tabler-arrow-narrow-left"
                @click="prevDate"
              />
              <AppDateTimePicker
                v-model="filtersData.start_date"
                :config="{
                  dateFormat: 'Y-m-d',
                  minDate: new Date().toISOString().slice(0, 10),
                  maxDate: maxDate,
                }"
                style="width: 150px"
              />
              <VBtn
                variant="tonal"
                icon="tabler-arrow-narrow-right"
                @click="nextDate"
              />
            </div>
            <div class="d-flex justify-end gap-4">
              <VBtn
                color="secondary"
                variant="tonal"
                :disabled="!isDirty"
                @click="resetAllChanges"
              >
                Đặt lại
              </VBtn>
              <VBtn :disabled="!isDirty" @click="saveAllChanges">
                Lưu thay đổi
              </VBtn>
            </div>
          </div>
          <template v-if="filteredRoomTypes.length">
            <div class="table-container">
              <VTable density="compact" class="inventory-table" fixed-header>
                <thead class="table-header header-style">
                  <tr>
                    <th class="min-w-[120px]! sticky-col-1">Gói giá</th>
                    <th class="sticky-col-2">Nguồn</th>
                    <th class="sticky-col-3">Trường</th>
                    <th
                      v-for="date in dates"
                      :key="date.fullDate"
                      class="text-center py-2"
                    >
                      <div class="font-weight-bold">{{ date.dayOfWeek }}</div>
                      <div>{{ date.dayOfMonth }}</div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <template
                    v-for="roomType in filteredRoomTypes"
                    :key="roomType.id"
                  >
                    <tr class="">
                      <td
                        colspan="2"
                        class="text-primary font-weight-bold bg-grey-lighten-2 py-2 sticky-col-1"
                      >
                        {{ roomType.name }}
                      </td>
                      <td style="padding: 8px !important" class="sticky-col-3">
                        SL
                      </td>
                      <td
                        v-for="date in dates"
                        :key="date.fullDate"
                        class="text-center clickable-cell"
                        :class="{
                          'changed-cell': hasPendingChange(date, roomType),
                          'text-error': isZeroAvailability(
                            getDisplayValue(date, roomType)
                          ),
                          'highlighted-cell': isCellHighlighted(
                            date,
                            'avl',
                            roomType
                          ),
                          'zero-availability-row': isZeroAvailabilityRow(
                            date,
                            roomType
                          ),
                        }"
                        :data-date="date.fullDate"
                        @click="openOverrideDialog('avl', roomType, date)"
                        @mousedown="
                          handleMouseDown($event, 'avl', roomType, date)
                        "
                      >
                        <div
                          v-if="
                            formatDisplayValue(
                              getDisplayValue(date, roomType)
                            ) <= 0
                          "
                          class="rate-value py-1 bg-red"
                        >
                          {{
                            formatDisplayValue(getDisplayValue(date, roomType))
                          }}
                        </div>
                        <div v-else class="py-1 bg-green rounded">
                          {{
                            formatDisplayValue(getDisplayValue(date, roomType))
                          }}
                        </div>
                      </td>
                    </tr>
                    <!-- Các dòng rate plan và OTA -->
                    <template
                      v-for="ratePlan in getFilteredRatePlans(roomType)"
                      :key="ratePlan.id"
                    >
                      <template
                        v-for="(row, rowIdx) in getFilteredRatePlanRows(
                          ratePlan
                        )"
                        :key="row.key"
                      >
                        <tr>
                          <!-- Cột 1: RatePlan title, merge dòng -->
                          <td
                            v-if="rowIdx === 0"
                            :rowspan="getFilteredRatePlanRows(ratePlan).length"
                            style="padding-inline-start: 12px !important"
                            class="py-2 px-3 font-weight-bold sticky-col-1"
                          >
                            {{ ratePlan.title }}
                          </td>
                          <!-- Cột 2: Local/OTA name, merge dòng -->
                          <td
                            v-if="row.showOtaName"
                            :rowspan="row.otaRowspan"
                            class="py-2 px-3 sticky-col-2"
                            style="padding-inline-start: 12px !important"
                          >
                            {{ row.otaName }}
                          </td>
                          <!-- Cột 3: Trường -->
                          <td
                            style="padding: 8px !important"
                            class="sticky-col-3"
                          >
                            <v-tooltip v-if="row.fullLabel" location="top">
                              <template #activator="{ props }">
                                <span
                                  v-bind="props"
                                  class="cursor-help"
                                  style="font-size: 0.85em"
                                >
                                  {{ row.label }}
                                </span>
                              </template>
                              <span>{{ row.fullLabel }}</span>
                            </v-tooltip>
                            <span v-else class="" style="font-size: 0.85em">{{
                              row.label
                            }}</span>
                          </td>
                          <!-- Các cột ngày -->
                          <td
                            v-for="date in dates"
                            :key="date.fullDate"
                            class="text-center clickable-cell"
                            :class="{
                              'changed-cell': hasPendingChange(
                                date,
                                roomType,
                                ratePlan,
                                row.type,
                                row.otaObj?.id,
                                row.occupancy
                              ),
                              'disabled-cell': row.disabled,
                              'highlighted-cell': isCellHighlighted(
                                date,
                                row.type,
                                roomType,
                                ratePlan,
                                row.otaObj,
                                row.occupancy
                              ),
                              'zero-availability-row':
                                isZeroAvailabilityRow(date, roomType) &&
                                (row.type === 'local' ||
                                  row.type === 'ota_rate'),
                              'stop-sell-row':
                                isStopSellRow(
                                  date,
                                  roomType,
                                  ratePlan,
                                  row.type,
                                  row.otaObj?.id,
                                  row.occupancy
                                ) &&
                                (row.type === 'local' ||
                                  row.type === 'ota_rate'),
                            }"
                            :data-date="date.fullDate"
                            @click="
                              !row.disabled &&
                                openOverrideDialog(
                                  row.type,
                                  roomType,
                                  date,
                                  ratePlan,
                                  row.otaObj,
                                  row.occupancy
                                )
                            "
                            @mousedown="
                              !row.disabled &&
                                handleMouseDown(
                                  $event,
                                  row.type,
                                  roomType,
                                  date,
                                  ratePlan,
                                  row.otaObj,
                                  row.occupancy
                                )
                            "
                          >
                            <div
                              class="rate-value"
                              :class="{ 'disabled-text': row.disabled }"
                            >
                              <span
                                v-if="isBooleanField(row.type)"
                                v-html="
                                  formatBooleanValue(
                                    row.getValue(date, roomType, ratePlan)
                                  )
                                "
                              ></span>
                              <span v-else>
                                {{
                                  formatDisplayValue(
                                    row.getValue(date, roomType, ratePlan),
                                    row.type === "ota_rate" ||
                                      row.type === "local"
                                  )
                                }}
                              </span>
                            </div>
                          </td>
                        </tr>
                      </template>
                    </template>
                  </template>
                </tbody>
              </VTable>
            </div>
          </template>
          <div v-else type="error">
            Không tìm thấy loại phòng cho chỗ nghỉ đã chọn.
          </div>
        </VCardText>
      </VCard>
    </RequiredSpecificProperty>

    <!-- Value Override Dialog -->
    <VNavigationDrawer
      v-model="isDialogVisible"
      width="800"
      temporary
      location="end"
    >
      <VCard class="pa-6" flat>
        <VCardTitle class="px-6">
          Cập nhật
          <template v-if="overrideContext.type === 'avl'"
            >số phòng trống</template
          >
          <template v-else-if="overrideContext.type === 'local'"
            >giá đặt phòng trực tiếp</template
          >
          <template v-else-if="overrideContext.type === 'ota_rate'"
            >giá OTA</template
          >
          <template v-else>
            {{ restrictionLabels[overrideContext.field] }}
          </template>
        </VCardTitle>
        <VCardText class="pt-3">
          <div class="d-flex justify-space-between">
            <p class="mb-4">
              <strong class="mr-2">Loại phòng:</strong>
              <span class="text-primary font-weight-bold">{{
                overrideContext.roomType?.name
              }}</span>
            </p>
            <p v-if="overrideContext.ratePlan" class="mb-4">
              <strong class="mr-2">Gói giá:</strong>
              <VChip>{{ overrideContext.ratePlan?.title }}</VChip>
            </p>
          </div>
          <p v-if="overrideContext.ota" class="mb-4">
            <strong class="mr-2">Kênh đặt phòng:</strong>
            <VChip>{{ overrideContext.ota?.booking_source?.name }}</VChip>
          </p>
          <p v-else class="mb-4">
            <strong>Kênh đặt phòng:</strong> <VChip>Trực Tiếp</VChip>
          </p>

          <!-- Show current rates if editing a rate -->
          <!-- <div v-if="overrideContext.type !== 'avl'" class="mb-4">
            <div class="d-flex flex-column gap-2">
              <div
                v-if="overrideContext.type === 'local'"
                class="d-flex align-center"
              >
                <span class="me-2"><strong>Giá hiện tại: </strong></span>
                <span>{{
                  formatDisplayValue(
                    getDisplayValue(
                      overrideContext.date,
                      overrideContext.roomType,
                      overrideContext.ratePlan,
                      "local"
                    )?.price,
                    true
                  )
                }}</span>
              </div>
              <div
                v-if="overrideContext.type === 'ota'"
                class="d-flex align-center"
              >
                <span class="me-2"><strong>Giá hiện tại: </strong></span>
                <span>{{
                  formatDisplayValue(overrideContext.ota?.rate, true)
                }}</span>
              </div>
            </div>
          </div> -->

          <VRow>
            <VCol cols="12" md="6">
              <AppDateTimePicker
                v-model="overrideFormData.dateRange.start"
                label="Ngày bắt đầu"
                :config="{
                  minDate: new Date().toISOString().slice(0, 10),
                  maxDate: maxDate,
                  dateFormat: 'Y-m-d',
                }"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppDateTimePicker
                v-model="overrideFormData.dateRange.end"
                label="Ngày kết thúc"
                :config="{
                  minDate: new Date().toISOString().slice(0, 10),
                  maxDate: maxDate,
                  dateFormat: 'Y-m-d',
                }"
              />
            </VCol>
          </VRow>
          <!-- Boolean fields (switch) -->
          <div v-if="isBooleanField(overrideContext.type)" class="mt-4">
            <VSwitch
              v-model="overrideFormData.value"
              :label="getFieldLabel(overrideContext.type)"
              color="primary"
              hide-details
            />
          </div>

          <!-- Number fields (text input) -->
          <VTextField
            v-else
            v-model="overrideFormData.value"
            label="Giá trị"
            type="number"
            :suffix="
              overrideContext.type === 'ota_rate' ||
              overrideContext.type === 'local'
                ? `${props.currency}`
                : ''
            "
            class="mt-4"
          />
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn color="secondary" @click="isDialogVisible = false"> Huỷ </VBtn>
          <VBtn variant="elevated" @click="handleDialogUpdate"> OK </VBtn>
        </VCardActions>
      </VCard>
    </VNavigationDrawer>

    <!-- Price Settings Drawer -->
    <VNavigationDrawer
      v-model="isPriceDrawerOpen"
      temporary
      data-allow-mismatch
      class="scrollable-content"
      location="end"
      width="1000"
    >
      <AppDrawerHeaderSection
        title="Cài đặt giá"
        @cancel="isPriceDrawerOpen = false"
      />

      <VDivider />

      <PerfectScrollbar :options="{ wheelPropagation: false }">
        <VCard flat class="">
          <VCardText class="pa-4">
            <VRow>
              <VCol>
                <!-- 1. Chọn khoảng ngày áp dụng -->
                <div class="">
                  <h4 class="text-h6 mb-3">Khoảng ngày áp dụng</h4>
                  <div
                    v-for="(range, index) in priceSettings.dateRanges"
                    :key="index"
                    class="mb-4"
                  >
                    <VCard variant="outlined" class="pa-3">
                      <div
                        class="d-flex justify-space-between align-center mb-3"
                      >
                        <span class="text-subtitle-2"
                          >Khoảng ngày {{ index + 1 }}:</span
                        >
                        <VBtn
                          v-if="priceSettings.dateRanges.length > 1"
                          icon="tabler-trash"
                          variant="text"
                          color="error"
                          size="small"
                          @click="removeDateRange(index)"
                        />
                      </div>

                      <VRow>
                        <VCol cols="12" md="6">
                          <AppDateTimePicker
                            v-model="range.start"
                            placeholder="Chọn ngày bắt đầu"
                            label="Từ ngày"
                            :config="{
                              dateFormat: 'Y-m-d',
                              minDate: new Date().toISOString().slice(0, 10),
                              maxDate: maxDate,
                            }"
                          />
                        </VCol>
                        <VCol cols="12" md="6">
                          <AppDateTimePicker
                            v-model="range.end"
                            placeholder="Chọn ngày kết thúc"
                            label="Đến ngày"
                            :config="{
                              dateFormat: 'Y-m-d',
                              minDate: new Date().toISOString().slice(0, 10),
                              maxDate: maxDate,
                            }"
                          />
                        </VCol>
                      </VRow>

                      <div class="mt-3">
                        <div class="text-body-2 mb-2">
                          Chọn ngày trong tuần:
                        </div>
                        <div class="d-flex gap-1 flex-wrap">
                          <VBtn
                            v-for="(day, dayIndex) in weekDays"
                            :key="day"
                            :color="
                              range.weekdays.includes(dayIndex)
                                ? 'primary'
                                : 'grey'
                            "
                            :variant="
                              range.weekdays.includes(dayIndex)
                                ? 'elevated'
                                : 'outlined'
                            "
                            size="small"
                            @click="toggleWeekday(index, day)"
                            class="text-caption"
                            style="min-width: 40px"
                          >
                            {{ day }}
                          </VBtn>
                        </div>
                      </div>
                    </VCard>
                  </div>
                  <div class="mt-3">
                    <VBtn
                      variant="text"
                      color="primary"
                      prepend-icon="tabler-plus"
                      @click="addDateRange"
                    >
                      Thêm khoảng ngày
                    </VBtn>
                  </div>
                </div>

                <VDivider class="my-4" />

                <!-- 3. Cài đặt giá -->
                <div class="mb-6">
                  <h4 class="text-h6 mb-3">Cài đặt</h4>

                  <!-- Giá phòng (rate) -->
                  <VCard variant="outlined" class="pa-3 mb-3">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <VSwitch
                        v-model="priceSettings.enableRate"
                        label="Giá phòng"
                        color="primary"
                        hide-details
                      />
                    </div>

                    <div v-if="priceSettings.enableRate">
                      <VSelect
                        v-model="priceSettings.rateType"
                        :items="rateTypeOptions"
                        label="Loại giá"
                        variant="outlined"
                        density="compact"
                        class="mb-3"
                      />

                      <VTextField
                        v-model="priceSettings.rateValue"
                        :label="getRateInputLabel()"
                        type="number"
                        :suffix="getRateInputSuffix()"
                        variant="outlined"
                        density="compact"
                        class="mb-2"
                      />

                      <div class="text-caption text-grey">
                        {{ getRateDescription() }}
                      </div>
                    </div>
                  </VCard>

                  <!-- Mở/Ngừng bán (stop_sell) -->
                  <VCard variant="outlined" class="pa-3 mb-3">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <VSwitch
                        v-model="priceSettings.enableStopSell"
                        label="Mở/Ngừng bán"
                        color="primary"
                        hide-details
                      />
                      <VSwitch
                        v-if="priceSettings.enableStopSell"
                        v-model="priceSettings.stopSellValue"
                        label="Dừng bán"
                        color="primary"
                        hide-details
                      />
                    </div>
                    <div class="text-caption text-grey mt-1">
                      Cập nhật trạng thái mở/ngừng bán trên các kênh
                    </div>
                  </VCard>

                  <!-- Lưu trú tối thiểu khi đến (min_stay_arrival) -->
                  <VCard variant="outlined" class="pa-3 mb-3">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <VSwitch
                        v-model="priceSettings.enableMinStayArrival"
                        label="Lưu trú tối thiểu khi đến"
                        color="primary"
                        hide-details
                      />
                    </div>
                    <VTextField
                      v-if="priceSettings.enableMinStayArrival"
                      v-model="priceSettings.minStayArrivalValue"
                      label="Số đêm"
                      type="number"
                      variant="outlined"
                      density="compact"
                      class="mt-2"
                    />
                  </VCard>

                  <!-- Lưu trú tối thiểu xuyên suốt (min_stay_through) -->
                  <VCard variant="outlined" class="pa-3 mb-3">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <VSwitch
                        v-model="priceSettings.enableMinStayThrough"
                        label="Lưu trú tối thiểu xuyên suốt"
                        color="primary"
                        hide-details
                      />
                    </div>
                    <VTextField
                      v-if="priceSettings.enableMinStayThrough"
                      v-model="priceSettings.minStayThroughValue"
                      label="Số đêm"
                      type="number"
                      variant="outlined"
                      density="compact"
                      class="mt-2"
                    />
                  </VCard>

                  <!-- Lưu trú tối đa (max_stay) -->
                  <VCard variant="outlined" class="pa-3 mb-3">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <VSwitch
                        v-model="priceSettings.enableMaxStay"
                        label="Lưu trú tối đa"
                        color="primary"
                        hide-details
                      />
                    </div>
                    <VTextField
                      v-if="priceSettings.enableMaxStay"
                      v-model="priceSettings.maxStayValue"
                      label="Số đêm"
                      type="number"
                      variant="outlined"
                      density="compact"
                      class="mt-2"
                    />
                  </VCard>

                  <!-- Đóng cửa nhận phòng (closed_to_arrival) -->
                  <VCard variant="outlined" class="pa-3 mb-3">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <VSwitch
                        v-model="priceSettings.enableClosedToArrival"
                        label="Đóng cửa nhận phòng"
                        color="primary"
                        hide-details
                      />
                      <VSwitch
                        v-if="priceSettings.enableClosedToArrival"
                        v-model="priceSettings.closedToArrivalValue"
                        label="Đóng cửa đến ngày đến"
                        color="primary"
                        hide-details
                      />
                    </div>
                  </VCard>

                  <!-- Đóng cửa trả phòng (closed_to_departure) -->
                  <VCard variant="outlined" class="pa-3 mb-3">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <VSwitch
                        v-model="priceSettings.enableClosedToDeparture"
                        label="Đóng cửa trả phòng"
                        color="primary"
                        hide-details
                      />
                      <VSwitch
                        v-if="priceSettings.enableClosedToDeparture"
                        v-model="priceSettings.closedToDepartureValue"
                        label="Đóng cửa đến ngày đi"
                        color="primary"
                        hide-details
                      />
                    </div>
                  </VCard>
                </div>
              </VCol>
              <!-- 2. Chọn loại giá áp dụng -->
              <VCol class="mb-6">
                <h4 class="text-h6 mb-3">Áp dụng cho</h4>
                <!-- <div class="mb-3">
                  <VBtn
                    variant="text"
                    color="primary"
                    prepend-icon="tabler-plus"
                    @click="selectAllRoomTypes"
                    class="pa-0"
                  >
                    Chọn tất cả loại phòng
                  </VBtn>
                 </div> -->

                <div
                  v-for="(roomType, roomTypeId) in getGroupedRatePlans()"
                  :key="roomTypeId"
                  class="mb-4"
                >
                  <VCard variant="outlined" class="pa-3">
                    <div class="d-flex justify-space-between align-center mb-3">
                      <span
                        class="text-primary text-subtitle-1 font-weight-medium"
                        >{{ roomType.name }}</span
                      >
                      <VBtn
                        variant="text"
                        color="primary"
                        size="small"
                        @click="toggleAllRatePlansForRoomType(roomTypeId)"
                      >
                        Chọn tất cả
                      </VBtn>
                    </div>

                    <div
                      v-for="(
                        bookingSource, bookingSourceName
                      ) in roomType.ratePlans"
                      :key="bookingSourceName"
                      class="mb-3"
                    >
                      <div
                        class="d-flex justify-space-between align-center mb-2"
                      >
                        <span class="text-body-2 font-weight-medium">{{
                          bookingSource.name
                        }}</span>
                        <VBtn
                          variant="text"
                          color="primary"
                          size="x-small"
                          @click="
                            toggleAllRatePlansForBookingSource(
                              roomTypeId,
                              bookingSourceName
                            )
                          "
                        >
                          Chọn tất cả
                        </VBtn>
                      </div>

                      <div class="d-flex flex-wrap gap-2">
                        <VChip
                          v-for="item in bookingSource.items"
                          :key="item.id"
                          :color="
                            isRatePlanSelected(item.id) ? 'primary' : 'grey-500'
                          "
                          :variant="
                            isRatePlanSelected(item.id)
                              ? 'elevated'
                              : 'outlined'
                          "
                          size="small"
                          @click="toggleRatePlan(item.id)"
                          class="cursor-pointer"
                        >
                          {{ item.name }}
                        </VChip>
                      </div>
                    </div>
                  </VCard>
                </div>
              </VCol>
            </VRow>
            <div class="d-flex"></div>
          </VCardText>
        </VCard>
      </PerfectScrollbar>

      <VDivider />

      <template v-slot:append>
        <div class="float-end pa-4 mr-4 d-flex gap-2">
          <VSpacer />
          <VBtn variant="tonal" @click="isPriceDrawerOpen = false">
            Huỷ bỏ
          </VBtn>
          <VBtn color="primary" @click="handleSavePriceDrawer"> Cập Nhật </VBtn>
        </div>
      </template>
    </VNavigationDrawer>
  </Layout>
</template>

<script setup>
import RequiredSpecificProperty from "@/Components/properties/RequiredSpecificProperty.vue";
import Layout from "@/layouts/blank.vue";
import { usePropertyStore } from "@/stores/usePropertyStore";
import { INVENTORY_MAX_DATE } from "@/utils/constants";
import { formatCurrency } from "@/utils/formatters";
import { Head, router, useForm } from "@inertiajs/vue3";
import { cloneDeep } from "lodash";
import { computed, onMounted, reactive, ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { VDivider } from "vuetify/components";

const propertyStore = usePropertyStore();
const props = defineProps({
  propertyOptions: Array,
  filters: Object,
  dates: Array,
  roomTypes: Array,
  inventoryGrid: Object,
  currency: String,
});

const maxDate = computed(() => {
  return new Date(Date.now() + INVENTORY_MAX_DATE * 24 * 60 * 60 * 1000)
    .toISOString()
    .slice(0, 10);
});

const otaRestrictionFields = [
  "min_stay_arrival",
  "min_stay_through",
  "max_stay",
  "closed_to_arrival",
  "closed_to_departure",
  "stop_sell",
];

const restrictionLabels = {
  min_stay_arrival: "Lưu trú tối thiểu khi đến",
  min_stay_through: "Lưu trú tối thiểu xuyên suốt",
  max_stay: "Lưu trú tối đa",
  closed_to_arrival: "Đóng cửa nhận phòng",
  closed_to_departure: "Đóng cửa trả phòng",
  stop_sell: "Ngừng bán",
};

const restrictionShortLabels = {
  min_stay_arrival: "Min Arr",
  min_stay_through: "Min Thr",
  max_stay: "Max Stay",
  closed_to_arrival: "CTA",
  closed_to_departure: "CTD",
  stop_sell: "Stop",
};

const getOTARestrictionValue = (
  date,
  roomType,
  ratePlan,
  ratePlanOTAId,
  field,
  occupancy = null
) => {
  const otas = getOTARates(date, roomType, ratePlan);

  let ota;
  if (occupancy) {
    // Per person mode - tìm theo rate_plan_ota_id và occupancy
    ota = otas.find(
      (ota) =>
        ota.rate_plan_ota_id === ratePlanOTAId && ota.occupancy === occupancy
    );
  } else {
    // Per room mode - tìm theo rate_plan_ota_id
    ota = otas.find((ota) => ota.rate_plan_ota_id === ratePlanOTAId);
  }

  if (!ota) return null;

  // Ưu tiên pendingChanges - sử dụng occupancy nếu có
  const key = getCellKey(
    date,
    roomType,
    ratePlan,
    `ota_${field}`,
    ratePlanOTAId,
    occupancy
  );

  // Debug log để kiểm tra
  // console.log("getOTARestrictionValue:", {
  //   date: date.fullDate,
  //   roomTypeId: roomType.id,
  //   ratePlanId: ratePlan.id,
  //   ratePlanOTAId,
  //   field,
  //   occupancy,
  //   key,
  //   pendingValue: pendingChanges.value[key],
  //   otaValue: ota[field],
  // });

  if (pendingChanges.value[key] !== undefined) {
    return pendingChanges.value[key];
  }
  return ota[field] ?? null;
};

// --- Local State Management ---
const localInventoryGrid = ref({});
const pendingChanges = ref({});

const isDirty = computed(() => Object.keys(pendingChanges.value).length > 0);

onMounted(() => {
  localInventoryGrid.value = cloneDeep(props.inventoryGrid);
});

// Watch for prop changes to update the base grid, but DON'T reset pending changes
watch(
  () => props.inventoryGrid,
  (newGrid) => {
    localInventoryGrid.value = cloneDeep(newGrid);
  },
  { deep: true }
);

// --- Getters for Display ---
const getCellKey = (
  date,
  roomType,
  ratePlan = null,
  type = "avl",
  ratePlanOTAId = null,
  occupancy = null
) => {
  let key;
  if (type === "avl") {
    key = `${date.fullDate}_${roomType.id}_avl`;
  } else if (type === "local") {
    key = `${date.fullDate}_${roomType.id}_local_${ratePlan.id}`;
  } else if (type.startsWith("ota_")) {
    // Format: date_roomTypeId|ota|FIELD|ratePlanOTAId|occupancy (dùng | để tránh conflict với _ trong field name)
    const field = type.replace("ota_", "");
    if (occupancy) {
      key = `${date.fullDate}_${roomType.id}|ota_${ratePlan.id}|${field}|${ratePlanOTAId}|${occupancy}`;
    } else {
      key = `${date.fullDate}_${roomType.id}|ota_${ratePlan.id}|${field}|${ratePlanOTAId}`;
    }
  } else {
    key = `${date.fullDate}_${roomType.id}_${type}`;
  }

  // Debug log
  // console.log("getCellKey:", {
  //   date: date.fullDate,
  //   roomTypeId: roomType.id,
  //   ratePlanId: ratePlan?.id,
  //   type,
  //   ratePlanOTAId,
  //   occupancy,
  //   key,
  // });

  return key;
};

const getDisplayValue = (
  date,
  roomType,
  ratePlan = null,
  type = "avl",
  ratePlanOTAId = null,
  occupancy = null
) => {
  const key = getCellKey(
    date,
    roomType,
    ratePlan,
    type,
    ratePlanOTAId,
    occupancy
  );
  if (pendingChanges.value[key] !== undefined) {
    return pendingChanges.value[key];
  }

  if (type === "avl") {
    return localInventoryGrid.value[date.fullDate]?.[roomType.id]?.[type];
  } else if (type === "local") {
    return localInventoryGrid.value[date.fullDate]?.[roomType.id]?.[ratePlan.id]
      ?.local;
  } else if (type === "ota") {
    const otas =
      localInventoryGrid.value[date.fullDate]?.[roomType.id]?.[ratePlan.id]
        ?.otas;
    if (!otas) return null;

    const ota = Object.values(otas).find(
      (ota) => ota.rate_plan_ota_id === ratePlanOTAId
    );
    return ota || null;
  } else if (type.startsWith("ota_")) {
    // Xử lý các field OTA khác (ota_rate, ota_stop_sell, etc.)
    const field = type.replace("ota_", "");
    return getOTARestrictionValue(
      date,
      roomType,
      ratePlan,
      ratePlanOTAId,
      field,
      occupancy
    );
  }

  return null;
};

const hasPendingChange = (
  date,
  roomType,
  ratePlan = null,
  type = "avl",
  ratePlanOTAId = null,
  occupancy = null
) => {
  return (
    pendingChanges.value[
      getCellKey(date, roomType, ratePlan, type, ratePlanOTAId, occupancy)
    ] !== undefined
  );
};

const formatDisplayValue = (value, isRate = false, isBoolean = false) => {
  if (value === null || value === undefined || value === "") return "-";

  if (isBoolean) {
    return value ? "✓" : "✗";
  }

  if (isRate) {
    return formatCurrency(Number(value), props.currency);
  }

  return value;
};

const formatBooleanValue = (value) => {
  if (value === null || value === undefined || value === "") return "-";
  return value
    ? '<span style="color: #4CAF50; font-weight: bold; font-size: 1.2em;">✓</span>'
    : '<span style="color: #F44336; font-weight: bold; font-size: 1.2em;">✗</span>';
};

const isZeroAvailability = (value) => {
  return value === 0 || value === "0";
};

// --- Date Navigation and Filtering ---
const filtersData = ref({
  // property_id: Number(props.filters?.property_id) || null,
  property_id:
    propertyStore.selectedProperty ||
    Number(props.filters?.property_id) ||
    null,
  start_date:
    props.filters?.start_date || new Date().toLocaleDateString("en-CA"),
  room_type_ids: [],
  rate_plan_ids: [],
  restriction_types: [],
});

watch(
  () => filtersData.value.property_id,
  (val) => {
    propertyStore.setProperty(val);
  }
);

watch(
  () => propertyStore.selectedProperty,
  (val) => {
    filtersData.value.property_id = val;
  }
);

// Computed properties for filter options
const roomTypeOptions = computed(() => {
  return props.roomTypes.map((roomType) => ({
    id: roomType.id,
    name: roomType.name,
  }));
});

const ratePlanOptions = computed(() => {
  const ratePlans = [];
  const selectedRoomTypes =
    filtersData.value.room_type_ids.length > 0
      ? props.roomTypes.filter((roomType) =>
          filtersData.value.room_type_ids.includes(roomType.id)
        )
      : props.roomTypes;

  selectedRoomTypes.forEach((roomType) => {
    roomType.rate_plans?.forEach((ratePlan) => {
      ratePlans.push({
        id: ratePlan.id,
        title: `${roomType.name} - ${ratePlan.title}`,
      });
    });
  });
  return ratePlans;
});

const restrictionTypeOptions = computed(() => {
  return [
    { value: "min_stay_arrival", label: "Lưu trú tối thiểu khi đến" },
    { value: "min_stay_through", label: "Lưu trú tối thiểu xuyên suốt" },
    { value: "max_stay", label: "Lưu trú tối đa" },
    { value: "closed_to_arrival", label: "Đóng cửa nhận phòng" },
    { value: "closed_to_departure", label: "Đóng cửa trả phòng" },
    { value: "stop_sell", label: "Ngừng bán" },
  ];
});

// Computed property for filtered room types
const filteredRoomTypes = computed(() => {
  if (!filtersData.value.room_type_ids.length) {
    return props.roomTypes;
  }

  return props.roomTypes.filter((roomType) =>
    filtersData.value.room_type_ids.includes(roomType.id)
  );
});

const nextDate = () => {
  const currentDate = new Date(filtersData.value.start_date);
  currentDate.setDate(currentDate.getDate() + 14);
  filtersData.value.start_date = currentDate.toISOString().slice(0, 10);
};

const prevDate = () => {
  const currentDate = new Date(filtersData.value.start_date);
  currentDate.setDate(currentDate.getDate() - 14);

  // Chỉ cho phép điều hướng về trước nếu ngày không nhỏ hơn ngày hiện tại
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (currentDate >= today) {
    filtersData.value.start_date = currentDate.toISOString().slice(0, 10);
  }
};

watch(
  () => [filtersData.value.property_id, filtersData.value.start_date],
  ([newPropertyId], [oldPropertyId]) => {
    // If the property ID changes, clear the pending changes
    if (newPropertyId !== oldPropertyId) {
      pendingChanges.value = {};
      filtersData.value.rate_plan_ids = [];
      filtersData.value.restriction_types = [];
      filtersData.value.room_type_ids = [];
    }

    router.get(
      route("inventory.index"),
      {
        property_id: newPropertyId,
        start_date: filtersData.value.start_date,
      },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
    );
  },
  { deep: true }
);

onMounted(() => {
  if (filtersData.value.property_id) {
    router.get(
      route("inventory.index"),
      {
        property_id: filtersData.value.property_id,
        start_date: filtersData.value.start_date,
      },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
    );
  }
});

// --- Dialog State and Logic ---
const isDialogVisible = ref(false);
const overrideContext = ref({});
const overrideFormData = reactive({
  dateRange: { start: "", end: "" },
  value: null,
});

// Thêm các biến để xử lý kéo chọn
const isDragging = ref(false);
const dragStartCell = ref(null);
const dragEndCell = ref(null);
const dragStartDate = ref(null);
const dragEndDate = ref(null);

// Thêm biến để theo dõi các ô đang được highlight
const highlightedCells = ref(new Set());

// Thêm biến để theo dõi row đang được highlight
const highlightedRowInfo = ref(null);

// Thêm các methods để xử lý kéo chọn
const handleMouseDown = (
  event,
  type,
  roomType,
  date,
  ratePlan = null,
  ota = null,
  occupancy = null
) => {
  // Chỉ xử lý khi click chuột trái
  if (event.button !== 0) return;

  isDragging.value = true;
  dragStartCell.value = { type, roomType, date, ratePlan, ota, occupancy };
  dragStartDate.value = date;
  dragEndDate.value = date;

  // Lưu thông tin row để highlight
  highlightedRowInfo.value = { type, roomType, ratePlan, ota, occupancy };

  // Thêm event listeners cho mouse move và mouse up
  document.addEventListener("mousemove", handleMouseMove);
  document.addEventListener("mouseup", handleMouseUp);

  // Ngăn chặn text selection
  event.preventDefault();
};

const handleMouseMove = (event) => {
  if (!isDragging.value) return;

  // Tìm cell đang hover
  const target = event.target.closest("td.clickable-cell");
  if (!target) return;

  // Lấy ngày từ data-date attribute
  const dateStr = target.getAttribute("data-date");
  if (dateStr) {
    // Tìm date object tương ứng từ props.dates
    const currentDate = props.dates.find((date) => date.fullDate === dateStr);
    if (currentDate) {
      dragEndDate.value = currentDate;

      // Highlight tất cả các ô từ start đến end
      updateHighlightedCells();
    }
  }
};

const handleMouseUp = (event) => {
  if (!isDragging.value) return;

  // Tìm cell cuối cùng
  const target = event.target.closest("td.clickable-cell");
  if (target && dragStartCell.value) {
    // Xác định ngày kết thúc từ cell cuối
    // Mở modal với range date đã chọn
    openOverrideDialogWithRange();
  }

  // Reset trạng thái
  isDragging.value = false;
  dragStartCell.value = null;
  dragEndCell.value = null;
  dragStartDate.value = null;
  dragEndDate.value = null;
  highlightedRowInfo.value = null;

  // Bỏ highlight tất cả các ô
  highlightedCells.value.clear();

  // Remove event listeners
  document.removeEventListener("mousemove", handleMouseMove);
  document.removeEventListener("mouseup", handleMouseUp);
};

const openOverrideDialogWithRange = () => {
  if (!dragStartDate.value || !dragEndDate.value) return;

  const { type, roomType, ratePlan, ota, occupancy } = dragStartCell.value;

  // Sửa lỗi: Đảm bảo format date hợp lệ
  const startDateStr = dragStartDate.value.fullDate;
  const endDateStr = dragEndDate.value.fullDate;

  // Sắp xếp ngày bắt đầu và kết thúc
  const startDate = startDateStr < endDateStr ? startDateStr : endDateStr;
  const endDate = startDateStr < endDateStr ? endDateStr : startDateStr;

  // Mở modal với range date đã chọn
  openOverrideDialog(
    type,
    roomType,
    { fullDate: startDate },
    ratePlan,
    ota,
    occupancy
  );

  // Cập nhật form data với range date
  overrideFormData.dateRange.start = startDate;
  overrideFormData.dateRange.end = endDate;
};

// Cập nhật hàm openOverrideDialog để hỗ trợ range date
const openOverrideDialog = (
  type,
  roomType,
  date,
  ratePlan = null,
  ota = null,
  occupancy = null
) => {
  // Bỏ highlight khi mở dialog
  highlightedCells.value.clear();
  highlightedRowInfo.value = null;

  let currentValue;
  let field = null;

  if (type === "avl") {
    currentValue = getDisplayValue(date, roomType, ratePlan, type);
  } else if (type === "local") {
    const localData = getDisplayValue(date, roomType, ratePlan, type);
    currentValue = localData?.price || 0;
  } else if (type.startsWith("ota_")) {
    field = type.replace("ota_", "");
    currentValue = getOTARestrictionValue(
      date,
      roomType,
      ratePlan,
      ota.id,
      field,
      occupancy
    );
  } else if (type === "ota_rate") {
    currentValue = getOTARestrictionValue(
      date,
      roomType,
      ratePlan,
      ota.id,
      "rate",
      occupancy
    );
  }

  overrideContext.value = {
    type,
    field,
    roomType,
    ratePlan,
    ota,
    date,
    occupancy,
    originalValue: currentValue,
  };

  // Nếu có range date từ drag, sử dụng range đó
  if (dragStartDate.value && dragEndDate.value) {
    const startDateStr = dragStartDate.value.fullDate;
    const endDateStr = dragEndDate.value.fullDate;

    // Sắp xếp ngày bắt đầu và kết thúc
    const startDate = startDateStr < endDateStr ? startDateStr : endDateStr;
    const endDate = startDateStr < endDateStr ? endDateStr : startDateStr;

    overrideFormData.dateRange.start = startDate;
    overrideFormData.dateRange.end = endDate;
  } else {
    // Sử dụng ngày hiện tại như cũ
    overrideFormData.dateRange.start = date.fullDate;
    overrideFormData.dateRange.end = date.fullDate;
  }

  overrideFormData.value = currentValue;
  isDialogVisible.value = true;
};

// --- Action Handlers ---
const handleDialogUpdate = () => {
  if (
    String(overrideFormData.value) ===
    String(overrideContext.value.originalValue)
  ) {
    isDialogVisible.value = false;
    return;
  }

  const { roomType, ratePlan, ota, type, field, occupancy } =
    overrideContext.value;
  const startDate = new Date(overrideFormData.dateRange.start);
  const endDate = new Date(overrideFormData.dateRange.end);

  // Lưu lại ngày gốc để không bị thay đổi khi lặp
  let d = new Date(startDate);
  while (d <= endDate) {
    const dateStr = d.toISOString().slice(0, 10);
    // Tạo object giả cho ngày để dùng chung hàm getCellKey
    const dateObj = { fullDate: dateStr };
    let key;
    if (type === "avl") {
      key = getCellKey(dateObj, roomType, ratePlan, type);
    } else if (type === "local") {
      key = getCellKey(dateObj, roomType, ratePlan, type);
    } else if (type.startsWith("ota_")) {
      key = getCellKey(dateObj, roomType, ratePlan, type, ota?.id, occupancy);
    } else if (type === "ota_rate") {
      key = getCellKey(
        dateObj,
        roomType,
        ratePlan,
        "ota_rate",
        ota?.id,
        occupancy
      );
    }

    // Debug log
    // console.log("handleDialogUpdate - saving key:", {
    //   type,
    //   otaId: ota?.id,
    //   occupancy,
    //   key,
    //   value: overrideFormData.value,
    // });

    pendingChanges.value[key] = overrideFormData.value;
    d.setDate(d.getDate() + 1);
  }
  isDialogVisible.value = false;
};

const resetAllChanges = () => {
  pendingChanges.value = {};
};

const saveAllChanges = () => {
  const changesPayload = Object.entries(pendingChanges.value)
    .map(([key, value]) => {
      // Kiểm tra nếu key có chứa | (OTA key)
      if (key.includes("|")) {
        const [dateRoomType, ota, field, ratePlanOTAId, occupancy] =
          key.split("|");
        const [date, room_type_id] = dateRoomType.split("_");
        const [_, rateplan_id] = ota.split("_");

        const payload = {
          date,
          room_type_id,
          rate_plan_id: rateplan_id,
          rate_plan_ota_id: ratePlanOTAId,
          type: field === "rate" ? "rate" : field,
          value,
        };

        // Add occupancy_option_id if occupancy is provided (per_person mode)
        if (occupancy) {
          // Find the occupancy_option_id from the data
          const roomType = props.roomTypes.find(
            (rt) => rt.id.toString() === room_type_id
          );
          if (roomType) {
            const ratePlan = roomType.rate_plans.find(
              (rp) => rp.id.toString() === rateplan_id
            );
            if (ratePlan) {
              const ratePlanOTA = ratePlan.rate_plan_o_t_as?.find(
                (rpo) => rpo.id.toString() === ratePlanOTAId
              );
              if (ratePlanOTA) {
                const occupancyOption = ratePlanOTA.occupancy_options?.find(
                  (oo) => oo.occupancy.toString() === occupancy
                );
                if (occupancyOption) {
                  payload.occupancy_option_id = occupancyOption.id;
                  // Thêm external_id cho Channex
                  payload.external_id = occupancyOption.external_id;

                  // Debug log
                  // console.log("Found occupancy option:", {
                  //   occupancy,
                  //   occupancyOptionId: occupancyOption.id,
                  //   externalId: occupancyOption.external_id,
                  //   payload,
                  // });
                }
              }
            }
          }
        }

        return payload;
      } else {
        // Logic cũ cho avl và local
        const parts = key.split("_");
        const date = parts[0];
        const room_type_id = parts[1];
        const typeInfo = parts.slice(2);

        if (typeInfo[0] === "avl") {
          return { date, room_type_id, type: "avl", value };
        } else if (typeInfo[0] === "local") {
          return {
            date,
            room_type_id,
            rate_plan_id: typeInfo[1],
            type: "rate",
            value,
          };
        }
      }
    })
    .filter(Boolean);

  if (changesPayload.length === 0) {
    return;
  }

  // Gửi tất cả changes trong một request như format cũ
  const payload = {
    property_id: propertyStore.selectedProperty,
    changes: changesPayload,
  };

  router.post(route("inventory.storeMultiple"), payload, {
    onSuccess: () => {
      // Remove from pending changes on success
      Object.keys(pendingChanges.value).forEach((key) => {
        delete pendingChanges.value[key];
      });
      resetAllChanges();
    },
    onError: (errors) => {
      console.error("Failed to update inventory:", errors);
    },
  });
};

// --- Helper for OTA Rates ---
const getOTARates = (date, roomType, ratePlan) => {
  const rateData =
    localInventoryGrid.value[date.fullDate]?.[roomType.id]?.[ratePlan.id];
  if (!rateData || !rateData.otas) return [];

  return Object.values(rateData.otas);
};

const getOTARateValue = (date, roomType, ratePlan, ratePlanOTAId) => {
  // Check if there's a pending change first
  const key = getCellKey(date, roomType, ratePlan, "ota", ratePlanOTAId);
  if (pendingChanges.value[key] !== undefined) {
    return pendingChanges.value[key];
  }

  // Otherwise get from original data
  const ota = getOTARates(date, roomType, ratePlan).find(
    (ota) => ota.rate_plan_ota_id === ratePlanOTAId
  );
  if (ota) {
    return ota.rate;
  }
  return null;
};

const getLocalRateValue = (date, roomType, ratePlan) => {
  const key = getCellKey(date, roomType, ratePlan, "local");
  if (pendingChanges.value[key] !== undefined) {
    return pendingChanges.value[key];
  }
  return (
    localInventoryGrid.value[date.fullDate]?.[roomType.id]?.[ratePlan.id]?.local
      ?.price || 0
  );
};

const getRoomTypeRowspan = (roomType) => {
  // 1 dòng cho số phòng trống + tổng số dòng của tất cả rate plan
  let total = 1;
  for (const ratePlan of roomType.rate_plans) {
    total += getRatePlanRows(ratePlan).length;
  }
  return total;
};

const getRatePlanRows = (ratePlan) => {
  const rows = [];
  rows.push({
    key: `local_${ratePlan.id}`,
    label: "Giá trực tiếp",
    type: "local",
    otaName: "Trực Tiếp",
    showOtaName: true,
    otaRowspan: 1,
    otaId: null,
    otaObj: null,
    getValue: (date, roomType, ratePlan) =>
      getLocalRateValue(date, roomType, ratePlan),
  });

  if (ratePlan.rate_plan_o_t_as) {
    for (const ota of ratePlan.rate_plan_o_t_as) {
      if (
        ratePlan.sell_mode === "per_person" &&
        ota.occupancy_options &&
        ota.occupancy_options.length > 0
      ) {
        // Per person mode - hiển thị tất cả occupancy options trong cùng một OTA
        let primaryOccupancy = null;

        // Tìm primary occupancy để tính toán số dòng restrictions
        for (const occupancyOption of ota.occupancy_options) {
          if (occupancyOption.is_primary) {
            primaryOccupancy = occupancyOption;
            break;
          }
        }

        // Tạo các dòng giá cho từng occupancy
        let occupancyCount = 0; // Đếm số occupancy options thực tế được thêm vào
        for (const occupancyOption of ota.occupancy_options) {
          const occupancy = occupancyOption.occupancy;
          const isPrimary = occupancyOption.is_primary;
          occupancyCount++;

          // Kiểm tra xem có phải auto mode và không phải primary occupancy không
          const isAutoMode = ratePlan.rate_mode === "auto";
          const isDisabled = isAutoMode && !isPrimary;

          rows.push({
            key: `ota_rate_${ota.id}_occupancy_${occupancy}`,
            label: `Giá ${occupancy} người${isPrimary ? " (chính)" : ""}`,
            type: "ota_rate",
            occupancy: occupancy,
            isPrimary: isPrimary,
            otaName: ota.booking_source?.name || "OTA",
            showOtaName: occupancyCount === 1, // Chỉ hiển thị OTA name cho occupancy đầu tiên
            otaRowspan: primaryOccupancy
              ? ota.occupancy_options.length + otaRestrictionFields.length
              : ota.occupancy_options.length,
            otaId: ota.id,
            occupancyOptionId: occupancyOption.id,
            otaObj: ota,
            disabled: isDisabled, // Thêm thuộc tính disabled
            getValue: (date, roomType, ratePlan) =>
              getOTARestrictionValue(
                date,
                roomType,
                ratePlan,
                ota.id,
                "rate",
                occupancy
              ),
          });
        }

        // Restriction rows (chỉ cho primary)
        if (primaryOccupancy) {
          for (const field of otaRestrictionFields) {
            rows.push({
              key: `ota_${field}_${ota.id}_occupancy_${primaryOccupancy.occupancy}`,
              label: restrictionShortLabels[field],
              fullLabel: restrictionLabels[field],
              type: `ota_${field}`,
              occupancy: primaryOccupancy.occupancy,
              isPrimary: true,
              otaName: ota.booking_source?.name || "OTA",
              showOtaName: false,
              otaRowspan: 0,
              otaId: ota.id,
              occupancyOptionId: primaryOccupancy.id,
              otaObj: ota,
              getValue: (date, roomType, ratePlan) =>
                getOTARestrictionValue(
                  date,
                  roomType,
                  ratePlan,
                  ota.id,
                  field,
                  primaryOccupancy.occupancy
                ),
            });
          }
        }
      } else {
        // Per room mode - hiển thị như cũ
        rows.push({
          key: `ota_rate_${ota.id}`,
          label: "Giá OTA",
          type: "ota_rate",
          otaName: ota.booking_source?.name || "OTA",
          showOtaName: true,
          otaRowspan: 1 + otaRestrictionFields.length,
          otaId: ota.id,
          otaObj: ota,
          getValue: (date, roomType, ratePlan) =>
            getOTARestrictionValue(date, roomType, ratePlan, ota.id, "rate"),
        });

        for (const field of otaRestrictionFields) {
          rows.push({
            key: `ota_${field}_${ota.id}`,
            label: restrictionShortLabels[field],
            fullLabel: restrictionLabels[field],
            type: `ota_${field}`,
            otaName: ota.booking_source?.name || "OTA",
            showOtaName: false,
            otaRowspan: 0,
            otaId: ota.id,
            otaObj: ota,
            getValue: (date, roomType, ratePlan) =>
              getOTARestrictionValue(date, roomType, ratePlan, ota.id, field),
          });
        }
      }
    }
  }
  return rows;
};

const isBooleanField = (type) => {
  return [
    "ota_closed_to_arrival",
    "ota_closed_to_departure",
    "ota_stop_sell",
  ].includes(type);
};

const getFieldLabel = (type) => {
  if (type === "ota_closed_to_arrival") return "Đóng cửa đến ngày đến";
  if (type === "ota_closed_to_departure") return "Đóng cửa đến ngày đi";
  if (type === "ota_stop_sell") return "Dừng bán";
  return "";
};

const getFilteredRatePlans = (roomType) => {
  if (!filtersData.value.rate_plan_ids.length) {
    return roomType.rate_plans;
  }
  return roomType.rate_plans.filter((ratePlan) =>
    filtersData.value.rate_plan_ids.includes(ratePlan.id)
  );
};

const getFilteredRatePlanRows = (ratePlan) => {
  const rows = [];
  // rows.push({
  //   key: `local_${ratePlan.id}`,
  //   label: "Giá trực tiếp",
  //   type: "local",
  //   otaName: "Trực Tiếp",
  //   showOtaName: true,
  //   otaRowspan: 1,
  //   otaId: null,
  //   otaObj: null,
  //   getValue: (date, roomType, ratePlan) =>
  //     getLocalRateValue(date, roomType, ratePlan),
  // });

  if (ratePlan.rate_plan_o_t_as) {
    for (const ota of ratePlan.rate_plan_o_t_as) {
      const filteredFields = getFilteredRestrictionFields();
      const hasRestrictions = filteredFields.length > 0;

      if (
        ratePlan.sell_mode === "per_person" &&
        ota.occupancy_options &&
        ota.occupancy_options.length > 0
      ) {
        // Per person mode - hiển thị tất cả occupancy options trong cùng một OTA
        let primaryOccupancy = null;

        // Tìm primary occupancy để tính toán số dòng restrictions
        for (const occupancyOption of ota.occupancy_options) {
          if (occupancyOption.is_primary) {
            primaryOccupancy = occupancyOption;
            break;
          }
        }

        // Tạo các dòng giá cho từng occupancy
        let occupancyCount = 0; // Đếm số occupancy options thực tế được thêm vào
        for (const occupancyOption of ota.occupancy_options) {
          const occupancy = occupancyOption.occupancy;
          const isPrimary = occupancyOption.is_primary;
          occupancyCount++;

          // Kiểm tra xem có phải auto mode và không phải primary occupancy không
          const isAutoMode = ratePlan.rate_mode === "auto";
          const isDisabled = isAutoMode && !isPrimary;

          rows.push({
            key: `ota_rate_${ota.id}_occupancy_${occupancy}`,
            label: `Giá ${occupancy} người${isPrimary ? " (chính)" : ""}`,
            type: "ota_rate",
            occupancy: occupancy,
            isPrimary: isPrimary,
            otaName: ota.booking_source?.name || "OTA",
            showOtaName: occupancyCount === 1, // Chỉ hiển thị OTA name cho occupancy đầu tiên
            otaRowspan:
              primaryOccupancy && hasRestrictions
                ? ota.occupancy_options.length + filteredFields.length
                : ota.occupancy_options.length,
            otaId: ota.id,
            occupancyOptionId: occupancyOption.id,
            otaObj: ota,
            disabled: isDisabled, // Thêm thuộc tính disabled
            getValue: (date, roomType, ratePlan) =>
              getOTARestrictionValue(
                date,
                roomType,
                ratePlan,
                ota.id,
                "rate",
                occupancy
              ),
          });
        }

        // Restriction rows (chỉ cho primary và có filter)
        if (primaryOccupancy && hasRestrictions) {
          for (const field of filteredFields) {
            rows.push({
              key: `ota_${field}_${ota.id}_occupancy_${primaryOccupancy.occupancy}`,
              label: restrictionShortLabels[field],
              fullLabel: restrictionLabels[field],
              type: `ota_${field}`,
              occupancy: primaryOccupancy.occupancy,
              isPrimary: true,
              otaName: ota.booking_source?.name || "OTA",
              showOtaName: false,
              otaRowspan: 0,
              otaId: ota.id,
              occupancyOptionId: primaryOccupancy.id,
              otaObj: ota,
              getValue: (date, roomType, ratePlan) =>
                getOTARestrictionValue(
                  date,
                  roomType,
                  ratePlan,
                  ota.id,
                  field,
                  primaryOccupancy.occupancy
                ),
            });
          }
        }
      } else {
        // Per room mode - hiển thị như cũ
        rows.push({
          key: `ota_rate_${ota.id}`,
          label: "Giá OTA",
          type: "ota_rate",
          otaName: ota.booking_source?.name || "OTA",
          showOtaName: true,
          otaRowspan: hasRestrictions ? 1 + filteredFields.length : 1,
          otaId: ota.id,
          otaObj: ota,
          getValue: (date, roomType, ratePlan) =>
            getOTARestrictionValue(date, roomType, ratePlan, ota.id, "rate"),
        });

        // Chỉ thêm restriction rows nếu có filter được chọn
        if (hasRestrictions) {
          for (const field of filteredFields) {
            rows.push({
              key: `ota_${field}_${ota.id}`,
              label: restrictionShortLabels[field],
              fullLabel: restrictionLabels[field],
              type: `ota_${field}`,
              otaName: ota.booking_source?.name || "OTA",
              showOtaName: false,
              otaRowspan: 0,
              otaId: ota.id,
              otaObj: ota,
              getValue: (date, roomType, ratePlan) =>
                getOTARestrictionValue(date, roomType, ratePlan, ota.id, field),
            });
          }
        }
      }
    }
  }
  return rows;
};

const getFilteredRestrictionFields = () => {
  if (!filtersData.value.restriction_types.length) {
    return []; // Mặc định không hiển thị hạn chế nào
  }
  return otaRestrictionFields.filter((field) =>
    filtersData.value.restriction_types.includes(field)
  );
};

const form = useForm({
  property_id: filtersData.value.property_id,
});
const syncChannex = () => {
  form.property_id = filtersData.value.property_id;
  form.post(route("inventory.fullsync"));
};

const isPriceDrawerOpen = ref(false);

const weekDays = ["T2", "T3", "T4", "T5", "T6", "T7", "CN"];

const rateTypeOptions = [
  { value: "exact", title: "Giá cụ thể" },
  { value: "increase_amount", title: "Tăng theo số tiền" },
  { value: "decrease_amount", title: "Giảm theo số tiền" },
  { value: "increase_percent", title: "Tăng theo %" },
  { value: "decrease_percent", title: "Giảm theo %" },
];

const defaultSetting = {
  dateRanges: [{ start: "", end: "", weekdays: [0, 1, 2, 3, 4, 5, 6] }],

  // Rate (giá phòng)
  enableRate: false,
  rateType: "exact",
  rateValue: "",

  // Stop sell (mở/ngừng bán)
  enableStopSell: false,
  stopSellValue: false,

  // Min stay arrival (lưu trú tối thiểu khi đến)
  enableMinStayArrival: false,
  minStayArrivalValue: "",

  // Min stay through (lưu trú tối thiểu xuyên suốt)
  enableMinStayThrough: false,
  minStayThroughValue: "",

  // Max stay (lưu trú tối đa)
  enableMaxStay: false,
  maxStayValue: "",

  // Closed to arrival (đóng cửa nhận phòng)
  enableClosedToArrival: false,
  closedToArrivalValue: false,

  // Closed to departure (đóng cửa trả phòng)
  enableClosedToDeparture: false,
  closedToDepartureValue: false,

  selectedRatePlans: [], // Format: "roomTypeId_ratePlanId_bookingSourceId"
};

const priceSettings = reactive({
  dateRanges: [{ start: "", end: "", weekdays: [0, 1, 2, 3, 4, 5, 6] }],

  // Rate (giá phòng)
  enableRate: false,
  rateType: "exact",
  rateValue: "",

  // Stop sell (mở/ngừng bán)
  enableStopSell: false,
  stopSellValue: false,

  // Min stay arrival (lưu trú tối thiểu khi đến)
  enableMinStayArrival: false,
  minStayArrivalValue: "",

  // Min stay through (lưu trú tối thiểu xuyên suốt)
  enableMinStayThrough: false,
  minStayThroughValue: "",

  // Max stay (lưu trú tối đa)
  enableMaxStay: false,
  maxStayValue: "",

  // Closed to arrival (đóng cửa nhận phòng)
  enableClosedToArrival: false,
  closedToArrivalValue: false,

  // Closed to departure (đóng cửa trả phòng)
  enableClosedToDeparture: false,
  closedToDepartureValue: false,

  selectedRatePlans: [], // Format: "roomTypeId_ratePlanId_bookingSourceId"
});

watch(isPriceDrawerOpen, (isOpen) => {
  if (!isOpen) {
    Object.assign(priceSettings, {
      ...defaultSetting,
      dateRanges: JSON.parse(JSON.stringify(defaultSetting.dateRanges)),
      selectedRatePlans: [...defaultSetting.selectedRatePlans],
    });
  }
});

function addDateRange() {
  priceSettings.dateRanges.push({
    start: "",
    end: "",
    weekdays: [0, 1, 2, 3, 4, 5, 6],
  });
}
function removeDateRange(idx) {
  priceSettings.dateRanges.splice(idx, 1);
}

function toggleWeekday(rangeIndex, day) {
  const index = priceSettings.dateRanges[rangeIndex].weekdays.indexOf(
    weekDays.indexOf(day)
  );
  if (index > -1) {
    priceSettings.dateRanges[rangeIndex].weekdays.splice(index, 1);
  } else {
    priceSettings.dateRanges[rangeIndex].weekdays.push(weekDays.indexOf(day));
  }
}

// function selectAllRoomTypes() {
//   const allRatePlans = [];
//   props.roomTypes.forEach((roomType) => {
//     roomType.rate_plans.forEach((ratePlan) => {
//       allRatePlans.push(`${roomType.id}_${ratePlan.id}`);
//     });
//   });
//   priceSettings.selectedRatePlans = [...new Set(allRatePlans)];
// }

// Cập nhật phần "Chọn loại giá áp dụng" để gom nhóm theo booking source
function getGroupedRatePlans() {
  const grouped = {};

  props.roomTypes.forEach((roomType) => {
    grouped[roomType.id] = {
      name: roomType.name,
      ratePlans: {},
    };

    roomType.rate_plans.forEach((ratePlan) => {
      // Thêm local rate plan
      // if (!grouped[roomType.id].ratePlans["local"]) {
      //   grouped[roomType.id].ratePlans["local"] = {
      //     name: "Trực tiếp",
      //     items: [],
      //   };
      // }
      // grouped[roomType.id].ratePlans["local"].items.push({
      //   id: `${roomType.id}_${ratePlan.id}_local`,
      //   name: ratePlan.title,
      //   roomTypeId: roomType.id,
      //   ratePlanId: ratePlan.id,
      //   bookingSourceId: "local",
      // });

      // Thêm OTA rate plans
      ratePlan.rate_plan_o_t_as.forEach((ota) => {
        const bookingSourceName = ota.booking_source?.name || "OTA";
        if (!grouped[roomType.id].ratePlans[bookingSourceName]) {
          grouped[roomType.id].ratePlans[bookingSourceName] = {
            name: bookingSourceName,
            items: [],
          };
        }
        grouped[roomType.id].ratePlans[bookingSourceName].items.push({
          id: `${roomType.id}_${ratePlan.id}_${ota.id}`,
          name: ratePlan.title,
          roomTypeId: roomType.id,
          ratePlanId: ratePlan.id,
          bookingSourceId: ota.id,
        });
      });
    });
  });

  return grouped;
}

function toggleAllRatePlansForRoomType(roomTypeId) {
  const grouped = getGroupedRatePlans();
  const roomType = grouped[roomTypeId];
  if (!roomType) return;

  const allKeys = [];
  Object.values(roomType.ratePlans).forEach((group) => {
    group.items.forEach((item) => {
      allKeys.push(item.id);
    });
  });

  const currentSelected = priceSettings.selectedRatePlans.filter((rp) =>
    allKeys.includes(rp)
  );

  if (currentSelected.length === allKeys.length) {
    // Nếu đã chọn tất cả thì bỏ chọn tất cả
    priceSettings.selectedRatePlans = priceSettings.selectedRatePlans.filter(
      (rp) => !allKeys.includes(rp)
    );
  } else {
    // Nếu chưa chọn tất cả thì chọn tất cả
    priceSettings.selectedRatePlans = [
      ...new Set([...priceSettings.selectedRatePlans, ...allKeys]),
    ];
  }
}

function toggleAllRatePlansForBookingSource(roomTypeId, bookingSourceName) {
  const grouped = getGroupedRatePlans();
  const bookingSource = grouped[roomTypeId]?.ratePlans[bookingSourceName];
  if (!bookingSource) return;

  const bookingSourceKeys = bookingSource.items.map((item) => item.id);
  const currentSelected = priceSettings.selectedRatePlans.filter((rp) =>
    bookingSourceKeys.includes(rp)
  );

  if (currentSelected.length === bookingSourceKeys.length) {
    // Nếu đã chọn tất cả thì bỏ chọn tất cả
    priceSettings.selectedRatePlans = priceSettings.selectedRatePlans.filter(
      (rp) => !bookingSourceKeys.includes(rp)
    );
  } else {
    // Nếu chưa chọn tất cả thì chọn tất cả
    priceSettings.selectedRatePlans = [
      ...new Set([...priceSettings.selectedRatePlans, ...bookingSourceKeys]),
    ];
  }
}

function toggleRatePlan(itemId) {
  const index = priceSettings.selectedRatePlans.indexOf(itemId);

  if (index > -1) {
    priceSettings.selectedRatePlans.splice(index, 1);
  } else {
    priceSettings.selectedRatePlans.push(itemId);
  }
}

function isRatePlanSelected(itemId) {
  return priceSettings.selectedRatePlans.includes(itemId);
}

const restrictionForm = useForm({});
function handleSavePriceDrawer() {
  // Validate required fields
  for (const range of priceSettings.dateRanges) {
    if (!range.start || !range.end || !range.weekdays.length) {
      alert(
        "Vui lòng nhập đầy đủ khoảng ngày và chọn ít nhất 1 ngày trong tuần."
      );
      return;
    }
  }
  if (!priceSettings.selectedRatePlans.length) {
    alert("Vui lòng chọn ít nhất một loại giá để áp dụng.");
    return;
  }

  // Validate giá trị nhập nếu enable
  if (
    priceSettings.enableRate &&
    (!priceSettings.rateValue || priceSettings.rateValue <= 0)
  ) {
    alert("Vui lòng nhập giá hợp lệ.");
    return;
  }
  if (
    priceSettings.enableMinStayArrival &&
    (!priceSettings.minStayArrivalValue ||
      priceSettings.minStayArrivalValue <= 0)
  ) {
    alert("Vui lòng nhập số đêm lưu trú tối thiểu khi đến hợp lệ.");
    return;
  }
  if (
    priceSettings.enableMinStayThrough &&
    (!priceSettings.minStayThroughValue ||
      priceSettings.minStayThroughValue <= 0)
  ) {
    alert("Vui lòng nhập số đêm lưu trú tối thiểu xuyên suốt hợp lệ.");
    return;
  }
  if (
    priceSettings.enableMaxStay &&
    (!priceSettings.maxStayValue || priceSettings.maxStayValue <= 0)
  ) {
    alert("Vui lòng nhập số đêm lưu trú tối đa hợp lệ.");
    return;
  }

  // Chuẩn bị payload gửi backend - chỉ gửi các field được enable
  const changes = {};

  if (priceSettings.enableRate) {
    changes.rate = priceSettings.rateValue;
    changes.rateType = priceSettings.rateType;
  }
  if (priceSettings.enableStopSell) {
    changes.stop_sell = priceSettings.stopSellValue;
  }
  if (priceSettings.enableMinStayArrival) {
    changes.min_stay_arrival = priceSettings.minStayArrivalValue;
  }
  if (priceSettings.enableMinStayThrough) {
    changes.min_stay_through = priceSettings.minStayThroughValue;
  }
  if (priceSettings.enableMaxStay) {
    changes.max_stay = priceSettings.maxStayValue;
  }
  if (priceSettings.enableClosedToArrival) {
    changes.closed_to_arrival = priceSettings.closedToArrivalValue;
  }
  if (priceSettings.enableClosedToDeparture) {
    changes.closed_to_departure = priceSettings.closedToDepartureValue;
  }

  const weekdaysForBackend = priceSettings.dateRanges.map((range) => ({
    ...range,
    weekdays: range.weekdays.map((idx) => (idx + 1) % 7),
  }));

  const payload = {
    property_id: filtersData.value.property_id,
    dateRanges: weekdaysForBackend,
    changes: changes,
    applyTo: priceSettings.selectedRatePlans,
  };

  console.log("Payload:", payload);

  restrictionForm
    .transform(() => payload)
    .post(route("inventory.update-bulk-restriction"), {
      onSuccess: () => {
        // Có thể reset form hoặc hiển thị thông báo thành công ở đây nếu muốn
      },
      onError: (errors) => {
        // Xử lý lỗi nếu cần
        console.error(errors);
      },
      preserveScroll: true,
    });
  isPriceDrawerOpen.value = false;
}

// Helper functions for rate input
function getRateInputLabel() {
  switch (priceSettings.rateType) {
    case "exact":
      return "Giá";
    case "increase_amount":
    case "decrease_amount":
      return "Số tiền";
    case "increase_percent":
    case "decrease_percent":
      return "Phần trăm";
    default:
      return "Giá trị";
  }
}

function getRateInputSuffix() {
  switch (priceSettings.rateType) {
    case "exact":
    case "increase_amount":
    case "decrease_amount":
      return props.currency;
    case "increase_percent":
    case "decrease_percent":
      return "%";
    default:
      return "";
  }
}

function getRateDescription() {
  switch (priceSettings.rateType) {
    case "exact":
      return "Giá cho những ngày bạn chọn sẽ được đặt theo giá bạn nhập.";
    case "increase_amount":
      return "Giá hiện tại sẽ được tăng thêm số tiền bạn nhập.";
    case "decrease_amount":
      return "Giá hiện tại sẽ được giảm đi số tiền bạn nhập.";
    case "increase_percent":
      return "Giá hiện tại sẽ được tăng thêm phần trăm bạn nhập.";
    case "decrease_percent":
      return "Giá hiện tại sẽ được giảm đi phần trăm bạn nhập.";
    default:
      return "";
  }
}

// Thêm function để cập nhật highlight
const updateHighlightedCells = () => {
  if (!dragStartDate.value || !dragEndDate.value || !highlightedRowInfo.value)
    return;

  highlightedCells.value.clear();

  const startDateStr = dragStartDate.value.fullDate;
  const endDateStr = dragEndDate.value.fullDate;

  // Sắp xếp ngày bắt đầu và kết thúc
  const startDate = startDateStr < endDateStr ? startDateStr : endDateStr;
  const endDate = startDateStr < endDateStr ? endDateStr : startDateStr;

  // Tìm tất cả các ngày trong range
  const startIndex = props.dates.findIndex(
    (date) => date.fullDate === startDate
  );
  const endIndex = props.dates.findIndex((date) => date.fullDate === endDate);

  if (startIndex !== -1 && endIndex !== -1) {
    // Chỉ highlight các ô trong range cho row duy nhất mà user đang kéo
    for (let i = startIndex; i <= endIndex; i++) {
      const date = props.dates[i];
      // Tạo key duy nhất cho row đang được kéo
      const cellKey = getCellKeyForHighlight(date, highlightedRowInfo.value);
      highlightedCells.value.add(cellKey);
    }
  }
};

// Thêm function để tạo key duy nhất cho row (không phụ thuộc vào ngày)
const getCellKeyForHighlight = (date, rowInfo) => {
  const { type, roomType, ratePlan, ota, occupancy } = rowInfo;

  if (type === "avl") {
    return `avl_${roomType.id}`;
  } else if (type === "local") {
    return `local_${roomType.id}_${ratePlan.id}`;
  } else if (type.startsWith("ota_")) {
    const field = type.replace("ota_", "");
    if (occupancy) {
      return `ota_${field}_${roomType.id}_${ratePlan.id}_${ota.id}_${occupancy}`;
    } else {
      return `ota_${field}_${roomType.id}_${ratePlan.id}_${ota.id}`;
    }
  } else if (type === "ota_rate") {
    if (occupancy) {
      return `ota_rate_${roomType.id}_${ratePlan.id}_${ota.id}_${occupancy}`;
    } else {
      return `ota_rate_${roomType.id}_${ratePlan.id}_${ota.id}`;
    }
  }

  return `${type}_${roomType.id}`;
};

// Thêm computed property để kiểm tra ô có được highlight không
const isCellHighlighted = (
  date,
  type,
  roomType,
  ratePlan = null,
  ota = null,
  occupancy = null
) => {
  if (!highlightedRowInfo.value) return false;

  // Kiểm tra xem cell hiện tại có phải là cùng row với row đang được kéo không
  const currentRowKey = getRowKey({ type, roomType, ratePlan, ota, occupancy });
  const highlightedRowKey = getRowKey(highlightedRowInfo.value);

  // Chỉ highlight nếu cùng row
  if (currentRowKey !== highlightedRowKey) return false;

  // Kiểm tra xem ngày có trong range được chọn không
  const startDateStr = dragStartDate.value?.fullDate;
  const endDateStr = dragEndDate.value?.fullDate;

  if (!startDateStr || !endDateStr) return false;

  const startDate = startDateStr < endDateStr ? startDateStr : endDateStr;
  const endDate = startDateStr < endDateStr ? endDateStr : startDateStr;

  return date.fullDate >= startDate && date.fullDate <= endDate;
};

// Thêm function để tạo key duy nhất cho row (không phụ thuộc vào ngày)
const getRowKey = (rowInfo) => {
  const { type, roomType, ratePlan, ota, occupancy } = rowInfo;

  if (type === "avl") {
    return `avl_${roomType.id}`;
  } else if (type === "local") {
    return `local_${roomType.id}_${ratePlan.id}`;
  } else if (type.startsWith("ota_")) {
    const field = type.replace("ota_", "");
    if (occupancy) {
      return `ota_${field}_${roomType.id}_${ratePlan.id}_${ota.id}_${occupancy}`;
    } else {
      return `ota_${field}_${roomType.id}_${ratePlan.id}_${ota.id}`;
    }
  } else if (type === "ota_rate") {
    if (occupancy) {
      return `ota_rate_${roomType.id}_${ratePlan.id}_${ota.id}_${occupancy}`;
    } else {
      return `ota_rate_${roomType.id}_${ratePlan.id}_${ota.id}`;
    }
  }

  return `${type}_${roomType.id}`;
};

// Thêm computed property để kiểm tra xem có phải zero availability row không
const isZeroAvailabilityRow = (date, roomType) => {
  // Lấy giá trị gốc từ localInventoryGrid, không phải từ pendingChanges
  const originalAvailability =
    localInventoryGrid.value[date.fullDate]?.[roomType.id]?.avl;
  return originalAvailability === 0 || originalAvailability === "0";
};

// Thêm function để kiểm tra xem có phải stop sell row không
const isStopSellRow = (
  date,
  roomType,
  ratePlan,
  type,
  ratePlanOTAId = null,
  occupancy = null
) => {
  // Chỉ kiểm tra cho rate cells (local và ota_rate)
  if (type !== "local" && type !== "ota_rate") {
    return false;
  }

  // Kiểm tra stop_sell cho OTA
  if (type === "ota_rate" && ratePlanOTAId) {
    const stopSellValue = getOTARestrictionValue(
      date,
      roomType,
      ratePlan,
      ratePlanOTAId,
      "stop_sell",
      occupancy
    );
    return stopSellValue === true;
  }

  // Kiểm tra stop_sell cho local (nếu có)
  // Note: Local thường không có stop_sell, nhưng có thể mở rộng trong tương lai
  return false;
};
</script>

<style lang="scss">
.table-container {
  overflow-x: auto;
  max-width: 100%;
  border-radius: 6px;
}

.inventory-table {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  table-layout: fixed;
  min-width: 800px; // Đảm bảo table có đủ width để scroll

  .disabled-cell {
    background-color: rgba(var(--v-theme-on-surface), 0.04) !important;
    cursor: not-allowed !important;
    opacity: 0.6;
  }

  .disabled-text {
    color: rgba(var(--v-theme-on-surface), 0.6) !important;
    font-style: italic;
  }

  .header-style tr th {
    background-color: rgb(var(--v-theme-blue-light), 1) !important;
  }

  th,
  td {
    border-inline-end: 1px solid
      rgba(var(--v-border-color), var(--v-border-opacity));
    width: 80px;
  }

  // th:first-child,
  // td:first-child {
  //   width: 200px;
  // }

  // th:nth-child(2),
  // td:nth-child(2) {
  //   width: 120px;
  // }

  // th:nth-child(3),
  // td:nth-child(3) {
  //   width: 100px;
  // }

  tr > td {
    border-block-end: 1px solid
      rgba(var(--v-border-color), var(--v-border-opacity));
  }

  .room-type-header {
    font-size: 1.1rem;
    padding-block: 0.8rem;
    text-align: left;
    padding-inline-start: 1rem;
    background: rgb(var(--v-theme-grey-100));
  }

  .sticky-col-1 {
    position: sticky;
    left: 0;
    background: rgb(var(--v-theme-surface));
    z-index: 2;
    min-width: 120px;
    // box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
  }

  .sticky-col-2 {
    position: sticky;
    left: 120px;
    background: rgb(var(--v-theme-surface));
    z-index: 2;
    min-width: 120px;
    // box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
  }

  .sticky-col-3 {
    position: sticky;
    left: 240px;
    background: rgb(var(--v-theme-surface));
    z-index: 2;
    min-width: 100px;
    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
  }

  // Đảm bảo các cột sticky có background phù hợp khi hover
  // .sticky-col-1:hover,
  // .sticky-col-2:hover,
  // .sticky-col-3:hover {
  //   background: rgb(var(--v-theme-grey-lighten-4)) !important;
  // }

  // Đảm bảo các cột sticky có background phù hợp
  .sticky-col-1.bg-grey-lighten-2 {
    background: rgb(var(--v-theme-grey-lighten-2)) !important;
  }

  .sticky-col-2.bg-grey-lighten-2 {
    background: rgb(var(--v-theme-grey-lighten-2)) !important;
  }

  // Đảm bảo các cột sticky có background phù hợp khi có class khác
  .sticky-col-1.text-primary,
  .sticky-col-2.text-primary {
    background: rgb(var(--v-theme-grey-lighten-2)) !important;
  }

  // Đảm bảo các cột sticky có background phù hợp khi có class khác
  .sticky-col-1.bg-grey-lighten-2,
  .sticky-col-2.bg-grey-lighten-2 {
    background: rgb(var(--v-theme-grey-lighten-2)) !important;
  }

  .sticky-col {
    position: sticky;
    inset-inline-start: 0;
    background: rgb(var(--v-theme-surface));
    z-index: 2;

    div {
      min-width: 120px;
    }

    .rateplan-name {
      width: 80px;
    }
  }

  thead th {
    position: sticky;
    top: 0;
    z-index: 3;
    background-color: rgb(var(--v-theme-grey-light)) !important;
  }

  thead th.sticky-col-1 {
    z-index: 5 !important;
    left: 0;
  }

  thead th.sticky-col-2 {
    z-index: 5 !important;
    left: 120px;
  }

  thead th.sticky-col-3 {
    z-index: 5 !important;
    left: 240px;
  }

  thead th:first-child {
    z-index: 4 !important;
  }

  th {
    user-select: none;
  }

  .clickable-cell {
    cursor: pointer;
    user-select: none; // Ngăn chặn text selection khi kéo

    &:hover {
      background-color: rgba(var(--v-theme-on-surface), 0.04);
    }

    // Thêm style cho trạng thái đang kéo
    &.dragging {
      background-color: rgba(var(--v-theme-primary), 0.1);
      border: 2px solid rgba(var(--v-theme-primary), 0.5);
    }

    // Style cho ô được highlight khi kéo
    &.highlighted-cell {
      background-color: rgba(var(--v-theme-primary), 0.15) !important;
      border: 2px solid rgba(var(--v-theme-primary), 0.6) !important;
      position: relative;

      &::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(var(--v-theme-primary), 0.1);
        pointer-events: none;
      }
    }
  }

  .changed-cell {
    background-color: rgba(var(--v-theme-warning), 0.2) !important;
    border: 2px solid rgba(var(--v-theme-warning), 0.5) !important;
  }

  .local-rate.changed-cell,
  .ota-rate.changed-cell {
    background-color: rgba(var(--v-theme-warning), 0.3) !important;
    border-color: rgba(var(--v-theme-warning), 0.7) !important;
  }

  .rate-display {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 4px;
  }

  .local-rate,
  .ota-rate {
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 4px;
    padding: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .local-rate:hover,
  .ota-rate:hover {
    background-color: rgba(var(--v-theme-on-surface), 0.04);
  }

  .local-rate {
    background-color: rgba(var(--v-theme-primary), 0.05);
    border-color: rgba(var(--v-theme-primary), 0.3);
  }

  .ota-rate {
    background-color: rgba(var(--v-theme-secondary), 0.05);
    border-color: rgba(var(--v-theme-secondary), 0.3);
  }

  .rate-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--v-theme-on-surface-variant);
    text-align: center;
  }

  .rate-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--v-theme-on-surface);
    text-align: center;
  }

  .cursor-help {
    cursor: help;
  }

  .override-indicator {
    border-color: rgba(var(--v-theme-warning), 0.5);
    background-color: rgba(var(--v-theme-warning), 0.1);
  }

  .override-indicator .rate-label::after {
    content: " *";
    color: var(--v-theme-warning);
  }

  .zero-availability-row {
    background-color: rgba(244, 67, 54, 0.1) !important; // Màu đỏ nhạt
    border-color: rgba(244, 67, 54, 0.3) !important;

    // Tăng độ đậm cho text để dễ đọc
    .rate-value {
      color: rgba(244, 67, 54, 0.8) !important;
      font-weight: 700;
    }

    // Style đặc biệt cho boolean fields
    .rate-value span {
      color: rgba(244, 67, 54, 0.8) !important;
    }
  }

  // Đảm bảo zero availability row có priority cao hơn các style khác
  .zero-availability-row.clickable-cell:hover {
    background-color: rgba(244, 67, 54, 0.2) !important;
  }

  .zero-availability-row.changed-cell {
    background-color: rgba(244, 67, 54, 0.25) !important;
    border-color: rgba(244, 67, 54, 0.5) !important;
  }

  .zero-availability-row.highlighted-cell {
    background-color: rgba(244, 67, 54, 0.3) !important;
    border-color: rgba(244, 67, 54, 0.7) !important;
  }

  // Style cho stop sell row (chỉ áp dụng cho rate cells)
  .stop-sell-row {
    background-color: rgba(244, 67, 54, 0.1) !important; // Màu cam nhạt
    border-color: rgba(244, 67, 54, 0.3) !important;

    // Tăng độ đậm cho text để dễ đọc
    .rate-value {
      // color: rgba(255, 152, 0, 0.8) !important;
      color: rgba(244, 67, 54, 0.8) !important;
      font-weight: 700;
    }

    // Style đặc biệt cho boolean fields
    .rate-value span {
      // color: rgba(255, 152, 0, 0.8) !important;
      color: rgba(244, 67, 54, 0.8) !important;
    }
  }

  // Đảm bảo stop sell row có priority cao hơn các style khác
  .stop-sell-row.clickable-cell:hover {
    background-color: rgba(244, 67, 54, 0.2) !important;
  }

  .stop-sell-row.changed-cell {
    background-color: rgba(244, 67, 54, 0.25) !important;
    border-color: rgba(244, 67, 54, 0.5) !important;
  }

  .stop-sell-row.highlighted-cell {
    background-color: rgba(244, 67, 54, 0.3) !important;
    border-color: rgba(244, 67, 54, 0.7) !important;
  }
}
@media (max-width: 768px) {
  .flex-mobile {
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }
}
</style>
