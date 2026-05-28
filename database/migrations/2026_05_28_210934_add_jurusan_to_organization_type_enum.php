<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE organizations MODIFY COLUMN type ENUM('JURUSAN','HMPS','HMJ','BEM','BLM','UKM','SBH') NOT NULL");
        DB::statement("ALTER TABLE workflows MODIFY COLUMN organization_type ENUM('JURUSAN','HMPS','HMJ','BEM','BLM','UKM','SBH') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE organizations MODIFY COLUMN type ENUM('HMPS','HMJ','BEM','BLM','UKM','SBH') NOT NULL");
        DB::statement("ALTER TABLE workflows MODIFY COLUMN organization_type ENUM('HMPS','HMJ','BEM','BLM','UKM','SBH') NOT NULL");
    }
};
