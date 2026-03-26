<template>
  <div class="workflow-page">
    <!-- Header -->
    <div class="workflow-header">
      <div class="workflow-header-left">
        <svg
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          class="workflow-icon-svg"
        >
          <path
            d="M4 4H20V8L12 13L4 8V4Z"
            stroke="#172B4D"
            stroke-width="1.5"
            fill="white"
          />
          <path
            d="M4 12L12 17L20 12"
            stroke="#172B4D"
            stroke-width="1.5"
            fill="none"
          />
          <path
            d="M4 16L12 21L20 16"
            stroke="#172B4D"
            stroke-width="1.5"
            fill="none"
          />
        </svg>
        <div>
          <h1 class="workflow-title">DEV Workflow - With QA</h1>
          <p class="workflow-subtitle">Used in 1 space</p>
        </div>
      </div>
      <div class="workflow-header-right">
        <button class="btn-update">Update workflow</button>
        <button class="btn-close">Close</button>
        <button class="btn-more">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="2" fill="#6B778C" />
            <circle cx="12" cy="5" r="2" fill="#6B778C" />
            <circle cx="12" cy="19" r="2" fill="#6B778C" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Action Buttons Bar -->
    <div class="workflow-actions">
      <button class="action-btn" @click="showAddStatus = true">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path d="M12 4V20M4 12H20" stroke="#172B4D" stroke-width="1.5" />
        </svg>
        Add status
      </button>
      <button class="action-btn" disabled>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path d="M7 7L17 17M7 17L17 7" stroke="#C1C7D0" stroke-width="1.5" />
          <circle cx="7" cy="7" r="2.5" stroke="#C1C7D0" stroke-width="1.5" />
          <circle cx="17" cy="17" r="2.5" stroke="#C1C7D0" stroke-width="1.5" />
        </svg>
        Add Transition
      </button>
      <button class="action-btn" disabled>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path
            d="M13 2L3 14H12L11 22L21 10H12L13 2Z"
            stroke="#C1C7D0"
            stroke-width="1.5"
            fill="none"
          />
        </svg>
        Add Rule
      </button>
    </div>

    <!-- Tabs -->
    <div class="workflow-tabs">
      <button class="tab active">Diagram</button>
      <button class="tab">Text</button>
      <div class="tab-spacer"></div>
      <label class="show-labels">
        <input type="checkbox" v-model="showTransitionLabels" />
        <span>Show transition labels</span>
      </label>
    </div>

    <!-- Main Content -->
    <div class="workflow-content">
      <div class="diagram-view">
        <!-- Status Flow Section -->
        <div class="status-flow-section">
          <div class="status-flow">
            <!-- START node -->
            <div class="start-node">
              <span>START</span>
            </div>

            <!-- Arrow from START -->
            <div class="arrow-down">
              <svg width="16" height="24" viewBox="0 0 16 24" fill="none">
                <path
                  d="M8 0L8 20M8 20L2 14M8 20L14 14"
                  stroke="#DFE1E6"
                  stroke-width="2"
                />
              </svg>
            </div>

            <!-- Status Cards -->
            <div class="status-cards">
              <div
                v-for="(status, index) in statuses"
                :key="status.value"
                class="status-card-wrapper"
              >
                <div class="status-card">
                  <div
                    class="status-badge"
                    :style="{ backgroundColor: status.color }"
                  >
                    Any
                  </div>
                  <div class="status-name" :style="{ color: status.textColor }">
                    {{ status.label.toUpperCase() }}
                  </div>
                  <div class="status-actions">
                    <button
                      class="status-edit"
                      @click.stop="editStatus(status)"
                    >
                      <svg
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="none"
                      >
                        <path
                          d="M17 3L21 7L7 21H3V17L17 3Z"
                          stroke="#6B778C"
                          stroke-width="1.5"
                          fill="none"
                        />
                      </svg>
                    </button>
                    <button
                      class="status-delete"
                      @click.stop="removeStatus(status.value)"
                    >
                      <svg
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="none"
                      >
                        <path
                          d="M4 7H20M10 11V16M14 11V16M5 7L6 19C6 20.1 6.9 21 8 21H16C17.1 21 18 20.1 18 19L19 7"
                          stroke="#6B778C"
                          stroke-width="1.5"
                        />
                      </svg>
                    </button>
                  </div>
                </div>
                <div v-if="index < statuses.length - 1" class="arrow-right">
                  <svg width="24" height="16" viewBox="0 0 24 16" fill="none">
                    <path
                      d="M0 8H20M20 8L14 2M20 8L14 14"
                      stroke="#DFE1E6"
                      stroke-width="2"
                    />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Info Panel -->
        <div class="info-panel">
          <div class="info-graphic">
            <svg width="120" height="40" viewBox="0 0 120 40" fill="none">
              <circle cx="20" cy="20" r="8" fill="#0052CC" />
              <circle cx="45" cy="20" r="6" fill="#6554C0" />
              <circle cx="70" cy="20" r="4" fill="#172B4D" />
              <circle cx="90" cy="20" r="6" fill="#8777D9" />
              <circle cx="110" cy="20" r="8" fill="#2684FF" />
            </svg>
          </div>
          <h3 class="info-title">Make work flow your way</h3>
          <p class="info-text">
            Workflows represent your team's process and control how people
            progress your project's work.
          </p>
          <p class="info-text">
            Here, you can add statuses, which appear as drop zones for the cards
            on your project's board. You can create pathways between statuses
            called transitions, and automate repetitive actions using rules.
          </p>
          <p class="info-text">Select a status to reveal more details.</p>
          <a href="#" class="info-link" @click.prevent>Learn more</a>
          <div class="feedback-link">
            <a href="#" @click.prevent>Give feedback</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Status Modal -->
    <div
      v-if="showAddStatus"
      class="modal-overlay"
      @click.self="cancelAddStatus"
    >
      <div class="modal-content">
        <h3 class="modal-title">
          {{ editingStatus ? "Edit status" : "Add status" }}
        </h3>

        <div class="modal-field">
          <label>Status name <span class="required">*</span></label>
          <input
            type="text"
            v-model="newStatus.label"
            placeholder="e.g. In Review"
            :class="{ error: newStatusError }"
          />
          <span v-if="newStatusError" class="error-message">{{
            newStatusError
          }}</span>
        </div>

        <div class="modal-field">
          <label>Category</label>
          <select v-model="newStatus.category">
            <option v-for="cat in categoryOptions" :key="cat" :value="cat">
              {{ cat }}
            </option>
          </select>
        </div>

        <div class="modal-field">
          <label>Color</label>
          <div class="color-picker">
            <div
              v-for="color in colorOptions"
              :key="color.value"
              class="color-swatch"
              :style="{ backgroundColor: color.value }"
              :class="{ selected: newStatus.color === color.value }"
              @click="newStatus.color = color.value"
            ></div>
          </div>
        </div>

        <div class="modal-field">
          <label>Description</label>
          <input
            type="text"
            v-model="newStatus.description"
            placeholder="Optional description"
          />
        </div>

        <div class="modal-actions">
          <button class="modal-cancel" @click="cancelAddStatus">Cancel</button>
          <button class="modal-confirm" @click="confirmAddStatus">
            {{ editingStatus ? "Update" : "Add" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from "vue";

interface WorkflowStatus {
  value: string;
  label: string;
  color: string;
  textColor: string;
  category: string;
  description?: string;
}

const showTransitionLabels = ref(false);
const showAddStatus = ref(false);
const editingStatus = ref<WorkflowStatus | null>(null);
const newStatusError = ref("");

const statuses = ref<WorkflowStatus[]>([
  {
    value: "to_do",
    label: "To Do",
    color: "#6B778C",
    textColor: "#42526E",
    category: "To Do",
    description: "Work not yet started",
  },
  {
    value: "in_progress",
    label: "In Progress",
    color: "#0052CC",
    textColor: "#0052CC",
    category: "In Progress",
    description: "Work actively being done",
  },
  {
    value: "done_by_dev",
    label: "Done by Dev",
    color: "#00875A",
    textColor: "#00875A",
    category: "Done",
    description: "Dev completed, pending QA",
  },
  {
    value: "testing",
    label: "Testing",
    color: "#FF991F",
    textColor: "#FF991F",
    category: "In Progress",
    description: "Under QA testing",
  },
  {
    value: "done_by_qa",
    label: "Done by QA",
    color: "#36B37E",
    textColor: "#36B37E",
    category: "Done",
    description: "Fully completed and verified",
  },
]);

const newStatus = reactive({
  label: "",
  category: "To Do",
  color: "#6B778C",
  description: "",
});

const categoryOptions = ["To Do", "In Progress", "Done"];

const colorOptions = [
  { value: "#6B778C" },
  { value: "#0052CC" },
  { value: "#00875A" },
  { value: "#FF991F" },
  { value: "#36B37E" },
  { value: "#DE350B" },
  { value: "#6554C0" },
  { value: "#00B8D9" },
  { value: "#FF8B00" },
  { value: "#E56910" },
];

const editStatus = (status: WorkflowStatus) => {
  editingStatus.value = status;
  newStatus.label = status.label;
  newStatus.category = status.category;
  newStatus.color = status.color;
  newStatus.description = status.description || "";
  showAddStatus.value = true;
};

const removeStatus = (value: string) => {
  statuses.value = statuses.value.filter((s) => s.value !== value);
};

const cancelAddStatus = () => {
  showAddStatus.value = false;
  editingStatus.value = null;
  newStatus.label = "";
  newStatus.category = "To Do";
  newStatus.color = "#6B778C";
  newStatus.description = "";
  newStatusError.value = "";
};

const confirmAddStatus = () => {
  newStatusError.value = "";
  if (!newStatus.label.trim()) {
    newStatusError.value = "Status name is required";
    return;
  }

  if (editingStatus.value) {
    const idx = statuses.value.findIndex(
      (s) => s.value === editingStatus.value!.value,
    );
    if (idx !== -1) {
      statuses.value[idx] = {
        ...statuses.value[idx],
        label: newStatus.label,
        category: newStatus.category,
        color: newStatus.color,
        textColor: newStatus.color,
        description: newStatus.description,
      };
    }
  } else {
    const value = newStatus.label
      .toLowerCase()
      .replace(/\s+/g, "_")
      .replace(/[^a-z0-9_]/g, "");
    if (statuses.value.find((s) => s.value === value)) {
      newStatusError.value = "Status with this name already exists";
      return;
    }
    statuses.value.push({
      value,
      label: newStatus.label,
      color: newStatus.color,
      textColor: newStatus.color,
      category: newStatus.category,
      description: newStatus.description,
    });
  }

  cancelAddStatus();
};
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.workflow-page {
  background: white;
  display: flex;
  flex-direction: column;
  height: 100vh;
  font-family:
    -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", sans-serif;
}

/* Header */
.workflow-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #ebecf0;
  background: white;
}

.workflow-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.workflow-icon-svg {
  width: 24px;
  height: 24px;
}

.workflow-title {
  font-size: 20px;
  font-weight: 500;
  color: #172b4d;
  margin-bottom: 4px;
}

.workflow-subtitle {
  font-size: 12px;
  color: #6b778c;
}

.workflow-header-right {
  display: flex;
  gap: 8px;
  align-items: center;
}

.btn-update {
  padding: 8px 12px;
  border: none;
  background: #0052cc;
  color: white;
  font-size: 14px;
  font-weight: 500;
  border-radius: 3px;
  cursor: pointer;
}

.btn-close {
  padding: 8px 12px;
  border: none;
  background: transparent;
  color: #42526e;
  font-size: 14px;
  font-weight: 500;
  border-radius: 3px;
  cursor: pointer;
}

.btn-more {
  padding: 8px;
  background: none;
  border: none;
  cursor: pointer;
  border-radius: 3px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Action Buttons */
.workflow-actions {
  display: flex;
  gap: 8px;
  padding: 12px 24px;
  border-bottom: 1px solid #ebecf0;
  background: white;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: none;
  border: none;
  font-size: 14px;
  font-weight: 500;
  color: #172b4d;
  cursor: pointer;
  border-radius: 3px;
}

.action-btn:disabled {
  color: #c1c7d0;
  cursor: not-allowed;
}

/* Tabs */
.workflow-tabs {
  display: flex;
  align-items: center;
  padding: 0 24px;
  border-bottom: 1px solid #ebecf0;
  background: white;
}

.tab {
  padding: 12px 16px;
  background: none;
  border: none;
  font-size: 14px;
  font-weight: 500;
  color: #6b778c;
  cursor: pointer;
  border-bottom: 2px solid transparent;
}

.tab.active {
  color: #0052cc;
  border-bottom-color: #0052cc;
}

.tab-spacer {
  flex: 1;
}

.show-labels {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #42526e;
  cursor: pointer;
}

.show-labels input {
  width: 16px;
  height: 16px;
  cursor: pointer;
  margin: 0;
}

/* Content */
.workflow-content {
  flex: 1;
  overflow: auto;
  background: white;
}

.diagram-view {
  display: flex;
  height: 100%;
  min-height: 500px;
}

/* Status Flow Section */
.status-flow-section {
  flex: 1;
  padding: 32px 40px;
  overflow-x: auto;
}

.status-flow {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.start-node {
  width: 64px;
  height: 64px;
  background: #172b4d;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
}

.arrow-down {
  margin-left: 24px;
  margin-top: 4px;
  margin-bottom: 4px;
}

.status-cards {
  display: flex;
  align-items: center;
  gap: 0;
  flex-wrap: wrap;
}

.status-card-wrapper {
  display: flex;
  align-items: center;
}

.status-card {
  position: relative;
  width: 160px;
  padding: 20px 12px 12px;
  border: 2px solid #dfe1e6;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  margin: 16px 0;
}

.status-card:hover {
  background: #fafbfd;
}

.status-badge {
  position: absolute;
  top: -10px;
  left: 12px;
  padding: 2px 8px;
  background: #6b778c;
  color: white;
  font-size: 10px;
  font-weight: 600;
  border-radius: 12px;
}

.status-name {
  font-size: 14px;
  font-weight: 600;
  text-align: center;
  margin-bottom: 12px;
}

.status-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
}

.status-edit,
.status-delete {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 3px;
  opacity: 0;
  transition: opacity 0.2s;
}

.status-card:hover .status-edit,
.status-card:hover .status-delete {
  opacity: 1;
}

.status-edit:hover,
.status-delete:hover {
  background: #ebecf0;
}

.arrow-right {
  margin: 0 8px;
}

/* Info Panel */
.info-panel {
  width: 320px;
  border-left: 1px solid #ebecf0;
  padding: 32px 24px;
  background: #fafbfd;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-graphic {
  display: flex;
  justify-content: center;
  margin-bottom: 8px;
}

.info-title {
  font-size: 20px;
  font-weight: 500;
  color: #172b4d;
  text-align: center;
}

.info-text {
  font-size: 14px;
  color: #6b778c;
  line-height: 1.5;
  text-align: center;
}

.info-link {
  font-size: 14px;
  font-weight: 500;
  color: #0052cc;
  text-align: center;
  text-decoration: none;
}

.feedback-link {
  margin-top: 8px;
  text-align: center;
}

.feedback-link a {
  font-size: 12px;
  color: #6b778c;
  text-decoration: none;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(9, 30, 66, 0.54);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 3px;
  width: 480px;
  max-width: 90%;
  padding: 24px;
}

.modal-title {
  font-size: 20px;
  font-weight: 500;
  color: #172b4d;
  margin-bottom: 20px;
}

.modal-field {
  margin-bottom: 20px;
}

.modal-field label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #172b4d;
  margin-bottom: 8px;
}

.required {
  color: #de350b;
}

.modal-field input,
.modal-field select {
  width: 100%;
  padding: 8px 12px;
  border: 2px solid #dfe1e6;
  border-radius: 3px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}

.modal-field input:focus,
.modal-field select:focus {
  border-color: #4c9aff;
}

.modal-field input.error {
  border-color: #de350b;
}

.error-message {
  display: block;
  font-size: 12px;
  color: #de350b;
  margin-top: 4px;
}

.color-picker {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.color-swatch {
  width: 32px;
  height: 32px;
  border-radius: 3px;
  cursor: pointer;
  border: 2px solid transparent;
}

.color-swatch.selected {
  border-color: #172b4d;
}

.modal-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
  justify-content: flex-end;
}

.modal-cancel,
.modal-confirm {
  padding: 8px 16px;
  border-radius: 3px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border: none;
}

.modal-cancel {
  background: transparent;
  color: #42526e;
}

.modal-confirm {
  background: #0052cc;
  color: white;
}

.modal-cancel:hover {
  background: #ebecf0;
}

.modal-confirm:hover {
  background: #0065ff;
}
</style>
