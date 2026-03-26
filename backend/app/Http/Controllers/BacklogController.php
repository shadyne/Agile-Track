<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Epic;
use App\Models\EpicItem;
use App\Models\EpicAttachment;
use App\Models\Sprint;
use App\Models\WorkflowStatus;
use Illuminate\Http\Request;

class BacklogController extends Controller
{
    public function index(Request $request, $boardId)
    {
        Board::whereHas('space', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->findOrFail($boardId);

        $backlogItems = EpicItem::with(['epic', 'assignee', 'children.assignee', 'children.epic'])
            ->where('board_id', $boardId)
            ->whereNull('sprint_id')
            ->whereNull('parent_id')
            ->orderBy('created_at', 'asc')
            ->get();

        $sprints = Sprint::with([
            'items' => function ($q) {
                $q->whereNull('parent_id')
                  ->with(['epic', 'assignee', 'children.assignee', 'children.epic']);
            }
        ])
        ->where('board_id', $boardId)
        ->where('status', '!=', 'completed')
        ->orderBy('created_at', 'asc')
        ->get();

        return response()->json([
            'backlog' => $backlogItems,
            'sprints' => $sprints,
        ]);
    }

    public function store(Request $request, $boardId)
    {
        $board = Board::with('space')
            ->whereHas('space', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })->findOrFail($boardId);

        $request->validate([
            'judul'             => 'required|string|max:255',
            'type'              => 'required|in:story,task,qa_task,bug',
            'priority'          => 'required|in:highest,high,medium,low,lowest',
            'labels'            => 'nullable|array|max:5',
            'labels.*'          => 'string|max:50',
            'start_date'        => 'nullable|date',
            'end_date'          => 'nullable|date',
            'deskripsi'         => 'nullable|string',
            'assignee_id'       => 'nullable|exists:users,id',
            'epic_id'           => 'nullable|exists:epics,id',
            'parent_id'         => 'nullable|exists:epic_items,id',
            'sprint_id'         => 'nullable|exists:sprints,id',
            'original_estimate' => 'nullable|string|max:20',
            'status'            => 'nullable|string|max:50',
            'attachments'       => 'nullable|array',
            'attachments.*'     => 'file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:51200',
        ]);

        $statusValue = $this->resolveStatus($request->status, 'to_do');

        $spaceKey = $board->space->key;

        $lastItem  = EpicItem::where('board_id', $boardId)->orderBy('id', 'desc')->first();
        $lastEpic  = Epic::where('board_id', $boardId)->orderBy('id', 'desc')->first();
        $maxId     = max($lastItem?->id ?? 0, $lastEpic?->id ?? 0);
        $kode      = $spaceKey . '-' . ($maxId + 1);

        $item = EpicItem::create([
            'epic_id'           => $request->epic_id,
            'parent_id'         => $request->parent_id,
            'board_id'          => $boardId,
            'user_id'           => $request->user()->id,
            'assignee_id'       => $request->assignee_id,
            'sprint_id'         => $request->sprint_id,
            'kode'              => $kode,
            'judul'             => $request->judul,
            'deskripsi'         => $request->deskripsi,
            'labels'            => $request->labels ?? [],
            'type'              => $request->type,
            'status'            => $statusValue,
            'priority'          => $request->priority,
            'start_date'        => $request->start_date,
            'end_date'          => $request->end_date,
            'original_estimate' => $request->original_estimate,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('epic-attachments', 'public');
                EpicAttachment::create([
                    'epic_id'   => $item->epic_id,
                    'nama_file' => $file->getClientOriginalName(),
                    'path'      => $path,
                    'mime_type' => $file->getMimeType(),
                    'ukuran'    => $file->getSize(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Task berhasil dibuat',
            'data'    => $item->load(['epic', 'assignee', 'children']),
        ], 201);
    }

    public function updateStatus(Request $request, $boardId, $itemId)
    {
        $item = EpicItem::where('board_id', $boardId)->findOrFail($itemId);

        $request->validate([
            'status' => 'required|string|max:50',
        ]);

        $statusValue = $this->resolveStatus($request->status);

        if ($statusValue === null) {
            return response()->json([
                'message' => 'Status tidak valid: ' . $request->status,
            ], 422);
        }

        $item->update(['status' => $statusValue]);

        return response()->json(['message' => 'Status updated', 'data' => $item]);
    }

    public function moveToSprint(Request $request, $boardId, $itemId)
    {
        $item = EpicItem::where('board_id', $boardId)->findOrFail($itemId);

        $request->validate([
            'sprint_id' => 'nullable|exists:sprints,id',
        ]);

        $item->update(['sprint_id' => $request->sprint_id]);

        return response()->json([
            'message' => 'Item dipindah',
            'data'    => $item->load(['epic', 'assignee']),
        ]);
    }

    private function resolveStatus(?string $status, ?string $default = null): ?string
    {
        if (empty($status)) {
            return $default;
        }

        $hardcoded = ['to_do', 'in_progress', 'done_by_dev', 'testing', 'done_by_qa'];
        if (in_array($status, $hardcoded)) {
            return $status;
        }

        $workflowStatus = WorkflowStatus::where('value', $status)->first();
        if ($workflowStatus) {
            $categoryMap = [
                'To Do'       => 'to_do',
                'In Progress' => 'in_progress',
                'Done'        => 'done_by_qa',
            ];
            return $categoryMap[$workflowStatus->category] ?? 'to_do';
        }

        return $default;
    }
}