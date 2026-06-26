<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_approvals', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('document_approvals', function (Blueprint $table) {
            $table->dropColumn('created_at');
        });
    }
};
