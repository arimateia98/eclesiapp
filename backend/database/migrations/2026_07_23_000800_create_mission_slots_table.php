<?php

use App\Modules\Missions\Domain\Enums\MissionSlotType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_slots', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->ulid('mission_id');
            $table->enum('slot_type', array_column(MissionSlotType::cases(), 'value'));
            $table->ulid('service_function_id')->nullable();
            $table->unsignedSmallInteger('quantity');
            $table->boolean('required')->default(true);
            $table->timestampsTz();

            $table->foreign(
                ['organization_id', 'mission_id'],
                'mission_slots_organization_mission_foreign',
            )->references(['publisher_organization_id', 'id'])->on('missions')->cascadeOnDelete();
            $table->foreign(
                ['organization_id', 'service_function_id'],
                'mission_slots_organization_function_foreign',
            )->references(['organization_id', 'id'])->on('service_functions')->restrictOnDelete();
            $table->unique(['mission_id', 'service_function_id'], 'mission_slots_function_unique');
            $table->index(['organization_id', 'service_function_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE mission_slots
                ADD CONSTRAINT mission_slots_person_function_required
                CHECK (slot_type <> 'person' OR service_function_id IS NOT NULL)
                SQL);
            DB::statement('ALTER TABLE mission_slots ADD CONSTRAINT mission_slots_quantity_positive CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_slots');
    }
};
