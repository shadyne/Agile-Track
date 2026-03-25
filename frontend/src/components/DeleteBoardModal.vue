<template>
  <v-dialog v-model="isOpen" max-width="460" persistent>
    <v-card rounded="xl" class="delete-card">
      <v-card-text class="delete-body">
        <p class="delete-desc">
          "Apakah anda yakin akan menghapus board
          <strong>{{ boardName }}</strong> ini?"
        </p>
      </v-card-text>

      <v-card-actions class="delete-actions">
        <v-btn
          color="cancel"
          variant="flat"
          class="action-btn"
          @click="isOpen = false"
        >
          <span style="color: white; font-weight: 700">Cancel</span>
        </v-btn>
        <v-btn
          color="create"
          variant="flat"
          class="action-btn"
          :loading="loading"
          @click="handleDelete"
        >
          <span style="color: #020f40; font-weight: 700">Yes</span>
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { useSpaceStore } from "../stores/space";

const props = defineProps<{
  modelValue: boolean;
  spaceId: number;
  boardId: number;
  boardName: string;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", val: boolean): void;
  (e: "deleted"): void;
}>();

const spaceStore = useSpaceStore();
const isOpen = ref<boolean>(props.modelValue);
const loading = ref<boolean>(false);

watch(
  () => props.modelValue,
  (val) => (isOpen.value = val),
);
watch(isOpen, (val) => emit("update:modelValue", val));

const handleDelete = async (): Promise<void> => {
  loading.value = true;
  try {
    await spaceStore.deleteBoard(props.spaceId, props.boardId);
    emit("deleted");
    isOpen.value = false;
  } catch (err) {
    console.error("Gagal hapus board:", err);
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.delete-card {
  padding: 8px 0;
}

.delete-body {
  padding: 32px 36px 16px !important;
}

.delete-desc {
  font-size: 15px;
  color: #111827;
  line-height: 1.7;
  text-align: center;
}

.delete-actions {
  padding: 8px 36px 28px;
  gap: 12px;
  justify-content: center;
}

.action-btn {
  min-width: 120px;
  height: 40px;
  border-radius: 8px;
}
</style>
