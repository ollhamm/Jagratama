<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_guides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // 'key' bukan ENUM — dipakai sebagai identifier role (pengaju/approval/admin)
            $table->string('key')->unique();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_guides');
    }
};
