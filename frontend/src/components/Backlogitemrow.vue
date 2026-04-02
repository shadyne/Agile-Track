<template>
  <div
    class="backlog-row"
    :draggable="draggable"
    @dragstart="onDragStart"
    @dragend="onDragEnd"
  >
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
      <!-- Epic dengan dropdown -->
      <v-menu location="bottom end">
        <template #activator="{ props: menuProps }">
          <span
            v-if="item.epic"
            class="epic-badge"
            :title="item.epic.judul"
            v-bind="menuProps"
            style="cursor: pointer"
          >
            {{ item.epic.kode }}
            <v-icon icon="mdi-chevron-down" size="10" />
          </span>
          <span
            v-else
            class="epic-badge-none"
            v-bind="menuProps"
            style="cursor: pointer"
          >
            + Epic
            <v-icon icon="mdi-chevron-down" size="10" />
          </span>
        </template>
        <v-list density="compact" min-width="220" rounded="lg" elevation="3">
          <v-list-item
            v-for="epic in boardEpics"
            :key="epic.id"
            @click="epic.id !== item.epic?.id && handleEpicChange(epic.id)"
            :disabled="epic.id === item.epic?.id"
          >
            <template #prepend>
              <v-icon icon="mdi-lightning-bolt" size="14" color="#7C3AED" />
            </template>
            <v-list-item-title>
              <span
                :style="{
                  color: epic.id === item.epic?.id ? '#9CA3AF' : '#7C3AED',
                  fontWeight: 700,
                }"
              >
                {{ epic.kode }} - {{ epic.judul }}
              </span>
            </v-list-item-title>
            <template #append>
              <v-icon
                v-if="epic.id === item.epic?.id"
                icon="mdi-check"
                size="16"
                color="#16A34A"
              />
            </template>
          </v-list-item>
          <v-divider class="my-1" />
          <v-list-item @click="handleEpicChange(null)">
            <v-list-item-title style="color: #8290a4">
              Hapus dari epic
            </v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>

      <!-- Status dropdown -->
      <v-menu location="bottom end">
        <template #activator="{ props: menuProps }">
          <span
            class="status-badge"
            :style="workflowStore.getStatusStyle(item.status)"
            v-bind="menuProps"
            style="cursor: pointer"
          >
            {{ workflowStore.getStatusLabel(item.status) }}
            <v-icon icon="mdi-chevron-down" size="10" />
          </span>
        </template>
        <v-list density="compact" min-width="180" rounded="lg" elevation="3">
          <v-list-item
            v-for="s in workflowStore.statuses"
            :key="s.value"
            @click="emit('statusChange', s.value)"
          >
            <template #prepend>
              <span class="status-dot" :style="{ backgroundColor: s.color }" />
            </template>
            <v-list-item-title>{{ s.label }}</v-list-item-title>
          </v-list-item>
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
        <!-- Epic untuk child item (dengan dropdown) -->
        <v-menu location="bottom end">
          <template #activator="{ props: menuProps }">
            <span
              v-if="child.epic"
              class="epic-badge"
              :title="child.epic.judul"
              v-bind="menuProps"
              style="cursor: pointer"
            >
              {{ child.epic.kode }}
              <v-icon icon="mdi-chevron-down" size="10" />
            </span>
            <span
              v-else
              class="epic-badge-none"
              v-bind="menuProps"
              style="cursor: pointer"
            >
              + Epic
              <v-icon icon="mdi-chevron-down" size="10" />
            </span>
          </template>
          <v-list density="compact" min-width="220" rounded="lg" elevation="3">
            <v-list-item
              v-for="epic in boardEpics"
              :key="epic.id"
              @click="
                epic.id !== child.epic?.id &&
                handleChildEpicChange(child.id, epic.id)
              "
              :disabled="epic.id === child.epic?.id"
            >
              <template #prepend>
                <v-icon icon="mdi-lightning-bolt" size="14" color="#7C3AED" />
              </template>
              <v-list-item-title>
                <span
                  :style="{
                    color: epic.id === child.epic?.id ? '#9CA3AF' : '#7C3AED',
                    fontWeight: 700,
                  }"
                >
                  {{ epic.kode }} - {{ epic.judul }}
                </span>
              </v-list-item-title>
              <template #append>
                <v-icon
                  v-if="epic.id === child.epic?.id"
                  icon="mdi-check"
                  size="16"
                  color="#16A34A"
                />
              </template>
            </v-list-item>
            <v-divider class="my-1" />
            <v-list-item @click="handleChildEpicChange(child.id, null)">
              <v-list-item-title style="color: #8290a4">
                Hapus dari epic
              </v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>

        <span
          class="status-badge"
          :style="workflowStore.getStatusStyle(child.status)"
        >
          {{ workflowStore.getStatusLabel(child.status) }}
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
import { ref, onMounted } from "vue";
import type { EpicItem, Epic } from "../types";
import { RouterLink } from "vue-router";
import { useWorkflowStore } from "../stores/workflow";
import api from "../api/axios";

const props = defineProps<{
  item: EpicItem;
  boardId?: number;
  draggable?: boolean;
}>();
const emit = defineEmits<{
  (e: "statusChange", status: string): void;
  (e: "epicChange", itemId: number, epicId: number | null): void;
  (e: "refresh"): void;
}>();

const workflowStore = useWorkflowStore();
const expanded = ref(false);
const boardEpics = ref<Epic[]>([]);

const ambilEpics = async () => {
  if (!props.boardId && !props.item.board_id) return;
  const boardId = props.boardId || props.item.board_id;

  try {
    const res = await api.get<Epic[]>(`/boards/${boardId}/epics`);
    boardEpics.value = res.data;
  } catch (error) {
    console.error("Gagal ambil epics:", error);
  }
};

const handleEpicChange = async (epicId: number | null) => {
  try {
    await api.put(`/boards/${props.item.board_id}/items/${props.item.id}`, {
      epic_id: epicId,
    });

    if (epicId) {
      const selectedEpic = boardEpics.value.find((e) => e.id === epicId);
      props.item.epic = selectedEpic || null;
      props.item.epic_id = epicId;
    } else {
      props.item.epic = null;
      props.item.epic_id = null;
    }

    emit("refresh");
  } catch (error) {
    console.error("Gagal update epic:", error);
  }
};

const handleChildEpicChange = async (
  childId: number,
  epicId: number | null,
) => {
  try {
    await api.put(`/boards/${props.item.board_id}/items/${childId}`, {
      epic_id: epicId,
    });

    const child = props.item.children?.find((c) => c.id === childId);
    if (child) {
      if (epicId) {
        const selectedEpic = boardEpics.value.find((e) => e.id === epicId);
        child.epic = selectedEpic || null;
        child.epic_id = epicId;
      } else {
        child.epic = null;
        child.epic_id = null;
      }
    }

    emit("refresh");
  } catch (error) {
    console.error("Gagal update epic child:", error);
  }
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

onMounted(() => {
  ambilEpics();
});
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
  text-decoration: none;
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
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.3px;
  white-space: nowrap;
  cursor: pointer;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
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
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.epic-badge-none {
  font-size: 10px;
  color: #8290a4;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 4px;
  border-radius: 4px;
  transition: all 0.2s;
}

.epic-badge-none:hover {
  color: #7c3aed;
  background: rgba(124, 58, 237, 0.05);
}
</style>
