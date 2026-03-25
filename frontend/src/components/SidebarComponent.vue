<template>
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
        @click="emit('update:activeView', 'dashboard')"
      >
        <v-icon icon="mdi-view-dashboard-outline" size="18" />
        Dashboard
      </RouterLink>

      <a class="menu-item" style="cursor: pointer" @click="emit('openRecent')">
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
          @click.stop="emit('openCreateSpace')"
        />
      </div>

      <div v-if="spacesExpanded" class="space-list">
        <div v-for="space in spaceStore.spaces" :key="space.id">
          <div
            class="space-item"
            :class="{
              active: activeView === 'space' && activeSpaceId === space.id,
            }"
            @click="toggleSpace(space.id)"
          >
            <div class="space-item-left">
              <v-icon
                :icon="
                  expandedSpaces.includes(space.id)
                    ? 'mdi-chevron-down'
                    : 'mdi-chevron-right'
                "
                size="14"
                color="#020f40"
                @click.stop="toggleSpace(space.id)"
              />
              <span class="space-name">{{ space.nama }}</span>
            </div>

            <div class="space-item-actions" @click.stop>
              <v-btn
                icon="mdi-plus"
                variant="text"
                size="20"
                style="
                  background-color: #0034f61a;
                  border-radius: 4px;
                  border: 1px solid #0034f61a;
                "
                color="#020f40"
                title="Tambah Board"
                @click="emit('openCreateBoard', space)"
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
                    @click="emit('openEditSpace', space)"
                  />
                  <v-list-item
                    prepend-icon="mdi-trash-can-outline"
                    title="Delete Space"
                    base-color="cancel"
                    @click="emit('openDeleteSpace', space)"
                  />
                </v-list>
              </v-menu>
            </div>
          </div>

          <div v-if="expandedSpaces.includes(space.id)" class="board-list">
            <div
              v-for="board in spaceStore.getBoardsBySpace(space.id)"
              :key="board.id"
              class="board-item"
              :class="{ active: activeBoardId === board.id }"
              @click="pilihBoard(board)"
            >
              <div class="board-item-left">
                <span class="board-name">{{ board.nama }}</span>
              </div>

              <div class="board-item-actions" @click.stop>
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
                      prepend-icon="mdi-cog-outline"
                      title="Setting Board"
                      @click="emit('openBoardSetting', board)"
                    />
                    <v-list-item
                      prepend-icon="mdi-trash-can-outline"
                      title="Delete Board"
                      base-color="cancel"
                      @click="emit('openDeleteBoard', space, board)"
                    />
                  </v-list>
                </v-menu>
              </div>
            </div>

            <p
              v-if="spaceStore.getBoardsBySpace(space.id).length === 0"
              class="no-board-text"
            >
              Belum ada board
            </p>
          </div>
        </div>

        <p v-if="spaceStore.spaces.length === 0" class="no-space-text">
          Belum ada space
        </p>
      </div>
    </nav>
  </aside>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useSpaceStore } from "../stores/space";
import type { Space, Board } from "../types";

const props = defineProps<{
  activeView: "dashboard" | "space";
  activeSpaceId: number;
  activeBoardId: number;
}>();

const emit = defineEmits<{
  (e: "update:activeView", val: "dashboard" | "space"): void;
  (e: "update:activeSpaceId", val: number): void;
  (e: "update:activeBoardId", val: number): void;
  (e: "openRecent"): void;
  (e: "openCreateSpace"): void;
  (e: "openEditSpace", space: Space): void;
  (e: "openDeleteSpace", space: Space): void;
  (e: "openCreateBoard", space: Space): void;
  (e: "openDeleteBoard", space: Space, board: Board): void;
  (e: "openBoardSetting", board: Board): void;
  (e: "spaceSelected", spaceId: number): void;
}>();

const router = useRouter();
const spaceStore = useSpaceStore();
const spacesExpanded = ref<boolean>(true);
const expandedSpaces = ref<number[]>([]);

const toggleSpace = (spaceId: number): void => {
  emit("update:activeView", "space");
  emit("update:activeSpaceId", spaceId);
  emit("spaceSelected", spaceId);

  const idx = expandedSpaces.value.indexOf(spaceId);
  if (idx === -1) {
    expandedSpaces.value.push(spaceId);
    spaceStore.ambilBoards(spaceId);
  } else {
    expandedSpaces.value.splice(idx, 1);
  }
};
const pilihBoard = (board: Board): void => {
  emit("update:activeView", "space");
  emit("update:activeSpaceId", board.space_id);
  emit("update:activeBoardId", board.id);
  router.push(`/board/${board.id}`);
};
</script>
