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
            <span class="estimate-text">0m</span>
            <span class="estimate-text estimate-active">{{
              totalEstimate(sprint.items)
            }}</span>
            <span class="estimate-text">0m</span>

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
          <BacklogItemRow
            v-for="item in sprint.items"
            :key="item.id"
            :item="item"
            @status-change="(status) => updateItemStatus(item, status)"
          />
          <div v-if="sprint.items.length === 0" class="empty-section">
            Belum ada task. Drag item dari backlog atau
            <span class="link-text" @click="openCreateTask(sprint.id)"
              >buat task baru</span
            >
          </div>
        </div>
      </div>

      <div class="backlog-section">
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
            <span class="estimate-text">{{
              totalEstimate(filteredBacklog)
            }}</span>
            <span class="estimate-text">0m</span>
            <span class="estimate-text">0m</span>
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
          <BacklogItemRow
            v-for="item in filteredBacklog"
            :key="item.id"
            :item="item"
            @status-change="(status) => updateItemStatus(item, status)"
          />
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
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import api from "../api/axios";
import type {
  BacklogData,
  EpicItem,
  Sprint,
  Epic,
  EpicItemStatus,
} from "../types";
import CreateTaskModal from "./CreateTaskModal.vue";
import BacklogItemRow from "./Backlogitemrow.vue";

const props = defineProps<{
  boardId: number;
  epics: Epic[];
  boardMembers: { id: number; name: string; email: string }[];
}>();

const emit = defineEmits<{
  (e: "refresh"): void;
}>();

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
const startSprintForm = ref({ start_date: "", end_date: "" });
const addTask = (task: any) => {
  backlogItems.value.unshift(task);
};

defineExpose({
  addTask,
});
const allLabels = computed<string[]>(() => {
  const set = new Set<string>();
  [...backlogItems.value, ...sprints.value.flatMap((s) => s.items)].forEach(
    (item) => {
      item.labels?.forEach((l) => set.add(l));
    },
  );
  return ["All", ...Array.from(set)];
});

const epicFilterOptions = computed(() => [
  "All",
  ...props.epics.map((e) => e.judul),
]);

const typeFilterOptions = ["All", "Task", "Story", "Bug", "QA Task"];
const labelFilterOptions = allLabels;

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
    if (filterLabel.value !== "All") {
      if (!item.labels?.includes(filterLabel.value)) return false;
    }
    return true;
  });
};

const availableLabels = computed(() => {
  const labels = backlogItems.value.flatMap((item) => item.labels || []);
  return [...new Set(labels)];
});

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

const toggleSection = (id: number) => {
  const idx = expandedSections.value.indexOf(id);
  if (idx === -1) expandedSections.value.push(id);
  else expandedSections.value.splice(idx, 1);
};

const sprintDateRange = (sprint: Sprint): string => {
  if (!sprint.start_date || !sprint.end_date) return "";
  const fmt = (d: string) => {
    const date = new Date(d);
    return `${date.getDate()} ${date.toLocaleString("en", { month: "short" })}`;
  };
  return `${fmt(sprint.start_date)} – ${fmt(sprint.end_date)}`;
};

const totalEstimate = (items: EpicItem[]): string => {
  const total = items.reduce((sum, i) => {
    if (!i.original_estimate) return sum;
    const match = i.original_estimate.match(/(\d+)h/);
    return sum + (match ? parseInt(match[1]) : 0);
  }, 0);
  return total > 0 ? `${total}h` : "0m";
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
    console.error("Gagal ambil backlog:", err);
  } finally {
    loading.value = false;
  }
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
};

const openCreateSprint = async () => {
  try {
    const count = sprints.value.length + 1;
    const res = await api.post<{ data: Sprint }>(
      `/boards/${props.boardId}/sprints`,
      {
        nama: `Sprint ${count}`,
      },
    );
    const newSprint = res.data.data;
    newSprint.items = [];
    sprints.value.push(newSprint);
    expandedSections.value.push(newSprint.id);
  } catch (err) {
    console.error("Gagal buat sprint:", err);
  }
};

const openStartSprint = (sprint: Sprint) => {
  selectedSprint.value = sprint;
  startSprintForm.value = { start_date: "", end_date: "" };
  showStartSprint.value = true;
};

const confirmStartSprint = async () => {
  if (!selectedSprint.value) return;
  startingSprintLoading.value = true;
  try {
    const res = await api.post<{ data: Sprint }>(
      `/boards/${props.boardId}/sprints/${selectedSprint.value.id}/start`,
      startSprintForm.value,
    );
    const idx = sprints.value.findIndex(
      (s) => s.id === selectedSprint.value!.id,
    );
    if (idx !== -1) sprints.value[idx].status = "active";
    showStartSprint.value = false;
  } catch (err) {
    console.error("Gagal start sprint:", err);
  } finally {
    startingSprintLoading.value = false;
  }
};

const completeSprint = async (sprint: Sprint) => {
  try {
    await api.post(`/boards/${props.boardId}/sprints/${sprint.id}/complete`);
    const undone = sprint.items.filter((i) => i.status !== "done_by_qa");
    backlogItems.value.push(...undone);
    sprints.value = sprints.value.filter((s) => s.id !== sprint.id);
  } catch (err) {
    console.error("Gagal complete sprint:", err);
  }
};

const deleteSprint = async (sprint: Sprint) => {
  try {
    await api.delete(`/boards/${props.boardId}/sprints/${sprint.id}`);
    backlogItems.value.push(...sprint.items);
    sprints.value = sprints.value.filter((s) => s.id !== sprint.id);
  } catch (err) {
    console.error("Gagal hapus sprint:", err);
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

const updateItemStatus = async (item: EpicItem, status: EpicItemStatus) => {
  try {
    await api.patch(
      `/boards/${props.boardId}/backlog/items/${item.id}/status`,
      { status },
    );
    item.status = status;
  } catch (err) {
    console.error("Gagal update status:", err);
  }
};

onMounted(ambilData);
</script>

<style scoped>
.backlog-wrap {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: white;
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
}

.backlog-section {
  margin-bottom: 8px;
  border: 1px solid rgba(130, 144, 164, 0.2);
  border-radius: 8px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  background: #f9fafb;
  border-bottom: 1px solid rgba(130, 144, 164, 0.15);
  gap: 8px;
}

.section-header-left {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  flex: 1;
  overflow: hidden;
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
</style>
