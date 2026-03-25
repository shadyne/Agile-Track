<template>
  <div class="dashboard-layout">
    <SidebarComponent
      v-model:active-view="activeView"
      v-model:active-space-id="activeSpaceId"
      :active-board-id="activeBoardId"
      @open-recent="showRecent = true"
      @open-create-space="openCreateSpace"
      @open-edit-space="openEditSpace"
      @open-delete-space="openDeleteSpace"
      @open-create-board="openCreateBoard"
      @open-delete-board="openDeleteBoard"
      @space-selected="ambilData"
    />

    <div class="main-area">
      <TopbarComponent />

      <main class="content-area">
        <div v-if="loading" class="flex items-center justify-center h-64">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <template v-else>
          <div class="summary-grid">
            <div
              class="summary-card"
              v-for="card in summaryCards"
              :key="card.key"
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
                <span class="summary-sub">In the last 7 days</span>
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
            <RecentActivityModal
              v-model="showRecent"
              :space-id="activeSpaceId"
            />
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
          </div>
        </template>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { RouterLink, useRouter } from "vue-router";
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
import type { DashboardData, DashboardSummary, Space, Board } from "../types";
import "../assets/css/dashboard.css";
import RecentActivityModal from "@/components/RecentActivityModal.vue";
import CreateSpaceModal from "../components/CreateSpaceModal.vue";
import DeleteSpaceModal from "../components/DeleteSpaceModal.vue";
import CreateBoardModal from "../components/CreateBoardModal.vue";
import DeleteBoardModal from "../components/DeleteBoardModal.vue";
import { useSpaceStore } from "@/stores/space";
import TopbarComponent from "@/components/TopbarComponent.vue";
import SidebarComponent from "@/components/SidebarComponent.vue";

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
);

const SPACE_ID = 1;
const router = useRouter();
const authStore = useAuthStore();
const spaceStore = useSpaceStore();

const activeView = ref<"dashboard" | "space">("dashboard");

const showRecent = ref<boolean>(false);
const showCreateSpace = ref<boolean>(false);
const showDeleteSpace = ref<boolean>(false);
const showCreateBoard = ref<boolean>(false);
const showDeleteBoard = ref<boolean>(false);
const spacesExpanded = ref<boolean>(true);
const expandedSpaces = ref<number[]>([]);
const editSpaceData = ref<Space | null>(null);
const deleteSpaceData = ref<Space | null>(null);
const selectedSpaceForBoard = ref<Space | null>(null);
const deleteBoardData = ref<{
  spaceId: number;
  boardId: number;
  boardName: string;
} | null>(null);
const activeSpaceId = ref<number>(0);
const activeBoardId = ref<number>(0);
const searchQuery = ref<string>("");
const loading = ref<boolean>(false);
const dashboardData = ref<DashboardData | null>(null);

const userInitial = computed<string>(
  () => authStore.user?.name?.charAt(0).toUpperCase() || "U",
);

const toggleSpace = (spaceId: number): void => {
  activeSpaceId.value = spaceId;
  activeView.value = "space";

  const idx = expandedSpaces.value.indexOf(spaceId);
  if (idx === -1) {
    expandedSpaces.value.push(spaceId);
    spaceStore.ambilBoards(spaceId);
  } else {
    expandedSpaces.value.splice(idx, 1);
  }

  ambilData();
};

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
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: {
      beginAtZero: true,
      max: 100,
      grid: { color: "rgba(130,144,164,0.2)" },
      ticks: {
        stepSize: 10,
        color: "#65A9EC",
        font: { size: 11 },
      },
    },
    x: {
      grid: { display: false },
      ticks: { color: "#65A9EC", font: { size: 11 } },
    },
  },
};

const pieChartData = computed(() => {
  if (!dashboardData.value) return null;
  const so = dashboardData.value.status_overview;
  return {
    labels: ["To Do", "In Progress", "Done by Dev", "Testing", "Done by QA"],
    datasets: [
      {
        data: [
          so.to_do ?? 0,
          so.in_progress ?? 0,
          so.done_by_dev ?? 0,
          so.testing ?? 0,
          so.done_by_qa ?? 0,
        ],
        backgroundColor: [
          "#DC3434",
          "#29B6F6",
          "#43A047",
          "#FBC02D",
          "#7B1FA2",
        ],
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

const toggleExpandSpace = (spaceId: number): void => {
  const idx = expandedSpaces.value.indexOf(spaceId);
  if (idx === -1) {
    expandedSpaces.value.push(spaceId);
    spaceStore.ambilBoards(spaceId);
  } else {
    expandedSpaces.value.splice(idx, 1);
  }
};

const pilihSpace = (id: number): void => {
  activeSpaceId.value = id;
  activeView.value = "space";
  if (!expandedSpaces.value.includes(id)) {
    expandedSpaces.value.push(id);
    spaceStore.ambilBoards(id);
  }
  ambilData();
};

const pilihBoard = (board: Board): void => {
  activeBoardId.value = board.id;
  router.push(`/board/${board.id}`);
};

const goToBoardSetting = (board: Board): void => {
  // router.push(`/board/${board.id}/setting`)
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
    const spaceId = selectedSpaceForBoard.value.id;
    if (!expandedSpaces.value.includes(spaceId)) {
      expandedSpaces.value.push(spaceId);
    }
  }
};

const onBoardDeleted = (): void => {
  activeBoardId.value = 0;
};

const ambilData = async (): Promise<void> => {
  loading.value = true;

  try {
    let res;

    if (activeView.value === "dashboard") {
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
  activeView.value = "dashboard";
  if (spaceStore.spaces.length > 0) {
    activeSpaceId.value = spaceStore.spaces[0]?.id ?? 0;
    if (activeSpaceId.value) ambilData();
  }
});
</script>
