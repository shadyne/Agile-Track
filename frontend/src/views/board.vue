<template>
  <div class="board-main">
    <div class="board-title-area">
      <h1 class="board-title">{{ board?.nama?.toUpperCase() }}</h1>
    </div>

    <div class="board-tabs">
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'timeline' }"
        @click="activeTab = 'timeline'"
      >
        Timeline
      </button>
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'backlog' }"
        @click="activeTab = 'backlog'"
      >
        Backlog
      </button>
    </div>

    <template v-if="activeTab === 'timeline'">
      <div class="timeline-filters">
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
          :items="epicOptions"
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 120px"
        />
        <v-select
          v-model="filterType"
          label="Type"
          :items="['All', 'Story', 'Task', 'Bug']"
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 120px"
        />
        <v-select
          v-model="filterLabel"
          label="Label"
          :items="allLabels"
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 130px"
        />
        <v-select
          v-model="filterStatus"
          label="Status"
          :items="['All', ...workflowStore.statuses.map((s) => s.label)]"
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 180px"
        />
        <v-select
          v-model="filterUser"
          label="Assignee"
          :items="assigneeOptions"
          item-title="title"
          item-value="value"
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 150px"
        />
      </div>

      <div v-if="loadingEpics" class="flex items-center justify-center h-64">
        <v-progress-circular indeterminate color="primary" />
      </div>

      <div v-else class="timeline-container" ref="timelineRef">
        <div class="timeline-table">
          <div class="timeline-header">
            <div class="work-col">Work</div>
            <div class="date-cols">
              <div
                v-for="month in timelineMonths"
                :key="month.key"
                class="month-group"
              >
                <div class="month-label">{{ month.label }}</div>
                <div class="day-labels">
                  <div
                    v-for="day in month.days"
                    :key="day.date"
                    class="day-cell"
                    :class="{ today: day.isToday }"
                  >
                    {{ day.num }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- 
          <div class="timeline-section-label">
            <div class="section-name">Sprints</div>
            <div class="section-bar-area"></div>
          </div> -->

          <div class="timeline-section-label">
            <div class="section-name">Releases</div>
            <div class="section-bar-area"></div>
          </div>

          <template v-for="epic in filteredEpics" :key="epic.id">
            <div class="epic-row">
              <div class="epic-row-left">
                <input type="checkbox" class="row-checkbox" />
                <span class="expand-btn" @click="toggleExpandEpic(epic.id)">
                  <v-icon
                    :icon="
                      expandedEpics.includes(epic.id)
                        ? 'mdi-chevron-down'
                        : 'mdi-chevron-right'
                    "
                    size="14"
                    :color="epic.items?.length ? '#020F40' : '#ccc'"
                  />
                </span>
                <v-icon
                  class="epic-type-icon"
                  :icon="getTypeIcon(epic.type)"
                  size="14"
                  :color="getTypeColor(epic.type)"
                />
                <RouterLink
                  :to="`/board/${boardId}/epic/${epic.id}`"
                  class="epic-key"
                >
                  {{ epic.kode }}
                </RouterLink>
                <template v-if="epic.labels?.length">
                  <span
                    v-for="lbl in epic.labels"
                    :key="lbl"
                    class="epic-label-tag"
                    :style="{
                      color: getLabelColor(lbl),
                      borderColor: getLabelColor(lbl),
                    }"
                  >
                    {{ lbl }}
                  </span>
                </template>

                <span
                  class="status-badge"
                  :style="workflowStore.getStatusStyle(epic.status)"
                >
                  {{ workflowStore.getStatusLabel(epic.status) }}
                </span>

                <span
                  class="priority-dot"
                  :style="{
                    backgroundColor: getPriorityColor(epic.priority),
                  }"
                  :title="epic.priority"
                />
              </div>

              <div class="epic-bar-area">
                <div
                  v-if="epic.start_date && epic.end_date"
                  class="gantt-bar"
                  :style="
                    getBarStyle(epic.start_date, epic.end_date, epic.labels)
                  "
                  :title="`${epic.start_date} → ${epic.end_date}`"
                />
                <div
                  v-else-if="isOutOfRange(epic)"
                  class="gantt-bar out-of-range"
                >
                  ←
                </div>
              </div>
            </div>

            <template v-if="expandedEpics.includes(epic.id)">
              <div v-for="item in epic.items" :key="item.id" class="child-row">
                <div class="child-row-left">
                  <input type="checkbox" class="row-checkbox" />
                  <span class="child-indent" />
                  <v-icon
                    :icon="getTypeIcon(item.type)"
                    size="13"
                    :color="getTypeColor(item.type)"
                  />
                  <RouterLink
                    :to="`/board/${boardId}/item/${item.id}`"
                    class="epic-key"
                  >
                    {{ item.kode }}
                  </RouterLink>
                  <span class="epic-judul">{{ item.judul }}</span>
                  <span
                    class="status-badge"
                    :style="workflowStore.getStatusStyle(item.status)"
                  >
                    {{ workflowStore.getStatusLabel(item.status) }}
                  </span>
                  <span
                    class="priority-dot"
                    :style="{
                      backgroundColor: getPriorityColor(item.priority),
                    }"
                    :title="item.priority"
                  />
                </div>
                <div class="epic-bar-area">
                  <div
                    v-if="item.start_date && item.end_date"
                    class="gantt-bar"
                    :style="
                      getBarStyle(item.start_date, item.end_date, epic.labels)
                    "
                  />
                </div>
              </div>
            </template>
          </template>
        </div>
      </div>

      <div class="view-controls">
        <button
          v-for="v in viewModes"
          :key="v.value"
          class="view-btn"
          :class="{ active: viewMode === v.value }"
          @click="viewMode = v.value"
        >
          {{ v.label }}
        </button>
        <v-btn icon="mdi-information-outline" variant="text" size="small" />
        <v-btn icon="mdi-chevron-right" variant="text" size="small" />
      </div>
    </template>

    <!-- BACKLOG TAB -->
    <template v-else-if="activeTab === 'backlog'">
      <BacklogView
        ref="backlogRef"
        :board-id="boardId"
        :epics="epics"
        :board-members="boardMembers"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useWorkflowStore } from "../stores/workflow";
import api from "../api/axios";
import type { Epic, Board } from "../types";
import "../assets/css/dashboard.css";
import "../assets/css/board.css";
import BacklogView from "../components/BacklogComponent.vue";

const route = useRoute();
const authStore = useAuthStore();
const workflowStore = useWorkflowStore();
const boardId = computed(() => Number(route.params.boardId));

const board = ref<Board | null>(null);
const epics = ref<Epic[]>([]);
const loadingEpics = ref<boolean>(false);
const activeTab = ref<"timeline" | "backlog">("timeline");
const expandedEpics = ref<number[]>([]);
const viewMode = ref<string>("weeks");
const timelineRef = ref<HTMLElement | null>(null);
const filterUserId = ref<number | null>(null);
const backlogRef = ref();

const filterEpic = ref("All");
const filterType = ref("All");
const filterLabel = ref("All");
const filterStatus = ref("All");
const filterUser = ref<number | "All">("All");

onMounted(async () => {
  const tabParam = route.query.tab as string;
  if (tabParam === "backlog") {
    activeTab.value = "backlog";
  }
  await workflowStore.fetchStatuses();
  await ambilBoard();
  await ambilEpics();
});

watch(
  () => route.params.boardId,
  async () => {
    expandedEpics.value = [];
    await ambilBoard();
    await ambilEpics();
  },
);

const emit = defineEmits<{
  (e: "updateTab", val: "timeline" | "backlog"): void;
}>();

watch(
  activeTab,
  (val) => {
    emit("updateTab", val);
  },
  { immediate: true },
);

const viewModes = [
  { label: "Today", value: "today" },
  { label: "Weeks", value: "weeks" },
  { label: "Months", value: "months" },
  { label: "Quarters", value: "quarters" },
];

const boardMembers = computed(() => {
  const members = board.value?.member_emails ?? [];
  const list = members.map((email: string, i: number) => ({
    id: i + 100,
    name: email.split("@")[0],
    email,
  }));
  return list;
});

console.log("member", boardMembers);

const toggleUserFilter = (userId: number) => {
  filterUserId.value = filterUserId.value === userId ? null : userId;
};

const epicOptions = computed(() => ["All", ...epics.value.map((e) => e.judul)]);

const allLabels = computed<string[]>(() => {
  const labels = epics.value.flatMap((e) => e.labels ?? []);
  return ["All", ...new Set(labels)];
});

const assigneeOptions = computed(() => {
  const users = epics.value
    .map((e) => ({ id: e.assignee_id, name: e.assignee?.name }))
    .filter((u) => u.id);
  const unique = Array.from(new Map(users.map((u) => [u.id, u])).values());
  return [
    { title: "All", value: "All" },
    ...unique.map((u) => ({ title: u.name ?? `User ${u.id}`, value: u.id })),
  ];
});

const filteredEpics = computed<Epic[]>(() => {
  return epics.value.filter((epic) => {
    if (filterEpic.value !== "All" && epic.judul !== filterEpic.value)
      return false;
    if (filterStatus.value !== "All") {
      const statusLabel = workflowStore.getStatusLabel(epic.status);
      if (statusLabel !== filterStatus.value) return false;
    }
    if (filterLabel.value !== "All") {
      if (!epic.labels?.includes(filterLabel.value)) return false;
    }
    if (filterUser.value !== "All" && epic.assignee_id !== filterUser.value)
      return false;
    if (
      filterType.value !== "All" &&
      epic.type?.toLowerCase() !== filterType.value.toLowerCase()
    )
      return false;
    return true;
  });
});

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

const timelineStart = computed(() => {
  const d = new Date();
  if (viewMode.value === "today") d.setDate(d.getDate() - 3);
  else if (viewMode.value === "weeks") d.setDate(d.getDate() - 14);
  else if (viewMode.value === "months") d.setMonth(d.getMonth() - 1);
  else d.setMonth(d.getMonth() - 3);
  return d;
});

const timelineEnd = computed(() => {
  const d = new Date();
  if (viewMode.value === "today") d.setDate(d.getDate() + 14);
  else if (viewMode.value === "weeks") d.setDate(d.getDate() + 30);
  else if (viewMode.value === "months") d.setMonth(d.getMonth() + 3);
  else d.setMonth(d.getMonth() + 9);
  return d;
});

const timelineMonths = computed(() => {
  const months: {
    key: string;
    label: string;
    days: { num: number; date: string; isToday: boolean }[];
  }[] = [];
  const today = new Date().toISOString().split("T")[0];
  let current = new Date(timelineStart.value);
  while (current <= timelineEnd.value) {
    const monthKey = `${current.getFullYear()}-${current.getMonth()}`;
    let month = months.find((m) => m.key === monthKey);
    if (!month) {
      const names = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ];
      month = { key: monthKey, label: names[current.getMonth()], days: [] };
      months.push(month);
    }
    const dateStr = current.toISOString().split("T")[0]!;
    month.days.push({
      num: current.getDate(),
      date: dateStr,
      isToday: dateStr === today,
    });
    current.setDate(current.getDate() + 1);
  }
  return months;
});

const DAY_WIDTH = 32;

const getBarStyle = (startDate: string, endDate: string, labels: string[]) => {
  const start = new Date(startDate);
  const end = new Date(endDate);
  const tlStart = timelineStart.value;
  const startOffset = Math.floor(
    (start.getTime() - tlStart.getTime()) / (1000 * 60 * 60 * 24),
  );
  const duration = Math.max(
    1,
    Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)),
  );
  const colors = [
    "#B45309",
    "#1D4ED8",
    "#059669",
    "#7C3AED",
    "#DC2626",
    "#D97706",
    "#0891B2",
  ];
  const firstLabel = labels?.[0] ?? null;
  const colorIdx = firstLabel ? firstLabel.charCodeAt(0) % colors.length : 0;
  return {
    left: `${Math.max(0, startOffset * DAY_WIDTH)}px`,
    width: `${duration * DAY_WIDTH}px`,
    backgroundColor: colors[colorIdx],
  };
};

const isOutOfRange = (epic: Epic): boolean => {
  if (!epic.end_date) return false;
  return new Date(epic.end_date) < timelineStart.value;
};

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

const getLabelColor = (label: string): string => {
  const colors = ["#B45309", "#059669", "#1D4ED8", "#7C3AED", "#DC2626"];
  return colors[label.charCodeAt(0) % colors.length];
};

const toggleExpandEpic = (epicId: number): void => {
  const idx = expandedEpics.value.indexOf(epicId);
  if (idx === -1) expandedEpics.value.push(epicId);
  else expandedEpics.value.splice(idx, 1);
};

const ambilBoard = async (): Promise<void> => {
  try {
    const res = await api.get<Board>(`/boards/${boardId.value}`);
    board.value = res.data;
  } catch (err) {
    console.error("Gagal ambil board:", err);
  }
};

const ambilEpics = async (): Promise<void> => {
  loadingEpics.value = true;
  try {
    const res = await api.get<Epic[]>(`/boards/${boardId.value}/epics`);
    epics.value = res.data;
  } catch (err) {
    console.error("Gagal ambil epics:", err);
  } finally {
    loadingEpics.value = false;
  }
};
</script>

<style scoped>
.board-main {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  background: white;
}
</style>
