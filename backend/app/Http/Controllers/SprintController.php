<?php

namespace App\Http\Controllers;

use App\Models\Sprint;
use App\Models\Board;
use App\Models\EpicItem;
use Illuminate\Http\Request;

class SprintController extends Controller
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

        return response()->json($sprints);
    }

    public function store(Request $request, $boardId)
    {
        $this->authorizeBoard($boardId, $request);

        $request->validate([
            'nama'       => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $sprintCount = Sprint::where('board_id', $boardId)->count();
        $nama = $request->nama ?: 'Sprint ' . ($sprintCount + 1);

        $sprint = Sprint::create([
            'board_id'   => $boardId,
            'nama'       => $nama,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'status'     => 'planning',
        ]);

        return response()->json([
            'message' => 'Sprint berhasil dibuat',
            'data'    => $sprint->load('items'),
        ], 201);
    }

    public function rename(Request $request, $boardId, $sprintId)
    {
        $this->authorizeBoard($boardId, $request);

        $sprint = Sprint::where('board_id', $boardId)->findOrFail($sprintId);

        $request->validate(['nama' => 'required|string|max:100']);
        $sprint->update(['nama' => $request->nama]);

        return response()->json(['message' => 'Sprint direname', 'data' => $sprint]);
    }

    public function start(Request $request, $boardId, $sprintId)
    {
        $this->authorizeBoard($boardId, $request);

        $sprint = Sprint::where('board_id', $boardId)->findOrFail($sprintId);

        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        $sprint->update([
            'status'     => 'active',
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
        ]);

        return response()->json([
            'message' => 'Sprint dimulai',
            'data'    => $sprint->load('items'),
        ]);
    }

    public function complete(Request $request, $boardId, $sprintId)
    {
        $this->authorizeBoard($boardId, $request);

        $sprint = Sprint::where('board_id', $boardId)->findOrFail($sprintId);

        $allItems    = EpicItem::where('sprint_id', $sprintId)->get();
        $doneItems   = $allItems->where('status', 'done_by_qa');
        $undoneItems = $allItems->where('status', '!=', 'done_by_qa');

        EpicItem::whereIn('id', $doneItems->pluck('id'))
            ->update(['sprint_id' => null]);

        if ($undoneItems->isNotEmpty()) {
            $targetSprint = $this->findOrCreateNextSprint($boardId, $sprintId);

            EpicItem::whereIn('id', $undoneItems->pluck('id'))
                ->update(['sprint_id' => $targetSprint->id]);
        }

        $sprint->update(['status' => 'completed']);

        $nextSprint = isset($targetSprint)
            ? $targetSprint->load([
                'items' => fn($q) => $q->whereNull('parent_id')
                    ->with(['epic', 'assignee', 'children.assignee', 'children.epic'])
              ])
            : null;

        return response()->json([
            'message'     => 'Sprint selesai',
            'done_count'  => $doneItems->count(),
            'moved_count' => $undoneItems->count(),
            'next_sprint' => $nextSprint,
        ]);
    }

    public function destroy(Request $request, $boardId, $sprintId)
    {
        $this->authorizeBoard($boardId, $request);

        $sprint = Sprint::where('board_id', $boardId)->findOrFail($sprintId);

        EpicItem::where('sprint_id', $sprintId)->update(['sprint_id' => null]);
        $sprint->delete();

        return response()->json(['message' => 'Sprint dihapus']);
    }

    private function findOrCreateNextSprint(int $boardId, int $excludeSprintId): Sprint
    {
        $next = Sprint::where('board_id', $boardId)
            ->whereIn('status', ['planning', 'active'])
            ->where('id', '!=', $excludeSprintId)
            ->orderBy('created_at', 'asc')
            ->first();

        if ($next) {
            return $next;
        }

        $count = Sprint::where('board_id', $boardId)->count();

        return Sprint::create([
            'board_id' => $boardId,
            'nama'     => 'Sprint ' . ($count + 1),
            'status'   => 'planning',
        ]);
    }
}