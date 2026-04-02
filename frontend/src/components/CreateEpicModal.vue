<template>
  <v-dialog v-model="isOpen" max-width="560" scrollable>
    <v-card rounded="xl">
      <v-card-title class="modal-header">
        <span class="modal-title">Create Epic</span>
      </v-card-title>

      <v-divider />

      <v-card-text class="modal-body">
        <v-form ref="formRef" @submit.prevent="handleSubmit">
          <div class="field-wrap">
            <label class="field-label">
              Epic Name <span class="req">*</span>
            </label>
            <v-text-field
              v-model="form.judul"
              placeholder="AVIAT MOBILE WEB - Home"
              variant="outlined"
              counter="20"
              maxlength="20"
              :error-messages="errors.judul"
            />
          </div>

          <div class="field-wrap">
            <label class="field-label">
              Priority <span class="req">*</span>
            </label>
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
                      class="priority-dot-select"
                      :style="{ backgroundColor: getPriorityColor(item.value) }"
                    />
                  </template>
                </v-list-item>
              </template>
              <template #selection="{ item }">
                <div class="flex items-center gap-2">
                  <span
                    class="priority-dot-select"
                    :style="{ backgroundColor: getPriorityColor(item.value) }"
                  />
                  {{ item.label }}
                </div>
              </template>
            </v-select>
          </div>

          <div class="field-wrap">
            <label class="field-label">
              Labels <span class="req">*</span>
            </label>
            <v-combobox
              v-model="form.labels"
              variant="outlined"
              :items="availableLabels"
              placeholder="Ketik atau pilih label..."
              multiple
              chips
              closable-chips
              :rules="[(v) => v.length <= 5 || 'Maksimal 5 labels']"
              :error-messages="errors.labels"
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
            <p class="field-hint">{{ form.labels.length }}/5 labels</p>
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
              placeholder="Tulis deskripsi epic..."
              rows="5"
              no-resize
            />
          </div>

          <div class="field-wrap">
            <label class="field-label">Attachment</label>
            <div
              class="attachment-drop"
              variant="outlined"
              @dragover.prevent
              @drop.prevent="onFileDrop"
              @click="triggerFileInput"
            >
              <v-icon
                icon="mdi-cloud-upload-outline"
                size="24"
                color="#8290A4"
              />
              <span class="attachment-text">
                Drop File Or
                <span class="attachment-browse">Browse</span>
              </span>
              <input
                ref="fileInputRef"
                type="file"
                variant="outlined"
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
                <v-icon
                  :icon="
                    file.type.startsWith('video')
                      ? 'mdi-video-outline'
                      : 'mdi-image-outline'
                  "
                  size="16"
                  color="#65A9EC"
                />
                <span class="attachment-name">{{ file.name }}</span>
                <span class="attachment-size">{{ formatSize(file.size) }}</span>
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
                <span style="color: white; font-size: 11px; font-weight: 700">
                  {{ reporterInitial }}
                </span>
              </v-avatar>
              <span class="reporter-name">{{ authStore.user?.name }}</span>
            </div>
          </div>

          <div class="field-wrap">
            <label class="field-label">
              Assignee <span class="req">*</span>
            </label>
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
                    <v-avatar size="28" color="primary">
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
                <div class="flex items-center gap-2">
                  <v-avatar size="24" color="primary">
                    <span
                      style="color: white; font-size: 10px; font-weight: 700"
                    >
                      {{ item.name?.charAt(0)?.toUpperCase() || "?" }}
                    </span>
                  </v-avatar>
                  <span>{{ item.name || "-" }}</span>
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
          Cancel
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
import { ref, reactive, computed, watch, onMounted } from "vue";
import { useAuthStore } from "../stores/auth";
import api from "../api/axios";
import type { Epic, EpicForm } from "../types";

const props = defineProps<{
  modelValue: boolean;
  boardId: number;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", val: boolean): void;
  (e: "created", epic: Epic): void;
}>();

const authStore = useAuthStore();
const isOpen = ref<boolean>(props.modelValue);
const loading = ref<boolean>(false);
const fileInputRef = ref<HTMLInputElement | null>(null);
const availableLabels = ref<string[]>([]);
const boardMembers = ref<{ id: number; name: string; email: string }[]>([]);

const form = reactive<EpicForm>({
  judul: "",
  priority: "medium",
  labels: [],
  start_date: "",
  end_date: "",
  deskripsi: "",
  assignee_id: null,
  attachments: [],
});

const errors = reactive<{
  judul: string;
  priority: string;
  labels: string;
  end_date: string;
  assignee_id: string;
}>({
  judul: "",
  priority: "",
  labels: "",
  end_date: "",
  assignee_id: "",
});

const priorityOptions = [
  { label: "Highest", value: "highest" },
  { label: "High", value: "high" },
  { label: "Medium", value: "medium" },
  { label: "Low", value: "low" },
  { label: "Lowest", value: "lowest" },
];

const reporterInitial = computed<string>(
  () => authStore.user?.name?.charAt(0).toUpperCase() || "U",
);
console.log(authStore.user?.name);

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

const onLabelsChange = (val: string[]): void => {
  if (val.length > 5) {
    form.labels = val.slice(0, 5);
    errors.labels = "Maksimal 5 labels";
  } else {
    errors.labels = "";
  }
};

const triggerFileInput = (): void => {
  fileInputRef.value?.click();
};

const onFileChange = (e: Event): void => {
  const input = e.target as HTMLInputElement;
  if (input.files) {
    form.attachments.push(...Array.from(input.files));
  }
};

const onFileDrop = (e: DragEvent): void => {
  const files = e.dataTransfer?.files;
  if (files) {
    form.attachments.push(...Array.from(files));
  }
};

const removeFile = (index: number): void => {
  form.attachments.splice(index, 1);
};

const formatSize = (bytes: number): string => {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const ambilLabels = async (): Promise<void> => {
  try {
    const res = await api.get<string[]>(
      `/boards/${props.boardId}/epics/labels`,
    );
    availableLabels.value = res.data;
  } catch (err) {
    console.error("Gagal ambil labels:", err);
  }
};

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

watch(
  () => props.modelValue,
  (val) => {
    isOpen.value = val;
    if (val) {
      resetForm();
      ambilLabels();
      ambilMembers();
    }
  },
);
watch(isOpen, (val) => emit("update:modelValue", val));

watch(
  () => authStore.user,
  (user) => {
    if (user) {
      console.log(user.name);
    }
  },
  { immediate: true },
);
const resetForm = (): void => {
  form.judul = "";
  form.priority = "medium";
  form.labels = [];
  form.start_date = "";
  form.end_date = "";
  form.deskripsi = "";
  form.assignee_id = null;
  form.attachments = [];
  Object.keys(errors).forEach((k) => ((errors as any)[k] = ""));
};

const validate = (): boolean => {
  Object.keys(errors).forEach((k) => ((errors as any)[k] = ""));
  let valid = true;

  if (!form.judul.trim()) {
    errors.judul = "Epic name wajib diisi";
    valid = false;
  } else if (form.judul.length > 20) {
    errors.judul = "Epic name maksimal 20 karakter";
    valid = false;
  }
  if (!form.priority) {
    errors.priority = "Priority wajib dipilih";
    valid = false;
  }
  if (form.labels.length > 5) {
    errors.labels = "Maksimal 5 labels";
    valid = false;
  }
  if (form.end_date && form.start_date && form.end_date < form.start_date) {
    errors.end_date = "Due date tidak boleh sebelum start date";
    valid = false;
  }

  return valid;
};

const handleSubmit = async (): Promise<void> => {
  if (!validate()) return;

  loading.value = true;
  try {
    const formData = new FormData();
    formData.append("judul", form.judul);
    formData.append("priority", form.priority);
    formData.append("deskripsi", form.deskripsi);
    formData.append("start_date", form.start_date);
    formData.append("end_date", form.end_date);

    if (form.assignee_id) {
      formData.append("assignee_id", String(form.assignee_id));
    }

    form.labels.forEach((label, i) => {
      formData.append(`labels[${i}]`, label);
    });

    form.attachments.forEach((file, i) => {
      formData.append(`attachments[${i}]`, file);
    });

    const res = await api.post<{ data: Epic }>(
      `/boards/${props.boardId}/epics`,
      formData,
      { headers: { "Content-Type": "multipart/form-data" } },
    );

    emit("created", res.data.data);
    isOpen.value = false;
  } catch (err: any) {
    const serverErrors = err.response?.data?.errors;
    if (serverErrors) {
      if (serverErrors.judul) errors.judul = serverErrors.judul[0];
      if (serverErrors.priority) errors.priority = serverErrors.priority[0];
      if (serverErrors.labels) errors.labels = serverErrors.labels[0];
      if (serverErrors.end_date) errors.end_date = serverErrors.end_date[0];
      if (serverErrors.assignee_id)
        errors.assignee_id = serverErrors.assignee_id[0];
    }
  } finally {
    loading.value = false;
  }
};

const handleCancel = (): void => {
  resetForm();
  isOpen.value = false;
};
</script>

<style scoped>
.modal-header {
  padding: 24px 24px 16px;
}

.modal-title {
  font-family: "Barlow", sans-serif;
  font-weight: 800;
  font-size: 22px;
  color: #020f40;
}

.modal-body {
  padding: 16px 24px !important;
  max-height: 70vh;
}

.field-wrap {
  margin-bottom: 8px;
}

.field-label {
  display: block;
  font-size: 13px;
  font-weight: 800;
  color: #020f40;
  margin-bottom: 4px;
}

.req {
  color: #020f40;
  font-weight: 800;
}

.field-hint {
  font-size: 11px;
  color: #8290a4;
  margin-top: -4px;
  margin-bottom: 4px;
}

.attachment-drop {
  border: 1.5px dashed rgba(130, 144, 164, 0.6);
  border-radius: 8px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
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
  cursor: pointer;
}

.attachment-list {
  margin-top: 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.attachment-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
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

.attachment-size {
  font-size: 11px;
  color: #8290a4;
  flex-shrink: 0;
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

.priority-dot-select {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 4px;
  flex-shrink: 0;
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
  color: white !important;
}
</style>
