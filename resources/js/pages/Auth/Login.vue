<script setup>
import { nestApi } from "@/@core/libs/api";
import { getOrCreateDeviceId } from "@/device.js";
import { initFcm } from "@/firebase.js";
import { useAuthStore } from "@/views/pages/authentication/useAuthStore";
import AppTextField from "@core/components/app-form-elements/AppTextField.vue";
import { useGenerateImageVariant } from "@core/composable/useGenerateImageVariant";
import authV2LoginIllustrationBorderedDark from "@images/pages/auth-v2-login-illustration-bordered-dark.png";
import authV2LoginIllustrationBorderedLight from "@images/pages/auth-v2-login-illustration-bordered-light.png";
import authV2LoginIllustrationDark from "@images/pages/auth-v2-login-illustration-dark.png";
import authV2LoginIllustrationLight from "@images/pages/auth-v2-login-illustration-light.png";
import authV2MaskDark from "@images/pages/misc-mask-dark.png";
import authV2MaskLight from "@images/pages/misc-mask-light.png";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { VNodeRenderer } from "@layouts/components/VNodeRenderer";
import { themeConfig } from "@themeConfig";
import { ref } from "vue";

const auth = useAuthStore();
definePage({
  meta: { layout: "blank", public: true },
});

const form = useForm({
  login: null,
  password: null,
  remember: false,
  nest_token: null,
});

const isPasswordVisible = ref(false);
const authThemeImg = useGenerateImageVariant(
  authV2LoginIllustrationLight,
  authV2LoginIllustrationDark,
  authV2LoginIllustrationBorderedLight,
  authV2LoginIllustrationBorderedDark,
  true
);
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark);

const onSubmit = async () => {
  form.clearErrors();

  try {
    const deviceId = getOrCreateDeviceId();

    let fcmToken = null;
    try {
      const { token } = await initFcm(
        import.meta.env.VITE_FIREBASE_VAPID_PUBLIC_KEY
      );
      fcmToken = token || null;
    } catch (errors) {
      console.log(errors);
      fcmToken = null;
    }

    const res = await nestApi.post("/api/admin/users/login", {
      identifier: form.login,
      password: form.password,
      deviceId,
      fcmToken,
    });

    const data = res?.data?.data ?? res?.data ?? {};
    const token = data.access_token || data.token;
    if (!token) throw new Error(res?.data?.message);

    auth.setToken(token);
    auth.setUser(data.user || null);

    form.nest_token = token;
    await form.post("/login");
  } catch (e) {
    const msg =
      (e.response && (e.response.data?.message || e.response.statusText)) ||
      e.message ||
      "Đăng nhập thất bại";
    form.setError("login", msg);
  }
};
</script>

<template>
  <Head title="Login" />
  <a href="javascript:void(0)">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </a>

  <VRow no-gutters class="auth-wrapper bg-surface">
    <VCol md="8" class="d-none d-md-flex">
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 6.25rem"
        >
          <VImg
            max-width="613"
            :src="authThemeImg"
            class="auth-illustration mt-16 mb-2"
          />
        </div>
        <img
          class="auth-footer-mask flip-in-rtl"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        />
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard flat :max-width="500" class="mt-12 mt-sm-0 pa-6">
        <VCardText>
          <h4 class="text-h4 mb-1">
            Chào mừng đến
            <span class="text-capitalize">{{ themeConfig.app.title }}</span
            >! 👋🏻
          </h4>
          <p class="mb-0">
            Vui lòng đăng nhập vào tài khoản của bạn và cùng chúng tôi khởi đầu
            một cuộc phiêu lưu mới hướng tới sự phát triển vượt bậc.
          </p>
        </VCardText>
        <VCardText>
          <VForm @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  label="Email hoặc Username"
                  v-model="form.login"
                  :error-messages="form.errors.login"
                  placeholder="angiapms@email.com"
                  autofocus
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  label="Mật khẩu"
                  v-model="form.password"
                  :error-messages="form.errors.password"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  placeholder="············"
                  autocomplete="password"
                  :append-inner-icon="
                    isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'
                  "
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />

                <div
                  class="d-flex align-center flex-wrap justify-space-between my-6"
                >
                  <VCheckbox v-model="form.remember" label="Nhớ mật  khẩu" />
                  <Link class="text-primary" href="/forgot-password"
                    >Quên mật khẩu?</Link
                  >
                </div>

                <VBtn block type="submit">Đăng Nhập</VBtn>
              </VCol>

              <!-- <VCol cols="12" class="text-body-1 text-center">
                <span class="d-inline-block"
                  >Bạn mới dùng nền tảng của chúng tôi?</span
                >
                <Link
                  class="text-primary ms-1 d-inline-block text-body-1"
                  href="/register"
                  >Tạo tài khoản</Link
                >
              </VCol> -->
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
