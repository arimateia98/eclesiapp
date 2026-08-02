<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unavailabilities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('person_id')->constrained('people')->cascadeOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->timestampsTz();
            $table->index(['person_id', 'starts_at', 'ends_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE unavailabilities ADD CONSTRAINT unavailabilities_valid_interval CHECK (ends_at > starts_at)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unavailabilities');
    }
};
