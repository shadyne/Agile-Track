<?php

namespace App\Http\Controllers;

use App\Models\WorkflowStatus;
use App\Models\EpicItem;
use App\Models\Epic;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkflowController extends Controller
{
    /**
     * Get all workflow statuses ordered by sort_order
     */
    public function index()
    {
        $statuses = WorkflowStatus::orderBy('sort_order')->get();
        return response()->json($statuses);
    }

    /**
     * Create a new workflow status
     */
    public function store(Request $request)
    {
        $request->validate([
            'label'       => 'required|string|max:50',
            'color'       => 'required|string|max:20',
            'text_color'  => 'nullable|string|max:20',
            'bg_color'    => 'nullable|string|max:30',
            'category'    => 'required|in:To Do,In Progress,Done',
            'description' => 'nullable|string|max:255',
        ]);

        $baseValue = Str::snake(strtolower($request->label));
        $value = $baseValue;
        $i = 2;
        while (WorkflowStatus::where('value', $value)->exists()) {
            $value = $baseValue . '_' . $i;
            $i++;
        }

        $maxOrder = WorkflowStatus::max('sort_order') ?? 0;

        $status = WorkflowStatus::create([
            'value'       => $value,
            'label'       => $request->label,
            'color'       => $request->color,
            'text_color'  => $request->text_color ?? $request->color,
            'bg_color'    => $request->bg_color ?? '#F3F4F6',
            'category'    => $request->category,
            'description' => $request->description,
            'sort_order'  => $maxOrder + 1,
            'is_default'  => false,
        ]);

        return response()->json([
            'message' => 'Status berhasil dibuat',
            'data'    => $status,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $status = WorkflowStatus::findOrFail($id);

        $request->validate([
            'label'       => 'sometimes|string|max:50',
            'color'       => 'sometimes|string|max:20',
            'text_color'  => 'nullable|string|max:20',
            'bg_color'    => 'nullable|string|max:30',
            'category'    => 'sometimes|in:To Do,In Progress,Done',
            'description' => 'nullable|string|max:255',
            'sort_order'  => 'sometimes|integer',
        ]);

        $status->update($request->only([
            'label', 'color', 'text_color', 'bg_color',
            'category', 'description', 'sort_order',
        ]));

        return response()->json([
            'message' => 'Status berhasil diupdate',
            'data'    => $status,
        ]);
    }


    public function destroy($id)
    {
        $status = WorkflowStatus::findOrFail($id);

        if ($status->is_default) {
            return response()->json([
                'message' => 'Default status tidak bisa dihapus',
            ], 422);
        }

        $epicItemCount = EpicItem::where('status', $status->value)->count();
        $epicCount = Epic::where('status', $status->value)->count();

        if ($epicItemCount > 0 || $epicCount > 0) {
            return response()->json([
                'message' => "Status ini digunakan oleh {$epicItemCount} task dan {$epicCount} epic. Pindahkan task terlebih dahulu.",
            ], 422);
        }

        $status->delete();

        return response()->json(['message' => 'Status berhasil dihapus']);
    }


    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:workflow_statuses,id',
        ]);

        foreach ($request->order as $sortOrder => $statusId) {
            WorkflowStatus::where('id', $statusId)
                ->update(['sort_order' => $sortOrder + 1]);
        }

        return response()->json([
            'message'  => 'Urutan status berhasil diperbarui',
            'statuses' => WorkflowStatus::orderBy('sort_order')->get(),
        ]);
    }
}