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
      <button class="topbar-icon-btn">
        <v-icon icon="mdi-cog-outline" size="20" />
      </button>
      <v-avatar size="36" class="avatar-btn">
        <v-icon>mdi-account-circle</v-icon>
      </v-avatar>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref } from "vue";

const searchQuery = ref<string>("");
</script>
