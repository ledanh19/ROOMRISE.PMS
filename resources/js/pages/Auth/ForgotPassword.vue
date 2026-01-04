<script setup>
import {useGenerateImageVariant} from '@core/composable/useGenerateImageVariant'
import {VNodeRenderer} from '@layouts/components/VNodeRenderer'
import {themeConfig} from '@themeConfig'
import authV2ForgotPasswordIllustrationDark from '@images/pages/auth-v2-forgot-password-illustration-dark.png'
import authV2ForgotPasswordIllustrationLight from '@images/pages/auth-v2-forgot-password-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import {useForm, Link} from "@inertiajs/vue3";
import AppTextField from "@core/components/app-form-elements/AppTextField.vue";

const authThemeImg = useGenerateImageVariant(authV2ForgotPasswordIllustrationLight, authV2ForgotPasswordIllustrationDark)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const form = useForm({
  email: null,
});
</script>

<template>
  <VRow
      class="auth-wrapper bg-surface"
      no-gutters
  >
    <VCol
        lg="8"
        class="d-none d-lg-flex"
    >
      <div class="position-relative bg-background rounded-lg w-100 ma-8 me-0">
        <div class="d-flex align-center justify-center w-100 h-100">
          <VImg
              max-width="368"
              :src="authThemeImg"
              class="auth-illustration mt-16 mb-2"
          />
        </div>

        <VImg
            class="auth-footer-mask"
            :src="authThemeMask"
        />
      </div>
    </VCol>

    <VCol
        cols="12"
        lg="4"
        class="d-flex align-center justify-center"
    >
      <VCard
          flat
          :max-width="500"
          class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText>
          <VNodeRenderer
              :nodes="themeConfig.app.logo"
              class="mb-6"
          />
          <h4 class="text-h4 mb-1">
            Quên Mật Khẩu? 🔒
          </h4>
          <p class="mb-0">
            Nhập email của bạn và chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu cho bạn.
          </p>
        </VCardText>

        <VCardText>
          <p v-if="$page.props.flash.status" class="font-weight-bold mt-4 mb-5">{{ $page.props.flash.status }}</p>

          <VForm v-else @submit.prevent="form.post('/forgot-password')" class="mb-3">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField
                    label="Email"
                    v-model="form.email"
                    :error-messages="form.errors.email"
                    :rules="[requiredValidator]"
                    type="email"
                    placeholder="johndoe@email.com"
                    autofocus
                />
              </VCol>

              <!-- Reset link -->
              <VCol cols="12">
                <VBtn
                    block
                    type="submit"
                    :disabled="form.processing"
                >
                  Gửi liên kết đặt lại mật khẩu
                </VBtn>
              </VCol>

              <!-- back to login -->
              <VCol cols="12">
                <Link
                    class="d-flex align-center justify-center"
                    href="/login"
                >
                  <VIcon
                      icon="tabler-chevron-left"
                      class="flip-in-rtl"
                  />
                  <span>Quay lại đăng nhập</span>
                </Link>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
