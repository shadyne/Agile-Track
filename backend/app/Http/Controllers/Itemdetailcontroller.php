<?php

namespace App\Http\Controllers;

use App\Models\EpicItem;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ItemDetailController extends Controller
{
    public function show(Request $request, $boardId, $itemId)
    {
        $item = EpicItem::with([
            'epic',
            'user',
            'assignee',
            'sprint',
            'parent',
            'children.assignee',
            'children.epic',
        ])
        ->where('board_id', $boardId)
        ->findOrFail($itemId);

        return response()->json($item);
    }

    public function update(Request $request, $boardId, $itemId)
    {
        $item = EpicItem::where('board_id', $boardId)->findOrFail($itemId);

        $request->validate([
            'judul'             => 'sometimes|string|max:255',
            'priority'          => 'sometimes|in:highest,high,medium,low,lowest',
            'status'            => 'sometimes|in:to_do,in_progress,done_by_dev,testing,done_by_qa',
            'type'              => 'sometimes|in:story,task,qa_task,bug',
            'labels'            => 'sometimes|array|max:5',
            'labels.*'          => 'string|max:50',
            'deskripsi'         => 'sometimes|nullable|string',
            'start_date'        => 'sometimes|nullable|date',
            'end_date'          => 'sometimes|nullable|date',
            'assignee_id'       => 'sometimes|nullable|exists:users,id',
            'epic_id'           => 'sometimes|nullable|exists:epics,id',
            'original_estimate' => 'sometimes|nullable|string|max:20',
        ]);

        $changed = array_keys($request->only([
            'judul', 'priority', 'status', 'type', 'labels',
            'deskripsi', 'start_date', 'end_date',
            'assignee_id', 'epic_id', 'original_estimate',
        ]));

        $item->update($request->only($changed));

        foreach ($changed as $field) {
            $val = $request->$field;
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
                'epic', 'user', 'assignee', 'sprint', 'parent', 'children.assignee',
            ]),
        ]);
    }

    public function history($boardId, $itemId)
    {
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
}