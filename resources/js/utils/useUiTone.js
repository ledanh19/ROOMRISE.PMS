import { computed } from "vue";
import { useTheme } from "vuetify";

export function useUiTone() {
  const theme = useTheme();

  const isDark = computed(() => theme.global.current.value.dark);
  const tooltipTheme = computed(() => (isDark.value ? "dark" : "light"));
  const titleTone = computed(() => "default");
  const textColor = computed(() => (isDark.value ? "#ffffff" : "#111827"));

  return { isDark, tooltipTheme, titleTone, textColor };
}
