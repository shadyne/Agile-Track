<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('epic_items', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('epic_id')
                ->constrained('epic_items')
                ->nullOnDelete();
        });

        \DB::statement("ALTER TABLE epic_items MODIFY COLUMN type ENUM('story','task','qa_task','bug') NOT NULL DEFAULT 'task'");
    }

    public function down(): void
    {
        Schema::table('epic_items', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });

        \DB::statement("ALTER TABLE epic_items MODIFY COLUMN type ENUM('story','task','bug') NOT NULL DEFAULT 'task'");
    }
};