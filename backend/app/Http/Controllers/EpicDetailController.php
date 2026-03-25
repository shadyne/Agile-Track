<?php

namespace App\Http\Controllers;

use App\Models\Epic;
use App\Models\EpicComment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class EpicDetailController extends Controller
{
    public function show($boardId, $epicId)
    {
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
        $epic = Epic::where('board_id', $boardId)->findOrFail($epicId);

        $request->validate([
            'judul'             => 'sometimes|string|max:20',
            'priority'          => 'sometimes|in:highest,high,medium,low,lowest',
            'status'            => 'sometimes|in:to_do,in_progress,done_by_dev,testing,done_by_qa',
            'labels'            => 'sometimes|array|max:5',
            'labels.*'          => 'string|max:50',
            'deskripsi'         => 'sometimes|nullable|string',
            'start_date'        => 'sometimes|nullable|date',
            'end_date'          => 'sometimes|nullable|date',
            'assignee_id'       => 'sometimes|nullable|exists:users,id',
            'original_estimate' => 'sometimes|nullable|string|max:20',
        ]);

        $changed = array_keys($request->only([
            'judul', 'priority', 'status', 'labels',
            'deskripsi', 'start_date', 'end_date',
            'assignee_id', 'original_estimate'
        ]));

        $epic->update($request->only($changed));

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

    public function storeComment(Request $request, $boardId, $epicId)
    {
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
        $comment = EpicComment::where('epic_id', $epicId)
            ->where('user_id', $request->user()->id)
            ->findOrFail($commentId);

        $comment->delete();

        return response()->json(['message' => 'Komentar dihapus']);
    }

    public function history($boardId, $epicId)
    {
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
}