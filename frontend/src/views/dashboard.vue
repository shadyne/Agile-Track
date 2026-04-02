<template>
  <div class="dashboard-layout">
    <SidebarComponent
      v-model:active-view="activeView"
      v-model:active-space-id="activeSpaceId"
      v-model:active-board-id="activeBoardId"
      @open-recent="showRecent = true"
      @open-create-space="openCreateSpace"
      @open-edit-space="openEditSpace"
      @open-delete-space="openDeleteSpace"
      @open-create-board="openCreateBoard"
      @open-delete-board="openDeleteBoard"
      @space-selected="ambilData"
    />

    <div class="main-area">
      <TopbarComponent>
        <template #actions>
          <v-btn
            v-if="showCreateButton"
            color="second-btn"
            prepend-icon="mdi-plus"
            style="color: white"
            @click="handleCreateClick"
          >
            Create
          </v-btn>
        </template>
      </TopbarComponent>

      <div v-if="isOnBoardRoute" class="board-route-area">
        <RouterView @updateTab="currentTab = $event" />
      </div>

      <main v-else class="content-area">
        <div v-if="loading" class="flex items-center justify-center h-64">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <template v-else>
          <div class="summary-grid">
            <div
              v-for="card in summaryCards"
              :key="card.key"
              class="summary-card"
            >
              <div class="summary-icon">
                <v-icon :icon="card.icon" size="24" color="#020F40" />
              </div>
              <div class="summary-info">
                <span class="summary-count">
                  {{
                    dashboardData?.summary[
                      card.key as keyof DashboardSummary
                    ] ?? 0
                  }}
                  {{ card.label }}
                </span>
              </div>
            </div>
          </div>

          <div class="chart-grid">
            <div class="chart-card">
              <h3 class="chart-title">Priority Breakdown</h3>
              <div class="chart-wrap">
                <Bar
                  v-if="barChartData"
                  :data="barChartData"
                  :options="barOptions"
                />
              </div>
            </div>

            <div class="chart-card">
              <h3 class="chart-title">Status Overview</h3>
              <div class="chart-wrap">
                <Pie
                  v-if="pieChartData"
                  :data="pieChartData"
                  :options="pieOptions"
                />
              </div>
            </div>
          </div>
        </template>
      </main>
    </div>

    <!-- Modals -->
    <RecentActivityModal v-model="showRecent" :space-id="activeSpaceId" />
    <CreateSpaceModal
      v-model="showCreateSpace"
      :edit-data="editSpaceData"
      @created="onSpaceSaved"
    />
    <DeleteSpaceModal
      v-if="deleteSpaceData"
      v-model="showDeleteSpace"
      :space-id="deleteSpaceData.id"
      :space-name="deleteSpaceData.nama"
      @deleted="onSpaceDeleted"
    />
    <CreateBoardModal
      v-model="showCreateBoard"
      :space="selectedSpaceForBoard"
      @created="onBoardCreated"
    />
    <DeleteBoardModal
      v-if="deleteBoardData"
      v-model="showDeleteBoard"
      :space-id="deleteBoardData.spaceId"
      :board-id="deleteBoardData.boardId"
      :board-name="deleteBoardData.boardName"
      @deleted="onBoardDeleted"
    />

    <CreateEpicModal
      v-if="activeBoardId > 0"
      v-model="showCreateEpic"
      :board-id="activeBoardId"
      @created="onEpicCreated"
    />
    <CreateTaskModal
      v-if="activeBoardId > 0"
      v-model="showCreateTask"
      :board-id="activeBoardId"
      :board-members="boardMembers"
      :available-labels="availableLabels"
      @created="handleTaskCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { Bar, Pie } from "vue-chartjs";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
} from "chart.js";
import api from "../api/axios";
import { useAuthStore } from "../stores/auth";
import { useSpaceStore } from "@/stores/space";
import type {
  DashboardData,
  DashboardSummary,
  Space,
  Board,
  Epic,
  StatusItem,
} from "../types";
import "../assets/css/dashboard.css";

import RecentActivityModal from "@/components/RecentActivityModal.vue";
import CreateSpaceModal from "../components/CreateSpaceModal.vue";
import DeleteSpaceModal from "../components/DeleteSpaceModal.vue";
import CreateBoardModal from "../components/CreateBoardModal.vue";
import DeleteBoardModal from "../components/DeleteBoardModal.vue";
import TopbarComponent from "@/components/TopbarComponent.vue";
import SidebarComponent from "@/components/SidebarComponent.vue";
import CreateTaskModal from "../components/CreateTaskModal.vue";
import CreateEpicModal from "../components/CreateEpicModal.vue";

import { useWorkflowStore } from "../stores/workflow";
import { useRoute, useRouter } from "vue-router";

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
);

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const spaceStore = useSpaceStore();
const workflowStore = useWorkflowStore();

const activeView = ref<"dashboard" | "space">("dashboard");
const activeSpaceId = ref<number>(0);
const activeBoardId = ref<number>(0);
const showRecent = ref(false);
const showCreateSpace = ref(false);
const showDeleteSpace = ref(false);
const showCreateBoard = ref(false);
const showDeleteBoard = ref(false);
const showCreateEpic = ref(false);
const showCreateTask = ref(false);
const currentTab = ref<"timeline" | "backlog" | null>(null);
const editSpaceData = ref<Space | null>(null);
const deleteSpaceData = ref<Space | null>(null);
const selectedSpaceForBoard = ref<Space | null>(null);
const deleteBoardData = ref<{
  spaceId: number;
  boardId: number;
  boardName: string;
} | null>(null);

const loading = ref(false);
const dashboardData = ref<DashboardData | null>(null);

const isOnBoardRoute = computed(() => {
  return route.path.startsWith("/board") || route.path === "/workflow-settings";
});

const showCreateButton = computed(() => {
  return (
    isOnBoardRoute.value &&
    route.path.startsWith("/board") &&
    !route.path.includes("/epic/") &&
    !route.path.includes("/item/") &&
    currentTab.value !== null
  );
});

const boardMembers = computed(() => {
  if (!activeSpaceId.value) return [];
  const space = spaceStore.spaces.find((s) => s.id === activeSpaceId.value);
  if (!space) return [];
  const members = space.member_emails ?? [];
  const list = members.map((email: string, i: number) => ({
    id: i + 100,
    name: email.split("@")[0],
    email,
  }));
});

const availableLabels = ref<string[]>([]);

const summaryCards = [
  { key: "completed", label: "Completed", icon: "mdi-checkbox-marked-outline" },
  { key: "updated", label: "Updated", icon: "mdi-pencil-outline" },
  { key: "created", label: "Created", icon: "mdi-plus-box-outline" },
  { key: "due_soon", label: "Due Soon", icon: "mdi-calendar-clock-outline" },
];

const barChartData = computed(() => {
  if (!dashboardData.value) return null;
  const pb = dashboardData.value.priority_breakdown;
  return {
    labels: ["Highest", "High", "Medium", "Low", "Lowest"],
    datasets: [
      {
        label: "Tasks",
        data: [
          pb.highest ?? 0,
          pb.high ?? 0,
          pb.medium ?? 0,
          pb.low ?? 0,
          pb.lowest ?? 0,
        ],
        backgroundColor: "#020F40",
        borderRadius: 4,
      },
    ],
  };
});

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    y: {
      beginAtZero: true,
      max: 100,
      grid: { color: "rgba(130,144,164,0.2)" },
      ticks: { stepSize: 10, color: "#65A9EC", font: { size: 11 } },
    },
    x: {
      grid: { display: false },
      ticks: { color: "#65A9EC", font: { size: 11 } },
    },
  },
};

const pieChartData = computed(() => {
  if (!dashboardData.value) return null;

  const statusItems = dashboardData.value.status_overview;
  if (!statusItems || statusItems.length === 0) return null;

  return {
    labels: statusItems.map((item: StatusItem) => item.label),
    datasets: [
      {
        data: statusItems.map((item: StatusItem) => item.total),
        backgroundColor: statusItems.map((item: StatusItem) => item.color),
        borderWidth: 0,
      },
    ],
  };
});

const pieOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: "right" as const,
      labels: {
        font: { size: 12, family: "Nunito" },
        padding: 16,
        usePointStyle: true,
      },
    },
  },
};

const openCreateSpace = (): void => {
  editSpaceData.value = null;
  showCreateSpace.value = true;
};
const openEditSpace = (space: Space): void => {
  editSpaceData.value = space;
  showCreateSpace.value = true;
};
const openDeleteSpace = (space: Space): void => {
  deleteSpaceData.value = space;
  showDeleteSpace.value = true;
};
const openCreateBoard = (space: Space): void => {
  selectedSpaceForBoard.value = space;
  showCreateBoard.value = true;
};
const openDeleteBoard = (space: Space, board: Board): void => {
  deleteBoardData.value = {
    spaceId: space.id,
    boardId: board.id,
    boardName: board.nama,
  };
  showDeleteBoard.value = true;
};

const onSpaceSaved = (): void => {
  if (spaceStore.spaces.length > 0 && activeSpaceId.value === 0) {
    activeSpaceId.value = spaceStore.spaces[0]?.id ?? 0;
    ambilData();
  }
};
const onSpaceDeleted = (): void => {
  if (spaceStore.spaces.length > 0) {
    activeSpaceId.value = spaceStore.spaces[0]?.id ?? 0;
    ambilData();
  } else {
    activeSpaceId.value = 0;
    activeView.value = "dashboard";
    dashboardData.value = null;
  }
};
const onBoardCreated = (): void => {
  if (selectedSpaceForBoard.value) {
    spaceStore.ambilBoards(selectedSpaceForBoard.value.id);
  }
};
const onBoardDeleted = (): void => {
  activeBoardId.value = 0;
  router.push("/");
};

const handleCreateClick = (): void => {
  if (activeBoardId.value === 0) return;
  if (currentTab.value === "timeline") {
    showCreateEpic.value = true;
  } else if (currentTab.value === "backlog") {
    showCreateTask.value = true;
  }
};

const onEpicCreated = (epic: Epic): void => {
  console.log("Epic created:", epic.kode);
};

const handleTaskCreated = (task: any): void => {
  console.log("Task created:", task.kode);
};

watch(
  () => route.params.boardId,
  (id) => {
    if (id) activeBoardId.value = Number(id);
  },
  { immediate: true },
);

watch(
  () => route.path,
  (path) => {
    if (!path.startsWith("/board")) {
      currentTab.value = null;
    }
  },
);

const ambilData = async (): Promise<void> => {
  loading.value = true;
  try {
    let res;
    if (activeView.value === "dashboard" || !activeSpaceId.value) {
      res = await api.get("/dashboard");
    } else {
      res = await api.get(`/spaces/${activeSpaceId.value}/dashboard`);
    }
    dashboardData.value = res.data;
  } catch (err) {
    console.error("Gagal ambil data dashboard:", err);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await spaceStore.ambilSpaces();
  await workflowStore.fetchStatuses();
  activeView.value = "dashboard";
  if (spaceStore.spaces.length > 0) {
    activeSpaceId.value = spaceStore.spaces[0]?.id ?? 0;
    if (activeSpaceId.value) ambilData();
  }
});
</script>

<style scoped>
.board-route-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-height: 0;
}
</style>
