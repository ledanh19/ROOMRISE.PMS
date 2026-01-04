<script setup>
import { useForm } from '@inertiajs/vue3';
import { toast } from "vue3-toastify";
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const isTwoFactorDialogOpen = ref(false);

const props = defineProps({
  userData: {
    type: Object,
    required: true,
  },
})

const form = useForm({
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  form.put(route('users.password-update', { id: props.userData.id }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(response.data.message, {
        theme: "colored",
        type: "success",
      });
    },
    onError: (errors) => {
      toast.error(errors, {
        theme: "colored",
        type: "error",
      });
    },
  });
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- 👉 Change password -->
      <VCard title="Change Password">
        <VCardText>
          <VAlert closable variant="tonal" color="warning" class="mb-4" title="Ensure that these requirements are met"
            text="Minimum 8 characters long, uppercase & symbol" />

          <VForm @submit.prevent="updatePassword">
            <VRow>
              <VCol cols="12" md="6">
                <AppTextField v-model="form.password" label="New Password" placeholder="············"
                  :type="isNewPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="form.password_confirmation" label="Confirm Password" autocomplete="confirm-password" placeholder="············"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible" />
              </VCol>

              <VCol cols="12">
                <VBtn type="submit" :loading="form.processing">
                  Change Password
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Enable One Time Password Dialog -->
  <TwoFactorAuthDialog v-model:is-dialog-visible="isTwoFactorDialogOpen" :sms-code="smsVerificationNumber" />
</template>
