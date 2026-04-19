<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Epic;
use App\Models\EpicItem;
use App\Models\EpicAttachment;
use App\Models\Sprint;
use App\Models\User;
use App\Models\WorkflowStatus;
use Illuminate\Http\Request;

class BacklogController extends Controller
{

    private function authorizeBoard(int $boardId, Request $request): Board
    {
        $user = $request->user();

        return Board::whereHas('space', function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereJsonContains('member_emails', $user->email);
        })->findOrFail($boardId);
    }

    public function index(Request $request, $boardId)
    {
        $this->authorizeBoard($boardId, $request);

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
        $board = $this->authorizeBoard($boardId, $request);
        $board->load('space');

        $request->validate([
            'judul'             => 'required|string|max:255',
            'type'              => 'required|in:story,task,qa_task,bug',
            'priority'          => 'required|in:highest,high,medium,low,lowest',
            'epic_id'           => 'nullable|exists:epics,id',
            'labels'            => 'nullable|array|max:5',
            'labels.*'          => 'string|max:50',
            'start_date'        => 'nullable|date',
            'end_date'          => 'nullable|date',
            'deskripsi'         => 'nullable|string',
            'assignee_id'       => 'nullable',
            'parent_id'         => 'nullable|exists:epic_items,id',
            'sprint_id'         => 'nullable|exists:sprints,id',
            'original_estimate' => 'nullable|string|max:20',
            'status'            => 'nullable|string|max:50',
            'attachments'       => 'nullable|array',
            'attachments.*'     => 'file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:51200',
        ], [
            'type.required'     => 'Tipe task harus dipilih',
            'type.in'           => 'Tipe task tidak valid',
            'priority.required' => 'Prioritas harus dipilih',
            'priority.in'       => 'Prioritas tidak valid',
            'judul.required'    => 'Judul task wajib diisi',
        ]);

        $statusValue        = $this->resolveStatus($request->status, 'to_do');
        $resolvedAssigneeId = $this->resolveAssigneeId($request->assignee_id, $request->user()->id);
        $kode               = $this->generateKode($board->space->key, $boardId);

        $item = EpicItem::create([
            'epic_id'           => $request->epic_id,
            'parent_id'         => $request->parent_id,
            'board_id'          => $boardId,
            'user_id'           => $request->user()->id,
            'assignee_id'       => $resolvedAssigneeId,
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
        $this->authorizeBoard($boardId, $request);

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

        if ($statusValue === 'done_by_qa' && empty($item->epic_id)) {
            return response()->json([
                'message'    => 'Cannot set "Done by QA" — please assign this item to an epic first.',
                'error_code' => 'NO_EPIC_FOR_DONE_BY_QA',
            ], 422);
        }

        $item->update(['status' => $statusValue]);

        return response()->json(['message' => 'Status updated', 'data' => $item]);
    }

    public function moveToSprint(Request $request, $boardId, $itemId)
    {
        $this->authorizeBoard($boardId, $request);

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

    public function updateEpic(Request $request, $boardId, $itemId)
    {
        $this->authorizeBoard($boardId, $request);

        $item = EpicItem::where('board_id', $boardId)->findOrFail($itemId);

        $request->validate([
            'epic_id' => 'nullable|exists:epics,id',
        ]);

        $item->update(['epic_id' => $request->epic_id]);

        return response()->json([
            'message' => 'Epic assignment updated',
            'data'    => $item->load(['epic', 'assignee']),
        ]);
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
        if (empty($value)) return null;

        if (is_string($value) && str_contains($value, '@')) {
            return User::where('email', $value)->first()?->id;
        }

        $numericId = (int) $value;
        return $numericId === $currentUserId ? $currentUserId : User::find($numericId)?->id;
    }

    private function resolveStatus(?string $status, ?string $default = null): ?string
    {
        if (empty($status)) return $default;

        $workflowStatus = WorkflowStatus::where('value', $status)->first();
        if ($workflowStatus) return $status;

        return $default;
    }
}