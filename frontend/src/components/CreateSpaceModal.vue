<template>
  <v-dialog v-model="isOpen" max-width="440" persistent>
    <v-card rounded="xl">
      <v-card-title class="modal-header">
        <span class="modal-title">{{
          isEdit ? "Edit Space" : "Create Space"
        }}</span>
      </v-card-title>

      <v-card-text class="modal-body">
        <v-form @submit.prevent="handleSubmit">
          <div class="field-wrap">
            <label class="field-label">Name <span class="req">*</span></label>
            <v-text-field
              v-model="form.nama"
              placeholder="Space Name"
              variant="outlined"
              :error-messages="errors.nama"
              @input="generateKey"
            />
          </div>

          <!-- Key -->
          <div class="field-wrap">
            <label class="field-label">Key <span class="req">*</span></label>
            <v-text-field
              v-model="form.key"
              variant="outlined"
              placeholder="KEY"
              :error-messages="errors.key"
              style="font-weight: 700"
              bg-color="#e8e8e8"
              @input="form.key = form.key.toUpperCase()"
            />
          </div>

          <div class="field-wrap">
            <label class="field-label">Bring team along</label>
            <v-text-field
              v-model="emailInput"
              variant="outlined"
              placeholder="Insert email"
              type="email"
              :error-messages="errors.email"
              @keydown.enter.prevent="addEmail"
            >
              <template #append-inner>
                <v-btn
                  size="x-small"
                  variant="text"
                  icon="mdi-plus"
                  @click="addEmail"
                />
              </template>
            </v-text-field>

            <div v-if="form.member_emails.length > 0" class="email-chips">
              <v-chip
                v-for="(email, i) in form.member_emails"
                :key="i"
                size="small"
                closable
                color="field"
                text-color="primary"
                @click:close="removeEmail(i)"
              >
                {{ email }}
              </v-chip>
            </div>
          </div>
        </v-form>
      </v-card-text>

      <v-card-actions class="modal-actions">
        <v-btn
          color="cancel"
          variant="flat"
          class="action-btn"
          style="box-shadow: 0 4px 6px -2px rgba(0, 0, 0, 0.48)"
          @click="handleCancel"
        >
          Cancel
        </v-btn>
        <v-btn
          color="create"
          variant="flat"
          style="box-shadow: 0 4px 6px -2px rgba(0, 0, 0, 0.48)"
          class="action-btn"
          :loading="loading"
          @click="handleSubmit"
        >
          <span class="create-btn-text">{{
            isEdit ? "Update" : "Create"
          }}</span>
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from "vue";
import { useSpaceStore } from "../stores/space";
import type { Space, SpaceForm } from "../types";

const props = defineProps<{
  modelValue: boolean;
  editData?: Space | null;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", val: boolean): void;
  (e: "created"): void;
}>();

const spaceStore = useSpaceStore();
const isOpen = ref<boolean>(props.modelValue);
const isEdit = ref<boolean>(false);
const loading = ref<boolean>(false);
const emailInput = ref<string>("");

const form = reactive<SpaceForm>({
  nama: "",
  key: "",
  member_emails: [],
});

const errors = reactive<{ nama: string; key: string; email: string }>({
  nama: "",
  key: "",
  email: "",
});

watch(
  () => props.modelValue,
  (val) => {
    isOpen.value = val;
    if (val) initForm();
  },
);
watch(isOpen, (val) => emit("update:modelValue", val));

const initForm = (): void => {
  if (props.editData) {
    isEdit.value = true;
    form.nama = props.editData.nama;
    form.key = props.editData.key;
    form.member_emails = [...(props.editData.member_emails || [])];
  } else {
    isEdit.value = false;
    form.nama = "";
    form.key = "";
    form.member_emails = [];
  }
  emailInput.value = "";
  errors.nama = "";
  errors.key = "";
  errors.email = "";
};

const generateKey = (): void => {
  if (!isEdit.value) {
    form.key = form.nama
      .replace(/[^a-zA-Z\s]/g, "")
      .trim()
      .substring(0, 3)
      .toUpperCase();
  }
};

const addEmail = (): void => {
  errors.email = "";
  const email = emailInput.value.trim();
  if (!email) return;

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    errors.email = "Format email tidak valid";
    return;
  }
  if (form.member_emails.includes(email)) {
    errors.email = "Email sudah ditambahkan";
    return;
  }

  form.member_emails.push(email);
  emailInput.value = "";
};

const removeEmail = (index: number): void => {
  form.member_emails.splice(index, 1);
};

const validate = (): boolean => {
  errors.nama = "";
  errors.key = "";
  let valid = true;

  if (!form.nama) {
    errors.nama = "Nama space wajib diisi";
    valid = false;
  }
  if (!form.key) {
    errors.key = "Key wajib diisi";
    valid = false;
  }
  if (form.key.length < 2) {
    errors.key = "Key minimal 2 karakter";
    valid = false;
  }
  return valid;
};

const handleSubmit = async (): Promise<void> => {
  if (!validate()) return;

  loading.value = true;
  try {
    if (isEdit.value && props.editData) {
      await spaceStore.updateSpace(props.editData.id, form);
    } else {
      await spaceStore.createSpace(form);
    }
    emit("created");
    isOpen.value = false;
  } catch (err: any) {
    const serverErrors = err.response?.data?.errors;
    if (serverErrors?.key) errors.key = serverErrors.key[0];
    if (serverErrors?.nama) errors.nama = serverErrors.nama[0];
  } finally {
    loading.value = false;
  }
};

const handleCancel = (): void => {
  isOpen.value = false;
};
</script>

<style scoped>
.modal-header {
  padding: 24px 24px 8px;
}

.modal-title {
  font-family: "Barlow", sans-serif;
  font-weight: 800;
  font-size: 20px;
  color: #020f40;
}

.modal-body {
  padding: 8px 24px 0 !important;
}

.field-wrap {
  margin-bottom: 4px;
}

.field-wrap input::placeholder {
  color: #020f40 !important;
}
.field-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #111827;
  margin-bottom: 4px;
}

.req {
  color: #020f40;
}

.email-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: -4px;
  margin-bottom: 12px;
}

.email-chips .v-chip {
  background-color: #020f40;
  color: white;
  font-weight: 700;
}
.modal-actions {
  padding: 12px 14px 14px;
  gap: 4px;
  justify-content: space-around;
}

.action-btn {
  min-width: 130px;
  min-height: 40px;
  border-radius: 8px;
  font-weight: 600;
  color: white !important;
}

.create-btn-text {
  color: #020f40;
  border-radius: 8px;
  font-weight: 600;
}
</style>
