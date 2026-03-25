<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epic_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kode');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('label')->nullable();
            $table->enum('type', ['story', 'task', 'bug'])->default('task');
            $table->enum('status', [
                'to_do', 'in_progress', 'done_by_dev', 'testing', 'done_by_qa'
            ])->default('to_do');
            $table->enum('priority', [
                'highest', 'high', 'medium', 'low', 'lowest'
            ])->default('medium');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epic_items');
    }
};