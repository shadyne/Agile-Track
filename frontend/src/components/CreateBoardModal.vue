<template>
  <v-dialog v-model="isOpen" max-width="420" persistent>
    <v-card rounded="xl">
      <v-card-title class="modal-header">
        <span class="modal-title">Create Board</span>
      </v-card-title>

      <v-card-text class="modal-body">
        <v-form @submit.prevent="handleSubmit">
          <div class="field-wrap">
            <label class="field-label"
              >Framework <span class="req">*</span></label
            >
            <v-select
              v-model="form.framework"
              :items="frameworkOptions"
              item-title="label"
              item-value="value"
              variant="outlined"
              placeholder="Pilih framework"
              :error-messages="errors.framework"
            />
          </div>

          <div class="field-wrap">
            <label class="field-label">Name <span class="req">*</span></label>
            <v-text-field
              v-model="form.nama"
              variant="outlined"
              placeholder="Board name"
              :error-messages="errors.nama"
            />
          </div>

          <div class="field-wrap">
            <label class="field-label">Bring team along</label>
            <v-select
              v-model="form.member_emails"
              variant="outlined"
              :items="memberOptions"
              item-title="title"
              item-value="value"
              placeholder="Pilih member"
              multiple
              chips
              closable-chips
              :no-data-text="
                memberOptions.length === 0
                  ? 'Belum ada member di space ini'
                  : 'Member tidak ditemukan'
              "
            >
              <template #chip="{ item, props: chipProps }">
                <v-chip
                  v-bind="chipProps"
                  size="small"
                  color="primary"
                  closable
                >
                  {{ item.title }}
                </v-chip>
              </template>
            </v-select>
          </div>
        </v-form>
      </v-card-text>

      <v-card-actions class="modal-actions">
        <v-btn
          color="cancel"
          variant="flat"
          class="action-btn"
          style="box-shadow: 0 4px 6px -2px rgba(0, 0, 0, 0.48)"
          @click="isOpen = false"
        >
          Cancel
        </v-btn>
        <v-btn
          color="create"
          variant="flat"
          class="action-btn"
          style="box-shadow: 0 4px 6px -2px rgba(0, 0, 0, 0.48)"
          :loading="loading"
          @click="handleSubmit"
        >
          <span class="create-btn-text">Create</span>
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from "vue";
import { useSpaceStore } from "../stores/space";
import type { BoardForm, Space } from "../types";

const props = defineProps<{
  modelValue: boolean;
  space: Space | null;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", val: boolean): void;
  (e: "created"): void;
}>();

const spaceStore = useSpaceStore();
const isOpen = ref<boolean>(props.modelValue);
const loading = ref<boolean>(false);

const form = reactive<BoardForm>({
  nama: "",
  framework: "scrum",
  member_emails: [],
});

const errors = reactive<{ framework: string; nama: string }>({
  framework: "",
  nama: "",
});

const frameworkOptions = [
  { label: "Scrum", value: "scrum" },
  { label: "Kanban", value: "kanban" },
];

const memberOptions = computed(() => {
  const members = props.space?.member_emails ?? [];
  return members.map((email) => ({
    title: email,
    value: email,
  }));
});

watch(
  () => props.modelValue,
  (val) => {
    isOpen.value = val;
    if (val) resetForm();
  },
);
watch(isOpen, (val) => emit("update:modelValue", val));

const resetForm = (): void => {
  form.nama = "";
  form.framework = "scrum";
  form.member_emails = [];
  errors.framework = "";
  errors.nama = "";
};

const validate = (): boolean => {
  errors.framework = "";
  errors.nama = "";
  let valid = true;

  if (!form.framework) {
    errors.framework = "Framework wajib dipilih";
    valid = false;
  }
  if (!form.nama.trim()) {
    errors.nama = "Nama board wajib diisi";
    valid = false;
  }
  return valid;
};

const handleSubmit = async (): Promise<void> => {
  if (!validate() || !props.space) return;

  loading.value = true;
  try {
    await spaceStore.createBoard(props.space.id, form);
    emit("created");
    isOpen.value = false;
  } catch (err: any) {
    const serverErrors = err.response?.data?.errors;
    if (serverErrors?.nama) errors.nama = serverErrors.nama[0];
    if (serverErrors?.framework) errors.framework = serverErrors.framework[0];
  } finally {
    loading.value = false;
  }
};
</script>
