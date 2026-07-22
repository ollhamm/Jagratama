<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            // Tambah dulu index baru sebelum drop yang lama — MySQL menolak drop index
            // (workflow_id, step_order) selama itu satu-satunya index yang menopang FK
            // workflow_id, kalau belum ada index pengganti yang juga diawali workflow_id.
            $table->unique(['workflow_id', 'step_order', 'role_id']);
            $table->dropUnique(['workflow_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->unique(['workflow_id', 'step_order']);
            $table->dropUnique(['workflow_id', 'step_order', 'role_id']);
        });
    }
};
