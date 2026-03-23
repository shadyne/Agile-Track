<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RecentController extends Controller
{
    public function index(Request $request, $spaceId)
    {
        $logs = ActivityLog::with('user')
            ->where('space_id', $spaceId)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        $grouped = $logs->groupBy(function ($log) {
            $date = $log->created_at;
            if ($date->isToday())    return 'Today';
            if ($date->isYesterday()) return 'Yesterday';
            if ($date->isCurrentWeek()) return 'This Week';
            return $date->format('d M Y');
        });

        $result = [];
        foreach ($grouped as $label => $items) {
            $result[] = [
                'date_label' => $label,
                'activities' => $items->map(fn($log) => [
                    'id'            => $log->id,
                    'user_name'     => $log->user->name,
                    'field_updated' => $log->field_updated,
                    'task_code'     => $log->task_code,
                    'time_ago'      => $log->created_at->diffForHumans(),
                ])
            ];
        }

        return response()->json($result);
    }
}