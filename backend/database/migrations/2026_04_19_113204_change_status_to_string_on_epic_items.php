<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('epic_items', function (Blueprint $table) {
            $table->string('status', 50)->default('to_do')->change();
        });
        Schema::table('epics', function (Blueprint $table) {
            $table->string('status', 50)->default('to_do')->change();
        });
    }

    public function down(): void
    {
        Schema::table('epic_items', function (Blueprint $table) {
            $table->enum('status', [
                'to_do', 'in_progress', 'done_by_dev', 'testing', 'done_by_qa'
            ])->default('to_do')->change();
        });
        Schema::table('epics', function (Blueprint $table) {
            $table->enum('status', [
                'to_do', 'in_progress', 'done_by_dev', 'testing', 'done_by_qa'
            ])->default('to_do')->change();
        });
    }
};