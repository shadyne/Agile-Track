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
        <div
          style="
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #8290a4;
            margin-bottom: 10px;
          "
        >
          <span
            style="cursor: pointer; color: #65a9ec"
            @click="router.push(`/board/${boardId}`)"
            >{{ boardNama }}</span
          >
          <v-icon icon="mdi-chevron-right" size="14" />
          <span v-if="item?.epic">
            <span
              style="cursor: pointer; color: #7c3aed; font-weight: 700"
              @click="router.push(`/board/${boardId}/epic/${item.epic.id}`)"
              >{{ item.epic.kode }}</span
            >
          </span>
          <v-icon v-if="item?.epic" icon="mdi-chevron-right" size="14" />
          <span style="color: #374151; font-weight: 700">{{ item?.kode }}</span>
        </div>

        <!-- Tabs -->
        <div class="board-tabs" style="padding: 0; margin-top: 0">
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

      <div v-else-if="item" class="epic-detail-content">
        <div class="epic-body">
          <div class="epic-header">
            <v-icon
              :icon="getTypeIcon(item.type)"
              size="22"
              :color="getTypeColor(item.type)"
              style="flex-shrink: 0"
            />

            <input
              v-model="editJudul"
              class="epic-title-input"
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
                    :color="getStatusColor(item.status)"
                    style="font-size: 11px; font-weight: 700"
                  >
                    {{ getStatusLabel(item.status) }}
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

              <template v-if="isDirty">
                <v-btn
                  color="cancel"
                  size="small"
                  variant="outlined"
                  style="font-size: 11px; font-weight: 700"
                  @click="cancelEdit"
                >
                  Cancel
                </v-btn>
                <v-btn
                  color="primary"
                  size="small"
                  variant="flat"
                  style="color: white; font-size: 11px; font-weight: 700"
                  :loading="saving"
                  @click="saveAll"
                >
                  Save
                </v-btn>
              </template>
            </div>
          </div>

          <div class="mb-6">
            <p class="section-label">Description</p>
            <textarea
              v-model="editDeskripsi"
              class="desc-area"
              placeholder="Add a description..."
            />
          </div>

          <div v-if="item.children && item.children.length" class="mb-6">
            <div class="child-table-wrap">
              <div class="child-table-header">
                <span class="child-table-title">Sub Tasks</span>
              </div>

              <div class="progress-bar-wrap">
                <div class="progress-bar-track">
                  <div
                    class="progress-bar-fill"
                    :style="{ width: `${progressPersen}%` }"
                  />
                </div>
                <p class="progress-label">{{ progressPersen }}% Done</p>
              </div>

              <div class="child-table-col-header">
                <span>Work</span>
                <span>Pri...</span>
                <span>Est.</span>
                <span>As...</span>
                <span>Status</span>
              </div>

              <div
                v-for="child in item.children"
                :key="child.id"
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
                    :icon="getTypeIcon(child.type)"
                    size="13"
                    :color="getTypeColor(child.type)"
                    style="flex-shrink: 0"
                  />
                  <RouterLink
                    :to="`/board/${boardId}/item/${child.id}`"
                    class="child-item-key"
                    >{{ child.kode }}</RouterLink
                  >
                  <span class="child-item-title">{{ child.judul }}</span>
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
                      backgroundColor: getPriorityColor(child.priority),
                    }"
                  />
                </div>
                <div
                  style="text-align: center; font-size: 12px; color: #8290a4"
                >
                  {{ child.original_estimate || "—" }}
                </div>
                <div style="display: flex; justify-content: center">
                  <v-avatar size="22" color="primary">
                    <span
                      style="color: white; font-size: 9px; font-weight: 700"
                    >
                      {{ child.assignee?.name?.charAt(0).toUpperCase() ?? "?" }}
                    </span>
                  </v-avatar>
                </div>
                <div>
                  <span class="status-badge" :class="`status-${child.status}`">
                    {{ getStatusLabel(child.status) }}
                  </span>
                </div>
              </div>
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

            <!-- Comments -->
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
                  placeholder="Add a comment... (Enter to send)"
                  @keydown.enter.exact.prevent="kirimKomentar"
                />
              </div>

              <div v-for="c in comments" :key="c.id" class="comment-item">
                <v-avatar size="32" color="primary">
                  <span style="color: white; font-size: 12px; font-weight: 700">
                    {{ c.user.name.charAt(0).toUpperCase() }}
                  </span>
                </v-avatar>
                <div class="comment-content">
                  <p class="comment-author">{{ c.user.name }}</p>
                  <p class="comment-time">{{ formatTime(c.created_at) }}</p>
                  <p class="comment-text">{{ c.isi }}</p>
                </div>
              </div>

              <div
                v-if="!comments.length"
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

            <!-- History -->
            <template v-else>
              <div v-if="loadingHistory" class="flex justify-center py-4">
                <v-progress-circular indeterminate size="24" color="primary" />
              </div>
              <div v-for="log in history" :key="log.id" class="history-item">
                <v-avatar size="28" color="second-btn">
                  <span
                    style="color: white; font-size: 10px; font-weight: 700"
                    >{{ log.user_initial }}</span
                  >
                </v-avatar>
                <div style="flex: 1">
                  <p class="history-text">
                    <strong>{{ log.user_name }}</strong> updated
                    <em>{{ log.field_updated }}</em> →
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
          <div class="detail-section-title">Details</div>

          <div class="detail-field">
            <p class="detail-field-label">Type</p>
            <v-menu>
              <template #activator="{ props: menuProps }">
                <div
                  class="detail-field-value clickable"
                  v-bind="menuProps"
                  style="display: flex; align-items: center; gap: 6px"
                >
                  <v-icon
                    :icon="getTypeIcon(item.type)"
                    size="14"
                    :color="getTypeColor(item.type)"
                  />
                  <span>{{ getTypeLabel(item.type) }}</span>
                </div>
              </template>
              <v-list
                density="compact"
                min-width="160"
                rounded="lg"
                elevation="3"
              >
                <v-list-item
                  v-for="t in typeOptions"
                  :key="t.value"
                  @click="simpanField('type', t.value)"
                >
                  <template #prepend>
                    <v-icon
                      :icon="getTypeIcon(t.value)"
                      size="14"
                      :color="getTypeColor(t.value)"
                    />
                  </template>
                  <v-list-item-title>{{ t.label }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
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
                      {{ item.assignee?.name?.charAt(0).toUpperCase() ?? "?" }}
                    </span>
                  </v-avatar>
                  <span>{{ item.assignee?.name ?? "Unassigned" }}</span>
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
                  {{ item.user?.name?.charAt(0).toUpperCase() ?? "?" }}
                </span>
              </v-avatar>
              <span>{{ item.user?.name ?? "—" }}</span>
            </div>
          </div>

          <div class="detail-field">
            <p class="detail-field-label">Epic</p>
            <div
              class="detail-field-value clickable"
              style="
                display: flex;
                align-items: center;
                gap: 6px;
                cursor: pointer;
              "
              @click="
                item.epic &&
                router.push(`/board/${boardId}/epic/${item.epic.id}`)
              "
            >
              <v-icon icon="mdi-lightning-bolt" size="14" color="#7C3AED" />
              <span style="color: #7c3aed; font-weight: 700">
                {{
                  item.epic ? item.epic.kode + " " + item.epic.judul : "None"
                }}
              </span>
            </div>
          </div>

          <div class="detail-field">
            <p class="detail-field-label">Sprint</p>
            <div class="detail-field-value">
              {{ item.sprint ? item.sprint.nama : "Backlog" }}
            </div>
          </div>

          <div class="detail-field">
            <p class="detail-field-label">Labels</p>
            <div style="display: flex; flex-wrap: wrap; gap: 4px">
              <span v-for="lbl in item.labels" :key="lbl" class="label-chip">{{
                lbl
              }}</span>
              <span
                v-if="!item.labels?.length"
                style="font-size: 13px; color: #8290a4"
                >None</span
              >
            </div>
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
                      backgroundColor: getPriorityColor(item.priority),
                    }"
                  />
                  <span>{{
                    item.priority.charAt(0).toUpperCase() +
                    item.priority.slice(1)
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
            <p class="detail-field-label">Due date</p>
            <input
              type="date"
              :value="item.end_date ?? ''"
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
              :value="item.start_date ?? ''"
              class="detail-date-input"
              @change="
                simpanField(
                  'start_date',
                  ($event.target as HTMLInputElement).value,
                )
              "
            />
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
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter, RouterLink } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useSpaceStore } from "../stores/space";
import SidebarComponent from "../components/SidebarComponent.vue";
import TopbarComponent from "../components/TopbarComponent.vue";
import api from "../api/axios";
import type { EpicItem, EpicHistory, EpicComment } from "../types";
import "../assets/css/dashboard.css";
import "../assets/css/board.css";
import "../assets/css/epic-detail.css";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const spaceStore = useSpaceStore();

const boardId = Number(route.params.boardId);
const itemId = Number(route.params.itemId);

const item = ref<EpicItem | null>(null);
const loading = ref(false);
const saving = ref(false);
const boardNama = ref("");
const boardMembers = ref<{ id: number; name: string; email: string }[]>([]);

const editJudul = ref("");
const editDeskripsi = ref("");
const editEstimate = ref("");

const isDirty = computed(() => {
  if (!item.value) return false;
  return (
    editJudul.value !== item.value.judul ||
    editDeskripsi.value !== (item.value.deskripsi ?? "") ||
    editEstimate.value !== (item.value.original_estimate ?? "")
  );
});

const activeTab = ref<"comments" | "history">("comments");
const comments = ref<EpicComment[]>([]);
const newComment = ref("");
const history = ref<EpicHistory[]>([]);
const loadingHistory = ref(false);

const activeView = ref<"dashboard" | "space">("space");
const activeSpaceId = ref(0);

const estimateHelp = "1mo=month, 1w=week, 1d=day, 1h=hour, 1m=minute";

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

const typeOptions = [
  { label: "Task", value: "task" },
  { label: "Story", value: "story" },
  { label: "Bug", value: "bug" },
  { label: "QA Task", value: "qa_task" },
];

const progressPersen = computed(() => {
  if (!item.value?.children?.length) return 0;
  const done = item.value.children.filter(
    (c) => c.status === "done_by_qa",
  ).length;
  return Math.round((done / item.value.children.length) * 100);
});

const getStatusLabel = (s: string) => {
  const map: Record<string, string> = {
    to_do: "To Do",
    in_progress: "In Progress",
    done_by_dev: "Done by Dev",
    testing: "Testing",
    done_by_qa: "Done by QA",
  };
  return map[s] ?? s;
};

const getStatusColor = (s: string) => {
  const map: Record<string, string> = {
    to_do: "default",
    in_progress: "info",
    done_by_dev: "success",
    testing: "warning",
    done_by_qa: "create",
  };
  return map[s] ?? "default";
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

const getTypeLabel = (type: string) => {
  const map: Record<string, string> = {
    task: "Task",
    story: "Story",
    bug: "Bug",
    qa_task: "QA Task",
    epic: "Epic",
  };
  return map[type] ?? type;
};

const formatTime = (dateStr: string) => {
  const diff = Date.now() - new Date(dateStr).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs}h ago`;
  return `${Math.floor(hrs / 24)}d ago`;
};

const ambilItem = async () => {
  loading.value = true;
  try {
    const res = await api.get<EpicItem>(`/boards/${boardId}/items/${itemId}`);
    item.value = res.data;
    editJudul.value = res.data.judul;
    editDeskripsi.value = res.data.deskripsi ?? "";
    editEstimate.value = res.data.original_estimate ?? "";
    comments.value = (res.data as any).comments ?? [];
  } catch (err) {
    console.error("Gagal ambil item:", err);
  } finally {
    loading.value = false;
  }
};

const ambilBoard = async () => {
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
        email,
      })),
    ];
  } catch (err) {
    console.error("Gagal ambil board:", err);
  }
};

const ambilHistory = async () => {
  loadingHistory.value = true;
  try {
    const res = await api.get<EpicHistory[]>(
      `/boards/${boardId}/items/${itemId}/history`,
    );
    history.value = res.data;
  } catch (err) {
    console.error("Gagal ambil history:", err);
  } finally {
    loadingHistory.value = false;
  }
};

const simpanField = async (field: string, value: any) => {
  if (!item.value) return;
  if ((item.value as any)[field] === value) return;

  try {
    const res = await api.put<{ data: EpicItem }>(
      `/boards/${boardId}/items/${itemId}`,
      { [field]: value },
    );
    item.value = res.data.data;
    editJudul.value = res.data.data.judul;
    editDeskripsi.value = res.data.data.deskripsi ?? "";
    editEstimate.value = res.data.data.original_estimate ?? "";
  } catch (err) {
    console.error(`Gagal simpan ${field}:`, err);
  }
};

const saveAll = async () => {
  if (!item.value) return;
  saving.value = true;
  try {
    const payload: Record<string, any> = {};
    if (editJudul.value !== item.value.judul) payload.judul = editJudul.value;
    if (editDeskripsi.value !== (item.value.deskripsi ?? ""))
      payload.deskripsi = editDeskripsi.value;
    if (editEstimate.value !== (item.value.original_estimate ?? ""))
      payload.original_estimate = editEstimate.value;

    if (Object.keys(payload).length === 0) return;

    const res = await api.put<{ data: EpicItem }>(
      `/boards/${boardId}/items/${itemId}`,
      payload,
    );
    item.value = res.data.data;
    editJudul.value = res.data.data.judul;
    editDeskripsi.value = res.data.data.deskripsi ?? "";
    editEstimate.value = res.data.data.original_estimate ?? "";
  } catch (err) {
    console.error("Gagal save:", err);
  } finally {
    saving.value = false;
  }
};

const cancelEdit = () => {
  if (!item.value) return;
  editJudul.value = item.value.judul;
  editDeskripsi.value = item.value.deskripsi ?? "";
  editEstimate.value = item.value.original_estimate ?? "";
};

const kirimKomentar = async () => {
  if (!newComment.value.trim()) return;
  try {
    if (!item.value?.epic_id) return;
    const res = await api.post(
      `/boards/${boardId}/epics/${item.value.epic_id}/comments`,
      { isi: newComment.value },
    );
    comments.value.push(res.data.data);
    newComment.value = "";
  } catch (err) {
    console.error("Gagal kirim komentar:", err);
  }
};

watch(activeTab, (tab) => {
  if (tab === "history" && !history.value.length) ambilHistory();
});

onMounted(async () => {
  await spaceStore.ambilSpaces();
  ambilBoard();
  ambilItem();
});
</script>
