import { defineStore } from "pinia";
import { ref } from "vue";
import api from "../api/axios";
import router from "../router";
import type { User, AuthResponse } from "../types";

export const useAuthStore = defineStore("auth", () => {
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem("token"));

  const login = async (email: string, password: string): Promise<void> => {
    const res = await api.post<AuthResponse>("/login", { email, password });

    token.value = res.data.token;
    user.value = res.data.user;

    localStorage.setItem("token", res.data.token);
    router.push("/");
  };

  const register = async (
    name: string,
    email: string,
    password: string,
    password_confirmation: string,
  ): Promise<void> => {
    const res = await api.post<AuthResponse>("/register", {
      name,
      email,
      password,
      password_confirmation,
    });
    router.push("/register/success");
  };

  const logout = async (): Promise<void> => {
    await api.post("/logout");
    token.value = null;
    user.value = null;
    localStorage.removeItem("token");
    router.push("/login");
  };

  const isLoggedIn = (): boolean => !!token.value;

  return { user, token, login, register, logout, isLoggedIn };
});
