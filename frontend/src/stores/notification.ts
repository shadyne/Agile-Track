import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "../api/axios";

export interface AppNotification {
  id: number;
  type: string;
  title: string;
  body: string;
  data: Record<string, any> | null;
  is_read: boolean;
  created_at: string;
}

export const useNotificationStore = defineStore("notification", () => {
  const notifications = ref<AppNotification[]>([]);
  const unreadCount = ref<number>(0);
  const loading = ref<boolean>(false);

  const hasUnread = computed(() => unreadCount.value > 0);

  const fetchNotifications = async (): Promise<void> => {
    loading.value = true;
    try {
      const res = await api.get<{
        notifications: AppNotification[];
        unread_count: number;
      }>("/notifications");
      notifications.value = res.data.notifications;
      unreadCount.value = res.data.unread_count;
    } catch (err) {
      console.error("Gagal ambil notifikasi:", err);
    } finally {
      loading.value = false;
    }
  };

  const markRead = async (id: number): Promise<void> => {
    try {
      await api.patch(`/notifications/${id}/read`);
      const n = notifications.value.find((n) => n.id === id);
      if (n && !n.is_read) {
        n.is_read = true;
        unreadCount.value = Math.max(0, unreadCount.value - 1);
      }
    } catch (err) {
      console.error("Gagal mark read:", err);
    }
  };

  const markAllRead = async (): Promise<void> => {
    try {
      await api.post("/notifications/read-all");
      notifications.value.forEach((n) => (n.is_read = true));
      unreadCount.value = 0;
    } catch (err) {
      console.error("Gagal mark all read:", err);
    }
  };

  const deleteNotification = async (id: number): Promise<void> => {
    try {
      await api.delete(`/notifications/${id}`);
      const idx = notifications.value.findIndex((n) => n.id === id);
      if (idx !== -1) {
        if (!notifications.value[idx].is_read) {
          unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
        notifications.value.splice(idx, 1);
      }
    } catch (err) {
      console.error("Gagal hapus notifikasi:", err);
    }
  };

  return {
    notifications,
    unreadCount,
    loading,
    hasUnread,
    fetchNotifications,
    markRead,
    markAllRead,
    deleteNotification,
  };
});
