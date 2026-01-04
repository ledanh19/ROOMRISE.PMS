<script setup>
import avatar1 from "@images/avatars/avatar-1.png";
import { Head, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { toast } from "vue3-toastify";
import Layout from "../../layouts/blank.vue";

const props = defineProps({
  user: Object,
  roles: Object,
  role_current: Object,
  properties: Array,
});

const isFormValid = ref(false);
const refForm = ref();
const isPasswordVisible = ref(false);
const refInputEl = ref();

const accountData = {
  avatarImg: avatar1,
};

const form = useForm({
  name: props.user.name,
  username: props.user.username,
  email: props.user.email,
  phone: props.user.phone,
  role: props.role_current ? props.role_current.id : null,
  password: "",
});

// Tạo roomOptions từ số lượng ban đầu trở lên
const currentPropertyCount = props.properties.length || 1;
const roomOptions = [...Array(100 - currentPropertyCount + 1).keys()].map(
  (i) => i + currentPropertyCount
);

const numberOfProperty = ref(currentPropertyCount);
const properties = ref(
  props.properties.length > 0
    ? props.properties.map((p) => ({
        id: p.id,
        name: p.name,
        type: p.type,
        max_room_types: p.max_room_types,
        max_rooms: p.max_rooms,
      }))
    : [
        {
          name: "Chỗ Nghỉ 1",
          type: "",
          max_room_types: 0,
          max_rooms: 0,
        },
      ]
);

const positiveValidator = (value) => {
  return Number(value) > 0 ? true : "Giá trị phải lớn hơn 0";
};

const maxRoomsValidator = (maxRooms, maxRoomTypes) => {
  if (Number(maxRooms) < Number(maxRoomTypes)) {
    return "Số phòng tối đa phải lớn hơn hoặc bằng số loại phòng";
  }
  return true;
};

// Watch để cập nhật properties khi thay đổi số lượng
watch(numberOfProperty, (val) => {
  if (val && val >= currentPropertyCount) {
    const currentProperties = properties.value;
    const newProperties = [];

    // Luôn giữ lại tất cả properties hiện tại (có id) - đây là properties ban đầu
    const existingProperties = currentProperties.filter((p) => p.id);
    const newPropertiesOnly = currentProperties.filter((p) => !p.id);

    // Giữ lại tất cả properties hiện tại (có id)
    newProperties.push(...existingProperties);

    // Tính toán số lượng properties mới cần thêm
    const remainingSlots = val - existingProperties.length;

    if (remainingSlots > 0) {
      // Nếu cần thêm properties mới
      if (remainingSlots <= newPropertiesOnly.length) {
        // Nếu có đủ properties mới hiện tại, sử dụng chúng
        newProperties.push(...newPropertiesOnly.slice(0, remainingSlots));
      } else {
        // Nếu cần thêm properties mới, sử dụng properties hiện tại trước
        newProperties.push(...newPropertiesOnly);

        // Thêm properties mới nếu cần
        const currentTotal =
          existingProperties.length + newPropertiesOnly.length;
        for (let i = currentTotal; i < val; i++) {
          newProperties.push({
            name: `Chỗ Nghỉ ${i + 1}`,
            type: "",
            max_room_types: 0,
            max_rooms: 0,
          });
        }
      }
    }

    properties.value = newProperties;
  } else {
    // Nếu cố gắng giảm xuống dưới số lượng ban đầu, reset và hiển thị thông báo
    numberOfProperty.value = currentPropertyCount;
    toast.warning(
      `Không thể giảm xuống dưới ${currentPropertyCount} chỗ nghỉ ban đầu!`
    );
  }
});

const partnerAdminRole = computed(() => {
  return props.roles?.find((role) => role.name === "Partner Admin");
});

const isPartnerAdmin = computed(() => {
  return partnerAdminRole.value && form.role == partnerAdminRole.value.id;
});

const roleItems = computed(() =>
  props.roles
    ? Object.values(props.roles).map((role) => ({
        title: role.name,
        value: role.id,
      }))
    : []
);

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      // Kiểm tra validation cho properties nếu là Partner Admin
      if (isPartnerAdmin.value) {
        const hasInvalidProperties = properties.value.some(
          (prop) =>
            !prop.max_room_types ||
            !prop.max_rooms ||
            Number(prop.max_room_types) <= 0 ||
            Number(prop.max_rooms) <= 0 ||
            Number(prop.max_rooms) < Number(prop.max_room_types)
        );

        if (hasInvalidProperties) {
          toast.error("Vui lòng kiểm tra lại thông tin chỗ nghỉ!");
          return;
        }
      }

      const formData = new FormData();
      formData.append("_method", "PUT");
      formData.append("name", form.name);
      formData.append("username", form.username);
      formData.append("email", form.email);
      formData.append("phone", form.phone);
      formData.append("role", form.role);
      formData.append("password", form.password);

      // Thêm properties data nếu là Partner Admin
      if (isPartnerAdmin.value) {
        formData.append("properties", JSON.stringify(properties.value));
      }

      if (refInputEl.value && refInputEl.value.files.length > 0) {
        formData.append("profile_photo_path", refInputEl.value.files[0]);
      }

      router.post(route("users.update", props.user.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
          toast.success("Cập nhật người dùng thành công!");
        },
        onError: (errors) => {
          console.error(errors);
          toast.error("Cập nhật người dùng thất bại");
        },
        forceFormData: true,
      });
    }
  });
};

const changeAvatar = (file) => {
  const fileReader = new FileReader();
  const { files } = file.target;

  if (files && files.length) {
    fileReader.readAsDataURL(files[0]);
    fileReader.onload = () => {
      if (typeof fileReader.result === "string") {
        selectedAvatar.value = fileReader.result;
        form.profile_photo = files[0];
      }
    };
  }
};

const selectedAvatar = ref(
  props.user.profile_photo_url || accountData.avatarImg
);

const resetAvatar = () => {
  selectedAvatar.value = props.user.profile_photo_url || accountData.avatarImg;
  form.profile_photo = null;
};
</script>

<template>
  <Head title="Edit User | Room Rise" />
  <Layout>
    <VCard>
      <VCardItem>
        <VCardTitle>Edit User</VCardTitle>
      </VCardItem>
      <VCardText>
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
          enctype="multipart/form-data"
        >
          <VRow>
            <VCol cols="12">
              <VCardText class="d-flex p-[0px]">
                <!-- 👉 Avatar -->
                <VAvatar
                  rounded
                  size="100"
                  class="me-6"
                  :image="selectedAvatar"
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
                      name="profile_photo"
                      accept=".jpeg,.png,.jpg,GIF"
                      hidden
                      @input="changeAvatar"
                    />

                    <VBtn
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
            <VCol cols="12">
              <AppTextField
                v-model="form.name"
                :rules="[requiredValidator]"
                label="Full Name"
                placeholder="John Doe"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="form.username"
                :rules="[requiredValidator]"
                label="Username"
                placeholder="Johndoe"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="form.email"
                :rules="[requiredValidator, emailValidator]"
                label="Email"
                placeholder="johndoe@email.com"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="form.password"
                label="New Password"
                :type="isPasswordVisible ? 'text' : 'password'"
                :append-inner-icon="
                  isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'
                "
                placeholder="Enter Password"
                autocomplete="on"
                @click:append-inner="isPasswordVisible = !isPasswordVisible"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="form.phone"
                type="number"
                :rules="[requiredValidator]"
                label="Phone"
                placeholder="+1-541-754-3010"
              />
            </VCol>
            <!-- <VCol cols="12">
              <AppSelect
                v-model="form.role"
                label="Select Role"
                placeholder="Select Role"
                :rules="[requiredValidator]"
                :items="roleItems"
              />
            </VCol> -->
            <template v-if="isPartnerAdmin">
              <VCol cols="12">
                <AppSelect
                  v-model="numberOfProperty"
                  label="Chọn số lượng chỗ nghỉ"
                  placeholder="Chọn số lượng chỗ nghỉ"
                  :rules="[requiredValidator, positiveValidator]"
                  :items="roomOptions"
                />
              </VCol>
              <VCol cols="12">
                <div
                  v-for="(item, index) in properties"
                  :key="index"
                  class="mb-4 p-4 border rounded"
                >
                  <!-- {{ item }} -->
                  <VCardTitle class="text-h6 mb-3">
                    <VRow class="align-center">
                      <VCol cols="6" class="d-flex align-end gap-2">
                        <AppTextField
                          v-model="item.name"
                          label="Chỗ nghỉ"
                          placeholder=""
                        />
                        <VChip
                          v-if="index < currentPropertyCount"
                          color="primary"
                          size="small"
                          class="ml-2"
                        >
                          Hiện tại
                        </VChip>
                        <VChip v-else color="success" size="small" class="ml-2">
                          Mới
                        </VChip>
                      </VCol>
                      <VCol cols="6">
                        <AppSelect
                          v-model="item.type"
                          :items="PROPERTY_TYPE_OPTIONS"
                          :rules="[requiredValidator]"
                          item-title="label"
                          item-value="value"
                          label="Loại hình chỗ nghỉ"
                          placeholder="Loại"
                        />
                      </VCol>
                    </VRow>
                  </VCardTitle>
                  <VRow class="px-4 pb-4">
                    <VCol cols="12" md="6">
                      <AppTextField
                        v-model="item.max_room_types"
                        type="number"
                        :rules="[requiredValidator, positiveValidator]"
                        label="Số loại phòng tối đa"
                        placeholder="Nhập số lượng"
                      />
                    </VCol>
                    <VCol cols="12" md="6">
                      <AppTextField
                        v-model="item.max_rooms"
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
                    </VCol>
                  </VRow>
                </div>
              </VCol>
            </template>

            <VCol cols="12">
              <VBtn type="submit" class="me-3"> Lưu thay đổi </VBtn>
              <VBtn
                type="reset"
                variant="tonal"
                color="error"
                @click="$inertia.visit(route('users.index'))"
              >
                Hủy bỏ
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </Layout>
</template>
