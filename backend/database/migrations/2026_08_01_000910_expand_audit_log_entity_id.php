<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('entity_id', 100)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('audit_logs')->whereRaw('LENGTH(entity_id) > 26')->exists()) {
            throw new RuntimeException('Audit logs with composite identifiers prevent a safe rollback.');
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->char('entity_id', 26)->change();
        });
    }
};
