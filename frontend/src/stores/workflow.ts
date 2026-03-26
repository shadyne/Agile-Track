import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "../api/axios";

export interface WorkflowStatus {
  id?: number;
  value: string;
  label: string;
  color: string;
  text_color: string;
  bg_color: string;
  category: string;
  description?: string;
  sort_order?: number;
  is_default?: boolean;
}

const FALLBACK_STATUSES: WorkflowStatus[] = [
  {
    value: "to_do",
    label: "To Do",
    color: "#6B7280",
    text_color: "#374151",
    bg_color: "#F3F4F6",
    category: "To Do",
    is_default: true,
  },
  {
    value: "in_progress",
    label: "In Progress",
    color: "#2563EB",
    text_color: "#1D4ED8",
    bg_color: "#DBEAFE",
    category: "In Progress",
    is_default: true,
  },
  {
    value: "done_by_dev",
    label: "Done by Dev",
    color: "#059669",
    text_color: "#065F46",
    bg_color: "#D1FAE5",
    category: "Done",
    is_default: true,
  },
  {
    value: "testing",
    label: "Testing",
    color: "#D97706",
    text_color: "#92400E",
    bg_color: "#FEF3C7",
    category: "In Progress",
    is_default: true,
  },
  {
    value: "done_by_qa",
    label: "Done by QA",
    color: "#16A34A",
    text_color: "#166534",
    bg_color: "#DCFCE7",
    category: "Done",
    is_default: true,
  },
];

export const useWorkflowStore = defineStore("workflow", () => {
  const statuses = ref<WorkflowStatus[]>([...FALLBACK_STATUSES]);
  const loading = ref(false);
  const loaded = ref(false);

  const fetchStatuses = async (): Promise<void> => {
    if (loaded.value) return;
    loading.value = true;
    try {
      const res = await api.get<WorkflowStatus[]>("/workflow/statuses");
      if (res.data && res.data.length > 0) {
        statuses.value = res.data;
      }
      loaded.value = true;
    } catch (err) {
      console.warn("Workflow: using fallback statuses", err);
      statuses.value = [...FALLBACK_STATUSES];
      loaded.value = true;
    } finally {
      loading.value = false;
    }
  };

  const refetch = async (): Promise<void> => {
    loaded.value = false;
    await fetchStatuses();
  };

  const createStatus = async (
    payload: Omit<WorkflowStatus, "id" | "value" | "is_default">,
  ): Promise<WorkflowStatus> => {
    const res = await api.post<{ data: WorkflowStatus }>(
      "/workflow/statuses",
      payload,
    );
    statuses.value.push(res.data.data);
    return res.data.data;
  };

  const updateStatus = async (
    id: number,
    payload: Partial<WorkflowStatus>,
  ): Promise<void> => {
    const res = await api.put<{ data: WorkflowStatus }>(
      `/workflow/statuses/${id}`,
      payload,
    );
    const idx = statuses.value.findIndex((s) => s.id === id);
    if (idx !== -1) statuses.value[idx] = res.data.data;
  };

  const deleteStatus = async (id: number): Promise<void> => {
    await api.delete(`/workflow/statuses/${id}`);
    statuses.value = statuses.value.filter((s) => s.id !== id);
  };

  const reorderStatuses = async (orderedIds: number[]): Promise<void> => {
    const res = await api.post<{ statuses: WorkflowStatus[] }>(
      "/workflow/statuses/reorder",
      { order: orderedIds },
    );
    statuses.value = res.data.statuses;
  };

  const statusOptions = computed(() =>
    statuses.value.map((s) => ({ label: s.label, value: s.value })),
  );

  const getStatusLabel = (value: string): string =>
    statuses.value.find((s) => s.value === value)?.label ?? value;

  const getStatusColor = (value: string): string =>
    statuses.value.find((s) => s.value === value)?.color ?? "#6B7280";

  const getStatusTextColor = (value: string): string =>
    statuses.value.find((s) => s.value === value)?.text_color ?? "#374151";

  const getStatusBgColor = (value: string): string =>
    statuses.value.find((s) => s.value === value)?.bg_color ?? "#F3F4F6";

  const getStatusStyle = (value: string): Record<string, string> => {
    const status = statuses.value.find((s) => s.value === value);
    return status
      ? { color: status.text_color, backgroundColor: status.bg_color }
      : {};
  };

  const getStatusItems = computed(() =>
    statuses.value.map((s) => ({
      title: s.label,
      value: s.value,
    })),
  );

  return {
    statuses,
    loading,
    loaded,
    fetchStatuses,
    refetch,
    createStatus,
    updateStatus,
    deleteStatus,
    reorderStatuses,
    statusOptions,
    getStatusItems,
    getStatusLabel,
    getStatusColor,
    getStatusTextColor,
    getStatusBgColor,
    getStatusStyle,
  };
});
