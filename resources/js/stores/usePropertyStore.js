import { defineStore } from "pinia";
import { ref, watch } from "vue";

export const usePropertyStore = defineStore("property", () => {
  const saved = localStorage.getItem("selectedProperty");
  const selectedProperty = ref(saved ? JSON.parse(saved) : null);
  const properties = ref([]);

  function setProperty(value) {
    selectedProperty.value = value;
  }

  function setProperties(list) {
    properties.value = list;
  }

  watch(
    selectedProperty,
    (val) => {
      localStorage.setItem("selectedProperty", JSON.stringify(val));
    },
    { deep: true }
  );

  watch(
    properties,
    (newList) => {
      if (
        selectedProperty.value &&
        !newList.some((item) => item.value === selectedProperty.value)
      ) {
        selectedProperty.value = null;
      }
    },
    { deep: true }
  );

  return { selectedProperty, properties, setProperty, setProperties };
});
