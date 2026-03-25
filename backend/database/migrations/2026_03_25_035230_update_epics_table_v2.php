<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('epics', function (Blueprint $table) {
            $table->dropColumn('label');
            $table->json('labels')->nullable()->after('kode');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('epics', function (Blueprint $table) {
            $table->dropColumn('labels');
            $table->dropColumn('assignee_id');
            $table->string('label')->nullable();
        });
    }
};