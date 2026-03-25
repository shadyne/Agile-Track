<template>
  <div class="dashboard-layout">
    <div class="main-area">
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
            label="Status Category"
            :items="statusOptions"
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

            <div class="timeline-section-label">
              <div class="section-name">Sprints</div>
              <div class="section-bar-area"></div>
            </div>

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

                  <!-- Status Badge -->
                  <span class="status-badge" :class="`status-${epic.status}`">
                    {{ getStatusLabel(epic.status) }}
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
                <div
                  v-for="item in epic.items"
                  :key="item.id"
                  class="child-row"
                >
                  <div class="child-row-left">
                    <input type="checkbox" class="row-checkbox" />

                    <span class="child-indent" />

                    <v-icon
                      :icon="getTypeIcon(item.type)"
                      size="13"
                      :color="getTypeColor(item.type)"
                    />

                    <RouterLink
                      :to="`/board/${boardId}/epic/${epic.id}`"
                      class="epic-key"
                    >
                      {{ item.kode }}
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

                    <span class="status-badge" :class="`status-${item.status}`">
                      {{ getStatusLabel(item.status) }}
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
                        getBarStyle(epic.start_date, epic.end_date, epic.labels)
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

      <template v-else-if="activeTab === 'backlog'">
        <BacklogView
          ref="backlogRef"
          :board-id="boardId"
          :epics="epics"
          :board-members="boardMembers"
        />
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useAuthStore } from "../stores/auth";
import api from "../api/axios";
import type { Epic, Board, Space } from "../types";
import "../assets/css/dashboard.css";
import "../assets/css/board.css";
import TopbarComponent from "@/components/TopbarComponent.vue";
import SidebarComponent from "@/components/SidebarComponent.vue";
import BacklogView from "../components/BacklogComponent.vue";

const route = useRoute();
const authStore = useAuthStore();
const boardId = Number(route.params.boardId);

const board = ref<Board | null>(null);
const epics = ref<Epic[]>([]);
const loadingEpics = ref<boolean>(false);
const activeTab = ref<"timeline" | "backlog">("timeline");
const expandedEpics = ref<number[]>([]);
const viewMode = ref<string>("weeks");
const showCreateTask = ref<boolean>(false);
const timelineRef = ref<HTMLElement | null>(null);
const filterUserId = ref<number | null>(null);
const activeView = ref<"dashboard" | "space">("space");
const activeSpaceId = ref<number>(0);
const showCreateEpic = ref<boolean>(false);
const showRecent = ref<boolean>(false);
const showCreateSpace = ref<boolean>(false);
const editSpaceData = ref<Space | null>(null);
const deleteSpaceData = ref<Space | null>(null);
const selectedSpace = ref<Space | null>(null);
const backlogRef = ref();
const deleteBoardData = ref<{
  spaceId: number;
  boardId: number;
  boardName: string;
} | null>(null);

const filterEpic = ref("All");
const filterType = ref("All");
const filterLabel = ref("All");
const filterStatus = ref("All");

const emit = defineEmits<{
  (e: "updateTab", val: "timeline" | "backlog"): void;
}>();

watch(activeTab, (val) => {
  emit("updateTab", val);
});

const viewModes = [
  { label: "Today", value: "today" },
  { label: "Weeks", value: "weeks" },
  { label: "Months", value: "months" },
  { label: "Quarters", value: "quarters" },
];

const statusOptions = [
  "All",
  "To Do",
  "In Progress",
  "Done by Dev",
  "Testing",
  "Done by QA",
];

const filterUser = ref<number | "All">("All");

const assigneeOptions = computed(() => {
  const users = epics.value
    .map((e) => ({
      id: e.assignee_id,
      name: e.assignee?.name,
    }))
    .filter((u) => u.id);

  const unique = Array.from(new Map(users.map((u) => [u.id, u])).values());

  return [
    { title: "All", value: "All" },
    ...unique.map((u) => ({
      title: u.name ?? `User ${u.id}`,
      value: u.id,
    })),
  ];
});

const handleTaskCreated = (task: any) => {
  backlogRef.value?.addTask(task);
};

const handleCreateClick = () => {
  if (activeTab.value === "timeline") {
    showCreateEpic.value = true;
  } else if (activeTab.value === "backlog") {
    showCreateTask.value = true;
  }
};

const boardMembers = computed(() => {
  const members = board.value?.member_emails ?? [];
  const list = members.map((email: string, i: number) => ({
    id: i + 100,
    name: email.split("@")[0],
    email,
  }));
  if (authStore.user) {
    list.unshift({
      id: authStore.user.id,
      name: authStore.user.name,
      email: authStore.user.email,
    });
  }
  return list;
});
const userInitial = computed<string>(
  () => authStore.user?.name?.charAt(0).toUpperCase() || "U",
);

const toggleUserFilter = (userId: number) => {
  filterUserId.value = filterUserId.value === userId ? null : userId;
};

const epicOptions = computed(() => {
  return ["All", ...epics.value.map((e) => e.judul)];
});

const allLabels = computed<string[]>(() => {
  const labels = epics.value
    .map((e) => e.label)
    .filter((l): l is string => !!l);
  return ["All", ...new Set(labels)];
});

const filteredEpics = computed<Epic[]>(() => {
  return epics.value.filter((epic) => {
    if (filterEpic.value !== "All" && epic.judul !== filterEpic.value) {
      return false;
    }

    if (filterStatus.value !== "All") {
      const map: Record<string, string> = {
        "To Do": "to_do",
        "In Progress": "in_progress",
        "Done by Dev": "done_by_dev",
        Testing: "testing",
        "Done by QA": "done_by_qa",
      };
      const mapped = map[filterStatus.value];
      if (!mapped || epic.status !== mapped) return false;
    }

    if (
      filterLabel.value !== "All" &&
      epic.label?.toLowerCase() !== filterLabel.value.toLowerCase()
    ) {
      return false;
    }
    if (filterUser.value !== "All") {
      if (epic.assignee_id !== filterUser.value) {
        return false;
      }
    }

    if (
      filterType.value !== "All" &&
      epic.type?.toLowerCase() !== filterType.value.toLowerCase()
    ) {
      return false;
    }

    return true;
  });
});
const getStatusLabel = (status: string): string => {
  const map: Record<string, string> = {
    to_do: "TO DO",
    in_progress: "IN PROGRESS",
    done_by_dev: "DONE BY DEV",
    testing: "TESTING",
    done_by_qa: "DONE BY QA",
  };
  return map[status] ?? status.toUpperCase();
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

const timelineStart = computed(() => {
  const d = new Date();
  if (viewMode.value === "today") {
    d.setDate(d.getDate() - 3);
  } else if (viewMode.value === "weeks") {
    d.setDate(d.getDate() - 14);
  } else if (viewMode.value === "months") {
    d.setMonth(d.getMonth() - 1);
  } else {
    d.setMonth(d.getMonth() - 3);
  }
  return d;
});

const timelineEnd = computed(() => {
  const d = new Date();
  if (viewMode.value === "today") {
    d.setDate(d.getDate() + 14);
  } else if (viewMode.value === "weeks") {
    d.setDate(d.getDate() + 30);
  } else if (viewMode.value === "months") {
    d.setMonth(d.getMonth() + 3);
  } else {
    d.setMonth(d.getMonth() + 9);
  }
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
      const monthNames = [
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
      const label =
        current.getDate() <= 15
          ? `${monthNames[current.getMonth()]}`
          : `${monthNames[current.getMonth()]} / ${monthNames[(current.getMonth() + 1) % 12]}`;

      month = { key: monthKey, label, days: [] };
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
  };
  return icons[type] || "mdi-circle-outline";
};

const getTypeColor = (type: string): string => {
  const colors: Record<string, string> = {
    epic: "#7C3AED",
    story: "#059669",
    task: "#1D4ED8",
    bug: "#DC2626",
  };
  return colors[type] || "#8290A4";
};

const getFirstLabel = (labels: string[]): string | null => {
  return labels?.[0] ?? null;
};

const getLabelColor = (label: string): string => {
  const colors = ["#B45309", "#059669", "#1D4ED8", "#7C3AED", "#DC2626"];
  return colors[label.charCodeAt(0) % colors.length];
};
const toggleExpandEpic = (epicId: number): void => {
  const idx = expandedEpics.value.indexOf(epicId);
  if (idx === -1) {
    expandedEpics.value.push(epicId);
  } else {
    expandedEpics.value.splice(idx, 1);
  }
};

const onEpicCreated = (epic: Epic): void => {
  epics.value.push(epic);
};

const ambilBoard = async (): Promise<void> => {
  try {
    const res = await api.get<Board>(`/boards/${boardId}`);
    board.value = res.data;
  } catch (err) {
    console.error("Gagal ambil board:", err);
  }
};

const ambilEpics = async (): Promise<void> => {
  loadingEpics.value = true;
  try {
    const res = await api.get<Epic[]>(`/boards/${boardId}/epics`);
    epics.value = res.data;
    console.log("EPICS:", res.data);
  } catch (err) {
    console.error("Gagal ambil epics:", err);
  } finally {
    loadingEpics.value = false;
  }
};

onMounted(() => {
  ambilBoard();
  ambilEpics();
});
</script>
