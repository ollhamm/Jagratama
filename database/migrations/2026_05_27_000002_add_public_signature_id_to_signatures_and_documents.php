<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->uuid('public_signature_id')->nullable()->after('signed_at');
            $table->foreign('public_signature_id')->references('id')->on('public_signatures')->nullOnDelete();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('public_submitter_signature_id')->nullable()->after('submitter_signature');
            $table->foreign('public_submitter_signature_id')->references('id')->on('public_signatures')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropForeign(['public_signature_id']);
            $table->dropColumn('public_signature_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['public_submitter_signature_id']);
            $table->dropColumn('public_submitter_signature_id');
        });
    }
};
