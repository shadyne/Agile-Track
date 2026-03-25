<template>
  <v-dialog v-model="isOpen" max-width="480" scrollable>
    <v-card rounded="xl">
      <!-- Header -->
      <v-card-title class="modal-header">
        <span class="modal-title">Recent Activity</span>
        <v-btn
          icon="mdi-close"
          variant="text"
          density="compact"
          @click="isOpen = false"
        />
      </v-card-title>

      <v-divider />

      <v-card-text class="modal-body">
        <div v-if="loading" class="flex justify-center py-8">
          <v-progress-circular indeterminate />
        </div>

        <div v-else-if="activities.length === 0" class="empty-state">
          <v-icon icon="mdi-history" size="40" color="line" />
          <p>Belum ada aktivitas</p>
        </div>

        <template v-else>
          <div
            v-for="group in activities"
            :key="group.date_label"
            class="activity-group"
          >
            <!-- Date label -->
            <p class="date-label">{{ group.date_label }}</p>

            <!-- Activity rows -->
            <div
              v-for="item in group.activities"
              :key="item.id"
              class="activity-row"
            >
              <v-avatar size="32" color="field" class="activity-avatar">
                <v-icon icon="mdi-account" size="16" color="second-btn" />
              </v-avatar>

              <div class="activity-info">
                <p class="activity-text">
                  <span class="activity-username">{{ item.user_name }},</span>
                  updated field
                  <span class="activity-field">"{{ item.field_updated }}"</span>
                  on
                  <span class="activity-code">{{ item.task_code }}</span>
                </p>
                <p class="activity-time">{{ item.time_ago }}</p>
              </div>
            </div>
          </div>
        </template>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import api from "../api/axios";
import type { ActivityGroup } from "../types";

const props = defineProps<{
  modelValue: boolean;
  spaceId: number;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", val: boolean): void;
}>();

const isOpen = ref<boolean>(props.modelValue);

watch(
  () => props.modelValue,
  (val) => {
    isOpen.value = val;
    if (val) ambilData();
  },
);
watch(isOpen, (val) => {
  emit("update:modelValue", val);
});

const loading = ref<boolean>(false);
const activities = ref<ActivityGroup[]>([]);

const ambilData = async (): Promise<void> => {
  loading.value = true;
  try {
    const res = await api.get<ActivityGroup[]>(
      `/spaces/${props.spaceId}/recent`,
    );
    activities.value = res.data;
  } catch (err) {
    console.error("Gagal ambil recent activity:", err);
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px 16px;
}

.modal-title {
  font-family: "Barlow", sans-serif;
  font-weight: 800;
  font-size: 20px;
  color: #020f40;
}

.modal-body {
  padding: 8px 24px 24px !important;
  max-height: 520px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 40px 0;
  color: #8290a4;
  font-size: 14px;
}

.activity-group {
  margin-bottom: 8px;
}

.date-label {
  font-size: 12px;
  font-weight: 700;
  color: #65a9ec;
  padding: 12px 0 8px;
}

.activity-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid rgba(130, 144, 164, 0.2);
}

.activity-row:last-child {
  border-bottom: none;
}

.activity-avatar {
  flex-shrink: 0;
  margin-top: 2px;
}

.activity-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.activity-text {
  font-size: 13px;
  color: #111827;
  line-height: 1.5;
}

.activity-username {
  font-weight: 700;
  color: #020f40;
}

.activity-field {
  color: #374151;
}

.activity-code {
  font-weight: 700;
  color: #65a9ec;
  text-decoration: underline;
  cursor: pointer;
}

.activity-time {
  font-size: 11px;
  color: #8290a4;
}
</style>
