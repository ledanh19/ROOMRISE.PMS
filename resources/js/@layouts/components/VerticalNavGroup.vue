<script setup>
import { usePage } from "@inertiajs/vue3";
import { layoutConfig } from "@layouts";
import { TransitionExpand, VerticalNavLink } from "@layouts/components";
import SvgIcon from "@layouts/components/SvgIcon.vue";
import { canViewNavMenuGroup } from "@layouts/plugins/casl";
import { useLayoutConfigStore } from "@layouts/stores/config";
import { injectionKeyIsVerticalNavHovered } from "@layouts/symbols";
import {
  getDynamicI18nProps,
  // isNavGroupActive,
  openGroups,
} from "@layouts/utils";
import { TransitionGroup } from "vue";

const props = defineProps({
  item: {
    type: null,
    required: true,
  },
});

defineOptions({
  name: "VerticalNavGroup",
});

const route = useRoute();
const router = useRouter();
const configStore = useLayoutConfigStore();
const hideTitleAndBadge = configStore.isVerticalNavMini();

/*ℹ️ We provided default value `ref(false)` because inject will return `T | undefined`
Docs: https://vuejs.org/api/composition-api-dependency-injection.html#inject
*/
const isVerticalNavHovered = inject(
  injectionKeyIsVerticalNavHovered,
  ref(false)
);
const isGroupActive = ref(false);
const isGroupOpen = ref(false);

const isAnyChildOpen = (children) => {
  return children.some((child) => {
    let result = openGroups.value.includes(child.name);

    if ("children" in child) result = isAnyChildOpen(child.children) || result;

    return result;
  });
};

const collapseChildren = (children) => {
  children.forEach((child) => {
    if ("children" in child) collapseChildren(child.children);
    openGroups.value = openGroups.value.filter((group) => group !== child.name);
  });
};

/*Watch for route changes, more specifically route path. Do note that this won't trigger if route's query is updated.

updates isActive & isOpen based on active state of group.
*/
const isNavGroupActive = (children) =>
  children.some((child) => {
    if ("children" in child) return isNavGroupActive(child.children);
    return isNavLinkActive(child);
  });

const isNavLinkActive = (item) => {
  const currentPage = usePage().url.split("?")[0];
  const formattedPage = currentPage.replace(/^\/+/, "");
  const formattedPageParts = formattedPage.split("/");

  const itemLinkParts = item.link ? item.link.split("/") : [];
  const isFirstPartMatch = formattedPageParts[0] === itemLinkParts[0];

  const isRestMatch =
    itemLinkParts.length === 1 ||
    formattedPageParts.slice(1).join("/") === itemLinkParts.slice(1).join("/");

  return isFirstPartMatch && isRestMatch;
};

watch(
  () => usePage().url,
  () => {
    const isActive = isNavGroupActive(props.item.children);

    isGroupOpen.value =
      isActive && !configStore.isVerticalNavMini(isVerticalNavHovered).value;
    isGroupActive.value = isActive;
  },
  { immediate: true }
);

watch(
  isGroupOpen,
  (val) => {
    // Find group index for adding/removing group from openGroups array
    const grpIndex = openGroups.value.indexOf(props.item.name);

    // update openGroups array for addition/removal of current group

    // If group is opened => Add it to `openGroups` array
    if (val && grpIndex === -1) {
      openGroups.value.push(props.item.name);
    } else if (!val && grpIndex !== -1) {
      openGroups.value.splice(grpIndex, 1);
      collapseChildren(props.item.children);
    }
  },
  { immediate: true }
);

/*Watch for openGroups

It will help in making vertical nav adapting the behavior of accordion.
If we open multiple groups without navigating to any route we must close the inactive or temporarily opened groups.

😵‍💫 Gotchas:
* If we open inactive group then it will auto close that group because we close groups based on active state.
Goal of this watcher is auto close groups which are not active when openGroups array is updated.
So, we have to find a way to do not close recently opened inactive group.
For this we will fetch recently added group in openGroups array and won't perform closing operation if recently added group is current group
*/
watch(
  openGroups,
  (val) => {
    // Prevent closing recently opened inactive group.
    const lastOpenedGroup = val.at(-1);
    if (lastOpenedGroup === props.item.name) return;
    const isActive = isNavGroupActive(props.item.children);

    // Goal of this watcher is to close inactive groups. So don't do anything for active groups.
    if (isActive) return;

    // We won't close group if any of child group is open in current group
    if (isAnyChildOpen(props.item.children)) return;
    isGroupOpen.value = isActive;
    isGroupActive.value = isActive;
  },
  { deep: true }
);

// ℹ️ Previously instead of below watcher we were using two individual watcher for `isVerticalNavHovered`, `isVerticalNavCollapsed` & `isLessThanOverlayNavBreakpoint`
watch(configStore.isVerticalNavMini(isVerticalNavHovered), (val) => {
  isGroupOpen.value = val ? false : isGroupActive.value;
});

const isImage = (icon) => {
  return typeof icon === "string" && /\.(png|jpe?g|svg|gif|webp)$/i.test(icon);
};

const isSvg = (icon) => {
  return typeof icon === "string" && icon.endsWith(".svg");
};

const page = usePage();
const baseUrl = computed(() => page.props.base_url);

const getIconUrl = (icon) => {
  // Đảm bảo baseUrl không có trailing slash và icon không có leading slash
  const cleanBaseUrl = baseUrl.value.replace(/\/$/, "");
  const cleanIcon = icon?.replace(/^\//, "") || "";
  return `${cleanBaseUrl}/${cleanIcon}`;
};
</script>

<template>
  <li
    v-if="canViewNavMenuGroup(item)"
    class="nav-group"
    :class="[
      {
        active: isGroupActive,
        open: isGroupOpen,
        disabled: item.disable,
      },
    ]"
  >
    <div class="nav-group-label" @click="isGroupOpen = !isGroupOpen">
      <!-- SVG Icon -->
      <SvgIcon
        v-if="isSvg(item.icon)"
        :src="getIconUrl(item.icon)"
        class="nav-item-icon"
      />

      <!-- Other icons (non-SVG) -->
      <Component
        v-else
        :is="
          isImage(item.icon) ? 'img' : layoutConfig.app.iconRenderer || 'div'
        "
        :src="isImage(item.icon) ? getIconUrl(item.icon) : undefined"
        v-bind="
          !isImage(item.icon)
            ? item.icon || layoutConfig.verticalNav.defaultNavItemIconProps
            : {}
        "
        class="nav-item-icon"
      />

      <Component :is="TransitionGroup" name="transition-slide-x">
        <!-- 👉 Title -->
        <Component
          :is="layoutConfig.app.i18n.enable ? 'i18n-t' : 'span'"
          v-bind="getDynamicI18nProps(item.name, 'span')"
          v-show="!hideTitleAndBadge"
          key="title"
          class="nav-item-title"
        >
          {{ item.name }}
        </Component>

        <Component
          :is="layoutConfig.app.iconRenderer || 'div'"
          v-show="!hideTitleAndBadge"
          v-bind="layoutConfig.icons.chevronRight"
          key="arrow"
          class="nav-group-arrow"
        />
      </Component>
    </div>
    <TransitionExpand>
      <ul v-show="isGroupOpen" class="nav-group-children">
        <Component
          :is="'children' in child ? 'VerticalNavGroup' : VerticalNavLink"
          v-for="child in item.children"
          :key="child.name"
          :item="child"
        />
      </ul>
    </TransitionExpand>
  </li>
</template>

<style lang="scss">
.layout-vertical-nav {
  .nav-group {
    &-label {
      display: flex;
      align-items: center;
      cursor: pointer;
    }

    // SVG Icon styling
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

    // Active state
    &.active .svg-icon-wrapper :deep(.svg-icon) {
      color: rgb(var(--v-theme-primary));
    }
  }
}
</style>
