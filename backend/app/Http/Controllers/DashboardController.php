<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\Epic;
use App\Models\Board;
use App\Models\EpicItem;
use App\Models\WorkflowStatus;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function global(Request $request)
    {
        $user = $request->user();

        $epics = Epic::whereHas('board.space', function ($q) use ($user) {
            $q->where('user_id', $user->id)
            ->orWhereJsonContains('member_emails', $user->email);
        });

        $epicItems = EpicItem::whereHas('board.space', function ($q) use ($user) {
            $q->where('user_id', $user->id)
            ->orWhereJsonContains('member_emails', $user->email);
        });

        $sevenDaysAgo = Carbon::now()->subDays(7);
        $sevenDaysNext = Carbon::now()->addDays(7);

        $completedEpics = (clone $epics)
            ->where('status', 'done_by_qa')
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count();

        $completedItems = (clone $epicItems)
            ->where('status', 'done_by_qa')
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count();

        $updatedEpics = (clone $epics)
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count();

        $updatedItems = (clone $epicItems)
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count();

        $createdEpics = (clone $epics)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();

        $createdItems = (clone $epicItems)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();

        $dueSoonEpics = (clone $epics)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [Carbon::now(), $sevenDaysNext])
            ->count();

        $dueSoonItems = (clone $epicItems)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [Carbon::now(), $sevenDaysNext])
            ->count();

        $summary = [
            'completed' => $completedEpics + $completedItems,
            'updated'   => $updatedEpics + $updatedItems,
            'created'   => $createdEpics + $createdItems,
            'due_soon'  => $dueSoonEpics + $dueSoonItems,
        ];

        $epicPriorities = (clone $epics)
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $itemPriorities = (clone $epicItems)
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $priorities = ['highest', 'high', 'medium', 'low', 'lowest'];
        $priorityData = [];
        foreach ($priorities as $p) {
            $priorityData[$p] = ($epicPriorities[$p] ?? 0) + ($itemPriorities[$p] ?? 0);
        }

        $epicStatuses = (clone $epics)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $itemStatuses = (clone $epicItems)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $allStatuses = array_unique(array_merge(
            array_keys($epicStatuses->toArray()),
            array_keys($itemStatuses->toArray())
        ));

        $workflowStatuses = WorkflowStatus::whereIn('value', $allStatuses)
            ->get()
            ->keyBy('value');

        $statusData = [];
        foreach ($allStatuses as $status) {
            $workflow = $workflowStatuses->get($status);
            $statusData[] = [
                'status'    => $status,
                'label'     => $workflow?->label ?? ucfirst(str_replace('_', ' ', $status)),
                'total'     => ($epicStatuses[$status] ?? 0) + ($itemStatuses[$status] ?? 0),
                'color'     => $workflow?->color ?? '#6b7280',  
                'bg_color'  => $workflow?->bg_color ?? '#f3f4f6',
                'text_color'=> $workflow?->text_color ?? '#ffffff',
                'category'  => $workflow?->category ?? 'default',
            ];
        }

        usort($statusData, function ($a, $b) {
            return ($a['sort_order'] ?? 999) <=> ($b['sort_order'] ?? 999);
        });

        return response()->json([
            'summary'           => $summary,
            'priority_breakdown'=> $priorityData,
            'status_overview'   => $statusData,
        ]);
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

        $epics = Epic::whereHas('board.space', function ($q) use ($spaceId, $user) {
            $q->where('id', $spaceId)
            ->where(function ($q2) use ($user) {
                $q2->where('user_id', $user->id)
                    ->orWhereJsonContains('member_emails', $user->email);
            });
        });

        $epicItems = EpicItem::whereHas('board.space', function ($q) use ($spaceId) {
            $q->where('id', $spaceId)
            ->where(function ($q2) use ($user) {
                $q2->where('user_id', $user->id)
                    ->orWhereJsonContains('member_emails', $user->email);
            });
        });

        $sevenDaysAgo = Carbon::now()->subDays(7);
        $sevenDaysNext = Carbon::now()->addDays(7);

        $completedEpics = (clone $epics)
            ->where('status', 'done_by_qa')
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count();

        $completedItems = (clone $epicItems)
            ->where('status', 'done_by_qa')
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count();

        $updatedEpics = (clone $epics)
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count();

        $updatedItems = (clone $epicItems)
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count();

        $createdEpics = (clone $epics)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();

        $createdItems = (clone $epicItems)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();

        $dueSoonEpics = (clone $epics)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [Carbon::now(), $sevenDaysNext])
            ->count();

        $dueSoonItems = (clone $epicItems)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [Carbon::now(), $sevenDaysNext])
            ->count();

        $summary = [
            'completed' => $completedEpics + $completedItems,
            'updated'   => $updatedEpics + $updatedItems,
            'created'   => $createdEpics + $createdItems,
            'due_soon'  => $dueSoonEpics + $dueSoonItems,
        ];

        $epicPriorities = (clone $epics)
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $itemPriorities = (clone $epicItems)
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $priorities = ['highest', 'high', 'medium', 'low', 'lowest'];
        $priorityData = [];
        foreach ($priorities as $p) {
            $priorityData[$p] = ($epicPriorities[$p] ?? 0) + ($itemPriorities[$p] ?? 0);
        }

        $epicStatuses = (clone $epics)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $itemStatuses = (clone $epicItems)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $allStatuses = array_unique(array_merge(
            array_keys($epicStatuses->toArray()),
            array_keys($itemStatuses->toArray())
        ));

        $workflowStatuses = WorkflowStatus::whereIn('value', $allStatuses)
            ->get()
            ->keyBy('value');

        $statusData = [];
        foreach ($allStatuses as $status) {
            $workflow = $workflowStatuses->get($status);
            $statusData[] = [
                'status'     => $status,
                'label'      => $workflow?->label ?? ucfirst(str_replace('_', ' ', $status)),
                'total'      => ($epicStatuses[$status] ?? 0) + ($itemStatuses[$status] ?? 0),
                'color'      => $workflow?->color ?? '#6b7280',
                'bg_color'   => $workflow?->bg_color ?? '#f3f4f6',
                'text_color' => $workflow?->text_color ?? '#ffffff',
                'category'   => $workflow?->category ?? 'default',
                'sort_order' => $workflow?->sort_order ?? 999,
            ];
        }

        usort($statusData, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        return response()->json([
            'space'              => $space->nama,
            'summary'            => $summary,
            'priority_breakdown' => $priorityData,
            'status_overview'    => $statusData,
        ]);
    }
}