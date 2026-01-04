<script setup>
import { usePropertyStore } from "@/stores/usePropertyStore";
import { Link, usePage } from "@inertiajs/vue3";
import { layoutConfig } from "@layouts";
import SvgIcon from "@layouts/components/SvgIcon.vue";
import { can } from "@layouts/plugins/casl";
import { useLayoutConfigStore } from "@layouts/stores/config";
import {
  // getComputedNavLinkToProp,
  getDynamicI18nProps,
} from "@layouts/utils";
import { computed } from "vue";

const currentIcon = computed(() => {
  const icon = isNavLinkActive(props.item)
    ? props.item.icon_active ?? props.item.icon
    : props.item.icon;
  return icon ?? null;
});

const getResolvedIconUrl = (iconStr) => {
  if (!iconStr || typeof iconStr !== "string") return null;
  const cleanBaseUrl = baseUrl.value.replace(/\/$/, "");
  const cleanIcon = iconStr.replace(/^\//, "");
  return `${cleanBaseUrl}/${cleanIcon}`;
};

const props = defineProps({
  item: {
    type: null,
    required: true,
  },
});

const page = usePage();
const configStore = useLayoutConfigStore();
const hideTitleAndBadge = configStore.isVerticalNavMini();
const baseUrl = computed(() => page.props.base_url);
const propertyStore = usePropertyStore();
const getComputedNavLinkToProp = (item) => {
  const { base_url } = usePage().props;

  return {
    href: item.link
      ? propertyStore.selectedProperty
        ? `${base_url}/${item.link}?property_id=${propertyStore.selectedProperty}`
        : `${base_url}/${item.link}`
      : "#",
  };
};

// const isNavLinkActive = (item) => {
//   console.log(item.link);
//   const currentPage = usePage().url.split("?")[0];
//   const formattedPage = currentPage.replace(/^\/+/, "");

//   const formattedPageParts = formattedPage.split("/");
//   const itemLinkParts = item.link ? item.link.split("/") : [];

//   const isFirstPartMatch = formattedPageParts[0] === itemLinkParts[0];
//   const isRestMatch =
//     itemLinkParts.length === 1 ||
//     formattedPageParts.slice(1).join("/") === itemLinkParts.slice(1).join("/");

//   return isFirstPartMatch && isRestMatch;
// };

const isNavLinkActive = (item) => {
  const currentPage = usePage().url.split("?")[0].replace(/^\/+/, "");
  const itemLink = item.link?.replace(/^\/+/, "") || "";
  return currentPage === itemLink;
};

const isImage = (icon) => {
  return typeof icon === "string" && /\.(png|jpe?g|svg|gif|webp)$/i.test(icon);
};

const isSvg = (icon) => {
  return typeof icon === "string" && icon.endsWith(".svg");
};

const getIconUrl = (item) => {
  const icon = isNavLinkActive(item) ? item.icon_active : item.icon;
  // Đảm bảo baseUrl không có trailing slash và icon không có leading slash
  const cleanBaseUrl = baseUrl.value.replace(/\/$/, "");
  const cleanIcon = icon?.replace(/^\//, "") || "";
  const fullUrl = `${cleanBaseUrl}/${cleanIcon}`;
  return fullUrl;
};
</script>

<template>
  <li
    v-if="can(item.action, item.subject) && item.menu_key !== 'overview'"
    class="nav-link"
    :class="{ disabled: item.disable }"
  >
    <Link
      v-bind="getComputedNavLinkToProp(item)"
      :class="{
        'router-link-active router-link-exact-active': isNavLinkActive(item),
      }"
    >
      <!-- SVG Icon -->
      <SvgIcon
        v-if="currentIcon && isSvg(currentIcon)"
        :src="getResolvedIconUrl(currentIcon)"
        class="nav-item-icon"
      />
      <img
        v-else-if="currentIcon && isImage(currentIcon)"
        :src="getResolvedIconUrl(currentIcon)"
        class="nav-item-icon"
        alt=""
      />
      <Component
        v-else
        :is="layoutConfig.app.iconRenderer || 'div'"
        v-bind="
          typeof currentIcon === 'string' &&
          !isImage(currentIcon) &&
          !isSvg(currentIcon)
            ? { icon: currentIcon }
            : currentIcon && typeof currentIcon === 'object'
            ? currentIcon
            : layoutConfig.verticalNav.defaultNavItemIconProps
        "
        class="nav-item-icon"
      />
      <TransitionGroup name="transition-slide-x">
        <Component
          :is="layoutConfig.app.i18n.enable ? 'i18n-t' : 'span'"
          v-show="!hideTitleAndBadge"
          key="title"
          class="nav-item-title"
          v-bind="getDynamicI18nProps(item.title, 'span')"
        >
          {{ item.name }}
        </Component>
      </TransitionGroup>
    </Link>
  </li>
</template>

<style lang="scss">
.layout-vertical-nav {
  .nav-link a {
    display: flex;
    align-items: center;
  }
}

.layout-nav-type-vertical .layout-vertical-nav .nav-link .nav-item-icon,
.layout-nav-type-vertical .layout-vertical-nav .nav-group .nav-item-icon {
  inline-size: 1.5rem;
}

// SVG Icon styling for nav links
.layout-vertical-nav {
  .nav-link {
    // Default state
    .svg-icon-wrapper {
      :deep(.svg-icon) {
        color: rgba(
          var(--v-theme-on-surface),
          var(--v-medium-emphasis-opacity)
        );
        transition: color 0.2s ease;
      }
    }

    // Hover state
    &:hover .svg-icon-wrapper :deep(.svg-icon) {
      color: rgb(var(--v-theme-primary));
    }

    // Active state (when link is active)
    a.router-link-active {
      .svg-icon-wrapper :deep(.svg-icon) {
        color: rgb(var(--v-theme-primary));
      }
    }
  }
}
</style>
