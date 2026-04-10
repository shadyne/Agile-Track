<?php

namespace App\Http\Controllers;

use App\Models\EpicItem;
use App\Models\Epic;
use App\Models\ActivityLog;
use App\Models\Board;
use App\Models\User;
use Illuminate\Http\Request;

class ItemDetailController extends Controller
{

    private function authorizeBoard(int $boardId, Request $request): Board
    {
        $user = $request->user();

        return Board::whereHas('space', function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereJsonContains('member_emails', $user->email);
        })->findOrFail($boardId);
    }

    public function show(Request $request, $boardId, $itemId)
    {
        $this->authorizeBoard($boardId, $request);

        $item = EpicItem::with([
            'epic',
            'user',
            'assignee',
            'sprint',
            'parent',
            'children.assignee',
            'children',
            'children.epic',
        ])
        ->where('board_id', $boardId)
        ->findOrFail($itemId);

        return response()->json($item);
    }

    public function update(Request $request, $boardId, $itemId)
    {
        $this->authorizeBoard($boardId, $request);

        $item = EpicItem::where('board_id', $boardId)->findOrFail($itemId);

        $request->validate([
            'judul'             => 'sometimes|string|max:255',
            'priority'          => 'sometimes|in:highest,high,medium,low,lowest',
            'status'            => 'sometimes|string|max:50',
            'type'              => 'sometimes|in:story,task,qa_task,bug',
            'labels'            => 'sometimes|array|max:5',
            'labels.*'          => 'string|max:50',
            'deskripsi'         => 'sometimes|nullable|string',
            'start_date'        => 'sometimes|nullable|date',
            'end_date'          => 'sometimes|nullable|date',
            'assignee_id'       => 'sometimes|nullable',
            'epic_id'           => 'sometimes|nullable|exists:epics,id',
            'original_estimate' => 'sometimes|nullable|string|max:20',
        ]);

        $changed = array_keys($request->only([
            'judul', 'priority', 'status', 'type', 'labels',
            'deskripsi', 'start_date', 'end_date',
            'assignee_id', 'epic_id', 'original_estimate',
        ]));

        $data = $request->only($changed);

        if (array_key_exists('assignee_id', $data)) {
            $data['assignee_id'] = $this->resolveAssigneeId($data['assignee_id'], $request->user()->id);
        }

        $item->update($data);

        foreach ($changed as $field) {
            $val = $data[$field] ?? null;
            ActivityLog::create([
                'user_id'       => $request->user()->id,
                'space_id'      => $item->board->space_id,
                'task_id'       => null,
                'task_code'     => $item->kode,
                'field_updated' => $field,
                'old_value'     => null,
                'new_value'     => is_array($val)
                    ? implode(', ', $val)
                    : $val,
            ]);
        }

        return response()->json([
            'message' => 'Task diupdate',
            'data'    => $item->fresh([
                'epic', 'user', 'assignee', 'sprint', 'parent',
                'children.assignee', 'children.epic', 'children'
            ]),
        ]);
    }

    public function storeChild(Request $request, $boardId, $parentId)
    {
        $this->authorizeBoard($boardId, $request);

        $parent = EpicItem::where('board_id', $boardId)->findOrFail($parentId);

        $request->validate([
            'judul'   => 'required|string|max:255',
            'epic_id' => 'nullable|exists:epics,id',
        ]);

        $epicId = $request->epic_id ?? $parent->epic_id;

        $board = $parent->board()->with('space')->first();
        $kode  = $this->generateKode($board->space->key, $boardId);

        $task = EpicItem::create([
            'board_id'   => $boardId,
            'epic_id'    => $epicId,
            'parent_id'  => $parent->id,
            'judul'      => $request->judul,
            'kode'       => $kode,
            'type'       => 'task',
            'priority'   => 'medium',
            'status'     => 'to_do',
            'user_id'    => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Child berhasil dibuat',
            'data'    => $task->load(['assignee', 'epic']),
        ]);
    }

    public function history(Request $request, $boardId, $itemId)
    {
        $this->authorizeBoard($boardId, $request);

        $item = EpicItem::where('board_id', $boardId)->findOrFail($itemId);

        $logs = ActivityLog::with('user')
            ->where('task_code', $item->kode)
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