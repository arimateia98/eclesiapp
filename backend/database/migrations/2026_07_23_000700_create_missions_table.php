<?php

use App\Modules\Missions\Domain\Enums\MissionParticipationPolicy;
use App\Modules\Missions\Domain\Enums\MissionStatus;
use App\Modules\Missions\Domain\Enums\MissionVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('event_id');
            $table->foreignUlid('publisher_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('target_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->ulid('ministry_type_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('visibility', array_column(MissionVisibility::cases(), 'value'));
            $table->enum(
                'participation_policy',
                array_column(MissionParticipationPolicy::cases(), 'value'),
            );
            $table->enum('status', array_column(MissionStatus::cases(), 'value'))->default(MissionStatus::Draft->value);
            $table->timestampTz('response_deadline')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->foreign(
                ['publisher_organization_id', 'event_id'],
                'missions_publisher_event_foreign',
            )->references(['publisher_organization_id', 'id'])->on('events')->cascadeOnDelete();
            $table->foreign(
                ['publisher_organization_id', 'ministry_type_id'],
                'missions_publisher_ministry_type_foreign',
            )->references(['organization_id', 'id'])->on('ministry_types')->restrictOnDelete();
            $table->unique(['publisher_organization_id', 'id'], 'missions_publisher_id_unique');
            $table->index(['event_id', 'status']);
            $table->index(['target_organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
