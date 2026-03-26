<template>
  <v-dialog v-model="dialog" max-width="400">
    <v-card rounded="xl">
      <v-card-title style="font-weight: 600; font-size: 16px">
        Create Child Item
      </v-card-title>

      <v-card-text>
        <v-text-field
          v-model="title"
          label="Task name"
          variant="outlined"
          density="compact"
          autofocus
          @keydown.enter="handleSubmit"
        />
      </v-card-text>

      <v-card-actions style="padding: 12px 16px">
        <v-spacer />
        <v-btn variant="text" @click="handleClose"> Cancel </v-btn>
        <v-btn
          color="primary"
          :loading="loading"
          :disabled="!title"
          @click="handleSubmit"
        >
          Create
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";

const props = defineProps<{
  modelValue: boolean;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: boolean): void;
  (e: "submit", title: string): void;
}>();

const dialog = computed({
  get: () => props.modelValue,
  set: (val: boolean) => emit("update:modelValue", val),
});

const title = ref("");
const loading = ref(false);

watch(
  () => props.modelValue,
  (val) => {
    if (val) title.value = "";
  },
);

const handleClose = () => {
  dialog.value = false;
};

const handleSubmit = async () => {
  if (!title.value.trim()) return;

  loading.value = true;

  try {
    await emit("submit", title.value);
    dialog.value = false;
  } finally {
    loading.value = false;
  }
};
</script>
