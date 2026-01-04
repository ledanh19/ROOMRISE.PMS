import { apiGet } from "@/utils/api";
import { defineStore } from "pinia";

export const useCustomerStore = defineStore("customer", {
  state: () => ({
    byId: {},
    currentId: null,
    loading: false,
    error: null,
    lastMessage: null,
    lastStatusCode: null,
  }),

  getters: {
    currentCustomer(state) {
      return state.currentId != null
        ? state.byId[state.currentId] || null
        : null;
    },
  },

  actions: {
    resetError() {
      this.error = null;
    },

    setCurrent(id) {
      this.currentId = id;
    },

    /**
     *
     * @param {number|string} id
     */
    async fetchDetail(id) {
      if (id === undefined || id === null) return null;

      this.loading = true;
      this.error = null;

      try {
        const res = await apiGet(
          `/api/admin/customers/detail/${encodeURIComponent(id)}`
        );

        this.lastMessage = res && res.message != null ? res.message : null;
        this.lastStatusCode =
          res && res.statusCode != null ? res.statusCode : null;

        const customer = res && res.data != null ? res.data : null;

        this.byId[id] = customer;
        this.currentId = id;

        return customer;
      } catch (e) {
        this.error = {
          status: e && e.status,
          message: (e && e.message) || "Request error",
          data: e && e.data,
        };
        throw e;
      } finally {
        this.loading = false;
      }
    },
  },
});
