import { defineStore } from "pinia";
import { ref } from "vue";
import api from "../api/axios";
import type { Space, SpaceForm, Board, BoardForm } from "../types";

export const useSpaceStore = defineStore("space", () => {
  const spaces = ref<Space[]>([]);
  const loading = ref<boolean>(false);

  const ambilSpaces = async (): Promise<void> => {
    loading.value = true;
    try {
      const res = await api.get<Space[]>("/spaces");
      spaces.value = res.data;
    } catch (err) {
      console.error("Gagal ambil spaces:", err);
    } finally {
      loading.value = false;
    }
  };

  const createSpace = async (form: SpaceForm): Promise<void> => {
    const res = await api.post("/spaces", form);
    spaces.value.unshift(res.data.data);
  };

  const updateSpace = async (id: number, form: SpaceForm): Promise<void> => {
    const res = await api.put(`/spaces/${id}`, form);
    const idx = spaces.value.findIndex((s) => s.id === id);
    if (idx !== -1) spaces.value[idx] = res.data.data;
  };

  const deleteSpace = async (id: number): Promise<void> => {
    await api.delete(`/spaces/${id}`);
    spaces.value = spaces.value.filter((s) => s.id !== id);
  };

  const ambilBoards = async (spaceId: number): Promise<Board[]> => {
    const res = await api.get<Board[]>(`/spaces/${spaceId}/boards`);
    return res.data;
  };

  const createBoard = async (
    spaceId: number,
    form: BoardForm,
  ): Promise<Board> => {
    const res = await api.post(`/spaces/${spaceId}/boards`, form);
    return res.data.data;
  };

  const deleteBoard = async (
    spaceId: number,
    boardId: number,
  ): Promise<void> => {
    await api.delete(`/spaces/${spaceId}/boards/${boardId}`);
  };

  return {
    spaces,
    loading,
    ambilSpaces,
    createSpace,
    updateSpace,
    deleteSpace,
    ambilBoards,
    createBoard,
    deleteBoard,
  };
});
