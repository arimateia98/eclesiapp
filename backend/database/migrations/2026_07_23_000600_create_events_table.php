<?php

use App\Modules\Scheduling\Domain\Enums\EventStatus;
use App\Modules\Scheduling\Domain\Enums\EventVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('publisher_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('host_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->ulid('event_type_id');
            $table->ulid('location_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->enum('visibility', array_column(EventVisibility::cases(), 'value'));
            $table->enum('status', array_column(EventStatus::cases(), 'value'))->default(EventStatus::Draft->value);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->foreign(
                ['publisher_organization_id', 'event_type_id'],
                'events_publisher_type_foreign',
            )->references(['organization_id', 'id'])->on('event_types')->restrictOnDelete();
            $table->foreign(
                ['host_organization_id', 'location_id'],
                'events_host_location_foreign',
            )->references(['organization_id', 'id'])->on('locations')->restrictOnDelete();
            $table->unique(['publisher_organization_id', 'id'], 'events_publisher_id_unique');
            $table->index(['publisher_organization_id', 'starts_at']);
            $table->index(['host_organization_id', 'starts_at']);
            $table->index(['status', 'starts_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE events ADD CONSTRAINT events_time_range_valid CHECK (ends_at > starts_at)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
