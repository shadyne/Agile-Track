<template>
  <div class="workflow-page">
    <div class="workflow-header">
      <div class="workflow-header-left">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
          <rect
            x="2"
            y="3"
            width="20"
            height="4"
            rx="1"
            fill="#172B4D"
            opacity=".15"
          />
          <rect
            x="2"
            y="3"
            width="20"
            height="4"
            rx="1"
            stroke="#172B4D"
            stroke-width="1.5"
          />
          <path
            d="M6 7v10M18 7v10"
            stroke="#172B4D"
            stroke-width="1.5"
            stroke-linecap="round"
          />
          <path
            d="M6 17h12"
            stroke="#172B4D"
            stroke-width="1.5"
            stroke-linecap="round"
          />
          <circle cx="6" cy="12" r="1.5" fill="#172B4D" />
          <circle cx="12" cy="12" r="1.5" fill="#172B4D" />
          <circle cx="18" cy="12" r="1.5" fill="#172B4D" />
        </svg>
        <div>
          <h1 class="workflow-title">DEV Workflow – With QA</h1>
          <p class="workflow-subtitle">Manages all boards in your project</p>
        </div>
      </div>
      <div class="workflow-header-right">
        <button class="btn-update" @click="handleSave" :disabled="saving">
          <v-progress-circular
            v-if="saving"
            size="14"
            width="2"
            indeterminate
            color="white"
            class="mr-1"
          />
          {{ saving ? "Saving…" : "Save workflow" }}
        </button>
        <button class="btn-close" @click="$router.back()">Close</button>
      </div>
    </div>

    <div class="workflow-actions-bar">
      <button class="action-pill" @click="openAddModal">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
          <path
            d="M12 4V20M4 12H20"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
          />
        </svg>
        Add status
      </button>
      <div class="action-pill disabled">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
          <path
            d="M17 3L21 7L7 21H3V17L17 3Z"
            stroke="currentColor"
            stroke-width="1.5"
            fill="none"
          />
        </svg>
        Edit transitions
      </div>
      <div class="action-pill-hint">Drag cards to reorder</div>
    </div>

    <!-- Loading State -->
    <div v-if="workflowStore.loading" class="loading-state">
      <v-progress-circular indeterminate color="#0052CC" />
      <span>Loading statuses…</span>
    </div>

    <div v-else class="workflow-body">
      <div class="flow-panel">
        <div class="flow-scroll">
          <div class="flow-start">
            <div class="start-circle">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="white">
                <circle cx="12" cy="12" r="5" />
              </svg>
              START
            </div>
            <div class="connector-v"></div>
          </div>

          <div class="status-row" @dragover.prevent>
            <template
              v-for="(status, idx) in workflowStore.statuses"
              :key="status.id ?? status.value"
            >
              <div
                class="status-card"
                :class="{
                  dragging: dragIndex === idx,
                  'drag-over': dropIndex === idx && dragIndex !== idx,
                }"
                draggable="true"
                @dragstart="onDragStart(idx)"
                @dragover.prevent="onDragOver(idx)"
                @drop="onDrop(idx)"
                @dragend="onDragEnd"
                @click="openEditModal(status)"
              >
                <div
                  class="card-top-badge"
                  :style="{ backgroundColor: status.color }"
                >
                  {{ status.category }}
                </div>
                <div class="card-handle">
                  <svg width="10" height="16" viewBox="0 0 10 16" fill="none">
                    <circle cx="3" cy="3" r="1.5" fill="#BCC0C9" />
                    <circle cx="7" cy="3" r="1.5" fill="#BCC0C9" />
                    <circle cx="3" cy="8" r="1.5" fill="#BCC0C9" />
                    <circle cx="7" cy="8" r="1.5" fill="#BCC0C9" />
                    <circle cx="3" cy="13" r="1.5" fill="#BCC0C9" />
                    <circle cx="7" cy="13" r="1.5" fill="#BCC0C9" />
                  </svg>
                </div>
                <div
                  class="card-color-strip"
                  :style="{ backgroundColor: status.color }"
                ></div>
                <div class="card-label" :style="{ color: status.color }">
                  {{ status.label.toUpperCase() }}
                </div>
                <div class="card-desc">
                  {{ status.description || status.category }}
                </div>
                <div class="card-actions">
                  <button
                    class="card-btn edit-btn"
                    @click.stop="openEditModal(status)"
                    title="Edit"
                  >
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                      <path
                        d="M17 3L21 7L7 21H3V17L17 3Z"
                        stroke="currentColor"
                        stroke-width="1.5"
                        fill="none"
                      />
                    </svg>
                  </button>
                  <button
                    class="card-btn delete-btn"
                    :class="{ 'btn-disabled': status.is_default }"
                    @click.stop="confirmDelete(status)"
                    :title="
                      status.is_default
                        ? 'Default status cannot be deleted'
                        : 'Delete'
                    "
                    :disabled="status.is_default"
                  >
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                      <path
                        d="M4 7H20M10 11V16M14 11V16M5 7L6 19C6 20.1 6.9 21 8 21H16C17.1 21 18 20.1 18 19L19 7"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                      />
                    </svg>
                  </button>
                </div>
              </div>

              <div
                v-if="idx < workflowStore.statuses.length - 1"
                class="connector-h"
              >
                <svg width="36" height="16" viewBox="0 0 36 16" fill="none">
                  <path d="M0 8H30" stroke="#CBD5E1" stroke-width="1.5" />
                  <path
                    d="M26 4L32 8L26 12"
                    stroke="#CBD5E1"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    fill="none"
                  />
                </svg>
              </div>
            </template>

            <button class="add-status-card" @click="openAddModal">
              <div class="add-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                  <path
                    d="M12 4V20M4 12H20"
                    stroke="#0052CC"
                    stroke-width="2"
                    stroke-linecap="round"
                  />
                </svg>
              </div>
              <span>Add status</span>
            </button>
          </div>

          <div class="flow-end">
            <div class="connector-v"></div>
            <div class="end-circle">END</div>
          </div>
        </div>

        <div class="legend">
          <div v-for="cat in categories" :key="cat.label" class="legend-item">
            <div
              class="legend-dot"
              :style="{ backgroundColor: cat.color }"
            ></div>
            <span>{{ cat.label }}</span>
          </div>
        </div>
      </div>

      <div class="info-sidebar">
        <div class="info-icon-wrap">
          <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
            <circle cx="24" cy="24" r="20" fill="#E8F0FE" />
            <path
              d="M16 24h16M24 16l8 8-8 8"
              stroke="#0052CC"
              stroke-width="2.5"
              stroke-linecap="round"
              stroke-linejoin="round"
              fill="none"
            />
          </svg>
        </div>
        <h3 class="info-title">Workflow statuses</h3>
        <p class="info-text">
          Each status represents a stage in your team's process. Items in the
          <strong>Timeline</strong> and <strong>Backlog</strong> use these
          statuses as their progress states.
        </p>
        <p class="info-text">
          <strong>Add</strong> new statuses to reflect custom steps,
          <strong>edit</strong> labels and colors, or <strong>drag</strong> to
          reorder.
        </p>
        <p class="info-text">
          Default statuses (<em>To Do, In Progress</em> etc.) cannot be deleted.
        </p>

        <!-- Status count by category -->
        <div class="info-counts">
          <div
            v-for="cat in categorySummary"
            :key="cat.category"
            class="count-row"
          >
            <div
              class="count-badge"
              :style="{ backgroundColor: cat.color + '20', color: cat.color }"
            >
              {{ cat.count }}
            </div>
            <span>{{ cat.category }}</span>
          </div>
        </div>
      </div>
    </div>

    <Transition name="modal">
      <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
        <div class="modal-box">
          <div class="modal-header">
            <h3>{{ editingStatus ? "Edit status" : "Add new status" }}</h3>
            <button class="modal-close-btn" @click="closeModal">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path
                  d="M18 6L6 18M6 6l12 12"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="form-field">
              <label>Status name <span class="req">*</span></label>
              <input
                v-model="form.label"
                type="text"
                placeholder="e.g. In Review"
                :class="{ 'input-error': formErrors.label }"
                @input="formErrors.label = ''"
              />
              <span v-if="formErrors.label" class="field-error">{{
                formErrors.label
              }}</span>
            </div>

            <div class="form-field">
              <label>Category</label>
              <div class="category-pills">
                <button
                  v-for="cat in categoryOptions"
                  :key="cat"
                  class="cat-pill"
                  :class="{ active: form.category === cat }"
                  @click="form.category = cat"
                  type="button"
                >
                  {{ cat }}
                </button>
              </div>
            </div>

            <div class="form-field">
              <label>Color</label>
              <div class="color-grid">
                <button
                  v-for="c in colorPalette"
                  :key="c"
                  class="color-swatch"
                  :style="{ backgroundColor: c }"
                  :class="{ 'color-selected': form.color === c }"
                  @click="form.color = c"
                  type="button"
                >
                  <svg
                    v-if="form.color === c"
                    width="12"
                    height="12"
                    viewBox="0 0 24 24"
                    fill="none"
                  >
                    <path
                      d="M5 13l4 4L19 7"
                      stroke="white"
                      stroke-width="2.5"
                      stroke-linecap="round"
                    />
                  </svg>
                </button>
              </div>
              <div class="custom-color-row">
                <input
                  type="color"
                  v-model="form.color"
                  class="color-native-input"
                />
                <input
                  type="text"
                  v-model="form.color"
                  class="color-text-input"
                  placeholder="#000000"
                />
              </div>
            </div>

            <div class="form-field">
              <label
                >Description <span class="optional">(optional)</span></label
              >
              <input
                v-model="form.description"
                type="text"
                placeholder="Short description of this status"
              />
            </div>

            <div class="status-preview">
              <span>Preview:</span>
              <span
                class="preview-badge"
                :style="{
                  backgroundColor: form.color + '20',
                  color: form.color,
                }"
              >
                {{ form.label || "Status name" }}
              </span>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn-cancel" @click="closeModal">Cancel</button>
            <button
              class="btn-confirm"
              @click="submitForm"
              :disabled="modalLoading"
            >
              <v-progress-circular
                v-if="modalLoading"
                size="14"
                width="2"
                indeterminate
                color="white"
                class="mr-1"
              />
              {{ editingStatus ? "Update" : "Add status" }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <Transition name="modal">
      <div
        v-if="showDeleteConfirm"
        class="modal-backdrop"
        @click.self="showDeleteConfirm = false"
      >
        <div class="modal-box modal-sm">
          <div class="modal-header">
            <h3>Delete status</h3>
          </div>
          <div class="modal-body">
            <p>
              Are you sure you want to delete
              <strong>{{ deletingStatus?.label }}</strong
              >?
            </p>
            <p class="delete-warning">
              All items currently in this status must be moved first.
            </p>
          </div>
          <div class="modal-footer">
            <button class="btn-cancel" @click="showDeleteConfirm = false">
              Cancel
            </button>
            <button
              class="btn-danger"
              @click="executeDelete"
              :disabled="deleteLoading"
            >
              <v-progress-circular
                v-if="deleteLoading"
                size="14"
                width="2"
                indeterminate
                color="white"
                class="mr-1"
              />
              Delete
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <Transition name="toast">
      <div v-if="toast.show" class="toast" :class="toast.type">
        <svg
          v-if="toast.type === 'success'"
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
        >
          <path
            d="M5 13l4 4L19 7"
            stroke="white"
            stroke-width="2.5"
            stroke-linecap="round"
          />
        </svg>
        <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path
            d="M18 6L6 18M6 6l12 12"
            stroke="white"
            stroke-width="2"
            stroke-linecap="round"
          />
        </svg>
        {{ toast.message }}
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useWorkflowStore, type WorkflowStatus } from "../stores/workflow";

const workflowStore = useWorkflowStore();

const saving = ref(false);
const showModal = ref(false);
const modalLoading = ref(false);
const editingStatus = ref<WorkflowStatus | null>(null);
const showDeleteConfirm = ref(false);
const deletingStatus = ref<WorkflowStatus | null>(null);
const deleteLoading = ref(false);
const dragIndex = ref<number | null>(null);
const dropIndex = ref<number | null>(null);

const toast = reactive({
  show: false,
  message: "",
  type: "success" as "success" | "error",
});

const form = reactive({
  label: "",
  category: "To Do",
  color: "#6B7280",
  description: "",
});
const formErrors = reactive({ label: "" });

const categoryOptions = ["To Do", "In Progress", "Done"];

const colorPalette = [
  "#6B7280",
  "#374151",
  "#111827",
  "#2563EB",
  "#1D4ED8",
  "#0891B2",
  "#059669",
  "#16A34A",
  "#15803D",
  "#D97706",
  "#EA580C",
  "#DC2626",
  "#7C3AED",
  "#DB2777",
  "#9333EA",
  "#0284C7",
  "#0369A1",
  "#065F46",
];

const categories = [
  { label: "To Do", color: "#6B7280" },
  { label: "In Progress", color: "#2563EB" },
  { label: "Done", color: "#16A34A" },
];

const categorySummary = computed(() =>
  categories.map((cat) => ({
    ...cat,
    count: workflowStore.statuses.filter((s) => s.category === cat.label)
      .length,
  })),
);

const openAddModal = () => {
  editingStatus.value = null;
  form.label = "";
  form.category = "To Do";
  form.color = "#6B7280";
  form.description = "";
  formErrors.label = "";
  showModal.value = true;
};

const openEditModal = (status: WorkflowStatus) => {
  editingStatus.value = status;
  form.label = status.label;
  form.category = status.category;
  form.color = status.color;
  form.description = status.description ?? "";
  formErrors.label = "";
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingStatus.value = null;
};

const submitForm = async () => {
  formErrors.label = "";
  if (!form.label.trim()) {
    formErrors.label = "Status name is required";
    return;
  }

  modalLoading.value = true;
  try {
    const bgColor = form.color + "20";
    const payload = {
      label: form.label.trim(),
      color: form.color,
      text_color: form.color,
      bg_color: bgColor,
      category: form.category,
      description: form.description,
    };

    if (editingStatus.value?.id) {
      await workflowStore.updateStatus(editingStatus.value.id, payload);
      showToast("Status updated successfully");
    } else {
      await workflowStore.createStatus(payload);
      showToast("Status added successfully");
    }
    closeModal();
  } catch (err: any) {
    const msg = err?.response?.data?.message ?? "Failed to save status";
    showToast(msg, "error");
  } finally {
    modalLoading.value = false;
  }
};

const confirmDelete = (status: WorkflowStatus) => {
  if (status.is_default) return;
  deletingStatus.value = status;
  showDeleteConfirm.value = true;
};

const executeDelete = async () => {
  if (!deletingStatus.value?.id) return;
  deleteLoading.value = true;
  try {
    await workflowStore.deleteStatus(deletingStatus.value.id);
    showDeleteConfirm.value = false;
    showToast("Status deleted");
  } catch (err: any) {
    const msg = err?.response?.data?.message ?? "Failed to delete status";
    showToast(msg, "error");
  } finally {
    deleteLoading.value = false;
    deletingStatus.value = null;
  }
};

const onDragStart = (idx: number) => {
  dragIndex.value = idx;
};
const onDragOver = (idx: number) => {
  dropIndex.value = idx;
};
const onDragEnd = () => {
  dragIndex.value = null;
  dropIndex.value = null;
};

const onDrop = async (targetIdx: number) => {
  if (dragIndex.value === null || dragIndex.value === targetIdx) return;

  const arr = [...workflowStore.statuses];
  const [moved] = arr.splice(dragIndex.value, 1);
  arr.splice(targetIdx, 0, moved);
  workflowStore.statuses.value = arr;

  dragIndex.value = null;
  dropIndex.value = null;

  const ids = arr.map((s) => s.id).filter(Boolean) as number[];
  if (ids.length === arr.length) {
    try {
      await workflowStore.reorderStatuses(ids);
    } catch {
      await workflowStore.refetch();
    }
  }
};

const handleSave = async () => {
  saving.value = true;
  try {
    await workflowStore.refetch();
    showToast("Workflow saved");
  } catch {
    showToast("Error refreshing workflow", "error");
  } finally {
    saving.value = false;
  }
};

let toastTimer: ReturnType<typeof setTimeout> | null = null;
const showToast = (message: string, type: "success" | "error" = "success") => {
  if (toastTimer) clearTimeout(toastTimer);
  toast.message = message;
  toast.type = type;
  toast.show = true;
  toastTimer = setTimeout(() => {
    toast.show = false;
  }, 3000);
};

onMounted(() => {
  workflowStore.fetchStatuses();
});
</script>

<style scoped>
.workflow-page {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #f8f9fc;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  overflow: hidden;
}

.workflow-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 28px;
  background: white;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0;
}
.workflow-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}
.workflow-title {
  font-size: 18px;
  font-weight: 700;
  color: #172b4d;
}
.workflow-subtitle {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}
.workflow-header-right {
  display: flex;
  align-items: center;
  gap: 10px;
}
.btn-update {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 18px;
  background: #0052cc;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}
.btn-update:hover {
  background: #0747a6;
}
.btn-update:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-close {
  padding: 8px 14px;
  background: none;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 14px;
  color: #475569;
  cursor: pointer;
}
.btn-close:hover {
  background: #f1f5f9;
}

.workflow-actions-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 28px;
  background: white;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0;
}
.action-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border: 1px solid #0052cc;
  border-radius: 20px;
  background: #eff6ff;
  color: #0052cc;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}
.action-pill:hover {
  background: #dbeafe;
}
.action-pill.disabled {
  border-color: #cbd5e1;
  background: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}
.action-pill-hint {
  font-size: 12px;
  color: #94a3b8;
  margin-left: auto;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  gap: 16px;
  color: #64748b;
}

.workflow-body {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.flow-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 32px 36px;
  overflow: auto;
  gap: 20px;
}
.flow-scroll {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.flow-start,
.flow-end {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding-left: 76px;
}
.start-circle,
.end-circle {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 16px;
  background: #172b4d;
  color: white;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.8px;
}
.end-circle {
  background: #64748b;
}
.connector-v {
  width: 2px;
  height: 20px;
  background: #cbd5e1;
  margin-left: 56px;
}

.status-row {
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  gap: 0;
  padding: 8px 0;
  overflow-x: auto;
}
.connector-h {
  flex-shrink: 0;
  padding: 0 4px;
}

.status-card {
  position: relative;
  flex-shrink: 0;
  width: 156px;
  padding: 28px 14px 14px;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  cursor: grab;
  transition: all 0.15s;
  user-select: none;
}
.status-card:hover {
  border-color: #93c5fd;
  box-shadow: 0 4px 12px rgba(0, 82, 204, 0.12);
}
.status-card.dragging {
  opacity: 0.5;
  transform: scale(0.97);
  cursor: grabbing;
}
.status-card.drag-over {
  border-color: #0052cc;
  border-style: dashed;
}

.card-top-badge {
  position: absolute;
  top: -11px;
  left: 12px;
  padding: 2px 10px;
  color: white;
  font-size: 10px;
  font-weight: 700;
  border-radius: 10px;
  letter-spacing: 0.3px;
}
.card-handle {
  position: absolute;
  top: 8px;
  right: 8px;
  cursor: grab;
  opacity: 0.4;
}
.status-card:hover .card-handle {
  opacity: 0.8;
}
.card-color-strip {
  height: 4px;
  border-radius: 3px;
  margin-bottom: 10px;
}
.card-label {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.5px;
  text-align: center;
  margin-bottom: 6px;
}
.card-desc {
  font-size: 11px;
  color: #94a3b8;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 10px;
}
.card-actions {
  display: flex;
  justify-content: center;
  gap: 6px;
  opacity: 0;
  transition: opacity 0.15s;
}
.status-card:hover .card-actions {
  opacity: 1;
}
.card-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  transition: background 0.12s;
}
.edit-btn {
  background: #eff6ff;
  color: #0052cc;
}
.edit-btn:hover {
  background: #dbeafe;
}
.delete-btn {
  background: #fff0f0;
  color: #dc2626;
}
.delete-btn:hover {
  background: #fee2e2;
}
.btn-disabled {
  opacity: 0.35;
  cursor: not-allowed !important;
}

.add-status-card {
  flex-shrink: 0;
  width: 120px;
  padding: 20px 12px;
  border: 2px dashed #cbd5e1;
  border-radius: 10px;
  background: none;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: #94a3b8;
  font-size: 12px;
  font-weight: 600;
  transition: all 0.15s;
  margin-left: 8px;
}
.add-status-card:hover {
  border-color: #0052cc;
  color: #0052cc;
  background: #eff6ff;
}
.add-icon {
  opacity: 0.7;
}
.add-status-card:hover .add-icon {
  opacity: 1;
}

.legend {
  display: flex;
  gap: 20px;
  padding: 12px 0;
  border-top: 1px solid #e2e8f0;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 12px;
  color: #64748b;
}
.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.info-sidebar {
  width: 300px;
  border-left: 1px solid #e2e8f0;
  padding: 32px 24px;
  background: white;
  overflow-y: auto;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.info-icon-wrap {
  text-align: center;
}
.info-title {
  font-size: 16px;
  font-weight: 700;
  color: #172b4d;
  text-align: center;
}
.info-text {
  font-size: 13px;
  line-height: 1.6;
  color: #64748b;
}
.info-counts {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 8px;
}
.count-row {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #475569;
}
.count-badge {
  min-width: 26px;
  height: 26px;
  padding: 0 6px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(9, 30, 66, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}
.modal-box {
  background: white;
  border-radius: 10px;
  width: 480px;
  max-width: 94vw;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  overflow: hidden;
}
.modal-sm {
  width: 380px;
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
}
.modal-header h3 {
  font-size: 16px;
  font-weight: 700;
  color: #172b4d;
}
.modal-close-btn {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: none;
  cursor: pointer;
  border-radius: 6px;
  color: #94a3b8;
}
.modal-close-btn:hover {
  background: #f1f5f9;
  color: #475569;
}
.modal-body {
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-field label {
  font-size: 12px;
  font-weight: 600;
  color: #334155;
}
.req {
  color: #dc2626;
}
.optional {
  color: #94a3b8;
  font-weight: 400;
}

.form-field input,
.form-field select {
  padding: 9px 12px;
  border: 1.5px solid #cbd5e1;
  border-radius: 6px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.15s;
}
.form-field input:focus {
  border-color: #0052cc;
}
.input-error {
  border-color: #dc2626 !important;
}
.field-error {
  font-size: 11px;
  color: #dc2626;
}

.category-pills {
  display: flex;
  gap: 8px;
}
.cat-pill {
  flex: 1;
  padding: 8px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  background: none;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  text-align: center;
  transition: all 0.12s;
}
.cat-pill.active {
  border-color: #0052cc;
  background: #eff6ff;
  color: #0052cc;
}
.cat-pill:hover:not(.active) {
  border-color: #94a3b8;
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(9, 1fr);
  gap: 6px;
}
.color-swatch {
  aspect-ratio: 1;
  border-radius: 5px;
  border: 2px solid transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.1s;
}
.color-swatch:hover {
  transform: scale(1.15);
}
.color-selected {
  border-color: #172b4d !important;
  transform: scale(1.1);
}

.custom-color-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
}
.color-native-input {
  width: 36px;
  height: 36px;
  border: none;
  cursor: pointer;
  border-radius: 6px;
  padding: 2px;
}
.color-text-input {
  flex: 1;
  padding: 8px 10px;
  border: 1.5px solid #cbd5e1;
  border-radius: 6px;
  font-size: 13px;
  font-family: monospace;
  outline: none;
}
.color-text-input:focus {
  border-color: #0052cc;
}

.status-preview {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: #f8fafc;
  border-radius: 8px;
  font-size: 12px;
  color: #64748b;
}
.preview-badge {
  padding: 3px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
}

.btn-cancel {
  padding: 8px 18px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: none;
  color: #475569;
  font-size: 14px;
  cursor: pointer;
}
.btn-cancel:hover {
  background: #f1f5f9;
}
.btn-confirm {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  border: none;
  border-radius: 6px;
  background: #0052cc;
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}
.btn-confirm:hover {
  background: #0747a6;
}
.btn-confirm:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-danger {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  border: none;
  border-radius: 6px;
  background: #dc2626;
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}
.btn-danger:hover {
  background: #b91c1c;
}
.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.delete-warning {
  font-size: 12px;
  color: #d97706;
  margin-top: 6px;
}

.toast {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 3000;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  color: white;
}
.toast.success {
  background: #16a34a;
}
.toast.error {
  background: #dc2626;
}

.modal-enter-active,
.modal-leave-active {
  transition: all 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.96);
}
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(12px);
}
</style>
