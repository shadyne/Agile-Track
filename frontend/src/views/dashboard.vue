<template>
  <div class="dashboard-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <span>AGILE</span>
        <v-icon icon="mdi-approximately-equal" size="20" />
        <span>TRACK</span>
      </div>

      <nav class="sidebar-menu">
        <RouterLink
          to="/"
          class="menu-item"
          :class="{ active: activeView === 'dashboard' }"
          @click="activeView = 'dashboard'"
        >
          <v-icon icon="mdi-view-dashboard-outline" size="18" />
          Dashboard
        </RouterLink>

        <a class="menu-item" @click="showRecent = true" style="cursor: pointer">
          <v-icon icon="mdi-clock-outline" size="18" />
          Recent
        </a>

        <div class="spaces-header" :class="{ active: spacesExpanded }">
          <div
            class="spaces-header-left"
            @click="spacesExpanded = !spacesExpanded"
          >
            <v-icon
              :icon="spacesExpanded ? 'mdi-chevron-down' : 'mdi-chevron-right'"
              size="16"
            />
            <v-icon icon="mdi-square-outline" size="16" />
            <span>Spaces</span>
          </div>
          <v-btn
            icon="mdi-plus"
            variant="text"
            density="compact"
            size="x-small"
            color="#020f40"
            @click.stop="openCreateSpace"
          />
        </div>

        <div v-if="spacesExpanded" class="space-list">
          <div
            v-for="space in spaceStore.spaces"
            :key="space.id"
            class="space-item"
            :class="{
              active: activeView === 'space' && activeSpaceId === space.id,
            }"
            @click="pilihSpace(space.id)"
          >
            <div class="space-item-left">
              <v-icon icon="mdi-chevron-right" size="14" color="#020f40" />
              <span class="space-name">{{ space.nama }}</span>
            </div>

            <div class="space-item-actions" @click.stop>
              <v-btn
                icon="mdi-plus"
                variant="text"
                style="
                  background-color: #0034f61a;
                  border-radius: 4px;
                  border: 1px solid #0034f61a;
                  font-weight: 700;
                "
                size="20"
                color="#020f40"
                @click="openCreateBoard(space)"
              />
              <v-menu location="right">
                <template #activator="{ props: menuProps }">
                  <v-btn
                    icon="mdi-dots-horizontal"
                    variant="text"
                    size="20"
                    style="
                      background-color: #0034f61a;
                      border-radius: 4px;
                      border: 1px solid #0034f61a;
                      font-weight: 700;
                    "
                    color="#020f40"
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
                    prepend-icon="mdi-pencil-outline"
                    title="Edit Space"
                    @click="openEditSpace(space)"
                  />
                  <v-list-item
                    prepend-icon="mdi-trash-can-outline"
                    title="Delete Space"
                    base-color="cancel"
                    @click="openDeleteSpace(space)"
                  />
                </v-list>
              </v-menu>
            </div>
          </div>

          <p v-if="spaceStore.spaces.length === 0" class="no-space-text">
            Belum ada space
          </p>
        </div>
      </nav>
    </aside>

    <div class="main-area">
      <header class="topbar">
        <div class="search-wrap">
          <v-text-field
            v-model="searchQuery"
            placeholder="Search"
            variant="outlined"
            density="compact"
            prepend-inner-icon="mdi-magnify"
            hide-details
            bg-color="field"
            color="primary"
          />
        </div>

        <div class="topbar-actions">
          <button class="topbar-icon-btn">
            <v-icon icon="mdi-bell-outline" size="20" />
          </button>
          <button class="topbar-icon-btn">
            <v-icon icon="mdi-cog-outline" size="20" />
          </button>
          <v-avatar size="36" class="avatar-btn">
            <v-icon>mdi-account-circle</v-icon>
          </v-avatar>
        </div>
      </header>

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

            <RecentActivityModal v-model="showRecent" :space-id="SPACE_ID" />
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
          </div>
        </template>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { RouterLink, useRoute } from "vue-router";
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
import type { DashboardData, DashboardSummary, Space } from "../types";
import "../assets/css/dashboard.css";
import RecentActivityModal from "@/components/RecentActivityModal.vue";
import CreateSpaceModal from "../components/CreateSpaceModal.vue";
import DeleteSpaceModal from "../components/DeleteSpaceModal.vue";
import CreateBoardModal from "../components/CreateBoardModal.vue";
import { useSpaceStore } from "@/stores/space";

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
);

const SPACE_ID = 1;
const route = useRoute();
const authStore = useAuthStore();
const spaceStore = useSpaceStore();

const activeView = ref<"dashboard" | "space">("dashboard");

const showRecent = ref<boolean>(false);
const showCreateSpace = ref<boolean>(false);
const showDeleteSpace = ref<boolean>(false);
const showCreateBoard = ref<boolean>(false);
const spacesExpanded = ref<boolean>(true);
const editSpaceData = ref<Space | null>(null);
const deleteSpaceData = ref<Space | null>(null);
const selectedSpaceForBoard = ref<Space | null>(null);
const activeSpaceId = ref<number>(0);
const searchQuery = ref<string>("");
const loading = ref<boolean>(false);
const dashboardData = ref<DashboardData | null>(null);

const userInitial = computed<string>(
  () => authStore.user?.name?.charAt(0).toUpperCase() || "U",
);

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
      grid: { color: "rgba(130,144,164,0.2)" },
      ticks: { color: "#65A9EC", font: { size: 11 } },
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

const onBoardCreated = (): void => {
  // refresh list board di sidebar
};

const pilihSpace = (id: number): void => {
  activeSpaceId.value = id;
  activeView.value = "space";

  ambilData();
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

onMounted(async () => {
  await spaceStore.ambilSpaces();
  activeView.value = "dashboard";
  if (spaceStore.spaces.length > 0) {
    activeSpaceId.value = spaceStore.spaces[0]?.id ?? 0;
    ambilData();
  }
});

const ambilData = async (): Promise<void> => {
  if (!activeSpaceId.value) return;
  loading.value = true;
  try {
    const res = await api.get<DashboardData>(
      `/spaces/${activeSpaceId.value}/dashboard`,
    );
    dashboardData.value = res.data;
  } catch (err) {
    console.error("Gagal ambil data dashboard:", err);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await spaceStore.ambilSpaces();
  if (spaceStore.spaces.length > 0) {
    activeSpaceId.value = spaceStore.spaces[0]?.id ?? 0;
    if (activeSpaceId.value) {
      ambilData();
    }
  }
});
</script>
