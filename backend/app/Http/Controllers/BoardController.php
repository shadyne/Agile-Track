<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Space;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function index(Request $request, $spaceId)
    {
        $space = Space::where('id', $spaceId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $boards = $space->boards()->orderBy('created_at', 'desc')->get();

        return response()->json($boards);
    }

    public function show(Request $request, $boardId)
{
    $board = Board::with('space')
        ->whereHas('space', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })
        ->findOrFail($boardId);

    return response()->json($board);
}

    public function store(Request $request, $spaceId)
    {
        $space = Space::where('id', $spaceId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $request->validate([
            'nama'            => 'required|string|max:255',
            'framework'       => 'required|in:scrum,kanban',
            'member_emails'   => 'nullable|array',
            'member_emails.*' => 'email',
        ]);

        $board = Board::create([
            'space_id'      => $space->id,
            'user_id'       => $request->user()->id,
            'nama'          => $request->nama,
            'framework'     => $request->framework,
            'member_emails' => $request->member_emails ?? [],
        ]);

        return response()->json([
            'message' => 'Board berhasil dibuat',
            'data'    => $board,
        ], 201);
    }

    public function update(Request $request, $spaceId, $boardId)
{
    $board = Board::whereHas('space', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })
        ->where('space_id', $spaceId)
        ->where('id', $boardId)
        ->firstOrFail();

    $request->validate([
        'nama'            => 'required|string|max:255',
        'framework'       => 'required|in:scrum,kanban',
        'member_emails'   => 'nullable|array',
        'member_emails.*' => 'email',
    ]);

    $board->update($request->only(['nama', 'framework', 'member_emails']));

    return response()->json([
        'message' => 'Board berhasil diupdate',
        'data'    => $board,
    ]);
}

    public function destroy(Request $request, $spaceId, $boardId)
    {
        $board = Board::whereHas('space', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->where('space_id', $spaceId)
            ->where('id', $boardId)
            ->firstOrFail();

        $board->delete();

        return response()->json(['message' => 'Board berhasil dihapus']);
    }
}