<template>
  <div class="backlog-wrap">
    <div class="backlog-filters">
      <div class="avatar-filter-group">
        <div
          v-for="member in boardMembers"
          :key="member.id"
          class="avatar-filter"
          :class="{ active: filterUserId === member.id }"
          :title="member.name"
          @click="toggleUserFilter(member.id)"
        >
          <span style="color: #020f40; font-size: 11px; font-weight: 700">
            {{ member.name.charAt(0).toUpperCase() }}
          </span>
        </div>
      </div>

      <div class="filter-divider" />

      <v-select
        v-model="filterEpic"
        label="Epic"
        :items="epicFilterOptions"
        variant="outlined"
        density="compact"
        hide-details
        style="max-width: 130px"
      />
      <v-select
        v-model="filterType"
        label="Type"
        :items="typeFilterOptions"
        variant="outlined"
        density="compact"
        hide-details
        style="max-width: 120px"
      />
      <v-select
        v-model="filterLabel"
        label="Label"
        :items="labelFilterOptions"
        variant="outlined"
        density="compact"
        hide-details
        style="max-width: 130px"
      />
    </div>

    <div v-if="loading" class="flex items-center justify-center h-64">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <div v-else class="backlog-content">
      <div
        v-for="sprint in filteredSprints"
        :key="sprint.id"
        class="backlog-section"
        :data-sprint-id="sprint.id"
        :class="{ 'drag-over': dragOverSprintId === sprint.id }"
        @dragover.prevent="handleDragOver(sprint.id)"
        @dragleave="handleDragLeave"
        @drop="handleDropToSprint($event, sprint.id)"
      >
        <div class="section-header">
          <div class="section-header-left" @click="toggleSection(sprint.id)">
            <v-icon
              :icon="
                expandedSections.includes(sprint.id)
                  ? 'mdi-chevron-down'
                  : 'mdi-chevron-right'
              "
              size="16"
              color="#020f40"
            />
            <input
              v-model="sprint.nama"
              class="sprint-name-input"
              @blur="renameSprint(sprint)"
              @click.stop
            />
            <span class="section-count">{{ sprintDateRange(sprint) }}</span>
            <span class="section-count"
              >({{ sprint.items.length }} work items)</span
            >
          </div>

          <div class="section-header-right">
            <span class="estimate-text">{{
              totalMonths(sprint.items) ?? "mo"
            }}</span>
            <span class="estimate-text estimate-active">{{
              totalDays(sprint.items)
            }}</span>
            <span class="estimate-text">{{
              totalHours(sprint.items) ?? "h"
            }}</span>

            <v-btn
              v-if="sprint.status === 'planning'"
              size="small"
              variant="outlined"
              color="primary"
              style="font-size: 12px; font-weight: 700"
              @click="openStartSprint(sprint)"
            >
              Start Sprint
            </v-btn>
            <v-btn
              v-else
              size="small"
              variant="outlined"
              color="success"
              style="font-size: 12px; font-weight: 700"
              :loading="completingSprintId === sprint.id"
              @click="completeSprint(sprint)"
            >
              Complete Sprint
            </v-btn>

            <v-menu location="bottom end">
              <template #activator="{ props: menuProps }">
                <v-btn
                  icon="mdi-dots-horizontal"
                  variant="text"
                  size="x-small"
                  v-bind="menuProps"
                />
              </template>
              <v-list
                density="compact"
                min-width="160"
                rounded="lg"
                elevation="3"
              >
                <v-list-item
                  prepend-icon="mdi-plus"
                  title="Create task"
                  @click="openCreateTask(sprint.id)"
                />
                <v-list-item
                  prepend-icon="mdi-trash-can-outline"
                  title="Delete sprint"
                  base-color="cancel"
                  @click="deleteSprint(sprint)"
                />
              </v-list>
            </v-menu>
          </div>
        </div>

        <div v-if="expandedSections.includes(sprint.id)" class="item-list">
          <div
            v-for="item in sprint.items"
            :key="item.id"
            class="backlog-item-wrapper"
            draggable="true"
            @dragstart="handleDragStart($event, item)"
            @dragend="handleDragEnd"
          >
            <BacklogItemRow
              :item="item"
              :boardId="boardId"
              @status-change="(status) => updateItemStatus(item, status)"
              @epicChange="handleEpicChange"
              @refresh="refreshData"
            />
          </div>
          <div v-if="sprint.items.length === 0" class="empty-section">
            Belum ada task.
            <span class="link-text" @click="openCreateTask(sprint.id)"
              >buat task baru</span
            >
          </div>
        </div>
      </div>

      <div class="backlog-section" data-sprint-id="backlog">
        <div class="section-header">
          <div class="section-header-left" @click="toggleSection(-1)">
            <v-icon
              :icon="
                expandedSections.includes(-1)
                  ? 'mdi-chevron-down'
                  : 'mdi-chevron-right'
              "
              size="16"
              color="#020f40"
            />
            <span class="section-title">Backlog</span>
            <span class="section-count"
              >({{ filteredBacklog.length }} work items)</span
            >
          </div>

          <div class="section-header-right">
            <v-btn
              size="small"
              variant="outlined"
              color="primary"
              style="font-size: 12px; font-weight: 700"
              @click="openCreateSprint"
            >
              Create sprint
            </v-btn>
            <v-btn icon="mdi-dots-horizontal" variant="text" size="x-small" />
          </div>
        </div>

        <div v-if="expandedSections.includes(-1)" class="item-list">
          <div
            v-for="item in filteredBacklog"
            :key="item.id"
            class="backlog-item-wrapper"
            draggable="true"
            @dragstart="handleDragStart($event, item)"
            @dragend="handleDragEnd"
          >
            <BacklogItemRow
              :item="item"
              :boardId="boardId"
              @status-change="(status) => updateItemStatus(item, status)"
              @epicChange="handleEpicChange"
              @refresh="refreshData"
            />
          </div>
          <div v-if="filteredBacklog.length === 0" class="empty-section">
            Backlog kosong.
          </div>
        </div>
      </div>
    </div>

    <CreateTaskModal
      v-model="showCreateTask"
      :board-id="boardId"
      :sprint-id="createTaskSprintId"
      :epics="epics"
      :board-members="boardMembers"
      :available-labels="availableLabels"
      @created="onTaskCreated"
    />

    <v-dialog v-model="showStartSprint" max-width="400" persistent>
      <v-card rounded="xl" style="padding: 8px 0">
        <v-card-title
          style="
            padding: 20px 24px 8px;
            font-family: Barlow;
            font-weight: 800;
            font-size: 18px;
            color: #020f40;
          "
        >
          Start Sprint
        </v-card-title>
        <v-card-text style="padding: 8px 24px !important">
          <v-text-field
            v-model="startSprintForm.start_date"
            label="Start Date"
            type="date"
            variant="outlined"
            density="comfortable"
            class="mb-2"
          />
          <v-text-field
            v-model="startSprintForm.end_date"
            label="End Date"
            type="date"
            variant="outlined"
            density="comfortable"
          />
        </v-card-text>
        <v-card-actions
          style="
            padding: 8px 24px 20px;
            gap: 12px;
            justify-content: space-around;
          "
        >
          <v-btn
            color="cancel"
            variant="flat"
            style="min-width: 120px; color: white; font-weight: 700"
            @click="showStartSprint = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="create"
            variant="flat"
            style="min-width: 120px; font-weight: 700"
            :loading="startingSprintLoading"
            @click="confirmStartSprint"
          >
            <span style="color: #020f40; font-weight: 700">Start</span>
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar
      v-model="showError"
      color="error"
      timeout="4000"
      location="bottom right"
    >
      {{ errorMessage }}
    </v-snackbar>

    <v-snackbar
      v-model="showSuccess"
      color="success"
      timeout="4000"
      location="bottom right"
    >
      {{ successMessage }}
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import api from "../api/axios";
import type {
  BacklogData,
  EpicItem,
  Sprint,
  Epic,
  EpicItemStatus,
  Board,
} from "../types";
import CreateTaskModal from "./CreateTaskModal.vue";
import BacklogItemRow from "./Backlogitemrow.vue";

const props = defineProps<{
  boardId: number;
  epics: Epic[];
  boardMembers?: { id: number; name: string; email: string }[];
}>();

const emit = defineEmits<{ (e: "refresh"): void }>();

const loading = ref(false);
const backlogItems = ref<EpicItem[]>([]);
const sprints = ref<Sprint[]>([]);
const expandedSections = ref<number[]>([-1]);
const filterUserId = ref<number | null>(null);
const filterEpic = ref("All");
const filterType = ref("All");
const filterLabel = ref("All");
const showCreateTask = ref(false);
const createTaskSprintId = ref<number | null>(null);
const showStartSprint = ref(false);
const selectedSprint = ref<Sprint | null>(null);
const startingSprintLoading = ref(false);
const completingSprintId = ref<number | null>(null);
const startSprintForm = ref({ start_date: "", end_date: "" });
const showError = ref(false);
const errorMessage = ref("");
const showSuccess = ref(false);
const successMessage = ref("");
const isMoving = ref(false);
const dragOverSprintId = ref<number | null>(null);
const draggingItem = ref<EpicItem | null>(null);

const addTask = (task: EpicItem) => {
  backlogItems.value.unshift(task);
};
defineExpose({ addTask });

const allLabels = computed<string[]>(() => {
  const set = new Set<string>();
  [...backlogItems.value, ...sprints.value.flatMap((s) => s.items)].forEach(
    (item) => item.labels?.forEach((l) => set.add(l)),
  );
  return ["All", ...Array.from(set)];
});

const epicFilterOptions = computed(() => [
  "All",
  ...props.epics.map((e) => e.judul),
]);
const typeFilterOptions = ["All", "Task", "Story", "Bug", "QA Task"];
const labelFilterOptions = allLabels;

const availableLabels = computed(() => {
  const labels = backlogItems.value.flatMap((item) => item.labels || []);
  return [...new Set(labels)];
});

const applyFilters = (items: EpicItem[]): EpicItem[] => {
  return items.filter((item) => {
    if (filterUserId.value && item.assignee_id !== filterUserId.value)
      return false;
    if (filterEpic.value !== "All" && item.epic?.judul !== filterEpic.value)
      return false;
    if (filterType.value !== "All") {
      const map: Record<string, string> = {
        Task: "task",
        Story: "story",
        Bug: "bug",
        "QA Task": "qa_task",
      };
      if (item.type !== map[filterType.value]) return false;
    }
    if (
      filterLabel.value !== "All" &&
      !item.labels?.includes(filterLabel.value)
    )
      return false;
    return true;
  });
};

const filteredBacklog = computed(() => applyFilters(backlogItems.value));
const filteredSprints = computed(() =>
  sprints.value.map((sprint) => ({
    ...sprint,
    items: applyFilters(sprint.items),
  })),
);

const toggleUserFilter = (userId: number) => {
  filterUserId.value = filterUserId.value === userId ? null : userId;
};

const getTotalMinutes = (items: EpicItem[]): number => {
  let total = 0;
  items.forEach((item) => {
    if (!item.original_estimate) return;
    const e = item.original_estimate;
    const mo = e.match(/(\d+)\s*mo/);
    if (mo) total += parseInt(mo[1]) * 30 * 8 * 60;
    const w = e.match(/(\d+)\s*w/);
    if (w) total += parseInt(w[1]) * 7 * 8 * 60;
    const d = e.match(/(\d+)\s*d/);
    if (d) total += parseInt(d[1]) * 8 * 60;
    const h = e.match(/(\d+)\s*h/);
    if (h) total += parseInt(h[1]) * 60;
    const m = e.match(/(\d+)\s*m(?!o)/);
    if (m) total += parseInt(m[1]);
  });
  return total;
};
const totalMonths = (items: EpicItem[]) => {
  const t = getTotalMinutes(items);
  return `${Math.floor(t / (30 * 8 * 60))}mo`;
};
const totalDays = (items: EpicItem[]) => {
  const t = getTotalMinutes(items);
  const r = t % (30 * 8 * 60);
  return `${Math.floor(r / (8 * 60))}d`;
};
const totalHours = (items: EpicItem[]) => {
  const t = getTotalMinutes(items);
  const r = t % (30 * 8 * 60);
  const d = r % (8 * 60);
  return `${Math.floor(d / 60)}h`;
};

const sprintDateRange = (sprint: Sprint): string => {
  if (!sprint.start_date || !sprint.end_date) return "";
  const fmt = (d: string) => {
    const dt = new Date(d);
    return `${dt.getDate()} ${dt.toLocaleString("en", { month: "short" })}`;
  };
  return `${fmt(sprint.start_date)} – ${fmt(sprint.end_date)}`;
};

const toggleSection = (id: number) => {
  const idx = expandedSections.value.indexOf(id);
  if (idx === -1) expandedSections.value.push(id);
  else expandedSections.value.splice(idx, 1);
};

const ambilData = async () => {
  loading.value = true;
  try {
    const res = await api.get<BacklogData>(`/boards/${props.boardId}/backlog`);
    backlogItems.value = res.data.backlog;
    sprints.value = res.data.sprints;
    sprints.value.forEach((s) => {
      if (!expandedSections.value.includes(s.id))
        expandedSections.value.push(s.id);
    });
  } catch (err) {
    errorMessage.value = "Gagal memuat data backlog";
    showError.value = true;
  } finally {
    loading.value = false;
  }
};

const refreshData = async () => {
  await ambilData();
};

const openCreateTask = (sprintId: number | null = null) => {
  createTaskSprintId.value = sprintId;
  showCreateTask.value = true;
};

const onTaskCreated = (item: EpicItem) => {
  if (item.sprint_id) {
    const sprint = sprints.value.find((s) => s.id === item.sprint_id);
    if (sprint) sprint.items.push(item);
  } else {
    backlogItems.value.push(item);
  }
  refreshData();
};

const openCreateSprint = async () => {
  try {
    const count = sprints.value.length + 1;
    const res = await api.post<{ data: Sprint }>(
      `/boards/${props.boardId}/sprints`,
      { nama: `Sprint ${count}` },
    );
    const newSprint = res.data.data;
    (newSprint as any).items = [];
    sprints.value.push(newSprint as any);
    expandedSections.value.push(newSprint.id);
  } catch (err) {
    errorMessage.value = "Gagal membuat sprint";
    showError.value = true;
  }
};

const openStartSprint = (sprint: Sprint) => {
  selectedSprint.value = sprint;
  startSprintForm.value = { start_date: "", end_date: "" };
  showStartSprint.value = true;
};

const confirmStartSprint = async () => {
  if (!selectedSprint.value) return;
  if (!startSprintForm.value.start_date || !startSprintForm.value.end_date) {
    errorMessage.value = "Tanggal mulai dan selesai wajib diisi";
    showError.value = true;
    return;
  }
  startingSprintLoading.value = true;
  try {
    await api.post(
      `/boards/${props.boardId}/sprints/${selectedSprint.value.id}/start`,
      startSprintForm.value,
    );
    const idx = sprints.value.findIndex(
      (s) => s.id === selectedSprint.value!.id,
    );
    if (idx !== -1) sprints.value[idx].status = "active";
    showStartSprint.value = false;
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message ?? "Gagal memulai sprint";
    showError.value = true;
  } finally {
    startingSprintLoading.value = false;
  }
};

const completeSprint = async (sprint: Sprint) => {
  completingSprintId.value = sprint.id;
  try {
    const res = await api.post<{
      message: string;
      done_count: number;
      moved_count: number;
      next_sprint: (Sprint & { items: EpicItem[] }) | null;
    }>(`/boards/${props.boardId}/sprints/${sprint.id}/complete`);

    const { done_count, moved_count, next_sprint } = res.data;

    sprints.value = sprints.value.filter((s) => s.id !== sprint.id);
    expandedSections.value = expandedSections.value.filter(
      (id) => id !== sprint.id,
    );

    if (next_sprint) {
      const existingIdx = sprints.value.findIndex(
        (s) => s.id === next_sprint.id,
      );
      if (existingIdx !== -1) {
        sprints.value[existingIdx] = next_sprint;
      } else {
        sprints.value.push(next_sprint);
        expandedSections.value.push(next_sprint.id);
      }
    }

    const parts: string[] = [];
    if (done_count > 0) parts.push(`${done_count} item selesai`);
    if (moved_count > 0) {
      const dest = next_sprint
        ? `dipindah ke "${next_sprint.nama}"`
        : "dipindah ke sprint baru";
      parts.push(`${moved_count} item ${dest}`);
    }
    successMessage.value = parts.length
      ? parts.join(", ") + "."
      : "Sprint selesai.";
    showSuccess.value = true;
  } catch (err: any) {
    errorMessage.value =
      err.response?.data?.message ?? "Gagal menyelesaikan sprint";
    showError.value = true;
  } finally {
    completingSprintId.value = null;
  }
};

const deleteSprint = async (sprint: Sprint) => {
  try {
    await api.delete(`/boards/${props.boardId}/sprints/${sprint.id}`);
    backlogItems.value.push(...sprint.items);
    sprints.value = sprints.value.filter((s) => s.id !== sprint.id);
    expandedSections.value = expandedSections.value.filter(
      (id) => id !== sprint.id,
    );
  } catch (err: any) {
    errorMessage.value =
      err.response?.data?.message ?? "Gagal menghapus sprint";
    showError.value = true;
  }
};

const renameSprint = async (sprint: Sprint) => {
  try {
    await api.patch(`/boards/${props.boardId}/sprints/${sprint.id}/rename`, {
      nama: sprint.nama,
    });
  } catch (err) {
    console.error("Gagal rename sprint:", err);
  }
};

const updateItemStatus = async (item: EpicItem, status: string) => {
  const oldStatus = item.status;
  item.status = status as EpicItemStatus;
  try {
    await api.patch(
      `/boards/${props.boardId}/backlog/items/${item.id}/status`,
      { status },
    );
  } catch (err: any) {
    item.status = oldStatus;
    const code = err.response?.data?.error_code;
    if (code === "NO_EPIC_FOR_DONE_BY_QA") {
      errorMessage.value =
        "Please assign this item to an epic first before marking as Done by QA.";
    } else {
      errorMessage.value = err.response?.data?.message ?? "Status tidak valid";
    }
    showError.value = true;
  }
};

const handleDragStart = (event: DragEvent, item: EpicItem) => {
  if (isMoving.value) {
    event.preventDefault();
    return;
  }
  draggingItem.value = item;
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = "move";
    event.dataTransfer.setData(
      "text/plain",
      JSON.stringify({ id: item.id, type: item.type }),
    );
  }
};
const handleDragEnd = () => {
  draggingItem.value = null;
  dragOverSprintId.value = null;
};
const handleDragOver = (sprintId: number) => {
  if (draggingItem.value && !isMoving.value) dragOverSprintId.value = sprintId;
};
const handleDragLeave = () => {
  dragOverSprintId.value = null;
};

const handleDropToSprint = async (event: DragEvent, targetSprintId: number) => {
  event.preventDefault();
  dragOverSprintId.value = null;
  if (!draggingItem.value || isMoving.value) return;
  const movedItem = draggingItem.value;
  if (movedItem.sprint_id !== null) {
    errorMessage.value = "Item sudah berada di sprint";
    showError.value = true;
    return;
  }
  isMoving.value = true;
  const originalBacklog = [...backlogItems.value];
  const originalSprints = JSON.parse(JSON.stringify(sprints.value));
  const targetSprint = sprints.value.find((s) => s.id === targetSprintId);
  if (!targetSprint) {
    isMoving.value = false;
    return;
  }
  try {
    const backlogIndex = backlogItems.value.findIndex(
      (i) => i.id === movedItem.id,
    );
    if (backlogIndex !== -1) backlogItems.value.splice(backlogIndex, 1);
    movedItem.sprint_id = targetSprintId;
    targetSprint.items.unshift(movedItem);
    await api.patch(
      `/boards/${props.boardId}/backlog/items/${movedItem.id}/move-to-sprint`,
      { sprint_id: targetSprintId },
    );
    await refreshData();
  } catch (err: any) {
    errorMessage.value =
      err.response?.data?.message ?? "Gagal memindahkan task";
    showError.value = true;
    backlogItems.value = originalBacklog;
    sprints.value = originalSprints;
  } finally {
    isMoving.value = false;
    draggingItem.value = null;
  }
};

const handleEpicChange = (_itemId: number, _epicId: number | null) => {
  refreshData();
};

onMounted(async () => {
  await ambilData();
});
</script>

<style scoped>
.backlog-wrap {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: white;
  min-height: 0;
}
.backlog-filters {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 28px;
  border-bottom: 1px solid rgba(130, 144, 164, 0.2);
  flex-wrap: wrap;
  flex-shrink: 0;
}
.avatar-filter-group {
  display: flex;
  gap: 4px;
}
.avatar-filter {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: #e5e7eb;
  border: 2px solid transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: border 0.15s;
}
.avatar-filter.active {
  border-color: #020f40;
  background: rgba(0, 52, 246, 0.1);
}
.avatar-filter:hover {
  border-color: #8290a4;
}
.filter-divider {
  width: 1px;
  height: 24px;
  background: rgba(130, 144, 164, 0.4);
  margin: 0 4px;
}
.backlog-content {
  flex: 1;
  overflow-y: auto;
  padding: 16px 28px 32px;
  min-height: 0;
}
.backlog-section {
  margin-bottom: 8px;
  border: 1px solid rgba(130, 144, 164, 0.2);
  border-radius: 8px;
  transition: all 0.2s ease;
}
.backlog-section.drag-over {
  background: rgba(124, 58, 237, 0.05);
  border: 2px dashed #7c3aed;
}
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  background: #f9fafb;
  border-bottom: 1px solid rgba(130, 144, 164, 0.15);
  gap: 8px;
  flex-wrap: nowrap;
}
.section-header-left {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  flex: 1;
  overflow: hidden;
  min-width: 0;
}
.section-header-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.sprint-name-input {
  font-family: "Barlow", sans-serif;
  font-weight: 800;
  font-size: 14px;
  color: #020f40;
  border: none;
  outline: none;
  background: transparent;
  padding: 2px 4px;
  border-radius: 4px;
  transition: background 0.15s;
  min-width: 80px;
  max-width: 200px;
}
.sprint-name-input:hover,
.sprint-name-input:focus {
  background: rgba(0, 52, 246, 0.05);
}
.section-title {
  font-family: "Barlow", sans-serif;
  font-weight: 800;
  font-size: 14px;
  color: #020f40;
}
.section-count {
  font-size: 12px;
  color: #8290a4;
}
.estimate-text {
  font-size: 11px;
  font-weight: 700;
  color: #8290a4;
  min-width: 28px;
  text-align: center;
}
.estimate-active {
  color: #020f40;
}
.item-list {
  padding: 4px 0;
}
.empty-section {
  padding: 16px 20px;
  font-size: 13px;
  color: #8290a4;
  text-align: center;
}
.link-text {
  color: #65a9ec;
  cursor: pointer;
  font-weight: 600;
}
.link-text:hover {
  text-decoration: underline;
}
.backlog-item-wrapper {
  cursor: grab;
  user-select: none;
}
.backlog-item-wrapper:active {
  cursor: grabbing;
}
</style>
