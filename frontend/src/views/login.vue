<template>
  <div class="login-page">
    <!-- Kiri: Form -->
    <div class="form-side">
      <div class="form-card">
        <div class="brand">
          <span>AGILE</span>
          <v-icon icon="mdi-approximately-equal" size="26" />
          <span>TRACK</span>
        </div>
        <p class="brand-subtitle">Log in to Continue</p>

        <v-form @submit.prevent="handleLogin">
          <label class="input-label">Email*</label>
          <v-text-field
            v-model="form.email"
            type="email"
            placeholder="Email@agiletrack.com"
            variant="outlined"
            density="comfortable"
            :error-messages="errors.email"
            autocomplete="email"
            color="primary"
            class="mb-3"
          />

          <label class="input-label">Password*</label>
          <v-text-field
            v-model="form.password"
            :type="showPass ? 'text' : 'password'"
            placeholder="********"
            variant="outlined"
            density="comfortable"
            :error-messages="errors.password"
            :append-inner-icon="
              showPass ? 'mdi-eye-off-outline' : 'mdi-eye-outline'
            "
            autocomplete="current-password"
            color="primary"
            @click:append-inner="showPass = !showPass"
          />

          <div class="forgot-link">
            <a href="#">Forget password?</a>
          </div>

          <v-checkbox
            v-model="form.remember"
            label="Remember Me"
            style="font-weight: 700"
            color="primary"
            density="compact"
            hide-details
            class="mb-5"
          />

          <v-alert
            v-if="errorLogin"
            type="error"
            variant="tonal"
            density="compact"
            :text="errorLogin"
            class="mb-4"
          />

          <v-btn
            type="submit"
            color="primary"
            variant="flat"
            style="color: white; font-weight: bold"
            block
            size="large"
            :loading="loading"
          >
            Log In
          </v-btn>

          <p class="register-text">
            Don't have an account?
            <RouterLink to="/register">Sign Up</RouterLink>
          </p>
        </v-form>
      </div>
    </div>

    <div class="image-side">
      <img
        v-if="heroImage"
        :src="heroImage"
        alt="Login illustration"
        class="hero-img"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from "vue";
import { useAuthStore } from "../stores/auth";
import type { LoginForm } from "../types";
import "../assets/css/login.css";
import bgImg from "../assets/image/bg.png";
import { RouterLink } from "vue-router";

const heroImage = ref<string>(bgImg);

const authStore = useAuthStore();

const form = reactive<LoginForm>({
  email: "",
  password: "",
  remember: true,
});

const errors = reactive<{ email: string; password: string }>({
  email: "",
  password: "",
});

const showPass = ref<boolean>(false);
const loading = ref<boolean>(false);
const errorLogin = ref<string>("");

const validate = (): boolean => {
  errors.email = "";
  errors.password = "";
  let valid = true;

  if (!form.email) {
    errors.email = "Email wajib diisi";
    valid = false;
  }
  if (!form.password) {
    errors.password = "Password wajib diisi";
    valid = false;
  }
  return valid;
};

const handleLogin = async (): Promise<void> => {
  if (!validate()) return;

  loading.value = true;
  errorLogin.value = "";

  try {
    await authStore.login(form.email, form.password);
  } catch (err: any) {
    errorLogin.value = err.response?.data?.message || "Login gagal, coba lagi";
  } finally {
    loading.value = false;
  }
};
</script>
