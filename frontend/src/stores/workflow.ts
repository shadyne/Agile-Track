import { defineStore } from "pinia";
import { ref, computed } from "vue";

export interface WorkflowStatus {
  value: string;
  label: string;
  color: string;
  bgColor: string;
  textColor: string;
  category: string;
  description?: string;
  isDefault?: boolean;
}

const DEFAULT_STATUSES: WorkflowStatus[] = [
  {
    value: "to_do",
    label: "To Do",
    color: "#6B7280",
    bgColor: "#F3F4F615",
    textColor: "#374151",
    category: "To Do",
    description: "Work not yet started",
    isDefault: true,
  },
  {
    value: "in_progress",
    label: "In Progress",
    color: "#2563EB",
    bgColor: "#EFF6FF15",
    textColor: "#1D4ED8",
    category: "In Progress",
    description: "Work actively being done",
    isDefault: true,
  },
  {
    value: "done_by_dev",
    label: "Done by Dev",
    color: "#059669",
    bgColor: "#ECFDF515",
    textColor: "#065F46",
    category: "Done",
    description: "Dev completed, pending QA",
    isDefault: true,
  },
  {
    value: "testing",
    label: "Testing",
    color: "#D97706",
    bgColor: "#FFFBEB15",
    textColor: "#92400E",
    category: "In Progress",
    description: "Under QA testing",
    isDefault: true,
  },
  {
    value: "done_by_qa",
    label: "Done by QA",
    color: "#16A34A",
    bgColor: "#F0FDF415",
    textColor: "#166534",
    category: "Done",
    description: "Fully completed and verified",
    isDefault: true,
  },
];

export const useWorkflowStore = defineStore("workflow", () => {
  const statuses = ref<WorkflowStatus[]>([...DEFAULT_STATUSES]);

  const statusOptions = computed(() =>
    statuses.value.map((s) => ({ label: s.label, value: s.value })),
  );

  const getStatusStyle = (value: string) => {
    const status = statuses.value.find((s) => s.value === value);
    return status
      ? { color: status.textColor, background: status.bgColor }
      : {};
  };

  const getStatusLabel = (value: string) => {
    return statuses.value.find((s) => s.value === value)?.label ?? value;
  };

  const getStatusColor = (value: string) => {
    return statuses.value.find((s) => s.value === value)?.color ?? "#6B7280";
  };

  const updateStatuses = (newStatuses: WorkflowStatus[]) => {
    statuses.value = newStatuses;
  };

  return {
    statuses,
    statusOptions,
    getStatusStyle,
    getStatusLabel,
    getStatusColor,
    updateStatuses,
  };
});
