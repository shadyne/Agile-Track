<template>
  <header class="topbar">
    <div class="search-wrap">
      <v-text-field
        v-model="searchQuery"
        placeholder="Search"
        variant="outlined"
        density="compact"
        prepend-inner-icon="mdi-magnify"
        hide-details
        bg-color="field"
        color="primary"
      />
    </div>

    <div class="topbar-extra">
      <slot name="actions" />
    </div>

    <div class="topbar-actions">
      <button class="topbar-icon-btn">
        <v-icon icon="mdi-bell-outline" size="20" />
      </button>

      <v-menu location="bottom end" :offset="8">
        <template #activator="{ props: menuProps }">
          <button class="topbar-icon-btn" v-bind="menuProps">
            <v-icon icon="mdi-cog-outline" size="20" />
          </button>
        </template>
        <v-list rounded="lg" elevation="3" min-width="200" density="compact">
          <v-list-subheader
            style="
              font-weight: 700;
              color: #020f40;
              font-size: 11px;
              letter-spacing: 0.5px;
            "
          >
            SETTINGS
          </v-list-subheader>
          <v-divider class="mb-1" />
          <v-list-item
            prepend-icon="mdi-sitemap-outline"
            title="Workflow"
            subtitle="Configure statuses & flow"
            @click="emit('openWorkflowSettings')"
            rounded="lg"
          />
        </v-list>
      </v-menu>

      <v-menu location="bottom end" :offset="8">
        <template #activator="{ props: menuProps }">
          <v-avatar
            size="36"
            class="avatar-btn"
            style="cursor: pointer"
            v-bind="menuProps"
          >
            <span style="color: white; font-size: 14px; font-weight: 700">
              {{ userInitial }}
            </span>
          </v-avatar>
        </template>
        <v-list rounded="lg" elevation="3" min-width="180" density="compact">
          <v-list-item
            :subtitle="userEmail"
            :title="userName"
            style="opacity: 0.8"
          />
          <v-divider class="my-1" />
          <v-list-item
            prepend-icon="mdi-logout"
            title="Logout"
            base-color="cancel"
            @click="handleLogout"
            rounded="lg"
          />
        </v-list>
      </v-menu>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useAuthStore } from "../stores/auth";

const emit = defineEmits<{
  (e: "openWorkflowSettings"): void;
}>();

const authStore = useAuthStore();
const searchQuery = ref<string>("");

const userInitial = computed<string>(
  () => authStore.user?.name?.charAt(0).toUpperCase() || "U",
);
const userName = computed<string>(() => authStore.user?.name || "");
const userEmail = computed<string>(() => authStore.user?.email || "");

const handleLogout = async () => {
  await authStore.logout();
};
</script>
