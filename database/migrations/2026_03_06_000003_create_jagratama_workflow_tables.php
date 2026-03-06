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
        $organizationTypes = ['HMPS', 'HMJ', 'BEM', 'BLM', 'UKM', 'SBH'];
        $documentStatuses = ['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'REJECTED', 'APPROVED', 'COMPLETED'];
        $approvalStatuses = ['PENDING', 'APPROVED', 'REJECTED', 'SKIPPED'];
        $signatureTypes = ['BARCODE'];

        Schema::create('organizations', function (Blueprint $table) use ($organizationTypes) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', $organizationTypes);
            $table->uuid('parent_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('parent_id')->references('id')->on('organizations')->nullOnDelete();
        });

        // Add users.organization_id FK after organizations exists.
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('role_id');
            $table->uuid('organization_id')->nullable();
            $table->timestamp('assigned_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->string('name');

            $table->unique('code');
        });

        Schema::create('documents', function (Blueprint $table) use ($documentStatuses) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->uuid('document_type_id');
            $table->uuid('organization_id');
            $table->uuid('created_by');
            $table->enum('current_status', $documentStatuses)->default('DRAFT');
            $table->integer('current_step_order')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('document_type_id')->references('id')->on('document_types')->restrictOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();

            $table->index('organization_id');
            $table->index('current_status');
        });

        Schema::create('document_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->string('file_path');
            $table->string('file_type');
            $table->timestamp('uploaded_at')->useCurrent();

            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
        });

        Schema::create('workflows', function (Blueprint $table) use ($organizationTypes) {
            $table->uuid('id')->primary();
            $table->enum('organization_type', $organizationTypes);
            $table->uuid('document_type_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);

            $table->foreign('document_type_id')->references('id')->on('document_types')->restrictOnDelete();
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->integer('step_order');
            $table->uuid('role_id');
            $table->boolean('is_required_signature')->default(false);
            $table->boolean('can_reject')->default(true);

            $table->foreign('workflow_id')->references('id')->on('workflows')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();

            $table->index('step_order');
            $table->unique(['workflow_id', 'step_order']);
        });

        Schema::create('document_workflow_instances', function (Blueprint $table) use ($documentStatuses) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->uuid('workflow_id');
            $table->integer('current_step_order')->default(0);
            $table->enum('status', $documentStatuses)->default('DRAFT');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();

            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->foreign('workflow_id')->references('id')->on('workflows')->restrictOnDelete();
        });

        Schema::create('document_approvals', function (Blueprint $table) use ($approvalStatuses) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->uuid('workflow_step_id');
            $table->uuid('approved_by');

            // Snapshot fields for immutable approval history when workflow definitions change.
            $table->integer('step_order');
            $table->uuid('role_id');

            $table->enum('status', $approvalStatuses)->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->foreign('workflow_step_id')->references('id')->on('workflow_steps')->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();

            $table->index('status');
            $table->index(['document_id', 'step_order']);
        });

        Schema::create('signatures', function (Blueprint $table) use ($signatureTypes) {
            $table->uuid('id')->primary();
            $table->uuid('document_approval_id');
            $table->enum('signature_type', $signatureTypes)->default('BARCODE');
            $table->text('signature_value');
            $table->timestamp('signed_at')->useCurrent();

            $table->foreign('document_approval_id')->references('id')->on('document_approvals')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
        Schema::dropIfExists('document_approvals');
        Schema::dropIfExists('document_workflow_instances');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('document_attachments');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
        });

        Schema::dropIfExists('organizations');
    }
};
