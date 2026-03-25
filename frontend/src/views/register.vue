<template>
  <div class="register-page">
    <!-- Kiri: Form -->
    <div class="form-side">
      <div class="form-card">
        <div class="brand">
          <span>AGILE</span>
          <v-icon icon="mdi-approximately-equal" size="26" />
          <span>RACK</span>
        </div>
        <p class="brand-subtitle">Log in to Continue</p>

        <v-form @submit.prevent="handleRegister">
          <label class="input-label">Full Name*</label>
          <v-text-field
            v-model="form.name"
            placeholder="Full Name"
            variant="outlined"
            density="comfortable"
            :error-messages="errors.name"
            autocomplete="name"
            color="primary"
            class="mb-3"
          />
          <label class="input-label">Email*</label>
          <v-text-field
            v-model="form.email"
            placeholder="Email@agiletrack.com"
            type="email"
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
            placeholder="********"
            :type="showPass ? 'text' : 'password'"
            variant="outlined"
            density="comfortable"
            :append-inner-icon="
              showPass ? 'mdi-eye-off-outline' : 'mdi-eye-outline'
            "
            :error-messages="errors.password"
            color="primary"
            @click:append-inner="showPass = !showPass"
            @input="checkStrength"
          />

          <div v-if="form.password" class="mb-3">
            <div class="strength-bars">
              <div
                v-for="i in 4"
                :key="i"
                class="strength-bar"
                :class="getBarClass(i)"
              />
            </div>
            <span class="strength-label" :class="strengthLevel.class">
              {{ strengthLevel.label }}
            </span>
          </div>
          <div v-else class="mb-3" />

          <label class="input-label">Confirm Password*</label>

          <v-text-field
            v-model="form.password_confirmation"
            placeholder="********"
            :type="showConfirmPass ? 'text' : 'password'"
            variant="outlined"
            density="comfortable"
            :append-inner-icon="
              showConfirmPass ? 'mdi-eye-off-outline' : 'mdi-eye-outline'
            "
            :error-messages="errors.password_confirmation"
            color="primary"
            @click:append-inner="showConfirmPass = !showConfirmPass"
          />

          <div
            v-if="form.password_confirmation"
            class="match-info mb-4"
            :class="isPasswordMatch ? 'match' : 'no-match'"
          >
            <v-icon
              :icon="
                isPasswordMatch
                  ? 'mdi-check-circle-outline'
                  : 'mdi-close-circle-outline'
              "
              size="14"
            />
            <span>{{
              isPasswordMatch ? "Password Match" : "Password not match"
            }}</span>
          </div>
          <div v-else class="mb-4" />

          <v-alert
            v-if="errorRegister"
            type="error"
            variant="tonal"
            density="compact"
            :text="errorRegister"
            class="mb-4"
          />

          <v-btn
            type="submit"
            color="primary"
            style="color: white; font-weight: bold"
            block
            size="large"
            :loading="loading"
          >
            Sign In
          </v-btn>

          <p class="login-text">
            Already have an account?
            <RouterLink to="/login">Log In</RouterLink>
          </p>
        </v-form>
      </div>
    </div>

    <div class="image-side">
      <img
        v-if="heroImage"
        :src="heroImage"
        alt="Register illustration"
        class="hero-img"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from "vue";
import { RouterLink } from "vue-router";
import { useAuthStore } from "../stores/auth";
import type { RegisterForm } from "../types";
import "../assets/css/register.css";
import bgImg from "../assets/image/bg.png";

const heroImage = ref<string>(bgImg);
const authStore = useAuthStore();

const form = reactive<RegisterForm>({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

const errors = reactive<Record<keyof RegisterForm, string>>({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

const showPass = ref<boolean>(false);
const showConfirmPass = ref<boolean>(false);
const loading = ref<boolean>(false);
const errorRegister = ref<string>("");
const strengthScore = ref<number>(0);

const checkStrength = (): void => {
  const p = form.password;
  let score = 0;
  if (p.length >= 6) score++;
  if (p.length >= 10) score++;
  if (/[A-Z]/.test(p) && /[0-9]/.test(p)) score++;
  if (/[^A-Za-z0-9]/.test(p)) score++;
  strengthScore.value = score;
};

const strengthLevel = computed(() => {
  if (strengthScore.value <= 1) return { label: "Weak", class: "weak" };
  if (strengthScore.value <= 2) return { label: "Medium", class: "medium" };
  if (strengthScore.value <= 3) return { label: "Strong", class: "strong" };
  return { label: "Very Strong", class: "strong" };
});

const getBarClass = (index: number): string => {
  if (strengthScore.value === 0) return "";
  if (strengthScore.value === 1 && index <= 1) return "weak";
  if (strengthScore.value === 2 && index <= 2) return "medium";
  if (strengthScore.value >= 3 && index <= 4) return "strong";
  return "";
};

const isPasswordMatch = computed<boolean>(() => {
  return form.password !== "" && form.password === form.password_confirmation;
});

const validate = (): boolean => {
  errors.name = "";
  errors.email = "";
  errors.password = "";
  errors.password_confirmation = "";
  let valid = true;

  if (!form.name) {
    errors.name = "Nama wajib diisi";
    valid = false;
  }
  if (!form.email) {
    errors.email = "Email wajib diisi";
    valid = false;
  }
  if (!form.password) {
    errors.password = "Password wajib diisi";
    valid = false;
  }
  if (!form.password_confirmation) {
    errors.password_confirmation = "Konfirmasi password wajib diisi";
    valid = false;
  }
  if (form.password && form.password_confirmation && !isPasswordMatch.value) {
    errors.password_confirmation = "Password tidak cocok";
    valid = false;
  }
  return valid;
};

const handleRegister = async (): Promise<void> => {
  if (!validate()) return;

  loading.value = true;
  errorRegister.value = "";

  try {
    await authStore.register(
      form.name,
      form.email,
      form.password,
      form.password_confirmation,
    );
  } catch (err: any) {
    const serverErrors = err.response?.data?.errors;
    if (serverErrors) {
      if (serverErrors.name) errors.name = serverErrors.name[0];
      if (serverErrors.email) errors.email = serverErrors.email[0];
      if (serverErrors.password) errors.password = serverErrors.password[0];
    } else {
      errorRegister.value =
        err.response?.data?.message || "Register gagal, coba lagi";
    }
  } finally {
    loading.value = false;
  }
};
</script>
