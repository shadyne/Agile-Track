<template>
  <header class="topbar">
    <div class="search-wrap" ref="searchWrapRef">
      <v-text-field
        v-model="searchQuery"
        placeholder="Search by code (e.g. SIM-1)"
        variant="outlined"
        density="compact"
        prepend-inner-icon="mdi-magnify"
        hide-details
        bg-color="field"
        color="primary"
        autocomplete="off"
        @input="onSearchInput"
        @focus="showDropdown = searchResults.length > 0"
        @keydown.escape="closeSearch"
        @keydown.down.prevent="moveDown"
        @keydown.up.prevent="moveUp"
        @keydown.enter.prevent="selectHighlighted"
      />

      <Transition name="search-drop">
        <div
          v-if="showDropdown && (searchResults.length > 0 || searching)"
          class="search-dropdown"
        >
          <div v-if="searching" class="search-loading">
            <v-progress-circular
              indeterminate
              size="16"
              width="2"
              color="primary"
            />
            <span>Mencari…</span>
          </div>

          <template v-else>
            <div
              v-for="(result, idx) in searchResults"
              :key="`${result.type}-${result.id}`"
              class="search-result-item"
              :class="{ 'search-result-active': highlightedIdx === idx }"
              @mouseenter="highlightedIdx = idx"
              @mouseleave="highlightedIdx = -1"
              @click="navigateTo(result)"
            >
              <v-icon
                :icon="getTypeIcon(result.type)"
                size="14"
                :color="getTypeColor(result.type)"
                class="result-type-icon"
              />
              <span class="result-kode">{{ result.kode }}</span>
              <span class="result-judul">{{ result.judul }}</span>
              <span
                class="result-status"
                :style="workflowStore.getStatusStyle(result.status)"
              >
                {{ workflowStore.getStatusLabel(result.status) }}
              </span>
              <span
                class="result-priority-dot"
                :style="{ backgroundColor: getPriorityColor(result.priority) }"
                :title="result.priority"
              />
            </div>

            <div
              v-if="searchResults.length === 0 && searchQuery.length > 0"
              class="search-empty"
            >
              Tidak ada hasil untuk "<strong>{{ searchQuery }}</strong
              >"
            </div>
          </template>
        </div>
      </Transition>
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
import { useWorkflowStore } from "../stores/workflow";
import { useRouter } from "vue-router";
import api from "../api/axios";
import type { AppNotification } from "../stores/notification";

const router = useRouter();
const authStore = useAuthStore();
const notifStore = useNotificationStore();
const workflowStore = useWorkflowStore();

interface SearchResult {
  id: number;
  kode: string;
  judul: string;
  type: string;
  board_id: number;
  status: string;
  priority: string;
}

const searchQuery = ref("");
const searchResults = ref<SearchResult[]>([]);
const searching = ref(false);
const showDropdown = ref(false);
const highlightedIdx = ref(-1);
const searchWrapRef = ref<HTMLElement | null>(null);
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const onSearchInput = () => {
  highlightedIdx.value = -1;
  if (searchTimer) clearTimeout(searchTimer);

  const q = searchQuery.value.trim();
  if (q.length === 0) {
    searchResults.value = [];
    showDropdown.value = false;
    return;
  }

  searching.value = true;
  showDropdown.value = true;

  searchTimer = setTimeout(async () => {
    try {
      const res = await api.get<SearchResult[]>("/search", { params: { q } });
      searchResults.value = res.data;
    } catch {
      searchResults.value = [];
    } finally {
      searching.value = false;
    }
  }, 300);
};

const closeSearch = () => {
  showDropdown.value = false;
  highlightedIdx.value = -1;
};

const moveDown = () => {
  if (highlightedIdx.value < searchResults.value.length - 1) {
    highlightedIdx.value++;
  }
};

const moveUp = () => {
  if (highlightedIdx.value > 0) {
    highlightedIdx.value--;
  }
};

const selectHighlighted = () => {
  if (highlightedIdx.value >= 0 && searchResults.value[highlightedIdx.value]) {
    navigateTo(searchResults.value[highlightedIdx.value]!);
  }
};

const navigateTo = (result: SearchResult) => {
  closeSearch();
  searchQuery.value = "";
  searchResults.value = [];

  if (result.type === "epic") {
    router.push(`/board/${result.board_id}/epic/${result.id}`);
  } else {
    router.push(`/board/${result.board_id}/item/${result.id}`);
  }
};

const handleOutsideClick = (e: MouseEvent) => {
  if (searchWrapRef.value && !searchWrapRef.value.contains(e.target as Node)) {
    closeSearch();
  }
};

const getTypeIcon = (type: string): string => {
  const icons: Record<string, string> = {
    epic: "mdi-lightning-bolt",
    story: "mdi-bookmark-outline",
    task: "mdi-check-circle-outline",
    bug: "mdi-bug-outline",
    qa_task: "mdi-clipboard-check-outline",
  };
  return icons[type] || "mdi-circle-outline";
};

const getTypeColor = (type: string): string => {
  const colors: Record<string, string> = {
    epic: "#7C3AED",
    story: "#059669",
    task: "#1D4ED8",
    bug: "#DC2626",
    qa_task: "#0891B2",
  };
  return colors[type] || "#8290A4";
};

const getPriorityColor = (p: string): string => {
  const map: Record<string, string> = {
    highest: "#DC2626",
    high: "#EA580C",
    medium: "#2563EB",
    low: "#16A34A",
    lowest: "#9CA3AF",
  };
  return map[p] ?? "#9CA3AF";
};

const notifOpen = ref(false);
let pollingTimer: ReturnType<typeof setInterval> | null = null;

onMounted(async () => {
  await notifStore.fetchNotifications();
  pollingTimer = setInterval(() => notifStore.fetchNotifications(), 30_000);
  document.addEventListener("click", handleOutsideClick);
});

onUnmounted(() => {
  if (pollingTimer) clearInterval(pollingTimer);
  if (searchTimer) clearTimeout(searchTimer);
  document.removeEventListener("click", handleOutsideClick);
});

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

const userInitial = computed<string>(
  () => authStore.user?.name?.charAt(0).toUpperCase() || "U",
);
const userName = computed<string>(() => authStore.user?.name || "");
const userEmail = computed<string>(() => authStore.user?.email || "");
</script>

<style scoped>
.search-wrap {
  position: relative;
  max-width: 480px;
  width: 100%;
  justify-self: center;
}

.search-dropdown {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  background: white;
  border: 1px solid rgba(130, 144, 164, 0.25);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(2, 15, 64, 0.14);
  z-index: 1000;
  overflow: hidden;
}

.search-loading {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  font-size: 13px;
  color: #8290a4;
}

.search-result-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  cursor: pointer;
  border-bottom: 1px solid rgba(130, 144, 164, 0.1);
  transition: background 0.1s;
}

.search-result-item:last-child {
  border-bottom: none;
}

.search-result-item:hover,
.search-result-active {
  background: rgba(0, 52, 246, 0.05);
}

.result-type-icon {
  flex-shrink: 0;
}

.result-kode {
  font-size: 12px;
  font-weight: 800;
  color: #65a9ec;
  flex-shrink: 0;
  min-width: 60px;
  font-family: "Barlow", sans-serif;
}

.result-judul {
  font-size: 13px;
  color: #111827;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.result-status {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 4px;
  flex-shrink: 0;
}

.result-priority-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.search-empty {
  padding: 14px 16px;
  font-size: 13px;
  color: #8290a4;
  text-align: center;
}

.search-drop-enter-active,
.search-drop-leave-active {
  transition: all 0.15s ease;
}
.search-drop-enter-from,
.search-drop-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

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
