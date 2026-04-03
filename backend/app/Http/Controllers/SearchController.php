<?php

namespace App\Http\Controllers;

use App\Models\Epic;
use App\Models\EpicItem;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->query('q', ''));

        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $normalised = strtoupper(preg_replace('/\s+/', '', $q));

        $user = $request->user();

        $epics = Epic::with(['board.space'])
            ->whereHas('board.space', function ($sq) use ($user) {
                $sq->where('user_id', $user->id);
            })
            ->get()
            ->filter(function ($epic) use ($normalised) {
                $kode = strtoupper(preg_replace('/\s+/', '', $epic->kode));
                return str_contains($kode, $normalised);
            })
            ->take(5)
            ->map(fn($epic) => [
                'id'       => $epic->id,
                'kode'     => $epic->kode,
                'judul'    => $epic->judul,
                'type'     => 'epic',
                'board_id' => $epic->board_id,
                'status'   => $epic->status,
                'priority' => $epic->priority,
            ]);

        $items = EpicItem::with(['board.space'])
            ->whereHas('board.space', function ($sq) use ($user) {
                $sq->where('user_id', $user->id);
            })
            ->get()
            ->filter(function ($item) use ($normalised) {
                $kode = strtoupper(preg_replace('/\s+/', '', $item->kode));
                return str_contains($kode, $normalised);
            })
            ->take(5)
            ->map(fn($item) => [
                'id'       => $item->id,
                'kode'     => $item->kode,
                'judul'    => $item->judul,
                'type'     => $item->type,  
                'board_id' => $item->board_id,
                'status'   => $item->status,
                'priority' => $item->priority,
            ]);

        $results = $epics->values()->merge($items->values())->take(8);

        return response()->json($results);
    }
}