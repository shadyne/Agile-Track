<?php

namespace App\Http\Controllers;

use App\Models\Space;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Epic;

class DashboardController extends Controller
{
    public function global(Request $request)
{
    $tasks = Epic::whereHas('board.space', function ($q) use ($request) {
        $q->where('user_id', $request->user()->id);
    });

    $sevenDaysAgo = Carbon::now()->subDays(7);
    $sevenDaysNext = Carbon::now()->addDays(7);

    $summary = [
        'completed' => (clone $tasks)
            ->where('status', 'done_by_qa')
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count(),

        'updated' => (clone $tasks)
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count(),

        'created' => (clone $tasks)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count(),

        'due_soon' => (clone $tasks)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [Carbon::now(), $sevenDaysNext])
            ->count(),
    ];

    $priorityBreakdown = (clone $tasks)
        ->selectRaw('priority, count(*) as total')
        ->groupBy('priority')
        ->pluck('total', 'priority');

    $priorities = ['highest', 'high', 'medium', 'low', 'lowest'];
    $priorityData = [];
    foreach ($priorities as $p) {
        $priorityData[$p] = $priorityBreakdown[$p] ?? 0;
    }

    $statusBreakdown = (clone $tasks)
        ->selectRaw('status, count(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status');

    $statuses = ['to_do', 'in_progress', 'done_by_dev', 'testing', 'done_by_qa'];
    $statusData = [];
    foreach ($statuses as $s) {
        $statusData[$s] = $statusBreakdown[$s] ?? 0;
    }

    return response()->json([
        'summary' => $summary,
        'priority_breakdown' => $priorityData,
        'status_overview' => $statusData,
    ]);
}

    public function index(Request $request, $spaceId)
    {
        $space = Space::where('id', $spaceId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $tasks = Epic::whereHas('board.space', function ($q) use ($spaceId) {
            $q->where('id', $spaceId);
        });
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $sevenDaysNext = Carbon::now()->addDays(7);

        $summary = [
            'completed' => (clone $tasks)
                ->where('status', 'done_by_qa')
                ->where('updated_at', '>=', $sevenDaysAgo)
                ->count(),

            'updated'   => (clone $tasks)
                ->where('updated_at', '>=', $sevenDaysAgo)
                ->count(),

            'created'   => (clone $tasks)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->count(),

            'due_soon'  => (clone $tasks)
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [Carbon::now(), $sevenDaysNext])
                ->count(),
        ];

        $priorityBreakdown = (clone $tasks)
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $priorities = ['highest', 'high', 'medium', 'low', 'lowest'];
        $priorityData = [];
        foreach ($priorities as $p) {
            $priorityData[$p] = $priorityBreakdown[$p] ?? 0;
        }

        $statusBreakdown = (clone $tasks)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = ['to_do', 'in_progress', 'done_by_dev', 'testing', 'done_by_qa'];
        $statusData = [];
        foreach ($statuses as $s) {
            $statusData[$s] = $statusBreakdown[$s] ?? 0;
        }

        return response()->json([
            'space'             => $space->nama,
            'summary'           => $summary,
            'priority_breakdown' => $priorityData,
            'status_overview'   => $statusData,
        ]);
    }
}

