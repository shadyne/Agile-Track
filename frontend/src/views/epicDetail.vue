<template>
  <div class="epic-detail-layout">
    <div class="epic-detail-main">
      <div
        style="
          padding: 16px 28px 0;
          background: white;
          border-bottom: 1px solid rgba(130, 144, 164, 0.2);
        "
      >
        <h1 class="board-title">{{ boardNama }}</h1>
        <div class="board-tabs" style="padding: 0">
          <button class="tab-btn" @click="router.push(`/board/${boardId}`)">
            Timeline
          </button>
          <button
            class="tab-btn"
            @click="router.push(`/board/${boardId}?tab=backlog`)"
          >
            Backlog
          </button>
        </div>
      </div>

      <div v-if="loading" class="flex items-center justify-center h-64">
        <v-progress-circular indeterminate color="primary" />
      </div>

      <div v-else-if="epic" class="epic-detail-content">
        <div class="epic-body">
          <div class="epic-header">
            <div
              class="epic-color-bar"
              :style="{ backgroundColor: getEpicColor(epic.labels?.[0]) }"
            />

            <input
              v-model="editJudul"
              class="epic-title-input"
              maxlength="20"
              @keydown.enter="simpanField('judul', editJudul)"
              @blur="simpanField('judul', editJudul)"
            />

            <div class="epic-top-actions">
              <v-menu>
                <template #activator="{ props: menuProps }">
                  <v-btn
                    variant="outlined"
                    density="compact"
                    size="small"
                    v-bind="menuProps"
                    :color="getStatusColor(epic.status)"
                    style="font-size: 11px; font-weight: 700"
                  >
                    {{ getStatusLabel(epic.status) }}
                    <v-icon icon="mdi-chevron-down" size="14" class="ml-1" />
                  </v-btn>
                </template>
                <v-list
                  density="compact"
                  min-width="160"
                  rounded="lg"
                  elevation="3"
                >
                  <v-list-item
                    v-for="s in statusOptions"
                    :key="s.value"
                    :title="s.label"
                    @click="simpanField('status', s.value)"
                  />
                </v-list>
              </v-menu>

              <v-btn
                icon="mdi-lightning-bolt"
                variant="text"
                density="compact"
                size="small"
              />
            </div>
          </div>

          <div class="mb-6">
            <p class="section-label">Description</p>
            <textarea
              v-model="editDeskripsi"
              class="desc-area"
              placeholder="Add a description..."
              @keydown.enter.ctrl="simpanField('deskripsi', editDeskripsi)"
              @blur="simpanField('deskripsi', editDeskripsi)"
            />
            <p style="font-size: 11px; color: #8290a4; margin-top: 4px">
              Ctrl+Enter untuk menyimpan
            </p>
          </div>

          <div class="mb-6">
            <div class="child-table-wrap">
              <div class="p-1">
                <div class="progress-bar-track">
                  <div
                    class="progress-bar-fill"
                    :style="{ width: `${progressPersen}%` }"
                  />
                </div>
              </div>

              <p class="progress-label p-1">{{ progressPersen }}% Done</p>
              <div class="child-table-header">
                <span class="child-table-title">Child work items</span>
                <div class="child-table-actions">
                  <v-btn
                    icon="mdi-dots-horizontal"
                    variant="text"
                    size="x-small"
                  />
                  <v-btn
                    icon="mdi-trash-can-outline"
                    variant="text"
                    size="x-small"
                    color="cancel"
                  />
                  <v-btn
                    icon="mdi-plus"
                    variant="text"
                    size="x-small"
                    color="primary"
                    @click="showCreateChild = true"
                  />
                </div>
              </div>

              <div class="child-table-col-header">
                <span>Work</span>
                <span>Pri...</span>
                <span>Stor...</span>
                <span>As...</span>
                <span>Status</span>
              </div>

              <div
                v-for="item in epic.items"
                :key="item.id"
                class="child-item-row"
              >
                <div
                  style="
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    overflow: hidden;
                  "
                >
                  <v-icon
                    :icon="getTypeIcon(item.type)"
                    size="13"
                    :color="getTypeColor(item.type)"
                    style="flex-shrink: 0"
                  />
                  <a class="child-item-key">{{ item.kode }}</a>
                  <span class="child-item-title">{{ item.judul }}</span>
                </div>

                <div
                  style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                  "
                >
                  <span
                    class="priority-dot"
                    :style="{
                      backgroundColor: getPriorityColor(item.priority),
                    }"
                    :title="item.priority"
                  />
                  <span
                    style="font-size: 11px; color: #8290a4; margin-left: 3px"
                  >
                    {{ item.priority.charAt(0).toUpperCase() }}
                  </span>
                </div>

                <div
                  style="text-align: center; font-size: 12px; color: #8290a4"
                >
                  —
                </div>

                <div style="display: flex; justify-content: center">
                  <v-avatar size="22" color="primary">
                    <span
                      style="color: white; font-size: 9px; font-weight: 700"
                    >
                      {{ item.user?.name?.charAt(0).toUpperCase() ?? "?" }}
                    </span>
                  </v-avatar>
                </div>

                <div>
                  <span class="status-badge" :class="`status-${item.status}`">
                    {{ getStatusLabel(item.status) }}
                    <v-icon icon="mdi-chevron-down" size="10" />
                  </span>
                </div>
              </div>

              <div
                v-if="epic.items?.length === 0"
                style="
                  padding: 16px;
                  text-align: center;
                  font-size: 13px;
                  color: #8290a4;
                "
              >
                Belum ada child item
              </div>
            </div>
          </div>

          <div class="linked-section mb-6">
            <p class="section-label">Linked work items</p>
            <div class="linked-form">
              <v-select
                v-model="linkedType"
                :items="[
                  'is blocked by',
                  'blocks',
                  'Is clonned by',
                  'clones',
                  'is duplicated by',
                  'duplicates',
                  'is caused by',
                  'causes',
                  'relates to',
                ]"
                variant="outlined"
                density="compact"
                hide-details
                style="max-width: 160px"
              />
              <v-text-field
                v-model="linkedUrl"
                placeholder="Type, search or paste URL"
                variant="outlined"
                density="compact"
                hide-details
                style="flex: 1"
              />
            </div>
            <div
              style="
                display: flex;
                gap: 12px;
                align-items: center;
                margin-top: 4px;
              "
            >
              <v-btn
                variant="text"
                size="small"
                prepend-icon="mdi-plus"
                color="primary"
                style="font-size: 12px"
              >
                Create linked work item
              </v-btn>
              <span style="font-size: 12px; color: #65a9ec; cursor: pointer"
                >Link</span
              >
              <span
                style="font-size: 12px; color: #8290a4; cursor: pointer"
                @click="linkedUrl = ''"
                >Cancel</span
              >
            </div>
          </div>

          <div class="activity-section">
            <p class="section-label">Activity</p>

            <div class="activity-tabs">
              <button
                class="activity-tab"
                :class="{ active: activeTab === 'comments' }"
                @click="activeTab = 'comments'"
              >
                Comments
              </button>
              <button
                class="activity-tab"
                :class="{ active: activeTab === 'history' }"
                @click="activeTab = 'history'"
              >
                History
              </button>
            </div>

            <template v-if="activeTab === 'comments'">
              <div class="comment-input-wrap mb-4">
                <v-avatar size="32" color="primary">
                  <span style="color: white; font-size: 12px; font-weight: 700">
                    {{ authStore.user?.name?.charAt(0).toUpperCase() }}
                  </span>
                </v-avatar>
                <textarea
                  v-model="newComment"
                  class="comment-box"
                  placeholder="Add a comment..."
                  @keydown.enter.exact.prevent="kirimKomentar"
                />
              </div>
              <p
                style="
                  font-size: 11px;
                  color: #8290a4;
                  margin-top: -12px;
                  margin-bottom: 16px;
                  margin-left: 42px;
                "
              >
                Enter untuk kirim
              </p>

              <div
                v-for="comment in epic.comments"
                :key="comment.id"
                class="comment-item"
              >
                <v-avatar size="32" color="primary">
                  <span style="color: white; font-size: 12px; font-weight: 700">
                    {{ comment.user.name.charAt(0).toUpperCase() }}
                  </span>
                </v-avatar>
                <div class="comment-content">
                  <p class="comment-author">{{ comment.user.name }}</p>
                  <p class="comment-time">
                    {{ formatTime(comment.created_at) }}
                  </p>
                  <p class="comment-text">{{ comment.isi }}</p>
                </div>
              </div>

              <div
                v-if="!epic.comments?.length"
                style="
                  font-size: 13px;
                  color: #8290a4;
                  text-align: center;
                  padding: 24px 0;
                "
              >
                Belum ada komentar
              </div>
            </template>

            <template v-else>
              <div v-if="loadingHistory" class="flex justify-center py-4">
                <v-progress-circular indeterminate size="24" color="primary" />
              </div>
              <div v-for="log in history" :key="log.id" class="history-item">
                <v-avatar size="28" color="second-btn">
                  <span style="color: white; font-size: 10px; font-weight: 700">
                    {{ log.user_initial }}
                  </span>
                </v-avatar>
                <div style="flex: 1">
                  <p class="history-text">
                    <strong>{{ log.user_name }}</strong>
                    updated <em>{{ log.field_updated }}</em> →
                    <strong>{{ log.new_value }}</strong>
                  </p>
                  <p class="history-time">{{ log.time_ago }}</p>
                </div>
              </div>
              <div
                v-if="!history.length && !loadingHistory"
                style="
                  font-size: 13px;
                  color: #8290a4;
                  text-align: center;
                  padding: 24px 0;
                "
              >
                Belum ada history
              </div>
            </template>
          </div>
        </div>

        <div class="epic-sidebar">
          <div class="detail-section-title">
            Details
            <v-btn icon="mdi-tune" variant="text" size="x-small" />
          </div>

          <div class="detail-field">
            <p class="detail-field-label">Assignee</p>
            <v-menu>
              <template #activator="{ props: menuProps }">
                <div
                  class="assignee-row detail-field-value clickable"
                  v-bind="menuProps"
                >
                  <v-avatar size="24" color="primary">
                    <span
                      style="color: white; font-size: 10px; font-weight: 700"
                    >
                      {{ epic.assignee?.name?.charAt(0).toUpperCase() ?? "?" }}
                    </span>
                  </v-avatar>
                  <span>{{ epic.assignee?.name ?? "Unassigned" }}</span>
                </div>
              </template>
              <v-list
                density="compact"
                min-width="200"
                rounded="lg"
                elevation="3"
              >
                <v-list-item
                  v-for="m in boardMembers"
                  :key="m.id"
                  :title="m.name"
                  @click="simpanField('assignee_id', m.id)"
                >
                  <template #prepend>
                    <v-avatar size="24" color="primary">
                      <span style="color: white; font-size: 10px">{{
                        m.name.charAt(0).toUpperCase()
                      }}</span>
                    </v-avatar>
                  </template>
                </v-list-item>
              </v-list>
            </v-menu>
            <p
              class="assign-me mt-1"
              @click="simpanField('assignee_id', authStore.user?.id)"
            >
              Assign to me
            </p>
          </div>

          <div class="detail-field">
            <p class="detail-field-label">Reporter</p>
            <div class="assignee-row detail-field-value">
              <v-avatar size="24" color="second-btn">
                <span style="color: white; font-size: 10px; font-weight: 700">
                  {{ epic.user?.name?.charAt(0).toUpperCase() ?? "?" }}
                </span>
              </v-avatar>
              <span>{{ epic.user?.name ?? "—" }}</span>
            </div>
          </div>

          <div class="detail-field">
            <p class="detail-field-label">Labels</p>
            <div style="display: flex; flex-wrap: wrap; gap: 4px">
              <span v-for="lbl in epic.labels" :key="lbl" class="label-chip">
                {{ lbl }}
              </span>
              <span
                v-if="!epic.labels?.length"
                style="font-size: 13px; color: #8290a4"
              >
                None
              </span>
            </div>
          </div>

          <div class="detail-field">
            <p class="detail-field-label">Due date</p>
            <input
              type="date"
              :value="epic.end_date"
              class="detail-date-input"
              @change="
                simpanField(
                  'end_date',
                  ($event.target as HTMLInputElement).value,
                )
              "
            />
          </div>

          <div class="detail-field">
            <p class="detail-field-label">Start date</p>
            <input
              type="date"
              :value="epic.start_date"
              class="detail-date-input"
              @change="
                simpanField(
                  'start_date',
                  ($event.target as HTMLInputElement).value,
                )
              "
            />
          </div>

          <v-divider class="my-4" />

          <div class="detail-field">
            <p class="detail-field-label">Priority</p>
            <v-menu>
              <template #activator="{ props: menuProps }">
                <div
                  class="priority-row detail-field-value clickable"
                  v-bind="menuProps"
                >
                  <span
                    class="priority-dot"
                    :style="{
                      backgroundColor: getPriorityColor(epic.priority),
                    }"
                  />
                  <span>{{
                    epic.priority.charAt(0).toUpperCase() +
                    epic.priority.slice(1)
                  }}</span>
                </div>
              </template>
              <v-list
                density="compact"
                min-width="160"
                rounded="lg"
                elevation="3"
              >
                <v-list-item
                  v-for="p in priorityOptions"
                  :key="p.value"
                  @click="simpanField('priority', p.value)"
                >
                  <template #prepend>
                    <span
                      class="priority-dot"
                      :style="{ backgroundColor: getPriorityColor(p.value) }"
                    />
                  </template>
                  <v-list-item-title>{{ p.label }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </div>

          <div class="detail-field">
            <p class="detail-field-label">Original Estimate</p>
            <input
              v-model="editEstimate"
              class="estimate-input"
              placeholder="e.g. 1d 2h"
              :title="estimateHelp"
              @keydown.enter="simpanField('original_estimate', editEstimate)"
              @blur="simpanField('original_estimate', editEstimate)"
            />
            <p
              style="
                font-size: 10px;
                color: #8290a4;
                margin-top: 4px;
                line-height: 1.6;
              "
            >
              {{ estimateHelp }}
            </p>
          </div>
        </div>
      </div>
    </div>
    <CreateChildItemModal
      v-model="showCreateChild"
      @submit="handleCreateChild"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useSpaceStore } from "../stores/space";
import api from "../api/axios";
import type { Epic, EpicHistory } from "../types";
import "../assets/css/dashboard.css";
import "../assets/css/board.css";
import "../assets/css/epic-detail.css";
import CreateChildItemModal from "@/components/CreateChildComponent.vue";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const spaceStore = useSpaceStore();

const boardId = Number(route.params.boardId);
const epicId = Number(route.params.epicId);

const epic = ref<Epic | null>(null);
const loading = ref<boolean>(false);
const loadingHistory = ref<boolean>(false);
const history = ref<EpicHistory[]>([]);
const activeTab = ref<"comments" | "history">("comments");
const newComment = ref<string>("");
const linkedType = ref<string>("is blocked by");
const linkedUrl = ref<string>("");
const boardNama = ref<string>("");
const boardMembers = ref<{ id: number; name: string; email: string }[]>([]);
const showCreateChild = ref(false);
const newChildTitle = ref("");
const loadingCreateChild = ref(false);

const activeView = ref<"dashboard" | "space">("space");
const activeSpaceId = ref<number>(0);

const editJudul = ref<string>("");
const editDeskripsi = ref<string>("");
const editEstimate = ref<string>("");

const estimateHelp =
  "1mo=month, 1w=week, 1d=day, 1h=hour, 1m=minute, 1s=second";

const statusOptions = [
  { label: "To Do", value: "to_do" },
  { label: "In Progress", value: "in_progress" },
  { label: "Done by Dev", value: "done_by_dev" },
  { label: "Testing", value: "testing" },
  { label: "Done by QA", value: "done_by_qa" },
];

const priorityOptions = [
  { label: "Highest", value: "highest" },
  { label: "High", value: "high" },
  { label: "Medium", value: "medium" },
  { label: "Low", value: "low" },
  { label: "Lowest", value: "lowest" },
];

const progressPersen = computed<number>(() => {
  if (!epic.value?.items?.length) return 0;
  const done = epic.value.items.filter((i) => i.status === "done_by_qa").length;
  return Math.round((done / epic.value.items.length) * 100);
});

const getStatusLabel = (status: string): string => {
  const map: Record<string, string> = {
    to_do: "To Do",
    in_progress: "In Progress",
    done_by_dev: "Done by Dev",
    testing: "Testing",
    done_by_qa: "Done by QA",
  };
  return map[status] ?? status;
};

const getStatusColor = (status: string): string => {
  const map: Record<string, string> = {
    to_do: "default",
    in_progress: "info",
    done_by_dev: "success",
    testing: "warning",
    done_by_qa: "create",
  };
  return map[status] ?? "default";
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

const getEpicColor = (label?: string): string => {
  if (!label) return "#020F40";
  const colors = ["#B45309", "#1D4ED8", "#059669", "#7C3AED", "#DC2626"];
  return colors[label.charCodeAt(0) % colors.length];
};

const formatTime = (dateStr: string): string => {
  const date = new Date(dateStr);
  const now = new Date();
  const diff = now.getTime() - date.getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs}h ago`;
  const days = Math.floor(hrs / 24);
  return `${days}d ago`;
};

const ambilEpic = async (): Promise<void> => {
  loading.value = true;
  try {
    const res = await api.get<Epic>(`/boards/${boardId}/epics/${epicId}`);
    epic.value = res.data;
    editJudul.value = res.data.judul;
    editDeskripsi.value = res.data.deskripsi ?? "";
    editEstimate.value = res.data.original_estimate ?? "";
  } catch (err) {
    console.error("Gagal ambil epic:", err);
  } finally {
    loading.value = false;
  }
};

const handleCreateChild = async (title: string): Promise<void> => {
  console.log(epic.value, "item");

  if (!epic.value) return;
  try {
    const res = await api.post(`/boards/${boardId}/epics/${epicId}/items`, {
      judul: title,
    });

    if (!epic.value?.items) epic.value.items = [];
    epic.value.items.push(res.data.data);
  } catch (err) {
    console.error("Gagal tambah child:", err);
  }
};
const ambilHistory = async (): Promise<void> => {
  loadingHistory.value = true;
  try {
    const res = await api.get<EpicHistory[]>(
      `/boards/${boardId}/epics/${epicId}/history`,
    );
    history.value = res.data;
  } catch (err) {
    console.error("Gagal ambil history:", err);
  } finally {
    loadingHistory.value = false;
  }
};

const ambilBoard = async (): Promise<void> => {
  try {
    const res = await api.get(`/boards/${boardId}`);
    boardNama.value = res.data.nama ?? "";
    const members = res.data.space?.member_emails ?? [];
    boardMembers.value = [
      ...(authStore.user
        ? [
            {
              id: authStore.user.id,
              name: authStore.user.name,
              email: authStore.user.email,
            },
          ]
        : []),
      ...members.map((email: string, i: number) => ({
        id: i + 100,
        name: email.split("@")[0],
        email: email,
      })),
    ];
  } catch (err) {
    console.error("Gagal ambil board:", err);
  }
};

const simpanField = async (field: string, value: any): Promise<void> => {
  if (!epic.value) return;
  if ((epic.value as any)[field] === value) return;

  try {
    const res = await api.put<{ data: Epic }>(
      `/boards/${boardId}/epics/${epicId}`,
      { [field]: value },
    );
    epic.value = res.data.data;
    editJudul.value = res.data.data.judul;
    editDeskripsi.value = res.data.data.deskripsi ?? "";
    editEstimate.value = res.data.data.original_estimate ?? "";
  } catch (err) {
    console.error(`Gagal simpan ${field}:`, err);
  }
};

const kirimKomentar = async (): Promise<void> => {
  if (!newComment.value.trim()) return;

  try {
    const res = await api.post(`/boards/${boardId}/epics/${epicId}/comments`, {
      isi: newComment.value,
    });
    epic.value?.comments?.push(res.data.data);
    newComment.value = "";
  } catch (err) {
    console.error("Gagal kirim komentar:", err);
  }
};

watch(activeTab, (tab) => {
  if (tab === "history" && !history.value.length) {
    ambilHistory();
  }
});

onMounted(async () => {
  await spaceStore.ambilSpaces();
  ambilBoard();
  ambilEpic();
});
</script>
