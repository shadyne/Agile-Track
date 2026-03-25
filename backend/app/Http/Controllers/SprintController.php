<?php

namespace App\Http\Controllers;

use App\Models\Sprint;
use App\Models\Board;
use App\Models\EpicItem;
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function index(Request $request, $boardId)
    {
        Board::whereHas('space', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->findOrFail($boardId);

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

    public function rename(Request $request, $boardId, $sprintId)
    {
        $sprint = Sprint::where('board_id', $boardId)->findOrFail($sprintId);
        $request->validate(['nama' => 'required|string|max:100']);
        $sprint->update(['nama' => $request->nama]);
        return response()->json(['message' => 'Sprint direname', 'data' => $sprint]);
    }

    public function store(Request $request, $boardId)
    {
        Board::whereHas('space', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->findOrFail($boardId);

        $request->validate([
            'nama'       => 'required|string|max:100',
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

    public function start(Request $request, $boardId, $sprintId)
    {
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
        $sprint = Sprint::where('board_id', $boardId)->findOrFail($sprintId);

        $sprint->update(['status' => 'completed']);

        EpicItem::where('sprint_id', $sprintId)
            ->where('status', '!=', 'done_by_qa')
            ->update(['sprint_id' => null]);

        return response()->json(['message' => 'Sprint selesai']);
    }

    public function destroy(Request $request, $boardId, $sprintId)
    {
        $sprint = Sprint::where('board_id', $boardId)->findOrFail($sprintId);

        EpicItem::where('sprint_id', $sprintId)->update(['sprint_id' => null]);

        $sprint->delete();

        return response()->json(['message' => 'Sprint dihapus']);
    }
}