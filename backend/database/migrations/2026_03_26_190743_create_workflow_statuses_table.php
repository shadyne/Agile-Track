<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('value')->unique(); 
            $table->string('label');           
            $table->string('color', 20);       
            $table->string('text_color', 20);   
            $table->string('bg_color', 30)->nullable(); 
            $table->string('category')->default('To Do');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_statuses');
    }
};