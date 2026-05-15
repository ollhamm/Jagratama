<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDatabase extends Command
{
    protected $signature = 'db:clean';

    protected $description = 'Hapus semua data operasional dan re-seed data struktur + admin';

    public function handle(): int
    {
        if (! $this->confirm('Semua data (dokumen, approval, user non-admin) akan dihapus. Lanjutkan?')) {
            $this->info('Dibatalkan.');
            return self::SUCCESS;
        }

        $this->info('Menonaktifkan foreign key checks...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'signatures',
            'document_approvals',
            'document_workflow_instances',
            'document_attachments',
            'documents',
            'system_notifications',
            'workflow_steps',
            'workflows',
            'user_roles',
            'users',
            'organizations',
            'roles',
            'document_types',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
            $this->line("  Truncated: {$table}");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->info('Foreign key checks diaktifkan kembali.');

        $this->info('Re-seeding data struktur...');
        $this->call('db:seed');

        $this->info('Selesai. Database bersih, hanya tersisa data admin.');

        return self::SUCCESS;
    }
}
