<?php

namespace App\Observers;

use App\Models\Task;

class TaskObserver
{
      private array $watchedFields = [
        'judul', 'status', 'priority', 'due_date', 'deskripsi'
    ];
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        ActivityLog::create([
            'user_id'       => Auth::id() ?? $task->user_id,
            'space_id'      => $task->space_id,
            'task_id'       => $task->id,
            'task_code'     => 'DEV-' . str_pad($task->id, 3, '0', STR_PAD_LEFT),
            'field_updated' => 'task',
            'old_value'     => null,
            'new_value'     => $task->judul,
        ]);
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
            $changed = $task->getDirty();

            foreach ($changed as $field => $newValue) {
                if (!in_array($field, $this->watchedFields)) continue;

                ActivityLog::create([
                    'user_id'       => Auth::id(),
                    'space_id'      => $task->space_id,
                    'task_id'       => $task->id,
                    'task_code'     => 'DEV-' . str_pad($task->id, 3, '0', STR_PAD_LEFT),
                    'field_updated' => $field,
                    'old_value'     => $task->getOriginal($field),
                    'new_value'     => $newValue,
                ]);
            }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }
}
