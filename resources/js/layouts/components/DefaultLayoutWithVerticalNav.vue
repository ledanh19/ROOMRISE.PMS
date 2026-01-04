<script setup>
import { themeConfig } from "@themeConfig";

// Components
import Footer from "@/layouts/components/Footer.vue";
import NavbarThemeSwitcher from "@/layouts/components/NavbarThemeSwitcher.vue";
import UserProfile from "@/layouts/components/UserProfile.vue";
import NavBarI18n from "@core/components/I18n.vue";
import { VerticalNavLayout } from "@layouts";

import axios from "axios";
import { onMounted, ref, watch } from "vue";

// Pinia store
import { usePropertyStore } from "@/stores/usePropertyStore";
const propertyStore = usePropertyStore();

// State local cho select
const filters = ref({
  property: null,
});

// Gọi API load property từ DB
const loadFilterOptions = async () => {
  try {
    const res = await axios.get(route("dashboard.properties"));
    const options = [
      { title: "Tất cả Property", value: null },
      ...res.data.map((p) => ({
        title: p.name,
        value: p.id,
      })),
    ];
    propertyStore.setProperties(options);
  } catch (err) {
    console.error("Lỗi khi load property:", err);
  }
};

onMounted(async () => {
  await loadFilterOptions();
});

// Khi user chọn property => cập nhật store toàn cục
watch(
  () => filters.value.property,
  (newVal) => {
    propertyStore.setProperty(newVal);
  }
);
</script>

<template>
  <VerticalNavLayout>
    <!-- 👉 navbar -->
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="d-flex h-100 align-center">
        <!-- Toggle button cho mobile -->
        <IconBtn
          id="vertical-nav-toggle-btn"
          class="ms-n3 d-lg-none"
          @click="toggleVerticalOverlayNavActive(true)"
        >
          <VIcon size="26" icon="tabler-menu-2" />
        </IconBtn>

        <NavbarThemeSwitcher />
        <VSpacer />

        <NavBarI18n
          v-if="
            themeConfig.app.i18n.enable &&
            themeConfig.app.i18n.langConfig?.length
          "
          :languages="themeConfig.app.i18n.langConfig"
        />
        <!-- ✅ Global Property Select -->
        <div class="d-flex align-center gap-2">
          <AppSelect
            v-model="propertyStore.selectedProperty"
            :items="propertyStore.properties"
            placeholder="Chọn property"
            class="mx-4"
            width="10rem"
          />

          <UserProfile />
        </div>
      </div>
    </template>

    <!-- 👉 Pages -->
    <slot />

    <!-- 👉 Footer -->
    <template #footer>
      <Footer />
    </template>
  </VerticalNavLayout>
</template>
