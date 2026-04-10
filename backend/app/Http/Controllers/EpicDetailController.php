<?php

namespace App\Http\Controllers;

use App\Models\Epic;
use App\Models\EpicItem;
use App\Models\EpicComment;
use App\Models\ActivityLog;
use App\Models\Board;
use App\Models\User;
use Illuminate\Http\Request;

class EpicDetailController extends Controller
{

    private function authorizeBoard(int $boardId, Request $request): Board
    {
        $user = $request->user();

        return Board::whereHas('space', function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereJsonContains('member_emails', $user->email);
        })->findOrFail($boardId);
    }

    public function show(Request $request, $boardId, $epicId)
    {
        $this->authorizeBoard($boardId, $request);

        $epic = Epic::with([
            'items.user',
            'user',
            'assignee',
            'attachments',
            'comments.user',
        ])
        ->where('board_id', $boardId)
        ->findOrFail($epicId);

        return response()->json($epic);
    }

    public function update(Request $request, $boardId, $epicId)
    {
        $this->authorizeBoard($boardId, $request);

        $epic = Epic::where('board_id', $boardId)->findOrFail($epicId);

        $request->validate([
            'judul'             => 'sometimes|string|max:20',
            'priority'          => 'sometimes|in:highest,high,medium,low,lowest',
            'status'            => 'sometimes|string|max:50',
            'labels'            => 'sometimes|array|max:5',
            'labels.*'          => 'string|max:50',
            'deskripsi'         => 'sometimes|nullable|string',
            'start_date'        => 'sometimes|nullable|date',
            'end_date'          => 'sometimes|nullable|date',
            'assignee_id'       => 'sometimes|nullable',
            'original_estimate' => 'sometimes|nullable|string|max:20',
        ]);

        $changed = array_keys($request->only([
            'judul', 'priority', 'status', 'labels',
            'deskripsi', 'start_date', 'end_date',
            'assignee_id', 'original_estimate'
        ]));

        $data = $request->only($changed);

        if (array_key_exists('assignee_id', $data)) {
            $data['assignee_id'] = $this->resolveAssigneeId($data['assignee_id'], $request->user()->id);
        }

        $epic->update($data);

        foreach ($changed as $field) {
            ActivityLog::create([
                'user_id'       => $request->user()->id,
                'space_id'      => $epic->board->space_id,
                'task_id'       => null,
                'task_code'     => $epic->kode,
                'field_updated' => $field,
                'old_value'     => null,
                'new_value'     => is_array($request->$field)
                    ? implode(', ', $request->$field)
                    : $request->$field,
            ]);
        }

        return response()->json([
            'message' => 'Epic diupdate',
            'data'    => $epic->fresh(['items.user', 'user', 'assignee', 'comments.user']),
        ]);
    }

    public function storeChild(Request $request, $boardId, $epicId)
    {
        $board = $this->authorizeBoard($boardId, $request);
        $board->load('space');

        $epic = Epic::where('board_id', $boardId)->findOrFail($epicId);

        $request->validate([
            'judul' => 'required|string|max:100',
        ]);

        $kode = $this->generateKode($epic->board->space->key, $boardId);

        $task = EpicItem::create([
            'board_id'   => $boardId,
            'epic_id'    => $epicId,
            'judul'      => $request->judul,
            'kode'       => $kode,
            'type'       => 'task',
            'priority'   => 'medium',
            'status'     => 'to_do',
            'user_id'    => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Child item created',
            'data'    => $task->load('user'),
        ], 201);
    }

    public function storeComment(Request $request, $boardId, $epicId)
    {
        $this->authorizeBoard($boardId, $request);

        Epic::where('board_id', $boardId)->findOrFail($epicId);

        $request->validate([
            'isi' => 'required|string|max:2000',
        ]);

        $comment = EpicComment::create([
            'epic_id' => $epicId,
            'user_id' => $request->user()->id,
            'isi'     => $request->isi,
        ]);

        return response()->json([
            'message' => 'Komentar ditambahkan',
            'data'    => $comment->load('user'),
        ], 201);
    }

    public function deleteComment(Request $request, $boardId, $epicId, $commentId)
    {
        $this->authorizeBoard($boardId, $request);

        $comment = EpicComment::where('epic_id', $epicId)
            ->where('user_id', $request->user()->id)
            ->findOrFail($commentId);

        $comment->delete();

        return response()->json(['message' => 'Komentar dihapus']);
    }

    public function history(Request $request, $boardId, $epicId)
    {
        $this->authorizeBoard($boardId, $request);

        $epic = Epic::where('board_id', $boardId)->findOrFail($epicId);

        $logs = ActivityLog::with('user')
            ->where('task_code', $epic->kode)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($log) => [
                'id'            => $log->id,
                'user_name'     => $log->user->name,
                'user_initial'  => strtoupper(substr($log->user->name, 0, 1)),
                'field_updated' => $log->field_updated,
                'new_value'     => $log->new_value,
                'time_ago'      => $log->created_at->diffForHumans(),
            ]);

        return response()->json($logs);
    }

    private function generateKode(string $spaceKey, int $boardId): string
    {
        $maxEpic = \App\Models\Epic::where('board_id', $boardId)
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