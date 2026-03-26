<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkflowStatus;

class WorkflowStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'value'       => 'to_do',
                'label'       => 'To Do',
                'color'       => '#6B7280',
                'text_color'  => '#374151',
                'bg_color'    => '#F3F4F6',
                'category'    => 'To Do',
                'description' => 'Work not yet started',
                'sort_order'  => 1,
                'is_default'  => true,
            ],
            [
                'value'       => 'in_progress',
                'label'       => 'In Progress',
                'color'       => '#2563EB',
                'text_color'  => '#1D4ED8',
                'bg_color'    => '#DBEAFE',
                'category'    => 'In Progress',
                'description' => 'Work actively being done',
                'sort_order'  => 2,
                'is_default'  => true,
            ],
            [
                'value'       => 'done_by_dev',
                'label'       => 'Done by Dev',
                'color'       => '#059669',
                'text_color'  => '#065F46',
                'bg_color'    => '#D1FAE5',
                'category'    => 'Done',
                'description' => 'Dev completed, pending QA',
                'sort_order'  => 3,
                'is_default'  => true,
            ],
            [
                'value'       => 'testing',
                'label'       => 'Testing',
                'color'       => '#D97706',
                'text_color'  => '#92400E',
                'bg_color'    => '#FEF3C7',
                'category'    => 'In Progress',
                'description' => 'Under QA testing',
                'sort_order'  => 4,
                'is_default'  => true,
            ],
            [
                'value'       => 'done_by_qa',
                'label'       => 'Done by QA',
                'color'       => '#16A34A',
                'text_color'  => '#166534',
                'bg_color'    => '#DCFCE7',
                'category'    => 'Done',
                'description' => 'Fully completed and verified',
                'sort_order'  => 5,
                'is_default'  => true,
            ],
        ];

        foreach ($statuses as $status) {
            WorkflowStatus::updateOrCreate(
                ['value' => $status['value']],
                $status
            );
        }
    }
}