import { defineStore } from "pinia";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    token: localStorage.getItem("nest_token") || "",
    user: null,
  }),
  actions: {
    setToken(t) {
      this.token = t || "";
      if (t) localStorage.setItem("nest_token", t);
      else localStorage.removeItem("nest_token");
    },
    setUser(u) {
      this.user = u || null;
    },
    logout() {
      this.setToken("");
      localStorage.removeItem("app_device_id");
      this.user = null;
    },
  },
});
