<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('epic_items', function (Blueprint $table) {
            $table->foreignId('sprint_id')
                ->nullable()
                ->after('board_id')
                ->constrained('sprints')
                ->nullOnDelete();

            $table->foreignId('assignee_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('original_estimate')->nullable()->after('end_date');

            $table->json('labels')->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('epic_items', function (Blueprint $table) {
            $table->dropForeign(['sprint_id']);
            $table->dropColumn('sprint_id');
            $table->dropForeign(['assignee_id']);
            $table->dropColumn('assignee_id');
            $table->dropColumn('original_estimate');
            $table->dropColumn('labels');
        });
    }
};