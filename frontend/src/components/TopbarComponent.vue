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
      <v-menu
        v-model="notifOpen"
        location="bottom end"
        :offset="8"
        :close-on-content-click="false"
        width="380"
      >
        <template #activator="{ props: menuProps }">
          <button class="topbar-icon-btn notif-btn" v-bind="menuProps">
            <v-badge
              v-if="notifStore.unreadCount > 0"
              :content="
                notifStore.unreadCount > 99 ? '99+' : notifStore.unreadCount
              "
              color="error"
              floating
            >
              <v-icon icon="mdi-bell-outline" size="20" />
            </v-badge>
            <v-icon v-else icon="mdi-bell-outline" size="20" />
          </button>
        </template>

        <v-card rounded="xl" elevation="6" class="notif-card">
          <div class="notif-header">
            <span class="notif-title">Notifications</span>
            <button
              v-if="notifStore.unreadCount > 0"
              class="notif-mark-all"
              @click="notifStore.markAllRead()"
            >
              Mark all as read
            </button>
          </div>

          <v-divider />

          <div v-if="notifStore.loading" class="notif-loading">
            <v-progress-circular indeterminate size="24" color="primary" />
          </div>

          <div
            v-else-if="notifStore.notifications.length === 0"
            class="notif-empty"
          >
            <v-icon icon="mdi-bell-sleep-outline" size="40" color="#8290A4" />
            <p>No notifications yet</p>
          </div>

          <div v-else class="notif-list">
            <div
              v-for="n in notifStore.notifications"
              :key="n.id"
              class="notif-item"
              :class="{ 'notif-unread': !n.is_read }"
              @click="handleNotifClick(n)"
            >
              <div class="notif-icon-wrap">
                <v-icon
                  :icon="getNotifIcon(n.type)"
                  size="20"
                  :color="getNotifColor(n.type)"
                />
              </div>

              <div class="notif-content">
                <p class="notif-item-title">{{ n.title }}</p>
                <p class="notif-item-body">{{ n.body }}</p>
                <p class="notif-item-time">{{ n.created_at }}</p>
              </div>

              <div v-if="!n.is_read" class="notif-dot" />

              <button
                class="notif-delete-btn"
                @click.stop="notifStore.deleteNotification(n.id)"
                title="Dismiss"
              >
                <v-icon icon="mdi-close" size="14" />
              </button>
            </div>
          </div>
        </v-card>
      </v-menu>

      <v-menu location="bottom end" :offset="8">
        <template #activator="{ props: menuProps }">
          <button class="topbar-icon-btn" v-bind="menuProps">
            <v-icon icon="mdi-cog-outline" size="20" />
          </button>
        </template>
        <v-list rounded="lg" elevation="3" min-width="220" density="compact">
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
            rounded="lg"
            @click="openWorkflowSettings"
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
            rounded="lg"
            @click="handleLogout"
          />
        </v-list>
      </v-menu>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useAuthStore } from "../stores/auth";
import { useNotificationStore } from "../stores/notification";
import { useRouter } from "vue-router";
import type { AppNotification } from "../stores/notification";

const router = useRouter();
const authStore = useAuthStore();
const notifStore = useNotificationStore();

const searchQuery = ref<string>("");
const notifOpen = ref<boolean>(false);

const userInitial = computed<string>(
  () => authStore.user?.name?.charAt(0).toUpperCase() || "U",
);
const userName = computed<string>(() => authStore.user?.name || "");
const userEmail = computed<string>(() => authStore.user?.email || "");

let pollingTimer: ReturnType<typeof setInterval> | null = null;

onMounted(async () => {
  await notifStore.fetchNotifications();
  pollingTimer = setInterval(() => notifStore.fetchNotifications(), 30_000);
});

onUnmounted(() => {
  if (pollingTimer) clearInterval(pollingTimer);
});

const onNotifMenuOpen = async (val: boolean) => {
  if (val) await notifStore.fetchNotifications();
};

const handleNotifClick = async (n: AppNotification) => {
  if (!n.is_read) await notifStore.markRead(n.id);
  notifOpen.value = false;
};

const openWorkflowSettings = () => {
  router.push("/workflow-settings");
};

const handleLogout = async () => {
  await authStore.logout();
};

const getNotifIcon = (type: string): string => {
  const map: Record<string, string> = {
    space_member_added: "mdi-account-plus-outline",
    board_member_added: "mdi-view-dashboard-outline",
    task_assigned: "mdi-clipboard-account-outline",
    mention: "mdi-at",
  };
  return map[type] ?? "mdi-bell-outline";
};

const getNotifColor = (type: string): string => {
  const map: Record<string, string> = {
    space_member_added: "#2563EB",
    board_member_added: "#7C3AED",
    task_assigned: "#059669",
    mention: "#D97706",
  };
  return map[type] ?? "#8290A4";
};
</script>

<style scoped>
.notif-btn {
  position: relative;
}

.notif-card {
  overflow: hidden;
  max-height: 480px;
  display: flex;
  flex-direction: column;
}

.notif-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 18px 12px;
}

.notif-title {
  font-family: "Barlow", sans-serif;
  font-weight: 800;
  font-size: 16px;
  color: #020f40;
}

.notif-mark-all {
  font-size: 12px;
  font-weight: 700;
  color: #65a9ec;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
}

.notif-mark-all:hover {
  text-decoration: underline;
}

.notif-loading,
.notif-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 36px 0;
  color: #8290a4;
  font-size: 13px;
}

.notif-list {
  overflow-y: auto;
  max-height: 380px;
}

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(130, 144, 164, 0.12);
  cursor: pointer;
  position: relative;
  transition: background 0.15s;
}

.notif-item:last-child {
  border-bottom: none;
}

.notif-item:hover {
  background: #f9fafb;
}

.notif-item.notif-unread {
  background: rgba(0, 52, 246, 0.04);
}

.notif-item.notif-unread:hover {
  background: rgba(0, 52, 246, 0.08);
}

.notif-icon-wrap {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(0, 52, 246, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 1px;
}

.notif-content {
  flex: 1;
  min-width: 0;
}

.notif-item-title {
  font-size: 13px;
  font-weight: 700;
  color: #020f40;
  margin-bottom: 2px;
}

.notif-item-body {
  font-size: 12px;
  color: #374151;
  line-height: 1.5;
  white-space: normal;
  word-break: break-word;
  margin-bottom: 4px;
}

.notif-item-time {
  font-size: 11px;
  color: #8290a4;
}

.notif-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #2563eb;
  flex-shrink: 0;
  margin-top: 4px;
}

.notif-delete-btn {
  flex-shrink: 0;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: none;
  background: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #8290a4;
  opacity: 0;
  transition:
    opacity 0.15s,
    background 0.15s;
}

.notif-item:hover .notif-delete-btn {
  opacity: 1;
}

.notif-delete-btn:hover {
  background: #fee2e2;
  color: #dc2626;
}
</style>
