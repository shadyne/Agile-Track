<?php

namespace App\Http\Controllers;

use App\Models\Epic;
use App\Models\Board;
use App\Models\Label;
use App\Models\EpicItem;
use App\Models\EpicAttachment;
use App\Models\User;
use Illuminate\Http\Request;

class EpicController extends Controller
{
    public function index(Request $request, $boardId)
    {
        Board::whereHas('space', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->findOrFail($boardId);

        $epics = Epic::with(['items', 'user', 'assignee', 'attachments'])
            ->where('board_id', $boardId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($epics);
    }

    public function store(Request $request, $boardId)
    {
        $board = Board::with('space')
            ->whereHas('space', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })->findOrFail($boardId);

        $request->validate([
            'judul'           => 'required|string|max:20',
            'priority'        => 'required|in:highest,high,medium,low,lowest',
            'labels'          => 'nullable|array|max:5',
            'labels.*'        => 'string|max:50',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'deskripsi'       => 'nullable|string',
            'assignee_id'     => 'nullable',
            'attachments'     => 'nullable|array',
            'attachments.*'   => 'file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:51200',
        ], [
            'judul.max'  => 'Epic name maksimal 20 karakter',
            'labels.max' => 'Labels maksimal 5',
        ]);

        if ($request->labels) {
            foreach ($request->labels as $labelNama) {
                Label::firstOrCreate([
                    'board_id' => $boardId,
                    'nama'     => $labelNama,
                ]);
            }
        }

        $resolvedAssigneeId = $this->resolveAssigneeId($request->assignee_id, $request->user()->id);

        $kode = $this->generateKode($board->space->key, $boardId);

        $epic = Epic::create([
            'board_id'    => $boardId,
            'user_id'     => $request->user()->id,
            'assignee_id' => $resolvedAssigneeId,
            'kode'        => $kode,
            'judul'       => $request->judul,
            'deskripsi'   => $request->deskripsi,
            'labels'      => $request->labels ?? [],
            'type'        => 'epic',
            'status'      => 'to_do',
            'priority'    => $request->priority,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('epic-attachments', 'public');
                EpicAttachment::create([
                    'epic_id'   => $epic->id,
                    'nama_file' => $file->getClientOriginalName(),
                    'path'      => $path,
                    'mime_type' => $file->getMimeType(),
                    'ukuran'    => $file->getSize(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Epic berhasil dibuat',
            'data'    => $epic->load(['items', 'user', 'assignee', 'attachments']),
        ], 201);
    }

    public function getLabels(Request $request, $boardId)
    {
        $labels = Label::where('board_id', $boardId)
            ->orderBy('nama')
            ->pluck('nama');

        return response()->json($labels);
    }

    public function show($boardId, $epicId)
    {
        $epic = Epic::with(['items', 'user', 'assignee', 'attachments', 'board.space'])
            ->where('board_id', $boardId)
            ->findOrFail($epicId);

        return response()->json($epic);
    }

    public function update(Request $request, $boardId, $epicId)
    {
        $epic = Epic::where('board_id', $boardId)->findOrFail($epicId);

        $request->validate([
            'judul'       => 'sometimes|string|max:20',
            'priority'    => 'in:highest,high,medium,low,lowest',
            'labels'      => 'nullable|array|max:5',
            'labels.*'    => 'string|max:50',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'deskripsi'   => 'nullable|string',
            'assignee_id' => 'nullable',
        ]);

        $data = $request->only([
            'judul', 'priority', 'labels', 'deskripsi',
            'start_date', 'end_date', 'assignee_id', 'status'
        ]);

        if (array_key_exists('assignee_id', $data)) {
            $data['assignee_id'] = $this->resolveAssigneeId($data['assignee_id'], $request->user()->id);
        }

        $epic->update($data);

        return response()->json([
            'message' => 'Epic berhasil diupdate',
            'data'    => $epic->fresh(['items', 'user', 'assignee']),
        ]);
    }

    public function destroy($boardId, $epicId)
    {
        $epic = Epic::where('board_id', $boardId)->findOrFail($epicId);
        $epic->delete();
        return response()->json(['message' => 'Epic berhasil dihapus']);
    }

    public function storeItem(Request $request, $boardId, $epicId)
    {
        $epic = Epic::where('board_id', $boardId)->findOrFail($epicId);
        $board = Board::with('space')->findOrFail($boardId);

        $request->validate([
            'judul'      => 'required|string|max:255',
            'type'       => 'in:story,task,bug,qa_task',
            'status'     => 'in:to_do,in_progress,done_by_dev,testing,done_by_qa',
            'priority'   => 'in:highest,high,medium,low,lowest',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $kode = $this->generateKode($board->space->key, $boardId);

        $item = EpicItem::create([
            'epic_id'    => $epic->id,
            'board_id'   => $boardId,
            'user_id'    => $request->user()->id,
            'kode'       => $kode,
            'judul'      => $request->judul,
            'deskripsi'  => $request->deskripsi,
            'labels'     => $request->labels ?? [],
            'type'       => $request->type ?? 'task',
            'status'     => $request->status ?? 'to_do',
            'priority'   => $request->priority ?? 'medium',
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
        ]);

        return response()->json(['message' => 'Item berhasil ditambahkan', 'data' => $item], 201);
    }

    private function generateKode(string $spaceKey, int $boardId): string
    {
        $maxEpic = Epic::where('board_id', $boardId)
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(kode, '-', -1) AS UNSIGNED)) as max_num")
            ->value('max_num') ?? 0;

        $maxItem = EpicItem::where('board_id', $boardId)
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(kode, '-', -1) AS UNSIGNED)) as max_num")
            ->value('max_num') ?? 0;

        $nextNum = max($maxEpic, $maxItem) + 1;

        return $spaceKey . '-' . $nextNum;
    }

    private function resolveAssigneeId(mixed $value, int $currentUserId): ?int
    {
        if (empty($value)) {
            return null;
        }

        if (is_string($value) && str_contains($value, '@')) {
            return User::where('email', $value)->first()?->id;
        }

        $numericId = (int) $value;
        if ($numericId === $currentUserId) {
            return $currentUserId;
        }

        return User::find($numericId)?->id;
    }
}