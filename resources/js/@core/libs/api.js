import { useAuthStore } from "@/views/pages/authentication/useAuthStore";
import axios from "axios";

export const nestApi = axios.create({
  baseURL: import.meta.env.VITE_NEST_URL || "http://localhost:3000",
  withCredentials: false,
});

nestApi.interceptors.request.use((config) => {
  try {
    const auth = useAuthStore();
    if (auth?.token) config.headers.Authorization = `Bearer ${auth.token}`;
  } catch {}
  return config;
});
