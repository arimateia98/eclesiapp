<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servants', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('parish_id')->constrained('parishes')->restrictOnDelete();
            $table->foreignUuid('person_id')->constrained('people')->restrictOnDelete();
            $table->string('status', 20)->default('ACTIVE');
            $table->date('joined_on')->nullable();
            $table->date('left_on')->nullable();
            $table->foreignUuid('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['parish_id', 'person_id'], 'servants_parish_person_unique');
            $table->index(['parish_id', 'status'], 'servants_parish_status_idx');
        });

        DB::statement("ALTER TABLE servants ADD CONSTRAINT servants_status_check CHECK (status IN ('ACTIVE', 'INACTIVE', 'SUSPENDED'))");
        DB::statement('ALTER TABLE servants ADD CONSTRAINT servants_period_check CHECK (left_on IS NULL OR joined_on IS NULL OR left_on >= joined_on)');
        DB::statement("CREATE INDEX servants_active_person_idx ON servants (person_id) WHERE status = 'ACTIVE'");
    }

    public function down(): void
    {
        Schema::dropIfExists('servants');
    }
};
