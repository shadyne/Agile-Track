<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Space;
use Illuminate\Http\Request;
use App\Models\User;

class BoardController extends Controller
{
    private function authorizeBoard(int $boardId, Request $request): Board
    {
        $user = $request->user();

        return Board::with('space')
            ->whereHas('space', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereJsonContains('member_emails', $user->email);
            })
            ->findOrFail($boardId);
    }

    public function index(Request $request, $spaceId)
    {
        $user = $request->user();

        $space = Space::where('id', $spaceId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereJsonContains('member_emails', $user->email);
            })
            ->firstOrFail();

        $boards = $space->boards()->orderBy('created_at', 'desc')->get();

        return response()->json($boards);
    }

    public function show(Request $request, $boardId)
    {
        return response()->json(
            $this->authorizeBoard($boardId, $request)
        );
    }

    public function store(Request $request, $spaceId)
    {
        $user = $request->user();

        $space = Space::where('id', $spaceId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereJsonContains('member_emails', $user->email);
            })
            ->firstOrFail();

        $request->validate([
            'nama'            => 'required|string|max:255',
            'framework'       => 'required|in:scrum,kanban',
            'member_emails'   => 'nullable|array',
            'member_emails.*' => 'email',
        ]);

        $board = Board::create([
            'space_id'      => $space->id,
            'user_id'       => $user->id,
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
        $user = $request->user();

        $board = Board::whereHas('space', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereJsonContains('member_emails', $user->email);
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
        $user = $request->user();

        $board = Board::whereHas('space', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereJsonContains('member_emails', $user->email);
            })
            ->where('space_id', $spaceId)
            ->where('id', $boardId)
            ->firstOrFail();

        $board->delete();

        return response()->json(['message' => 'Board berhasil dihapus']);
    }

    public function getMembers($boardId)
    {
        $board = Board::with('space')->findOrFail($boardId);

        $memberEmails = $board->space->member_emails ?? [];

        $members = User::whereIn('email', $memberEmails)
            ->select('id', 'name', 'email')
            ->get();

        return response()->json($members);
    }
}