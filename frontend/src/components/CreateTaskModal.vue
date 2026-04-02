<template>
  <v-dialog v-model="isOpen" max-width="560" scrollable>
    <v-card rounded="xl">
      <v-card-title class="modal-header">
        <span class="modal-title">Create Task</span>
      </v-card-title>
      <v-divider />

      <v-card-text class="modal-body">
        <v-form @submit.prevent="handleSubmit">
          <div class="field-wrap">
            <label class="field-label"
              >Task Name <span class="req">*</span></label
            >
            <v-text-field
              v-model="form.judul"
              placeholder="Task name..."
              variant="outlined"
              :error-messages="errors.judul"
            />
          </div>

          <div class="field-wrap">
            <label class="field-label"
              >Type Task <span class="req">*</span></label
            >
            <v-select
              v-model="form.type"
              variant="outlined"
              :items="typeOptions"
              item-title="label"
              item-value="value"
              :error-messages="errors.type"
            >
              <template #item="{ item, props: itemProps }">
                <v-list-item v-bind="itemProps">
                  <template #prepend>
                    <v-icon
                      :icon="getTypeIcon(item.value)"
                      size="16"
                      :color="getTypeColor(item.value)"
                    />
                  </template>
                </v-list-item>
              </template>
              <template #selection="{ item }">
                <div style="display: flex; align-items: center; gap: 8px">
                  <v-icon
                    :icon="getTypeIcon(item.value)"
                    size="16"
                    :color="getTypeColor(item.value)"
                  />
                  {{ item.label }}
                </div>
              </template>
            </v-select>
          </div>

          <div class="field-wrap">
            <label class="field-label"
              >Priority <span class="req">*</span></label
            >
            <v-select
              v-model="form.priority"
              variant="outlined"
              :items="priorityOptions"
              item-title="label"
              item-value="value"
              :error-messages="errors.priority"
            >
              <template #item="{ item, props: itemProps }">
                <v-list-item v-bind="itemProps">
                  <template #prepend>
                    <span
                      class="priority-dot-sm"
                      :style="{ backgroundColor: getPriorityColor(item.value) }"
                    />
                  </template>
                </v-list-item>
              </template>
              <template #selection="{ item }">
                <div style="display: flex; align-items: center; gap: 8px">
                  <span
                    class="priority-dot-sm"
                    :style="{ backgroundColor: getPriorityColor(item.value) }"
                  />
                  {{ item.label }}
                </div>
              </template>
            </v-select>
          </div>

          <div class="field-wrap">
            <label class="field-label">Initial Status</label>
            <v-select
              v-model="form.status"
              variant="outlined"
              :items="workflowStore.statuses"
              item-title="label"
              item-value="value"
              placeholder="Select initial status"
            >
              <template #selection="{ item }">
                <span
                  style="
                    padding: 2px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: 700;
                  "
                  :style="workflowStore.getStatusStyle(item.value)"
                >
                  {{ item.label }}
                </span>
              </template>
              <template #item="{ item, props: itemProps }">
                <v-list-item v-bind="itemProps">
                  <template #prepend>
                    <span
                      style="
                        width: 10px;
                        height: 10px;
                        border-radius: 50%;
                        margin-right: 6px;
                        flex-shrink: 0;
                      "
                      :style="{ backgroundColor: item.raw?.color ?? '#6B7280' }"
                    />
                  </template>
                </v-list-item>
              </template>
            </v-select>
          </div>

          <div class="field-wrap">
            <label class="field-label">Labels</label>
            <v-combobox
              v-model="form.labels"
              variant="outlined"
              :items="availableLabels"
              placeholder="Ketik atau pilih label..."
              multiple
              chips
              closable-chips
              @update:model-value="onLabelsChange"
            >
              <template #chip="{ item, props: chipProps }">
                <v-chip
                  v-bind="chipProps"
                  size="small"
                  color="primary"
                  closable
                >
                  {{ typeof item === "string" ? item : item.value }}
                </v-chip>
              </template>
            </v-combobox>
          </div>

          <div class="field-wrap">
            <label class="field-label">Start Date</label>
            <v-text-field
              v-model="form.start_date"
              variant="outlined"
              type="date"
              prepend-inner-icon="mdi-calendar-outline"
            />
          </div>

          <div class="field-wrap">
            <label class="field-label">Due Date</label>
            <v-text-field
              v-model="form.end_date"
              variant="outlined"
              type="date"
              prepend-inner-icon="mdi-calendar-outline"
              :error-messages="errors.end_date"
            />
          </div>

          <div class="field-wrap">
            <label class="field-label">Description</label>
            <v-textarea
              v-model="form.deskripsi"
              variant="outlined"
              placeholder="Tulis deskripsi task..."
              rows="4"
              no-resize
            />
          </div>

          <div class="field-wrap">
            <label class="field-label">Attachment</label>
            <div
              class="attachment-drop"
              @dragover.prevent
              @drop.prevent="onFileDrop"
              @click="triggerFileInput"
            >
              <v-icon
                icon="mdi-cloud-upload-outline"
                size="20"
                color="#8290A4"
              />
              <span class="attachment-text"
                >Drop File Or
                <span class="attachment-browse">Browse</span></span
              >
              <input
                ref="fileInputRef"
                type="file"
                multiple
                accept="image/*,video/*"
                style="display: none"
                @change="onFileChange"
              />
            </div>
            <div v-if="form.attachments.length > 0" class="attachment-list">
              <div
                v-for="(file, i) in form.attachments"
                :key="i"
                class="attachment-item"
              >
                <v-icon icon="mdi-file-outline" size="14" color="#65A9EC" />
                <span class="attachment-name">{{ file.name }}</span>
                <v-btn
                  icon="mdi-close"
                  variant="text"
                  size="x-small"
                  @click="removeFile(i)"
                />
              </div>
            </div>
          </div>

          <div class="field-wrap">
            <label class="field-label">Reporter</label>
            <div class="reporter-field">
              <v-avatar size="28" color="primary">
                <span style="color: white; font-size: 11px; font-weight: 700">{{
                  reporterInitial
                }}</span>
              </v-avatar>
              <span class="reporter-name">{{ authStore.user?.name }}</span>
            </div>
          </div>

          <div class="field-wrap">
            <label class="field-label"
              >Assignee <span class="req">*</span></label
            >
            <v-select
              v-model="form.assignee_id"
              variant="outlined"
              :items="boardMembers"
              item-title="name"
              item-value="email"
              placeholder="Pilih assignee..."
              :error-messages="errors.assignee_id"
              clearable
              :loading="boardMembers.length === 0"
            >
              <template #item="{ item, props: itemProps }">
                <v-list-item v-bind="itemProps">
                  <template #prepend>
                    <v-avatar size="26" color="primary">
                      <span
                        style="color: white; font-size: 10px; font-weight: 700"
                      >
                        {{ item.name?.charAt(0)?.toUpperCase() }}
                      </span>
                    </v-avatar>
                  </template>
                </v-list-item>
              </template>
              <template #selection="{ item }">
                <div style="display: flex; align-items: center; gap: 8px">
                  <v-avatar size="24" color="primary">
                    <span
                      style="color: white; font-size: 10px; font-weight: 700"
                    >
                      {{ item.name?.charAt(0)?.toUpperCase() }}
                    </span>
                  </v-avatar>
                  <span>{{ item.name }}</span>
                </div>
              </template>
            </v-select>
          </div>
        </v-form>
      </v-card-text>

      <v-divider />
      <v-card-actions class="modal-actions">
        <v-btn
          color="cancel"
          variant="flat"
          class="action-btn"
          style="box-shadow: 0 4px 6px -2px rgba(0, 0, 0, 0.48)"
          @click="handleCancel"
        >
          <span style="color: white; font-weight: 700">Cancel</span>
        </v-btn>
        <v-btn
          color="create"
          variant="flat"
          class="action-btn"
          style="box-shadow: 0 4px 6px -2px rgba(0, 0, 0, 0.48)"
          :loading="loading"
          @click="handleSubmit"
        >
          <span style="color: #020f40; font-weight: 700">Create</span>
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from "vue";
import { useAuthStore } from "../stores/auth";
import { useWorkflowStore } from "../stores/workflow";
import api from "../api/axios";
import type { EpicItem, EpicItemForm } from "../types";

const props = defineProps<{
  modelValue: boolean;
  boardId: number;
  sprintId?: number | null;
  availableLabels: string[];
}>();
const emit = defineEmits<{
  (e: "update:modelValue", val: boolean): void;
  (e: "created", item: EpicItem): void;
}>();

const authStore = useAuthStore();
const workflowStore = useWorkflowStore();
const isOpen = ref(props.modelValue);
const loading = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);
const boardMembers = ref<{ id: number; name: string; email: string }[]>([]);

const form = reactive<EpicItemForm & { status: string }>({
  judul: "",
  type: "task",
  priority: "medium",
  labels: [],
  start_date: "",
  end_date: "",
  deskripsi: "",
  assignee_id: null,
  epic_id: null,
  parent_id: null,
  sprint_id: props.sprintId ?? null,
  original_estimate: "",
  attachments: [],
  status: "to_do",
});

const ambilMembers = async (): Promise<void> => {
  try {
    const res = await api.get(`/boards/${props.boardId}`);
    const members = res.data.space?.member_emails ?? [];
    boardMembers.value = members.map((email: string, i: number) => ({
      id: i + 1,
      name: email.split("@")[0],
      email,
    }));
  } catch (err) {
    console.error("Gagal ambil members:", err);
    boardMembers.value = [];
  }
};

const errors = reactive<Record<string, string>>({
  judul: "",
  type: "",
  priority: "",
  end_date: "",
  assignee_id: "",
});

const typeOptions = [
  { label: "Task", value: "task" },
  { label: "Story", value: "story" },
  { label: "Bug", value: "bug" },
  { label: "QA Task", value: "qa_task" },
];

const priorityOptions = [
  { label: "Highest", value: "highest" },
  { label: "High", value: "high" },
  { label: "Medium", value: "medium" },
  { label: "Low", value: "low" },
  { label: "Lowest", value: "lowest" },
];

const reporterInitial = computed(
  () => authStore.user?.name?.charAt(0).toUpperCase() || "U",
);

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
const getPriorityColor = (priority: string): string => {
  const map: Record<string, string> = {
    highest: "#DC2626",
    high: "#EA580C",
    medium: "#2563EB",
    low: "#16A34A",
    lowest: "#9CA3AF",
  };
  return map[priority] ?? "#9CA3AF";
};

const onLabelsChange = (val: string[]) => {
  if (val.length > 5) form.labels = val.slice(0, 5);
};
const triggerFileInput = () => fileInputRef.value?.click();
const onFileChange = (e: Event) => {
  const input = e.target as HTMLInputElement;
  if (input.files) form.attachments.push(...Array.from(input.files));
};
const onFileDrop = (e: DragEvent) => {
  const files = e.dataTransfer?.files;
  if (files) form.attachments.push(...Array.from(files));
};
const removeFile = (i: number) => form.attachments.splice(i, 1);

const resetForm = () => {
  form.judul = "";
  form.type = "task";
  form.priority = "medium";
  form.labels = [];
  form.start_date = "";
  form.end_date = "";
  form.deskripsi = "";
  form.assignee_id = null;
  form.epic_id = null;
  form.parent_id = null;
  form.sprint_id = props.sprintId ?? null;
  form.original_estimate = "";
  form.attachments = [];
  form.status = workflowStore.statuses[0]?.value ?? "to_do";
  Object.keys(errors).forEach((k) => ((errors as any)[k] = ""));
};

const validate = (): boolean => {
  Object.keys(errors).forEach((k) => ((errors as any)[k] = ""));
  let valid = true;
  if (!form.judul.trim()) {
    errors.judul = "Task name wajib diisi";
    valid = false;
  }
  if (!form.type) {
    errors.type = "Type wajib dipilih";
    valid = false;
  }
  if (!form.priority) {
    errors.priority = "Priority wajib dipilih";
    valid = false;
  }
  if (form.end_date && form.start_date && form.end_date < form.start_date) {
    errors.end_date = "Due date tidak boleh sebelum start date";
    valid = false;
  }
  return valid;
};

const handleSubmit = async () => {
  if (!validate()) return;
  loading.value = true;
  try {
    const formData = new FormData();
    formData.append("judul", form.judul);
    formData.append("type", form.type);
    formData.append("priority", form.priority);
    formData.append("deskripsi", form.deskripsi);
    formData.append("start_date", form.start_date);
    formData.append("end_date", form.end_date);
    formData.append("original_estimate", form.original_estimate);
    formData.append("status", form.status);
    if (form.assignee_id)
      formData.append("assignee_id", String(form.assignee_id));
    if (form.epic_id) formData.append("epic_id", String(form.epic_id));
    if (form.parent_id) formData.append("parent_id", String(form.parent_id));
    if (form.sprint_id) formData.append("sprint_id", String(form.sprint_id));
    form.labels.forEach((lbl, i) => formData.append(`labels[${i}]`, lbl));
    form.attachments.forEach((file, i) =>
      formData.append(`attachments[${i}]`, file),
    );

    const res = await api.post<{ data: EpicItem }>(
      `/boards/${props.boardId}/backlog/items`,
      formData,
      { headers: { "Content-Type": "multipart/form-data" } },
    );
    emit("created", res.data.data);
    isOpen.value = false;
  } catch (err: any) {
    const serverErrors = err.response?.data?.errors;
    if (serverErrors) {
      Object.keys(serverErrors).forEach((k) => {
        if ((errors as any)[k] !== undefined)
          (errors as any)[k] = serverErrors[k][0];
      });
    }
  } finally {
    loading.value = false;
  }
};

const handleCancel = () => {
  resetForm();
  isOpen.value = false;
};

watch(
  () => props.modelValue,
  (val) => {
    isOpen.value = val;
    if (val) {
      ambilMembers();
      resetForm();
      resetForm();
    }
  },
);
watch(isOpen, (val) => emit("update:modelValue", val));
watch(
  () => props.sprintId,
  (val) => {
    form.sprint_id = val ?? null;
  },
);
</script>

<style scoped>
.modal-header {
  padding: 20px 24px 16px;
}
.modal-title {
  font-family: "Barlow", sans-serif;
  font-weight: 800;
  font-size: 20px;
  color: #020f40;
}
.modal-body {
  padding: 16px 24px !important;
  max-height: 65vh;
}
.field-wrap {
  margin-bottom: 8px;
}
.field-label {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: #020f40;
  margin-bottom: 4px;
}
.req {
  color: #dc3434;
}
.priority-dot-sm {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 4px;
  flex-shrink: 0;
}
.attachment-drop {
  border: 1.5px dashed rgba(130, 144, 164, 0.6);
  border-radius: 8px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  transition:
    border-color 0.2s,
    background 0.2s;
}
.attachment-drop:hover {
  border-color: #020f40;
  background: rgba(0, 52, 246, 0.03);
}
.attachment-text {
  font-size: 13px;
  color: #6b7280;
}
.attachment-browse {
  color: #020f40;
  font-weight: 700;
}
.attachment-list {
  margin-top: 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.attachment-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 10px;
  background: #f9fafb;
  border-radius: 6px;
  border: 1px solid rgba(130, 144, 164, 0.2);
}
.attachment-name {
  flex: 1;
  font-size: 12px;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.reporter-field {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border: 1px solid rgba(130, 144, 164, 0.4);
  border-radius: 8px;
  background: #f9fafb;
}
.reporter-name {
  font-size: 14px;
  color: #374151;
  font-weight: 500;
}
.modal-actions {
  padding: 12px 24px 20px;
  gap: 12px;
  justify-content: space-around;
}
.action-btn {
  min-width: 130px;
  min-height: 40px;
  border-radius: 8px;
  font-weight: 600;
}
</style>
