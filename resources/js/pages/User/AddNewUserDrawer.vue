<script setup>
import EventBus from "@/pages/Events/eventBus";
import avatar1 from "@images/avatars/avatar-1.png";
import axios from "axios";
import { ref, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { toast } from "vue3-toastify";
import { VForm } from "vuetify/components/VForm";
import { CURRENCIES, PROPERTY_TYPE_OPTIONS } from "@/utils/constants";
const page = usePage();
const user = computed(() => page.props.auth.user);
const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  roles: Array,
  partnerGroup: Array,
});

const emit = defineEmits(["update:isDrawerOpen", "userData"]);
const users = ref([]);
const isFormValid = ref(false);
const refForm = ref();
const first_name = ref("");
const last_name = ref("");
const username = ref("");
const email = ref("");
const phone = ref("");
const role = ref();
const password = ref();
const refInputEl = ref();
const partner_group_id = ref("");

const numberOfProperty = ref(0);
const properties = ref([]);
const positiveValidator = (value) => {
  // console.log("Validating value:", isNewPartnerAdmin.value);
  const numValue = Number(value);
  return numValue > 0 ? true : "Giá trị phải lớn hơn 0";
};

const maxRoomsValidator = (maxRooms, maxRoomTypes) => {
  const maxRoomsNum = Number(maxRooms);
  const maxRoomTypesNum = Number(maxRoomTypes);
  if (maxRoomsNum < maxRoomTypesNum) {
    return "Số phòng tối đa phải lớn hơn hoặc bằng số loại phòng";
  }
  return true;
};

watch(numberOfProperty, (val) => {
  const numVal = Number(val);
  if (numVal && numVal > 0) {
    const existingProperties = properties.value;
    const newProperties = [...Array(numVal).keys()].map((i) => {
      // Keep existing property data if it exists, otherwise create new one
      const existingProperty = existingProperties[i];
      return (
        existingProperty || {
          name: `Chỗ Nghỉ ${i + 1}`,
          type: "",
          max_room_types: 1,
          max_rooms: 1,
        }
      );
    });
    properties.value = newProperties;
  } else {
    properties.value = [];
  }
});

const roleItems = computed(() => {
  return props.roles
    ? Object.values(props.roles).map((role) => ({
        title: role.name,
        value: role.id,
      }))
    : [];
});

const accountData = {
  avatarImg: avatar1,
};

const accountDataLocal = ref(structuredClone(accountData));

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false);
  nextTick(() => {
    refForm.value?.reset();
    refForm.value?.resetValidation();
  });
};

const onSubmit = () => {
  refForm.value?.validate().then(async ({ valid, errors }) => {
    console.log({ valid, errors });
    console.log("Properties data:", properties.value);

    if (valid) {
      const formData = new FormData();
      formData.append("first_name", first_name.value);
      formData.append("last_name", last_name.value);
      formData.append("username", username.value);
      formData.append("role", role.value);
      formData.append("phone", phone.value);
      formData.append("partner_group_id", partner_group_id.value);
      formData.append("email", email.value);
      formData.append("password", password.value);

      if (isNewPartnerAdmin.value) {
        formData.append("properties", JSON.stringify(properties.value));
      }

      if (refInputEl.value.files[0]) {
        formData.append("profile_photo_path", refInputEl.value.files[0]);
      }

      try {
        const response = await axios.post(route("users.store"), formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        if (response.data.success) {
          emit("userData");
          toast.success(response.data.message, {
            theme: "colored",
            type: "success",
          });
          EventBus.emit("userCreated", response.data);
        }
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errors = error.response.data.errors;

          // Lấy tất cả message gộp lại
          const firstError = Object.values(errors).flat()[0];
          toast.error(firstError, {
            theme: "colored",
            type: "error",
          });
        } else {
          toast.error("Có lỗi xảy ra. Vui lòng thử lại.", {
            theme: "colored",
            type: "error",
          });
        }
      }

      emit("update:isDrawerOpen", false);
      refForm.value?.reset();
      refForm.value?.resetValidation();
    }
  });
};

const fetchUsers = async () => {
  try {
    const response = await axios.get(route("users.getData"));
    users.value = response.data.data;
  } catch (error) {
    console.error("Error fetching user:", error);
  }
};

onMounted(() => {
  fetchUsers();
});

const handleDrawerModelValueUpdate = (val) => {
  emit("update:isDrawerOpen", val);
};

const changeAvatar = (file) => {
  const fileReader = new FileReader();
  const { files } = file.target;
  if (files && files.length) {
    fileReader.readAsDataURL(files[0]);
    fileReader.onload = () => {
      if (typeof fileReader.result === "string")
        accountDataLocal.value.avatarImg = fileReader.result;
    };
  }
};

const resetAvatar = () => {
  accountDataLocal.value.avatarImg = accountData.avatarImg;
};

const partnerAdminRole = computed(() => {
  return props.roles?.find((role) => role.name === "Partner Admin");
});
const isNewPartnerAdmin = computed(() => {
  return partnerAdminRole.value && role.value == partnerAdminRole.value.id;
});

const superAdminRole = computed(() => {
  return props.roles?.find((role) => role.name === "Super Admin");
});

console.log("Super Admin Role:", superAdminRole.value);
console.log("Partner Admin Role:", partnerAdminRole.value);

const isOtherRole = computed(() => {
  return (
    role.value !== partnerAdminRole.value?.id &&
    role.value !== superAdminRole.value?.id
  );
});

const isPasswordVisible = ref(false);

const roomOptions = [...Array(100).keys()].map((i) => i + 1);
</script>

<template>
  <VNavigationDrawer
    data-allow-mismatch
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <AppDrawerHeaderSection
      title="Thêm người dùng"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />
    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <!-- 👉 Form -->
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <VCardText class="d-flex p-[0px]">
                  <!-- 👉 Avatar -->
                  <VAvatar
                    rounded
                    size="100"
                    class="me-6"
                    :image="accountDataLocal.avatarImg"
                  />
                  <!-- 👉 Upload Photo -->
                  <div class="d-flex flex-column justify-center gap-4">
                    <div class="d-flex flex-wrap gap-4">
                      <VBtn
                        color="primary"
                        size="small"
                        @click="refInputEl?.click()"
                      >
                        <VIcon icon="tabler-cloud-upload" class="d-sm-none" />
                        <span class="d-none d-sm-block">Tải ảnh mới</span>
                      </VBtn>

                      <input
                        ref="refInputEl"
                        type="file"
                        name="file"
                        accept=".jpeg,.png,.jpg,GIF"
                        hidden
                        @input="changeAvatar"
                      />

                      <VBtn
                        type="reset"
                        size="small"
                        color="secondary"
                        variant="tonal"
                        @click="resetAvatar"
                      >
                        <span class="d-none d-sm-block">Đặt lại</span>
                        <VIcon icon="tabler-refresh" class="d-sm-none" />
                      </VBtn>
                    </div>

                    <p class="text-body-1 mb-0">
                      Chỉ chấp nhận các định dạng JPG, GIF hoặc PNG. Dung lượng
                      tối đa 800KB.
                    </p>
                  </div>
                </VCardText>
              </VCol>
              <!-- 👉 Full name -->
              <VCol cols="12">
                <AppTextField
                  v-model="first_name"
                  :rules="[requiredValidator]"
                  label="Tên"
                  placeholder="John"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="last_name"
                  :rules="[requiredValidator]"
                  label="Họ"
                  placeholder="Doe"
                />
              </VCol>
              <!-- 👉 Username -->
              <VCol cols="12">
                <AppTextField
                  v-model="username"
                  :rules="[requiredValidator]"
                  label="Username"
                  placeholder="Johndoe"
                />
              </VCol>

              <!-- 👉 Email -->
              <VCol cols="12">
                <AppTextField
                  v-model="email"
                  :rules="[requiredValidator, emailValidator]"
                  label="Email"
                  placeholder="johndoe@email.com"
                />
              </VCol>

              <!-- 👉 Password -->
              <VCol cols="12">
                <AppTextField
                  v-model="password"
                  label="Password"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="
                    isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'
                  "
                  placeholder="Enter Password"
                  :rules="[requiredValidator, passwordValidator]"
                  autocomplete="on"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>

              <!-- 👉 Contact -->
              <VCol cols="12">
                <AppTextField
                  v-model="phone"
                  type="number"
                  :rules="[requiredValidator]"
                  label="Số điện thoại"
                  placeholder="+1-541-754-3010"
                />
              </VCol>

              <!-- 👉 Role -->
              <VCol cols="12">
                <AppSelect
                  v-model="role"
                  label="Chọn quyền"
                  placeholder="Chọn quyền"
                  :rules="[requiredValidator]"
                  :items="roleItems"
                />
              </VCol>

              <VCol
                cols="12"
                v-if="user.role == 'Super Admin' && !isNewPartnerAdmin"
              >
                <AppSelect
                  item-title="name"
                  item-value="id"
                  v-model="partner_group_id"
                  :items="props.partnerGroup"
                  label="Chọn Partner Admin"
                />
              </VCol>

              <template v-if="isNewPartnerAdmin">
                <VCol cols="12">
                  <AppTextField
                    v-model.number="numberOfProperty"
                    type="number"
                    label="Chọn số lượng chỗ nghỉ"
                    placeholder="Chọn số lượng chỗ nghỉ"
                    :max="50"
                    :rules="[requiredValidator, positiveValidator]"
                    :items="roomOptions"
                  />
                </VCol>
                <VCol cols="12">
                  <div
                    v-for="(item, index) in properties"
                    :key="index"
                    class="mb-4"
                  >
                    <div class="mb-3">
                      <AppTextField
                        v-model="item.name"
                        :rules="[requiredValidator]"
                        label="Tên chỗ nghỉ"
                        placeholder="Nhập tên chỗ nghỉ"
                      />
                    </div>
                    <div class="mb-3">
                      <AppSelect
                        v-model="item.type"
                        :items="PROPERTY_TYPE_OPTIONS"
                        :rules="[requiredValidator]"
                        item-title="label"
                        item-value="value"
                        label="Loại hình chỗ nghỉ"
                        placeholder="Loại"
                      />
                    </div>
                    <div>
                      <AppTextField
                        v-model.number="item.max_room_types"
                        type="number"
                        :rules="[requiredValidator, positiveValidator]"
                        label="Số loại phòng tối đa"
                        placeholder="Nhập số lượng"
                      />
                      <AppTextField
                        v-model.number="item.max_rooms"
                        type="number"
                        :rules="[
                          requiredValidator,
                          positiveValidator,
                          (value) =>
                            maxRoomsValidator(value, item.max_room_types),
                        ]"
                        label="Số phòng tối đa"
                        placeholder="Nhập số lượng"
                      />
                    </div>
                  </div>
                </VCol>
              </template>

              <!-- 👉 Submit and Cancel -->
              <VCol cols="12">
                <VBtn type="submit" class="me-3"> Submit </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="error"
                  @click="closeNavigationDrawer"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
