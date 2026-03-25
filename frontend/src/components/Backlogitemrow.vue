<template>
  <div class="backlog-row">
    <div class="row-left">
      <input type="checkbox" class="row-checkbox" />

      <span
        v-if="item.children && item.children.length"
        class="expand-btn"
        @click="expanded = !expanded"
      >
        <v-icon
          :icon="expanded ? 'mdi-chevron-down' : 'mdi-chevron-right'"
          size="14"
          color="#020f40"
        />
      </span>
      <span v-else style="width: 18px; flex-shrink: 0" />

      <v-icon
        :icon="getTypeIcon(item.type)"
        size="14"
        :color="getTypeColor(item.type)"
        style="flex-shrink: 0"
      />

      <RouterLink
        :to="`/board/${item.board_id}/item/${item.id}`"
        class="item-kode"
        >{{ item.kode }}</RouterLink
      >
      <span class="item-judul">{{ item.judul }}</span>

      <template v-if="item.labels?.length">
        <span
          v-for="lbl in item.labels"
          :key="lbl"
          class="item-label-tag"
          :style="{
            color: getLabelColor(lbl),
            borderColor: getLabelColor(lbl),
          }"
          >{{ lbl }}</span
        >
      </template>
    </div>

    <div class="row-right">
      <span v-if="item.epic" class="epic-badge" :title="item.epic.judul">
        {{ item.epic.kode }}
      </span>
      <span v-else class="epic-badge-none">+ Epic</span>

      <v-menu location="bottom end">
        <template #activator="{ props: menuProps }">
          <span
            class="status-badge"
            :class="`status-${item.status}`"
            v-bind="menuProps"
            style="cursor: pointer"
          >
            {{ getStatusLabel(item.status) }}
            <v-icon icon="mdi-chevron-down" size="10" />
          </span>
        </template>
        <v-list density="compact" min-width="160" rounded="lg" elevation="3">
          <v-list-item
            v-for="s in statusOptions"
            :key="s.value"
            :title="s.label"
            @click="emit('statusChange', s.value)"
          />
        </v-list>
      </v-menu>

      <span class="estimate-col">{{ item.original_estimate || "0m" }}</span>

      <span
        class="priority-dot"
        :style="{ backgroundColor: getPriorityColor(item.priority) }"
        :title="item.priority"
      />

      <v-avatar v-if="item.assignee" size="24" color="primary">
        <span style="color: white; font-size: 9px; font-weight: 700">
          {{ item.assignee.name.charAt(0).toUpperCase() }}
        </span>
      </v-avatar>
      <v-avatar v-else size="24" color="#e5e7eb">
        <v-icon icon="mdi-account" size="14" color="#8290a4" />
      </v-avatar>
    </div>
  </div>

  <template v-if="expanded && item.children?.length">
    <div
      v-for="child in item.children"
      :key="child.id"
      class="backlog-row child-row-indent"
    >
      <div class="row-left">
        <input type="checkbox" class="row-checkbox" />
        <span style="width: 30px; flex-shrink: 0" />
        <v-icon
          :icon="getTypeIcon(child.type)"
          size="13"
          :color="getTypeColor(child.type)"
          style="flex-shrink: 0"
        />
        <span class="item-kode">{{ child.kode }}</span>
        <span class="item-judul">{{ child.judul }}</span>
        <template v-if="child.labels?.length">
          <span
            v-for="lbl in child.labels"
            :key="lbl"
            class="item-label-tag"
            :style="{
              color: getLabelColor(lbl),
              borderColor: getLabelColor(lbl),
            }"
            >{{ lbl }}</span
          >
        </template>
      </div>
      <div class="row-right">
        <span v-if="child.epic" class="epic-badge">{{ child.epic.kode }}</span>
        <span v-else class="epic-badge-none">+ Epic</span>
        <span class="status-badge" :class="`status-${child.status}`">
          {{ getStatusLabel(child.status) }}
        </span>
        <span class="estimate-col">{{ child.original_estimate || "0m" }}</span>
        <span
          class="priority-dot"
          :style="{ backgroundColor: getPriorityColor(child.priority) }"
        />
        <v-avatar v-if="child.assignee" size="24" color="primary">
          <span style="color: white; font-size: 9px; font-weight: 700">
            {{ child.assignee.name.charAt(0).toUpperCase() }}
          </span>
        </v-avatar>
        <v-avatar v-else size="24" color="#e5e7eb">
          <v-icon icon="mdi-account" size="14" color="#8290a4" />
        </v-avatar>
      </div>
    </div>
  </template>
</template>

<script setup lang="ts">
import { ref } from "vue";
import type { EpicItem, EpicItemStatus } from "../types";
import { RouterLink } from "vue-router";

const props = defineProps<{ item: EpicItem }>();
const emit = defineEmits<{
  (e: "statusChange", status: EpicItemStatus): void;
}>();

const expanded = ref(false);

const statusOptions = [
  { label: "To Do", value: "to_do" },
  { label: "In Progress", value: "in_progress" },
  { label: "Done by Dev", value: "done_by_dev" },
  { label: "Testing", value: "testing" },
  { label: "Done by QA", value: "done_by_qa" },
] as const;

const getStatusLabel = (status: string) => {
  const map: Record<string, string> = {
    to_do: "TO DO",
    in_progress: "IN PROGRESS",
    done_by_dev: "DONE BY DEV",
    testing: "TESTING",
    done_by_qa: "DONE BY QA",
  };
  return map[status] ?? status.toUpperCase();
};

const getPriorityColor = (p: string) => {
  const map: Record<string, string> = {
    highest: "#DC2626",
    high: "#EA580C",
    medium: "#2563EB",
    low: "#16A34A",
    lowest: "#9CA3AF",
  };
  return map[p] ?? "#9CA3AF";
};

const getTypeIcon = (type: string) => {
  const icons: Record<string, string> = {
    epic: "mdi-lightning-bolt",
    story: "mdi-bookmark-outline",
    task: "mdi-check-circle-outline",
    bug: "mdi-bug-outline",
    qa_task: "mdi-clipboard-check-outline",
  };
  return icons[type] || "mdi-circle-outline";
};

const getTypeColor = (type: string) => {
  const colors: Record<string, string> = {
    epic: "#7C3AED",
    story: "#059669",
    task: "#1D4ED8",
    bug: "#DC2626",
    qa_task: "#0891B2",
  };
  return colors[type] || "#8290A4";
};

const getLabelColor = (label: string) => {
  const colors = ["#B45309", "#059669", "#1D4ED8", "#7C3AED", "#DC2626"];
  return colors[label.charCodeAt(0) % colors.length];
};
</script>

<style scoped>
.backlog-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 16px;
  border-bottom: 1px solid rgba(130, 144, 164, 0.1);
  min-height: 38px;
  gap: 8px;
  transition: background 0.12s;
}

.backlog-row:hover {
  background: #f9fafb;
}
.backlog-row:last-child {
  border-bottom: none;
}

.child-row-indent {
  padding-left: 32px;
  background: #fafafa;
}
.child-row-indent:hover {
  background: #f3f4f6;
}

.row-left {
  display: flex;
  align-items: center;
  gap: 6px;
  flex: 1;
  overflow: hidden;
  min-width: 0;
}

.row-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.row-checkbox {
  width: 13px;
  height: 13px;
  flex-shrink: 0;
  accent-color: #020f40;
}

.expand-btn {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-kode {
  font-size: 12px;
  font-weight: 700;
  color: #65a9ec;
  white-space: nowrap;
  flex-shrink: 0;
}

.item-judul {
  font-size: 13px;
  color: #111827;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}

.item-label-tag {
  font-size: 10px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 3px;
  border: 1px solid;
  white-space: nowrap;
  flex-shrink: 0;
}

.epic-badge {
  font-size: 10px;
  font-weight: 700;
  color: #7c3aed;
  background: rgba(124, 58, 237, 0.1);
  padding: 2px 8px;
  border-radius: 4px;
  white-space: nowrap;
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.epic-badge-none {
  font-size: 10px;
  color: #8290a4;
  cursor: pointer;
}

.epic-badge-none:hover {
  color: #020f40;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  padding: 2px 7px;
  border-radius: 4px;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.4px;
  white-space: nowrap;
}

.status-to_do {
  background: #e5e7eb;
  color: #374151;
}
.status-in_progress {
  background: #dbeafe;
  color: #1d4ed8;
}
.status-done_by_dev {
  background: #d1fae5;
  color: #065f46;
}
.status-testing {
  background: #fef9c3;
  color: #854d0e;
}
.status-done_by_qa {
  background: #dcfce7;
  color: #166534;
}

.estimate-col {
  font-size: 11px;
  font-weight: 700;
  color: #8290a4;
  min-width: 28px;
  text-align: center;
}

.priority-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
</style>
