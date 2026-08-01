<?php

use App\Modules\Scheduling\Domain\Enums\AssignmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mission_slots', function (Blueprint $table): void {
            $table->unique(['mission_id', 'id'], 'mission_slots_mission_id_unique');
        });

        Schema::create('assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->ulid('mission_id');
            $table->ulid('mission_slot_id');
            $table->foreignUlid('person_id')->constrained('people')->restrictOnDelete();
            $table->foreignUlid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', array_column(AssignmentStatus::cases(), 'value'));
            $table->timestampTz('assigned_at');
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestampsTz();

            $table->foreign(
                ['organization_id', 'mission_id'],
                'assignments_organization_mission_foreign',
            )->references(['publisher_organization_id', 'id'])->on('missions')->cascadeOnDelete();
            $table->foreign(
                ['mission_id', 'mission_slot_id'],
                'assignments_mission_slot_foreign',
            )->references(['mission_id', 'id'])->on('mission_slots')->cascadeOnDelete();
            $table->index(['person_id', 'status']);
            $table->index(['mission_slot_id', 'status']);
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX assignments_active_mission_person_unique
            ON assignments (mission_id, person_id)
            WHERE status IN ('pending', 'confirmed')
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');

        Schema::table('mission_slots', function (Blueprint $table): void {
            $table->dropUnique('mission_slots_mission_id_unique');
        });
    }
};
