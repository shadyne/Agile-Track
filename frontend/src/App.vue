<script setup lang="ts">
import { onMounted } from "vue";
import { useAuthStore } from "./stores/auth";
import { useWorkflowStore } from "./stores/workflow";

const authStore = useAuthStore();
const workflowStore = useWorkflowStore();

onMounted(() => {
  authStore.fetchUser();
  workflowStore.fetchStatuses();
});
</script>

<template>
  <router-view />
</template>

<style scoped></style>
