<?php

namespace App\Http\Controllers;

use App\Models\Space;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $spaces = Space::where('user_id', $user->id)
            ->orWhereJsonContains('member_emails', $user->email)
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

         $memberEmails = $request->member_emails ?? [];

        $space = Space::create([
            'user_id'       => $request->user()->id,
            'nama'          => $request->nama,
            'key'           => strtoupper($request->key),
            'member_emails' => $request->member_emails ?? [],
        ]);

         $this->notifyNewMembers(
            emails: $memberEmails,
            spaceName: $space->nama,
            spaceId: $space->id,
            addedBy: $request->user()->name,
            previousEmails: []
        );
 

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
        $previousEmails = $space->member_emails ?? [];
        $newEmails      = $request->member_emails ?? [];
        $addedEmails    = array_diff($newEmails, $previousEmails);

        $space->update([
            'nama'          => $request->nama,
            'key'           => strtoupper($request->key),
            'member_emails' => $request->member_emails ?? [],
        ]);

         $this->notifyNewMembers(
            emails: array_values($addedEmails),
            spaceName: $space->nama,
            spaceId: $space->id,
            addedBy: $request->user()->name,
            previousEmails: $previousEmails
        );

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
    private function notifyNewMembers(
        array  $emails,
        string $spaceName,
        int    $spaceId,
        string $addedBy,
        array  $previousEmails
    ): void {
        if (empty($emails)) {
            return;
        }
 
        $users = User::whereIn('email', $emails)->get();
 
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type'    => 'space_member_added',
                'title'   => 'You were added to a space',
                'body'    => "{$addedBy} added you to space \"{$spaceName}\"",
                'data'    => [
                    'space_id'   => $spaceId,
                    'space_name' => $spaceName,
                    'added_by'   => $addedBy,
                ],
            ]);
        }
    }
}