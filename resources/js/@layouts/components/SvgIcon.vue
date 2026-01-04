<script>
// Cache ở module scope - sẽ persist giữa các navigation
const svgCache = new Map();
</script>

<script setup>
import { onMounted, ref, watch } from "vue";

const props = defineProps({
  src: {
    type: String,
    required: true,
  },
});

const svgContent = ref("");
const isLoading = ref(true);
const error = ref(false);

const loadSvg = async () => {
  try {
    isLoading.value = true;
    error.value = false;

    // Check cache first
    if (svgCache.has(props.src)) {
      svgContent.value = svgCache.get(props.src);
      isLoading.value = false;
      return;
    }

    const response = await fetch(props.src);
    if (!response.ok) {
      throw new Error(`Failed to load SVG: ${response.status}`);
    }

    const svgText = await response.text();

    // Parse SVG và xử lý
    const parser = new DOMParser();
    const svgDoc = parser.parseFromString(svgText, "image/svg+xml");
    const svgElement = svgDoc.querySelector("svg");

    if (svgElement) {
      // Thêm class để có thể style với CSS
      svgElement.classList.add("svg-icon");

      // Đảm bảo SVG có kích thước phù hợp
      if (!svgElement.hasAttribute("width")) {
        svgElement.setAttribute("width", "24");
      }
      if (!svgElement.hasAttribute("height")) {
        svgElement.setAttribute("height", "24");
      }

      // Đảm bảo stroke/fill sử dụng currentColor nếu chưa có
      const paths = svgElement.querySelectorAll(
        "path, circle, rect, line, polyline, polygon"
      );
      paths.forEach((path) => {
        if (
          path.hasAttribute("stroke") &&
          path.getAttribute("stroke") !== "none"
        ) {
          if (!path.getAttribute("stroke").includes("currentColor")) {
            // Giữ nguyên nếu đã có màu cụ thể, hoặc set currentColor
            if (
              path.getAttribute("stroke") === "currentColor" ||
              !path.hasAttribute("stroke")
            ) {
              path.setAttribute("stroke", "currentColor");
            }
          }
        }
        if (path.hasAttribute("fill") && path.getAttribute("fill") !== "none") {
          if (!path.getAttribute("fill").includes("currentColor")) {
            // Chỉ set currentColor nếu fill không phải là none
            if (
              path.getAttribute("fill") !== "none" &&
              path.getAttribute("fill") !== ""
            ) {
              // path.setAttribute('fill', 'currentColor')
            }
          }
        }
      });

      const result = svgElement.outerHTML;
      svgContent.value = result;

      // Cache the result
      svgCache.set(props.src, result);
    } else {
      throw new Error("Invalid SVG content");
    }
  } catch (err) {
    console.error("Error loading SVG:", err);
    error.value = true;
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  loadSvg();
});

// Watch src changes
watch(
  () => props.src,
  () => {
    loadSvg();
  }
);
</script>

<template>
  <div v-if="isLoading" class="nav-item-icon svg-icon-wrapper">
    <!-- Loading placeholder -->
  </div>
  <div v-else-if="error" class="nav-item-icon svg-icon-wrapper svg-icon-error">
    <!-- Error fallback - show empty icon -->
  </div>
  <div v-else v-html="svgContent" class="svg-icon-wrapper" />
</template>

<style scoped>
.svg-icon-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 0;
}

.svg-icon-wrapper :deep(.svg-icon) {
  width: 1.5rem;
  height: 1.5rem;
  color: currentColor;
  transition: color 0.2s ease;
}

.svg-icon-error {
  opacity: 0.3;
}
</style>
