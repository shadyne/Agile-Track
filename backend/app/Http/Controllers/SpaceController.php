<?php

namespace App\Http\Controllers;

use App\Models\Space;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        $spaces = Space::where('user_id', $request->user()->id)
            ->withCount('tasks')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($spaces);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'          => 'required|string|max:255',
            'key'           => 'required|string|max:10|unique:spaces,key',
            'member_emails' => 'nullable|array',
            'member_emails.*' => 'email',
        ], [
            'key.unique' => 'Key ini sudah dipakai, coba yang lain',
        ]);

        $space = Space::create([
            'user_id'       => $request->user()->id,
            'nama'          => $request->nama,
            'key'           => strtoupper($request->key),
            'member_emails' => $request->member_emails ?? [],
        ]);

        return response()->json([
            'message' => 'Space berhasil dibuat',
            'data'    => $space,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $space = Space::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $request->validate([
            'nama'            => 'required|string|max:255',
            'key'             => 'required|string|max:10|unique:spaces,key,' . $id,
            'member_emails'   => 'nullable|array',
            'member_emails.*' => 'email',
        ]);

        $space->update([
            'nama'          => $request->nama,
            'key'           => strtoupper($request->key),
            'member_emails' => $request->member_emails ?? [],
        ]);

        return response()->json([
            'message' => 'Space berhasil diupdate',
            'data'    => $space,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $space = Space::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $space->delete();

        return response()->json(['message' => 'Space berhasil dihapus']);
    }
}