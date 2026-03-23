<template>
  <div class="success-page">
    <!-- Kiri: Success Card -->
    <div class="success-side">
      <div class="success-card">
        <div class="success-icon-wrap">
          <v-icon icon="mdi-check-decagram" color="#22c55e" size="80" />
        </div>

        <h2 class="success-title">Successful!</h2>

        <p class="success-desc">
          Your Account is created successfully<br />and ready now.
        </p>

        <v-btn
          color="primary"
          style="color: white; font-weight: bold"
          size="large"
          block
          @click="goToLogin"
        >
          Log in
        </v-btn>
      </div>
    </div>

    <div class="image-side">
      <img
        v-if="heroImage"
        :src="heroImage"
        alt="Success illustration"
        class="hero-img"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import "../assets/css/register-success.css";
import bgImg from "../assets/image/bg.png";

const router = useRouter();
const heroImage = ref<string>(bgImg);

const goToLogin = (): void => {
  router.push("/login");
};
</script>
