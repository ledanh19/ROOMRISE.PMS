import { defineStore } from "pinia";

export const useFilterDashboardStore = defineStore("filterDashboard", {
  state: () => ({
    dateFrom: null,
    dateTo: null,
    type: null,
    quickdate: null,
  }),

  actions: {
    setFilter({ dateFrom, dateTo, type, quickdate }) {
      this.dateFrom = dateFrom;
      this.dateTo = dateTo;
      this.type = type;
      this.quickdate = quickdate;
    },
  },
});
