<script setup>
import avatar1 from '@images/avatars/avatar-1.png';
import { router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { toast } from "vue3-toastify";

const props = defineProps({
  user: Object
});

const isFormValid = ref(false);
const refForm = ref();
const refInputEl = ref();

const accountData = {
  avatarImg: avatar1
}

const accountDataLocal = ref(structuredClone(accountData))
const selectedAvatar = ref(props.user.profile_photo_path || accountData.avatarImg);

const form = useForm({
  first_name: props.user.first_name,
  last_name: props.user.last_name,
  username: props.user.username,
  email: props.user.email,
  phone: props.user.phone,
  address: props.user.address,
  zipcode: props.user.zipcode
});

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      const formData = new FormData();
      formData.append("_method", "PUT");
      formData.append("first_name", form.first_name);
      formData.append("last_name", form.last_name);
      formData.append("username", form.username);
      formData.append("email", form.email);
      formData.append("phone", form.phone);
      formData.append("address", form.address);
      formData.append("zipcode", form.zipcode);

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
        forceFormData: true
      });
    }
  });
};

const changeAvatar = file => {
  const fileReader = new FileReader();
  const { files } = file.target;

  if (files && files.length) {
    fileReader.readAsDataURL(files[0]);
    fileReader.onload = () => {
      if (typeof fileReader.result === 'string') {
        selectedAvatar.value = fileReader.result;
        form.profile_photo = files[0];
      }
    };
  }
}

// reset avatar image
const resetAvatar = () => {
  accountDataLocal.value.avatarImg = accountData.avatarImg
}

const Back = () => {
    router.get(route('user.profile'))
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardText class="d-flex">
          <!-- 👉 Upload Photo -->
          <VForm class="d-flex flex-column justify-center gap-4" ref="refForm" v-model="isFormValid"
            @submit.prevent="onSubmit" enctype="multipart/form-data">
            <VRow>
              <VCol cols="12">
                <VCardText class="d-flex px-0">
                  <!-- 👉 Avatar -->
                  <VAvatar rounded size="100" class="me-6" :image="selectedAvatar" />

                  <!-- 👉 Upload Photo -->
                  <div class="d-flex flex-column justify-center gap-4">
                    <div class="d-flex flex-wrap gap-4">
                      <VBtn color="primary" size="small" @click="refInputEl?.click()">
                        <VIcon icon="tabler-cloud-upload" class="d-sm-none" />
                        <span class="d-none d-sm-block">Tải ảnh mới</span>
                      </VBtn>

                      <input ref="refInputEl" type="file" name="file" accept=".jpeg,.png,.jpg,GIF" hidden
                        @input="changeAvatar">

                      <VBtn type="reset" size="small" color="secondary" variant="tonal" @click="resetAvatar">
                        <span class="d-none d-sm-block">Đặt lại</span>
                        <VIcon icon="tabler-refresh" class="d-sm-none" />
                      </VBtn>
                    </div>

                    <p class="text-body-1 mb-0">
                      Chỉ chấp nhận các định dạng JPG, GIF hoặc PNG. Dung lượng tối đa 800KB.
                    </p>
                  </div>
                </VCardText>
                <VDivider class="mb-5" />
              </VCol>
              <!-- 👉 First Name -->
              <VCol md="6" cols="12">
                <AppTextField v-model="form.first_name" placeholder="John" label="First Name" />
              </VCol>

              <!-- 👉 Last Name -->
              <VCol md="6" cols="12">
                <AppTextField v-model="form.last_name" placeholder="Doe" label="Last Name" />
              </VCol>

              <!-- 👉 Email -->
              <VCol cols="12" md="6">
                <AppTextField v-model="form.email" label="E-mail" placeholder="johndoe@gmail.com" type="email" />
              </VCol>

              <!-- 👉 Phone -->
              <VCol cols="12" md="6">
                <AppTextField v-model="form.phone" label="Phone Number" placeholder="+1 (917) 543-9876" />
              </VCol>

              <!-- 👉 Address -->
              <VCol cols="12" md="6">
                <AppTextField v-model="form.address" label="Address"
                  placeholder="123 Main St, New York, NY 10001" />
              </VCol>

              <!-- 👉 Zip Code -->
              <VCol cols="12" md="6">
                <AppTextField v-model="form.zipcode" label="Zip Code" placeholder="10001" />
              </VCol>

              <!-- 👉 Form Actions -->
              <VCol cols="12" class="d-flex flex-wrap gap-4">
                <VBtn type="submit">Lưu thay đổi</VBtn>

                <VBtn color="secondary" variant="tonal" type="reset" @click="Back">
                  Hủy bỏ
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
